'use strict';
const fs=require('node:fs'), path=require('node:path'), http=require('node:http'), crypto=require('node:crypto');
const {EventEmitter}=require('node:events');
const pause=ms=>new Promise(r=>setTimeout(r,ms));
const clean=(value,max)=>String(value??'').replace(/[|\r\n]/g,' ').replace(/[^\x20-\x7e]/g,'?').slice(0,max);

class SlotLink extends EventEmitter {
 constructor(serial,{now=Date.now}={}) {
  super();this.serial=serial;this.now=now;this.buffer='';this.ready=false;this.hello=false;this.oledReady=false;this.sample=null;this.pending=null;
  serial.on('data',b=>this.read(b));serial.on('error',()=>this.lost());serial.on('close',()=>this.lost());
 }
 lost(){this.ready=false;this.pending?.reject(new Error('ESP32 disconnected or restarted.'));}
 read(bytes){
  this.buffer+=bytes.toString('utf8');
  let i;while((i=this.buffer.indexOf('\n'))>=0){const line=this.buffer.slice(0,i).trim();this.buffer=this.buffer.slice(i+1);const p=line.split('|');
   if(p[0]!=='PASS1')continue;
   if(p[1]==='HELLO'&&p.length===4&&/^[a-f0-9]{8}$/.test(p[3])){if(this.ready&&this.bootId!==p[3])this.lost();this.bootId=p[3];this.hello=true;this.oledReady=p[2]==='OLED_OK';this.emit('hello');}
   else if(p[1]==='HEALTH'&&p.length===3)this.oledReady=p[2]==='OLED_OK';
   else if(p[1]==='ACK'&&p.length===3&&p[2]===this.pending?.token)this.pending.resolve();
   else if(p[1]==='ERROR'&&p[2]===this.pending?.token)this.pending.reject(new Error(p[3]||'OLED command failed.'));
   else if(p[1]==='SENSOR'&&p.length===4){
    let state=p[2],distance=p[3]==='--'?null:Number(p[3]);
    if(!['available','occupied','unknown'].includes(state)||distance!==null&&(!Number.isFinite(distance)||distance<2||distance>400))continue;
    if(state==='occupied'&&(distance===null||distance>15)||state==='available'&&(distance===null||distance<20))continue;
    this.sample={state,distance,at:this.now()};
   }
  }
  if(this.buffer.length>2048)this.buffer='';
 }
 async open(){
  await new Promise((resolve,reject)=>this.serial.open(e=>e?reject(e):resolve()));
  // '?' is harmless to the previous character-command firmware. No structured
  // frame is sent until the new firmware identifies its protocol explicitly.
  const deadline=this.now()+7000;
  while(!this.hello){
   if(!this.serial.isOpen||this.now()>=deadline)throw new Error('Upload PASS_SlotStation.ino first; protocol handshake was not received.');
   await new Promise((resolve,reject)=>this.serial.write('?\n',e=>e?reject(e):resolve()));
   await pause(300);
  }
  this.ready=true;
 }
 async frame(kind,values={}){
  if(!this.ready||!this.serial.isOpen||this.pending)throw new Error('ESP32 is not ready.');
  const token=crypto.randomBytes(8).toString('hex');
  const fields=['@PASS1',token,kind,clean(values.plate_number,20),clean(values.area_name,40),clean(values.zone_name,30),clean(values.slot_code,30)];
  let timer;
  const acknowledged=new Promise((resolve,reject)=>{this.pending={token,resolve,reject};timer=setTimeout(()=>reject(new Error('OLED acknowledgement timed out.')),2500);});
  try{
   const written=new Promise((resolve,reject)=>this.serial.write(fields.join('|')+'\n',e=>e?reject(e):this.serial.drain(e=>e?reject(e):resolve())));
   await Promise.all([written,acknowledged]);
  }finally{clearTimeout(timer);this.pending=null;}
 }
 async close(){this.lost();if(this.serial.isOpen)await new Promise(r=>this.serial.close(()=>r()));}
}

