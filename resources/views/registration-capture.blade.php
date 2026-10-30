<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="referrer" content="no-referrer"><title>PASS · License photo</title>
    <style nonce="{{ $nonce }}">
        *{box-sizing:border-box}body{margin:0;background:#f4f7fb;color:#142442;font:16px/1.5 system-ui,sans-serif;padding:24px 16px}main{max-width:520px;margin:24px auto;background:white;padding:24px;border:1px solid #e0e7f0;border-radius:20px}h1{font-size:25px;line-height:1.2;color:#080677}h2{font-size:18px}p{color:#52627a}.tag{font-size:12px;letter-spacing:.12em;font-weight:700;color:#2558c8}.note{padding:14px;border-radius:12px;background:#eff6ff;color:#183b79}.error{background:#fff1f2;color:#9f1239}button,.pick{display:block;width:100%;padding:14px;border-radius:12px;border:0;background:#1d4ed8;color:white;font:inherit;text-align:center;margin:12px 0;cursor:pointer}button:disabled{opacity:.5;cursor:wait}.pick{background:#eef2ff;color:#172554;border:1px solid #c7d2fe}input[type=file]{max-width:100%;margin:8px 0}img{display:block;width:100%;max-height:280px;object-fit:contain;border-radius:12px;margin:12px 0}dl{background:#f8fafc;padding:16px;border-radius:12px}dt{font-size:12px;color:#64748b}dd{margin:0 0 12px;overflow-wrap:anywhere;font-weight:600}.small{font-size:13px}a{color:#1d4ed8}[hidden]{display:none!important}
    </style>
</head>
<body><main>
    <div class="tag">PASS · SECURE PHOTO CAPTURE</div>
    <h1>Photograph your driver’s license</h1>
    <p>This phone only supplies the photo. Finish creating your account on the device where you started registration.</p>
    <p id="status" class="note" role="status" aria-live="polite">Checking your temporary photo link…</p>
    <section id="capture" hidden>
        <p>Use the <strong>front of your Philippine driver’s license</strong>. Keep all four corners visible, fill the frame, and hold the camera parallel to the card. Avoid glare and shadows.</p>
        <label class="pick">Take a photo<input id="camera" type="file" accept="image/jpeg,image/png" capture="environment"></label>
        <label class="pick">Choose an existing photo<input id="library" type="file" accept="image/jpeg,image/png"></label>
        <img id="preview" alt="Your selected license photo" hidden>
        <button id="send" type="button" disabled>Check this photo</button>
        <p class="small">JPG or PNG · up to 5 MB. Extracted details cannot be edited. A clearer photo is required if the reader is uncertain.</p>
    </section>
    <section id="result" hidden><h2>Details read from your license</h2><dl id="fields"></dl><ul id="reasons"></ul></section>
    <p class="small">The temporary link expires after 10 minutes and stops working after a successful check. Close this page when finished.</p>
</main>
<script nonce="{{ $nonce }}">
(() => {
    'use strict';
    const fragment = new URLSearchParams(location.hash.slice(1));
    const id = fragment.get('id'), key = fragment.get('key');
    history.replaceState(null, '', location.pathname);
    const status = document.getElementById('status'), capture = document.getElementById('capture');
    const send = document.getElementById('send'), preview = document.getElementById('preview');
    const cameras = [document.getElementById('camera'), document.getElementById('library')];
    let file = null, previewUrl = null, busy = false, active = false;
    const message = (text, error = false) => { status.textContent = text; status.className = error ? 'note error' : 'note'; };
    const errorMessage = data => Object.values(data.errors || {}).flat().join(' ') || data.message || 'The photo could not be checked. Try again.';
    async function request(path, options = {}) {
        const controller = new AbortController(), timer = setTimeout(() => controller.abort(), 110000);
        try {
            const response = await fetch('/api/registration/capture/' + encodeURIComponent(id) + path, {...options, headers:{Accept:'application/json','X-Capture-Key':key}, signal:controller.signal, cache:'no-store'});
            const data = await response.json();
            if (!response.ok) {
                if ([404,410].includes(response.status)) { active = false; capture.hidden = true; }
                throw new Error(errorMessage(data));
            }
            return data.data;
        } catch(error) { if (error.name === 'AbortError') throw new Error('The reader took too long. Check the result on your original device before trying again.'); throw error; }
        finally { clearTimeout(timer); }
    }
    function showResult(data) {
        if (!data.license) return;
        const r = data.license; message(r.message, ['blocked','unavailable','retake'].includes(r.status));
        document.getElementById('result').hidden = false;
        const list = document.getElementById('fields'); list.replaceChildren();
        const labels = {full_name:'Full printed name',license_number:'License number',birth_date:'Birth date',expiry_date:'Expiration date'};
        for (const [name,label] of Object.entries(labels)) {
            const dt=document.createElement('dt'), dd=document.createElement('dd'); dt.textContent=label; dd.textContent=r.fields?.[name] || 'Could not be read'; list.append(dt,dd);
        }
        const reasons=document.getElementById('reasons'); reasons.replaceChildren();
        for (const text of r.reasons || []) { const li=document.createElement('li'); li.textContent=text; reasons.append(li); }
        if (r.status === 'passed') { active=false; capture.hidden=true; message('Photo checks passed. Return to your original device to review the details and create your account.'); }
    }
    for (const input of cameras) input.addEventListener('change', () => {
        const chosen=input.files?.[0]; if (!chosen || busy) return;
        if (!['image/jpeg','image/png'].includes(chosen.type) || chosen.size > 5*1024*1024) { message('Choose a JPG or PNG photo of 5 MB or smaller.',true); return; }
        file=chosen; if(previewUrl) URL.revokeObjectURL(previewUrl); previewUrl=URL.createObjectURL(file); preview.src=previewUrl; preview.hidden=false; send.disabled=false;
        message('Photo selected. Reading and checking it now…');
        send.click();
    });
    send.addEventListener('click', async () => {
        if (!file || busy || !active) return;
        busy=true; send.disabled=true; for(const input of cameras) input.disabled=true;
        message('Reading your license… This can take up to 90 seconds. Keep this page open.');
        const body=new FormData(); body.append('license_file',file);
        try { showResult(await request('/license',{method:'POST',body})); }
        catch(error) { message(error.message,true); }
        finally { busy=false; send.disabled=!file || !active; for(const input of cameras) input.disabled=false; }
    });
    if (!id || !key || !/^[a-f0-9]{64}$/.test(key)) { message('This photo link is missing or invalid. Generate a new link on your original device.',true); return; }
    request('').then(data => { active=true; capture.hidden=false; message('Photo link ready. Take a clear photo of your license front.'); showResult(data); }).catch(error=>message(error.message,true));
})();
</script></body></html>
