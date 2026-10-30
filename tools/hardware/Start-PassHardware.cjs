'use strict';
const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');
const crypto = require('node:crypto');
const { spawnSync } = require('node:child_process');

class BarrierStation {
  constructor({ serial, verify, journal, now = Date.now }) {
    this.serial = serial; this.verify = verify; this.journal = journal; this.now = now;
    this.ready = false; this.busy = false; this.pending = null; this.buffer = ''; this.firmwareError = false;
    this.closeToken = crypto.randomBytes(32).toString('hex');
    this.used = {};
    if (fs.existsSync(journal)) {
      if (fs.lstatSync(journal).isSymbolicLink()) throw new Error('Unsafe hardware journal.');
      this.used = JSON.parse(fs.readFileSync(journal, 'utf8'));
      if (!this.used || typeof this.used !== 'object' || Array.isArray(this.used)) throw new Error('Invalid hardware journal.');
    }
    serial.on('data', bytes => this.read(bytes));
    serial.on('close', () => this.lost());
    serial.on('error', () => this.lost());
  }
  lost() { this.ready = false; this.pending?.reject(new Error('ESP32 disconnected.')); }
  read(bytes) {
    this.buffer += bytes.toString('utf8');
    let i;
    while ((i = this.buffer.indexOf('\n')) >= 0) {
      const line = this.buffer.slice(0, i).trim(); this.buffer = this.buffer.slice(i + 1);
      if (line === this.pending?.expected) this.pending.resolve();
      if (line === 'Servo PWM initialization failed.') { this.firmwareError = true; this.lost(); }
      if (line === 'PASS HARDWARE TEST' && this.ready) this.lost();
    }
    if (this.buffer.length > 4096) this.buffer = this.buffer.slice(-1024);
  }
  remember(id) {
    const time = this.now();
    this.used = Object.fromEntries(Object.entries(this.used).filter(([,at]) => Number(at) > time - 600000));
    if (this.used[id]) throw new Error('This approval was already sent to the barrier. Check the gate; do not repeat the transaction.');
    this.used[id] = time;
    if (fs.existsSync(this.journal) && fs.lstatSync(this.journal).isSymbolicLink()) throw new Error('Unsafe hardware journal.');
    const temp = this.journal + '.' + crypto.randomBytes(6).toString('hex');
    try {
      fs.writeFileSync(temp, JSON.stringify(this.used), { flag: 'wx', mode: 0o600 });
      fs.renameSync(temp, this.journal);
    } finally { if (fs.existsSync(temp)) fs.unlinkSync(temp); }
  }
  async transmit(command) {
    if (!this.ready || !this.serial.isOpen || this.pending) throw new Error('ESP32 is unavailable.');
    let timer;
    const acknowledgement = new Promise((resolve, reject) => {
      this.pending = { expected: 'Servo command: ' + (command === 'O' ? 'OPEN' : 'CLOSE'), resolve, reject };
      timer = setTimeout(() => reject(new Error('No acknowledgement from the ESP32. Check the hardware before continuing.')), 3000);
    });
    try {
      const write = new Promise((resolve, reject) => {
        this.serial.write(command + '\n', err => {
          if (err) return reject(err);
          this.serial.drain(err => err ? reject(err) : resolve());
        });
      });
      await Promise.all([acknowledgement, write]);
    } catch (error) { this.ready = false; throw error; }
    finally { clearTimeout(timer); this.pending = null; }
  }
  async open(receipt) {
    if (!this.ready || !this.serial.isOpen) throw new Error('ESP32 hardware is not ready.');
    if (this.busy) throw new Error('The barrier is processing another action.');
    if (typeof receipt !== 'string' || receipt.length < 20 || receipt.length > 8192) throw new Error('A confirmed PASS gate approval is required.');
    this.busy = true;
    try {
      const proof = await this.verify(receipt);
      if (proof?.command !== 'O' || !/^[a-f0-9-]{36}$/.test(proof.receipt_id || '')) throw new Error('PASS did not approve this gate opening.');
      this.remember(proof.receipt_id); // Persist before write: never replay an uncertain command.
      await this.transmit('O');
      return { acknowledged: true, receipt_id: proof.receipt_id, close_token: this.closeToken };
    } finally { this.busy = false; }
  }
  async close(token, localOperator = false) {
    if (!localOperator && (typeof token !== 'string' || !/^[a-f0-9]{64}$/.test(token) ||
        !crypto.timingSafeEqual(Buffer.from(token), Buffer.from(this.closeToken)))) throw new Error('Use the window that opened the barrier to close it.');
    if (this.busy) throw new Error('Wait for the current gate action.');
    this.busy = true;
    try { await this.transmit('C'); return { acknowledged: true }; }
    finally { this.busy = false; }
  }
}

