import { ScanGuard } from './scan-core.js';

const $ = id => document.getElementById(id);
for (const img of document.querySelectorAll('.pass-logo')) img.addEventListener('error', () => {
  img.hidden = true; const fallback = img.nextElementSibling; if (fallback) fallback.hidden = false;
});
const guard = new ScanGuard();
const client = [...crypto.getRandomValues(new Uint8Array(16))].map(n => n.toString(16).padStart(2, '0')).join('');
const counts = { entry: 0, exit: 0, denied: 0 };
const clockFormat = new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', hour: 'numeric', minute: '2-digit', second: '2-digit' });
const dateFormat = new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', month: 'short', day: 'numeric', year: 'numeric' });
const field = $('epc');
let csrf = '', armed = false, direction = 'in', uncertain = false, changing = false, focusVersion = 0;

// PASS_AUTOMATIC_BARRIER_V3: approved opens are delivered by PASS to the gate PC.
const hardwarePanel = document.createElement('section');
hardwarePanel.className = 'notice';
hardwarePanel.innerHTML = '<strong>Parking station</strong><p id="hardware-status" role="status">Approved entries send assigned parking guidance to the OLED when the parking station is online. Servo disconnected.</p>';
$('message').insertAdjacentElement('afterend', hardwarePanel);
function showBarrierRequest(result, mode) {
  if (!((mode === 'in' && result.status === 'granted') || (mode === 'out' && result.status === 'completed'))) return;
  // PASS_PARKING_STATION_V1
  if (result.barrier_status === 'unavailable') { $('hardware-status').textContent = 'Recorded in PASS. Servo disconnected; the parking station handles OLED guidance and the slot sensor.'; return; }
  $('hardware-status').textContent = result.barrier_status === 'pending'
    ? 'Recorded in PASS. Automatic opening requested from the gate PC; check the physical barrier.'
    : 'Recorded in PASS. Barrier opening was not queued; check the gate controller.';
}