class StableSensor {
 constructor({now=Date.now,settleMs=2000}={}){this.now=now;this.settleMs=settleMs;this.candidate='unknown';this.since=now();this.stable='unknown';this.distance=null;}
 update(sample){
  const now=this.now();
  if(!sample||now-sample.at>1500||sample.state==='unknown'){this.candidate='unknown';this.stable='unknown';this.distance=null;this.since=now;return {state:'unknown',distance_cm:null};}
  if(sample.state!==this.candidate){this.candidate=sample.state;this.since=now;}
  if(now-this.since>=this.settleMs){this.stable=sample.state;this.distance=sample.distance;}
  // While a new state settles, its prior stable distance remains consistent
  // with the prior state. Unknown/fault readings invalidate it immediately.
  return {state:this.stable,distance_cm:this.distance};
 }
}

// Keep a fresh entry locally until its OLED command is acknowledged. Advancing
// the API cursor must not discard guidance during a brief OLED fault.
class GuidanceDelivery {
 constructor({now=Date.now,ttlMs=30000}={}){this.now=now;this.ttlMs=ttlMs;this.pending=null;this.clearPending=false;this.latestEventId=0;this.acknowledgedEventId=null;this.lastError=null;this.retryAt=0;}
 accept(poll){
  if(poll.assignment){
   const a=poll.assignment;
   if(!Number.isSafeInteger(a.event_id)||a.event_id<1||!a.slot_code)throw new Error('Invalid parking assignment.');
   if(a.event_id<=this.latestEventId)return;
   this.latestEventId=a.event_id;this.pending={assignment:a,at:this.now()};this.clearPending=false;this.lastError=null;this.retryAt=0;
  }else if(poll.clear_display){this.pending=null;this.clearPending=true;this.lastError=null;this.retryAt=0;}
 }
 async flush(link){
  if(this.pending&&this.now()-this.pending.at>=this.ttlMs){this.pending=null;this.lastError='Parking guidance expired before OLED acknowledgement.';return {kind:'expired',message:this.lastError};}
  if(!this.pending&&!this.clearPending)return null;
  if(!link.ready||!link.oledReady){this.lastError='Waiting for OLED connection.';return null;}
  if(this.now()<this.retryAt)return null;
  try{
   if(this.pending){const a=this.pending.assignment;await link.frame('DISPLAY',a);this.pending=null;this.acknowledgedEventId=a.event_id;this.lastError=null;return {kind:'displayed',assignment:a};}
   await link.frame('CLEAR');this.clearPending=false;this.lastError=null;return {kind:'cleared'};
  }catch(e){this.lastError=e.message;this.retryAt=this.now()+1000;return {kind:'retry',message:e.message};}
 }
 status(){return {pending_event_id:this.pending?.assignment.event_id??null,pending_slot:this.pending?.assignment.slot_code??null,last_acknowledged_event_id:this.acknowledgedEventId,last_error:this.lastError};}
}

function loadConfig(backend){
 const dir=path.join(backend,'.git/pass-usb-rfid'), file=path.join(dir,'reader.json');
 if(!fs.existsSync(file)||fs.lstatSync(dir).isSymbolicLink()||fs.lstatSync(file).isSymbolicLink())throw new Error('Existing USB reader configuration is required.');
 const config=JSON.parse(fs.readFileSync(file,'utf8')),base=new URL(config.api_base);
 if(!(base.protocol==='https:'||base.protocol==='http:'&&['localhost','127.0.0.1','[::1]'].includes(base.hostname))||base.username||base.password||!['','/'].includes(base.pathname))throw new Error('Invalid API origin.');
 if(!/^[A-Z0-9_-]{1,30}$/.test(config.reader_code||'')||!/^[a-f0-9]{64}$/.test(config.reader_key||''))throw new Error('Invalid reader configuration.');
 return {...config,base};
}

