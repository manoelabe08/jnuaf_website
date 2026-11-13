
@extends('layouts.admin')



@section('title', "Scanner d'entrée")



@section('content')

<style>

.scan-banner{position:fixed;top:0;left:0;right:0;z-index:9999;display:flex;flex-direction:column;gap:2px;padding:18px 22px;color:#fff;font-size:1.05rem;transform:translateY(-110%);transition:transform .35s ease;box-shadow:0 8px 24px rgba(0,0,0,.35)}

.scan-banner.show{transform:translateY(0)}

.scan-banner strong{font-size:1.35rem}

.scan-banner.ok{background:#1E7B3A}

.scan-banner.bad{background:#B3261E}

.cam-wrap{position:relative;max-width:560px;margin:0 auto;border-radius:16px;overflow:hidden;background:#000}

.cam-wrap video{display:block;width:100%}

.cam-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px}

.cam-actions [hidden]{display:none!important}

@media (prefers-reduced-motion: reduce){.scan-banner{transition:none}}

</style>



<div id="scanBanner" class="scan-banner" role="alert" aria-live="assertive" aria-atomic="true"></div>



<div class="top">

  <div>

    <h1>Scanner d'entrée</h1>

    <p class="muted" style="margin:0">La caméra reste active : chaque QR code détecté valide l'entrée automatiquement.</p>

  </div>

  <a class="btn ghost" href="{{ route('admin.checkin.index') }}">Saisie manuelle</a>

</div>



<div class="grid g2">

  <div class="card">

    <div class="cam-wrap"><video id="cam" playsinline muted></video></div>

    <p class="muted" id="camNote" style="font-size:.9rem">Démarrage de la caméra…</p>

    <div class="cam-actions">

      <button class="btn o" type="button" id="camStart" hidden>Démarrer la caméra</button>

      <button class="btn ghost" type="button" id="camSwitch" hidden>Changer de caméra</button>

      <button class="btn ghost" type="button" id="camFlash" hidden>Lampe</button>

    </div>

  </div>



  <div class="card">

    <h2>Cette session <span class="pill" id="count">0</span></h2>

    <table><tbody id="recent"><tr id="emptyRow"><td class="muted">Aucune entrée validée pour l'instant.</td></tr></tbody></table>

  </div>

</div>

@endsection



@push('scripts')

<script type="module">

import QrScanner from "{{ asset('js/vendor/qr-scanner/qr-scanner.min.js') }}";



const endpoint = @json(route('admin.checkin.store'));

const csrf = document.querySelector('meta[name="csrf-token"]').content;

const video = document.getElementById('cam');

const banner = document.getElementById('scanBanner');

const note = document.getElementById('camNote');

const startBtn = document.getElementById('camStart');

const switchBtn = document.getElementById('camSwitch');

const flashBtn = document.getElementById('camFlash');

const recent = document.getElementById('recent');

const counter = document.getElementById('count');



const titles = {ok: 'Entrée validée', already: 'Double scan', unknown: 'Badge inconnu', invalid: 'Entrée refusée'};

const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c]));



let busy = false, lastCode = '', lastAt = 0, hideTimer = null, total = 0;



function showBanner(kind, title, detail) {

  clearTimeout(hideTimer);

  banner.classList.remove('show');

  banner.className = 'scan-banner ' + kind;

  banner.innerHTML = '<strong>' + esc(title) + '</strong><span>' + esc(detail) + '</span>';

  void banner.offsetWidth;

  banner.classList.add('show');

  hideTimer = setTimeout(() => banner.classList.remove('show'), 5000);

}



function addRecent(d) {

  const empty = document.getElementById('emptyRow');

  if (empty) empty.remove();



  const row = document.createElement('tr');

  [new Date().toTimeString().slice(0, 5), d.name, d.territory].forEach(text => {

    const cell = document.createElement('td');

    cell.textContent = text || '';

    row.appendChild(cell);

  });

  recent.prepend(row);

  while (recent.children.length > 10) recent.lastChild.remove();



  total += 1;

  counter.textContent = total;

}



async function submit(code) {

  if (busy || (code === lastCode && Date.now() - lastAt < 6000)) return;

  busy = true; lastCode = code; lastAt = Date.now();



  try {

    const res = await fetch(endpoint, {

      method: 'POST',

      headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},

      body: JSON.stringify({code})

    });



    if (res.status === 419 || res.status === 401) {

      showBanner('bad', 'Session expirée', 'Rechargez la page et reconnectez-vous.');

      return;

    }



    const d = await res.json();



    if (d.state === 'ok') {

      const parts = [d.name, d.profile, d.territory, d.badge].filter(Boolean).join(' · ');

      showBanner('ok', titles.ok, parts + (d.accessibility ? ' — Besoin spécifique : ' + d.accessibility : ''));

      addRecent(d);

      if (navigator.vibrate) navigator.vibrate(120);

    } else {

      showBanner('bad', titles[d.state] || 'Erreur', (d.message || 'Badge non valide.') + (d.name ? ' — ' + d.name : ''));

      if (navigator.vibrate) navigator.vibrate([200, 80, 200]);

    }

  } catch (e) {

    showBanner('bad', 'Erreur réseau', 'Le pointage n\'a pas été enregistré. Réessayez.');

  } finally {

    busy = false;

  }

}



const scanner = new QrScanner(video, result => submit(result.data), {

  returnDetailedScanResult: true,

  preferredCamera: 'environment',

  maxScansPerSecond: 8,

  highlightScanRegion: true,

  highlightCodeOutline: true

});



let extrasDone = false, cams = [], camIndex = 0, wake = null;



async function keepAwake() {

  try { if ('wakeLock' in navigator) wake = await navigator.wakeLock.request('screen'); } catch (e) {}

}



async function setupExtras() {

  if (extrasDone) return;

  extrasDone = true;

  try {

    cams = await QrScanner.listCameras(true);

    if (cams.length > 1) switchBtn.hidden = false;

    if (await scanner.hasFlash()) flashBtn.hidden = false;

  } catch (e) {}

}



async function startCamera() {

  try {

    await scanner.start();

    note.textContent = 'Caméra active : présentez le QR code du badge.';

    startBtn.hidden = true;

    keepAwake();

    setupExtras();

  } catch (e) {

    note.textContent = 'Caméra indisponible ou accès refusé. Autorisez la caméra dans le navigateur, puis touchez « Démarrer la caméra ».';

    startBtn.hidden = false;

  }

}



startBtn.addEventListener('click', startCamera);



switchBtn.addEventListener('click', async () => {

  if (cams.length < 2) return;

  camIndex = (camIndex + 1) % cams.length;

  try { await scanner.setCamera(cams[camIndex].id); } catch (e) {}

});



flashBtn.addEventListener('click', async () => {

  try { await scanner.toggleFlash(); } catch (e) {}

});



document.addEventListener('visibilitychange', () => {

  if (document.hidden) { scanner.stop(); } else { startCamera(); }

});



startCamera();

</script>

@endpush