function message(text, tone = '') { $('message').textContent = text; $('message').className = `notice ${tone}`; }
function render() {
  $('state').querySelector('span').textContent = armed ? `${direction === 'in' ? 'Entry' : 'Exit'} · Listening` : 'Paused';
  $('state').className = `capture-status ${armed ? 'active' : 'paused'}`;
  $('start').disabled = !csrf || armed || changing || guard.busy || (uncertain && !$('reviewed').checked);
  $('pause').disabled = !armed;
  $('entry').disabled = $('exit').disabled = armed || changing || guard.busy;
  // Keep observing held tags during an in-flight scan; do not queue later frames.
  field.disabled = !armed;
  $('review-wrap').hidden = !uncertain;
}
function show(view, { result = null, tag = '', mode = direction, at = null } = {}) {
  const isExit = mode === 'out';
  const states = {
    paused: ['SCANNING PAUSED', 'Every arrival.\nAccounted for.', 'Select Entry or Exit, then start scanning when the gate is ready.', 'reader'],
    ready: ['READY TO SCAN', isExit ? 'Ready for exit.' : 'Ready for entry.', 'Present one RFID tag. Keep this window and the scan field focused.', 'reader'],
    checking: ['CHECKING ACCESS', 'Reading the tag…', 'Waiting for PASS to confirm this scan. Keep the tag steady.', 'reader'],
    granted: ['ACCESS CONFIRMED', 'Entry granted.', 'Entry was recorded by PASS. Check the vehicle and assigned slot.', 'check'],
    completed: ['SESSION COMPLETED', 'Exit recorded.', 'The parking session is complete. The recorded slot has been released.', 'check'],
    denied: ['ACCESS DENIED', isExit ? 'Exit not recorded.' : 'Entry not allowed.', 'Check the reason below before assisting the driver.', 'cross'],
    notice: ['ATTENTION NEEDED', 'Check this scan.', 'Review the result before continuing.', 'warning'],
    uncertain: ['SCAN NOT CONFIRMED', 'Check access records.', 'The last scan may have been saved. Review PASS records before resuming.', 'warning'],
    unavailable: ['READER UNAVAILABLE', 'Connection needed.', 'Configure the local reader and check the PASS connection.', 'warning'],
  };
  const copy = [...states[view]];
  if (result?.status === 'already_inside') { copy[0] = 'ALREADY INSIDE'; copy[1] = 'Already inside.'; copy[3] = 'clock'; }
  if (isExit && (result?.status === 'not_inside' || result?.status === 'not_found')) { copy[0] = 'EXIT NOT RECORDED'; copy[1] = 'Exit not recorded.'; }
  if (result?.status === 'full') { copy[0] = 'NO SLOT AVAILABLE'; copy[1] = 'Parking is full.'; }
  if (result?.status === 'ignored') { copy[0] = 'REPEAT SCAN IGNORED'; copy[1] = 'One scan at a time.'; }
  const confirmed = !!result && result.status !== 'ignored';
  $('scan-display').dataset.view = view;
  $('scan-display').setAttribute('aria-busy', String(view === 'checking'));
  $('display-context').textContent = confirmed ? 'LAST CONFIRMED SCAN' : view === 'checking' ? 'CURRENT SCAN' : view === 'uncertain' ? 'REVIEW REQUIRED' : view === 'ready' ? 'READER IS LISTENING' : 'READER STANDBY';
  $('display-direction').querySelector('span').textContent = `${isExit ? 'EXIT' : 'ENTRY'} MODE`;
  $('status-word').textContent = copy[0];
  $('scan-title').textContent = copy[1];
  $('scan-description').textContent = result?.message || result?.reason || copy[2];
  for (const icon of document.querySelectorAll('[data-icon]')) icon.toggleAttribute('hidden', icon.dataset.icon !== copy[3]);
  const hasVehicle = confirmed && !!(result.plate_number || result.slot_code);
  $('vehicle-details').hidden = !hasVehicle;
  $('ready-guide').hidden = hasVehicle || view === 'checking' || view === 'uncertain';
  $('plate').textContent = result?.plate_number || 'UNIDENTIFIED';
  $('slot').textContent = result?.slot_code || '—';
  $('slot-label').textContent = result?.status === 'completed' ? 'RELEASED SLOT' : result?.status === 'granted' ? 'ASSIGNED SLOT' : 'RECORDED SLOT';
  $('scan-tag').textContent = tag ? `EPC · ${tag}` : view === 'ready' ? 'Waiting for the next RFID tag' : 'Awaiting RFID scan';
  $('scan-time').textContent = at ? `${clockFormat.format(at)} PHT` : '';
  $('scan-time').dateTime = at ? at.toISOString() : '';
  if (confirmed || view === 'uncertain') $('result-announcement').textContent = `${copy[0]}. ${result?.plate_number || ''} ${result?.slot_code || ''}. ${$('scan-description').textContent}`;
}
function tick() { const now = new Date(); $('clock').textContent = clockFormat.format(now); $('date').textContent = `${dateFormat.format(now)} · PHT`; }
tick(); setInterval(tick, 1000);