async function main(argv=process.argv.slice(2)){
 let port='COM10';for(let i=0;i<argv.length;i++){if(argv[i]==='--port'&&argv[i+1])port=argv[++i];else throw new Error('Usage: node tools/hardware/Start-PassParking.cjs [--port COM10]');}
 const backend=path.resolve(__dirname,'../..'),config=loadConfig(backend);
 let SerialPort;try{({SerialPort}=require('serialport'));}catch{throw new Error('Run npm install in tools/hardware first (serialport dependency).');}
 const request=async(route,body)=>{
  const response=await fetch(new URL('/api/devices/parking-station/'+route,config.base),{method:'POST',redirect:'error',signal:AbortSignal.timeout(6000),
   headers:{'Content-Type':'application/json',Accept:'application/json','X-Reader-Code':config.reader_code,'X-Reader-Key':config.reader_key},body:JSON.stringify(body)});
  const data=await response.json().catch(()=>({}));if(!response.ok){const e=new Error(data.message||'Parking station API returned HTTP '+response.status);e.status=response.status;throw e;}return data;
 };
 let active=null,session=null,fixedSlot=null,guidance=null,eventCursor=null,stopping=false,lastError=0;
 const server=http.createServer((req,res)=>{
  res.setHeader('Content-Type','application/json');res.setHeader('Cache-Control','no-store');
  if(req.headers.host!=='127.0.0.1:8788'||!['127.0.0.1','::ffff:127.0.0.1'].includes(req.socket.remoteAddress)){res.writeHead(403);return res.end('{}');}
  if(req.method!=='GET'||req.url!=='/status'){res.writeHead(404);return res.end('{}');}
  res.end(JSON.stringify({mode:'parking-station',connected:!!(active?.ready&&session),busy:false,oled_ready:!!active?.oledReady,fixed_slot:fixedSlot?.slot_code??null,servo_available:false,event_cursor:eventCursor,guidance:guidance?.status()??null}));
 });
 await new Promise((resolve,reject)=>{server.once('error',reject);server.listen(8788,'127.0.0.1',resolve);}).catch(()=>{throw new Error('Close the previous hardware controller with Ctrl+C, then start this window again. Port 8788 is busy.');});
 process.on('SIGINT',()=>{stopping=true;active?.lost();server.close();void active?.close();});
 console.log('PASS PARKING STATION | '+port+' | servo disabled');
 console.log('Keep this window open. Scan RFID in the USB Reader browser page.');
 try{
  while(!stopping){
   try{
    active=new SlotLink(new SerialPort({path:port,baudRate:115200,autoOpen:false}));
    await active.open();
    session=crypto.randomUUID();
    const setup=await request('start',{session_id:session});fixedSlot=setup.slot;
    let cursor=setup.cursor,sequence=0,lastObservation=0;
    guidance=new GuidanceDelivery();eventCursor=cursor;
    const sensor=new StableSensor();
    try{await active.frame('BIND',fixedSlot);}catch(e){active.oledReady=false;console.error('OLED unavailable: '+e.message);}
    console.log('PASS PARKING READY | fixed sensor: '+fixedSlot.slot_code+' | OLED '+(active.oledReady?'ready':'unavailable'));
    while(!stopping&&active.ready){
     const observed=sensor.update(active.sample);
     if(Date.now()-lastObservation>=2000){
      const outcome=await request('observe',{session_id:session,sequence:++sequence,oled_ready:active.oledReady,...observed});lastObservation=Date.now();
      if(outcome.arrival_detected)console.log('Assigned slot arrival detected | '+fixedSlot.slot_code+' | staff notified');
     }
     const poll=await request('poll',{session_id:session,after_event_id:cursor});
     if(!Number.isSafeInteger(poll.cursor)||poll.cursor<cursor)throw new Error('Invalid gate event cursor.');
     guidance.accept(poll);cursor=poll.cursor;eventCursor=cursor;
     const delivery=await guidance.flush(active);
     if(delivery?.kind==='displayed'){const a=delivery.assignment;console.log('OLED assignment acknowledged | '+a.plate_number+' | '+a.area_name+' | '+a.zone_name+' | '+a.slot_code);}
     else if(delivery?.kind==='retry'||delivery?.kind==='expired')console.error('OLED guidance: '+delivery.message);
     await pause(600);
    }
   }catch(e){
    if(!stopping&&Date.now()-lastError>5000){console.error(e.message);lastError=Date.now();}
    if([401,403,404,409,422,423].includes(e.status)){console.error('Station stopped. Check configuration/API, then start again.');stopping=true;process.exitCode=1;}
   }finally{session=null;fixedSlot=null;guidance=null;eventCursor=null;await active?.close();active=null;}
   if(!stopping)await pause(1500);
  }
 }finally{server.close();}
}
module.exports={SlotLink,StableSensor,GuidanceDelivery,loadConfig,main};
if(require.main===module)main().catch(e=>{console.error('STOP: '+e.message);process.exitCode=1;});