class CommandPump {
  constructor({station,request,log=console.log}) { this.station=station;this.request=request;this.log=log;this.busy=false; }
  async tick() {
    if(this.busy || !this.station.ready || !this.station.serial.isOpen || this.station.busy)return;
    this.busy=true;
    try {
      const result=await this.request('/api/devices/barrier/commands/claim',{poll:true});
      const command=result.command;
      if(!command)return;
      if(!Number.isSafeInteger(command.command_id) || command.command_id<=0 || typeof command.receipt!=='string')throw new Error('Invalid automatic gate command.');
      let acknowledged=false,error='';
      try { await this.station.open(command.receipt);acknowledged=true;this.log('OPEN acknowledged | command #'+command.command_id); }
      catch(e) { error=String(e.message||'Opening was not confirmed.').slice(0,500);this.log('OPEN not confirmed | command #'+command.command_id+' | '+error); }
      // A failed acknowledgement report never repeats the physical command.
      try { await this.request('/api/devices/barrier/commands/'+command.command_id+'/ack',{acknowledged,error:error||null}); }
      catch { this.log('Command #'+command.command_id+' result could not be saved. Do not repeat this gate transaction.'); }
    } finally {this.busy=false;}
  }
}

function createServer(station) {
  return http.createServer(async (req, res) => {
    const headers = { 'Content-Type': 'application/json', 'Cache-Control': 'no-store', 'X-Content-Type-Options': 'nosniff' };
    const reply = (status, body) => { res.writeHead(status, headers); res.end(JSON.stringify(body)); };
    if (req.headers.host !== '127.0.0.1:8788' || !['127.0.0.1','::ffff:127.0.0.1'].includes(req.socket.remoteAddress)) return reply(403,{message:'Local hardware access only.'});
    const origin = req.headers.origin;
    if (origin) {
      let url; try { url = new URL(origin); } catch { return reply(403,{message:'Invalid origin.'}); }
      if (!(url.protocol === 'https:' || (url.protocol === 'http:' && ['localhost','127.0.0.1','[::1]'].includes(url.hostname))) || url.origin !== origin) return reply(403,{message:'Origin not allowed.'});
      headers['Access-Control-Allow-Origin'] = origin; headers.Vary = 'Origin';
      headers['Access-Control-Allow-Methods'] = 'GET, POST, OPTIONS';
      headers['Access-Control-Allow-Headers'] = 'Content-Type';
      headers['Access-Control-Allow-Private-Network'] = 'true';
    }
    if (req.method === 'OPTIONS') return reply(204,{});
    if (req.url === '/status' && req.method === 'GET') return reply(200,{connected:station.ready && station.serial.isOpen,busy:station.busy});
    if (!['/open','/close'].includes(req.url) || req.method !== 'POST') return reply(404,{message:'Not found.'});
    if (!(req.headers['content-type'] || '').toLowerCase().startsWith('application/json')) return reply(415,{message:'JSON required.'});
    let size=0, chunks=[];
    try {
      for await (const chunk of req) { size += chunk.length; if (size > 16384) return reply(413,{message:'Request too large.'}); chunks.push(chunk); }
      const data = JSON.parse(Buffer.concat(chunks).toString('utf8'));
      if(req.url==='/open')return reply(409,{message:'PASS now sends approved opening commands automatically from the backend. Refresh the dashboard.'});
      const result = await station.close(data.close_token);
      reply(200,result);
    } catch (error) { reply(409,{message:error.message || 'Hardware action was not confirmed.'}); }
  });
}

