@extends('layouts.admin')
@section('title', 'Pointage')

@section('content')
<div class="top"><div><h1>Pointage des entrées</h1><p class="muted" style="margin:0">Scannez le QR code du badge avec la caméra, une douchette USB, ou saisissez le code imprimé.</p></div>@if (auth()->user()->isAdmin())<form method="POST" action="{{ route('admin.attestations.send') }}" data-confirm="Envoyer l'attestation de participation par email à toutes les personnes pointées qui ne l'ont pas encore reçue ?">@csrf<button class="btn ghost">Envoyer les attestations</button></form>@endif</div>

<div class="grid g2">
  <div class="card form">
    <form id="checkinForm" class="form" method="POST" action="{{ route('admin.checkin.store') }}">
      @csrf
      <label>Code du badge ou contenu du QR
        <input id="code" name="code" autocomplete="off" autofocus placeholder="JNUAF27-ABJ-XXXXXX" required>
      </label>
      <button class="btn o" type="submit">Valider l'entrée</button>
    </form>
    <div>
      <a class="btn o" href="{{ route('admin.checkin.camera') }}">Mode scanner continu</a> <button class="btn ghost" type="button" id="camBtn">Scanner avec la caméra</button>
      <p class="muted" id="camNote" style="font-size:.85rem"></p>
      <video id="video" playsinline muted hidden></video>
    </div>
  </div>

  <div>
    <div id="result" aria-live="assertive">
      @if (session('checkin'))
        @php($c = session('checkin'))
        <div class="scan-result {{ $c['state'] }}"><strong>{{ $c['message'] }}</strong>@if ($c['name']){{ $c['name'] }} · <code>{{ $c['badge'] }}</code>@endif</div>
      @endif
    </div>
    <div class="card" style="margin-top:16px">
      <h2>Dernières entrées</h2>
      <table><tbody id="recent">
        @forelse ($recent as $r)
          <tr><td>{{ $r->checked_in_at->format('H:i') }}</td><td>{{ $r->fullName() }}</td><td><span class="pill">{{ $r->profile->label() }}</span></td><td>{{ $r->territory->city }}</td></tr>
        @empty
          <tr><td class="muted">Aucune entrée pointée.</td></tr>
        @endforelse
      </tbody></table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const form = document.getElementById('checkinForm'), input = document.getElementById('code'), out = document.getElementById('result');
  const token = document.querySelector('meta[name="csrf-token"]').content;
  const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  let busy = false, lastCode = '', lastAt = 0;

  async function submit(code) {
    // Évite le double pointage quand la caméra lit le même QR plusieurs fois de suite.
    if (busy || (code === lastCode && Date.now() - lastAt < 4000)) return;
    busy = true; lastCode = code; lastAt = Date.now();
    try {
      const res = await fetch(form.action, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token}, body: JSON.stringify({code})});
      const d = await res.json();
      out.innerHTML = '<div class="scan-result ' + esc(d.state) + '"><strong>' + esc(d.message) + '</strong>'
        + (d.name ? esc(d.name) + ' · ' + esc(d.profile) + ' · ' + esc(d.territory) + ' · <code>' + esc(d.badge) + '</code>' : '')
        + (d.accessibility ? '<br><b>Besoin spécifique :</b> ' + esc(d.accessibility) : '') + '</div>';
      if (d.state === 'ok') {
        const row = document.createElement('tr');
        row.innerHTML = '<td>' + new Date().toTimeString().slice(0, 5) + '</td><td>' + esc(d.name) + '</td><td><span class="pill">' + esc(d.profile) + '</span></td><td>' + esc(d.territory) + '</td>';
        document.getElementById('recent').prepend(row);
        if (navigator.vibrate) navigator.vibrate(120);
      }
    } catch (e) {
      out.innerHTML = '<div class="scan-result invalid"><strong>Erreur réseau.</strong>Réessayez.</div>';
    } finally {
      busy = false; input.value = ''; input.focus();
    }
  }
  form.addEventListener('submit', e => { e.preventDefault(); if (input.value.trim()) submit(input.value.trim()); });

  // Lecture caméra via l'API BarcodeDetector (Chrome/Edge Android, Safari récent).
  document.getElementById('camBtn').addEventListener('click', async () => {
    const note = document.getElementById('camNote'), video = document.getElementById('video');
    if (!('BarcodeDetector' in window)) { note.textContent = "Ce navigateur ne lit pas les QR codes : utilisez l'appareil photo du téléphone (le lien ouvre la page de pointage) ou une douchette."; return; }
    try {
      const stream = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}});
      video.srcObject = stream; video.hidden = false; await video.play();
      const detector = new BarcodeDetector({formats: ['qr_code']});
      note.textContent = 'Caméra active : présentez le QR code.';
      const loop = async () => {
        try { const codes = await detector.detect(video); if (codes[0]) await submit(codes[0].rawValue); } catch (e) {}
        requestAnimationFrame(loop);
      };
      loop();
    } catch (e) { note.textContent = "Accès à la caméra refusé ou indisponible."; }
  });
})();
</script>
@endpush