async function request(path, body) {
  const controller = new AbortController(); const timer = setTimeout(() => controller.abort(), 12500);
  try {
    const response = await fetch(path, { method: body ? 'POST' : 'GET', credentials: 'same-origin', cache: 'no-store',
      headers: { Accept: 'application/json', ...(body ? { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf } : {}) },
      ...(body ? { body: JSON.stringify({ ...body, client }) } : {}), signal: controller.signal });
    if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('The local reader returned an unreadable response. Check PASS access records before resuming.');
    const data = await response.json();
    if (!response.ok) { const e = new Error(data.message || 'The request did not complete. Check PASS access records.'); e.uncertain = data.uncertain; throw e; }
    return data;
  } finally { clearTimeout(timer); }
}
async function pause(text = 'Paused. Choose the direction, then start scanning.') {
  const wasArmed = armed; armed = false;
  if (['ready', 'paused'].includes($('scan-display').dataset.view)) show('paused');
  if (!wasArmed) { render(); return; }
  changing = true; render();
  try { await request('/api/control', { action: 'pause' }); message(text); }
  catch { uncertain = true; $('reviewed').checked = false; show('uncertain'); message('Reader paused. Check PASS access records before resuming.', 'danger'); }
  finally { changing = false; render(); }
}
function choose(value) {
  if (armed || changing || guard.busy) return;
  direction = value;
  for (const [id, mode] of [['entry', 'in'], ['exit', 'out']]) { $(id).classList.toggle('selected', mode === value); $(id).setAttribute('aria-pressed', String(mode === value)); }
  // Changing the direction does not clear held-tag suppression or change a past result.
  if (['paused', 'ready'].includes($('scan-display').dataset.view)) show('paused');
  message(`${value === 'in' ? 'Entry' : 'Exit'} selected. Remove the tag for 3 seconds, then start scanning.`);
}
$('entry').onclick = () => choose('in'); $('exit').onclick = () => choose('out');
$('pause').onclick = () => pause(); $('reviewed').onchange = render;
$('start').onclick = async () => {
  if (changing || armed || guard.busy || (uncertain && !$('reviewed').checked)) return;
  const startFocusVersion = focusVersion;
  changing = true; render();
  try {
    await request('/api/control', { action: 'arm', direction, reviewed: $('reviewed').checked });
    if (document.hidden || focusVersion !== startFocusVersion) {
      // Focus may leave while Start is awaiting its response. Release that lease too.
      armed = true;
      await pause('Paused because focus left while starting. Press Start scanning to resume.');
      return;
    }
    uncertain = false; $('reviewed').checked = false; armed = true; field.value = ''; show('ready'); render(); field.focus();
    message(`${direction === 'in' ? 'Entry' : 'Exit'} ready. Present one tag to the USB reader.`);
  } catch (e) { uncertain ||= !!e.uncertain; if (uncertain) show('uncertain'); message(e.message, 'danger'); }
  finally { changing = false; render(); }
};
function addResult(result, tag, mode, at) {
  $('empty').hidden = true; $('history-columns').hidden = false;
  const success = ['granted', 'completed'].includes(result.status);
  const entryBlocked = mode === 'in' && ['denied', 'full', 'not_found', 'not_inside'].includes(result.status);
  if (result.status === 'granted' && mode === 'in') counts.entry++;
  if (result.status === 'completed' && mode === 'out') counts.exit++;
  if (entryBlocked) counts.denied++;
  for (const kind of ['entry', 'exit', 'denied']) $(`count-${kind}`).textContent = counts[kind];
  const row = document.createElement('li'); row.className = 'result'; row.dataset.status = result.status;
  const top = document.createElement('div'); top.className = 'result-main';
  const identity = document.createElement('div'); identity.className = 'result-identity';
  const plate = document.createElement('strong'); plate.textContent = result.plate_number || 'Unidentified vehicle';
  const code = document.createElement('code'); code.textContent = tag; identity.append(plate, code);
  const modeLabel = document.createElement('span'); modeLabel.className = 'result-direction'; modeLabel.textContent = mode === 'in' ? '↘ Entry' : '↗ Exit';
  const badge = document.createElement('span'); badge.className = `pill ${success ? 'active' : entryBlocked ? 'danger' : 'paused'}`;
  const labels = { granted: 'Entry granted', completed: 'Exit recorded', denied: 'Denied', already_inside: 'Already inside', not_inside: 'No active stay', not_found: 'Not recorded', full: 'Parking full' };
  badge.textContent = labels[result.status] || result.status.replaceAll('_', ' ');
  const slot = document.createElement('span'); slot.className = 'result-slot'; slot.textContent = result.slot_code || '—';
  const time = document.createElement('time'); time.dateTime = at.toISOString(); time.textContent = clockFormat.format(at);
  const note = document.createElement('p'); note.className = 'result-note'; note.textContent = result.message || result.reason || '';
  top.append(identity, modeLabel, badge, slot, time); row.append(top, note); $('results').prepend(row);
  while ($('results').children.length > 30) $('results').lastElementChild.remove();
}
field.addEventListener('keydown', async event => {
  if (event.key !== 'Enter') return;
  event.preventDefault(); const raw = field.value; field.value = '';
  if (!armed) return;
  const frame = guard.observe(raw);
  if (frame.type === 'invalid') { message('Incomplete scan. Present one 24-character tag, then Enter.', 'danger'); return; }
  if (frame.type === 'duplicate') { message('Repeated tag ignored. Remove it from range for at least 3 seconds.'); return; }
  if (frame.type === 'busy') { message('A scan is processing. This tag was not queued; remove it and scan again after the result.'); return; }
  const scanMode = direction;
  show('checking', { tag: frame.tag, mode: scanMode }); render(); message('Checking access with PASS…');
  try {
    const result = await request('/api/scan', { tag: frame.tag });
    const at = new Date();
    if (result.status !== 'ignored') addResult(result, frame.tag, scanMode, at);
    const view = ['granted', 'completed'].includes(result.status) ? result.status : ['denied', 'full'].includes(result.status) ? 'denied' : 'notice';
    show(view, { result, tag: frame.tag, mode: scanMode, at });
    message(result.message || result.reason || result.status, ['granted', 'completed'].includes(result.status) ? 'success' : ['denied', 'full'].includes(result.status) ? 'danger' : '');
    showBarrierRequest(result, scanMode);
  } catch (e) {
    armed = false; uncertain = true; $('reviewed').checked = false;
    show('uncertain', { tag: frame.tag, mode: scanMode });
    message(e.message || 'No confirmed result. Check PASS access records before resuming.', 'danger');
    // No automatic retry: PASS may have saved the scan before the response was lost.
  } finally { guard.complete(); render(); }
});
function loseFocus(text) { focusVersion++; if (armed) void pause(text); }
field.addEventListener('blur', () => loseFocus('Paused because the scan field lost focus. Press Start scanning to resume.'));
window.addEventListener('blur', () => loseFocus('Paused because this window lost focus. Press Start scanning to resume.'));
document.addEventListener('visibilitychange', () => { if (document.hidden) loseFocus('Paused because this tab is hidden. Press Start scanning to resume.'); });
setInterval(async () => {
  if (!armed || guard.busy || changing) return;
  try { await request('/api/control', { action: 'heartbeat' }); }
  catch (e) { armed = false; uncertain ||= !!e.uncertain; if (uncertain) show('uncertain'); else if ($('scan-display').dataset.view === 'ready') show('paused'); message(e.message || 'Connection lost. Reader paused.', 'danger'); render(); }
}, 5000);
if (!document.fullscreenEnabled) $('fullscreen').hidden = true;
$('fullscreen').onclick = async () => {
  if (armed) await pause('Paused for display change. Press Start scanning to resume.');
  try { if (document.fullscreenElement) await document.exitFullscreen(); else await document.documentElement.requestFullscreen(); }
  catch { message('Fullscreen is unavailable in this browser. You can continue in this window.'); }
};
document.addEventListener('fullscreenchange', () => {
  const label = document.fullscreenElement ? 'Exit fullscreen' : 'Enter fullscreen';
  $('fullscreen').setAttribute('aria-label', label); $('fullscreen').title = label;
});
try {
  const data = await request('/api/session'); csrf = data.csrf;
  $('reader-code').textContent = data.reader_code; $('api').textContent = data.api_base;
  if (data.gate_name) $('gate-name').textContent = data.gate_name;
  message('Connected. Choose Entry or Exit, then press Start scanning.'); render();
} catch (e) {
  $('setup-error').hidden = false; $('setup-error').textContent = e.message || 'Start the local reader and configure the connection.';
  show('unavailable'); message('Reader unavailable.', 'danger'); render();
}