async function main(argv = process.argv.slice(2)) {
  let portName = 'COM10';
  for(let i=0;i<argv.length;i++) {
    if(argv[i]==='--port' && argv[i+1])portName=argv[++i];
    else throw new Error('Usage: node tools/hardware/Start-PassHardware.cjs --port COM10');
  }
  const backend = path.resolve(__dirname,'../..');
  const privateDir = path.join(backend,'.git/pass-usb-rfid');
  const configPath = path.join(privateDir,'reader.json');
  if (!fs.existsSync(configPath) || fs.lstatSync(privateDir).isSymbolicLink() || fs.lstatSync(configPath).isSymbolicLink()) throw new Error('Existing USB RFID reader configuration is required.');
  const config = JSON.parse(fs.readFileSync(configPath,'utf8'));
  const base = new URL(config.api_base);
  if (!(base.protocol==='https:' || (base.protocol==='http:' && ['localhost','127.0.0.1','[::1]'].includes(base.hostname))) || base.username || base.password || !['','/'].includes(base.pathname)) throw new Error('Invalid PASS API origin.');
  if(!/^[A-Z0-9_-]{1,30}$/.test(config.reader_code||'') || !/^[a-f0-9]{64}$/.test(config.reader_key||''))throw new Error('Invalid reader configuration.');
  let SerialPort;
  try { ({SerialPort} = require('serialport')); }
  catch {
    console.log('Installing the USB serial dependency (first run only)...');
    const npm = process.platform==='win32'
      ? spawnSync(process.env.ComSpec||'cmd.exe',['/d','/s','/c','npm install --ignore-scripts --no-audit --no-fund'],{cwd:__dirname,stdio:'inherit'})
      : spawnSync('npm',['install','--ignore-scripts','--no-audit','--no-fund'],{cwd:__dirname,stdio:'inherit'});
    if(npm.error || npm.status!==0)throw new Error('USB serial dependency installation failed.');
    ({SerialPort} = require('serialport'));
  }
  const serial = new SerialPort({path:portName,baudRate:115200,autoOpen:false});
  const request = async (route,body) => {
    const response = await fetch(new URL(route,base),{method:'POST',redirect:'error',signal:AbortSignal.timeout(8000),
      headers:{'Content-Type':'application/json',Accept:'application/json','X-Reader-Code':config.reader_code,'X-Reader-Key':config.reader_key},body:JSON.stringify(body)});
    if(!response.ok)throw new Error('PASS hardware API returned HTTP '+response.status+'. Check the API and configured gate.');
    return response.json();
  };
  const verify = receipt => request('/api/devices/barrier/verify',{receipt});
  // Verify the live API update before allowing any parking action to be recorded.
  console.log('Checking the PASS hardware endpoint...');
  let probe;
  try { probe = await fetch(new URL('/api/devices/barrier/commands/claim',base),{method:'POST',redirect:'error',signal:AbortSignal.timeout(8000),
    headers:{'Content-Type':'application/json',Accept:'application/json','X-Reader-Code':config.reader_code,'X-Reader-Key':config.reader_key},body:'{}'}); }
  catch { throw new Error('PASS API is unreachable. Start the Laravel server, then run this hardware command again.'); }
  const diagnostic = await probe.json().catch(()=>({}));
  if (probe.status !== 422 || !diagnostic.errors?.poll) throw new Error('PASS automatic hardware API is not ready (HTTP '+probe.status+'). Apply this backend update and migration to the configured API: '+base.origin);
  const station = new BarrierStation({serial,verify,journal:path.join(privateDir,'hardware-used.json')});
  await new Promise((resolve,reject)=>serial.open(error=>error?reject(new Error('Cannot open '+portName+'. Close Arduino Serial Monitor and other ESP32 connections.')):resolve()));
  await new Promise(resolve=>setTimeout(resolve,2200));
  if (station.firmwareError || !serial.isOpen) { serial.close(()=>{}); throw new Error('ESP32 servo initialization failed or the USB connection was lost.'); }
  station.ready = true;
  const pump=new CommandPump({station,request});
  let pollTimer, lastError=0;
  const server = createServer(station);
  server.on('error',()=>{clearInterval(pollTimer);station.ready=false;process.stdin.pause();serial.close(()=>{});console.error('Hardware server could not start on 127.0.0.1:8788. Close the other hardware window.');process.exitCode=1;});
  server.listen(8788,'127.0.0.1',()=>{
    console.log('PASS HARDWARE READY | '+portName+' | 115200 baud');
    console.log('AUTOMATIC MODE: approved RFID, visitor QR, and emergency actions are received directly from PASS.');
    console.log('Keep this window open on the gate PC. No browser Connect Hardware or manual OPEN is needed.');
    console.log('Type C then Enter here to close manually only when the path is clear.');
    pollTimer=setInterval(()=>void pump.tick().catch(e=>{if(Date.now()-lastError>10000){lastError=Date.now();console.error(e.message);}}),700);
  });
  process.stdin.setEncoding('utf8');process.stdin.on('data',data=>{if(data.trim().toUpperCase()==='C')void station.close(null,true).then(()=>console.log('CLOSE acknowledged.')).catch(e=>console.error(e.message));});
  process.on('SIGINT',()=>{clearInterval(pollTimer);station.ready=false;server.close();serial.close(()=>process.exit(0));});
}
module.exports = { BarrierStation, CommandPump, createServer, main };
if(require.main===module)main().catch(error=>{console.error('STOP: '+error.message);process.exitCode=1;});
