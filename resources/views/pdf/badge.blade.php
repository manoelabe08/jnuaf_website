<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
  @page { margin: 0; }
  body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #13281A; }
  .top { background: #14592A; color: #fff; padding: 18px 20px 14px; }
  .brand { font-size: 26px; font-weight: bold; letter-spacing: 1px; }
  .brand .o { color: #F26B1D; }
  .sub { font-size: 9px; opacity: .85; margin-top: 2px; }
  .band { height: 6px; background: #F26B1D; }
  .body { padding: 16px 20px 0; text-align: center; }
  .profile { display: inline-block; background: #FDEBDF; color: #F26B1D; font-size: 9px; font-weight: bold; padding: 4px 10px; border-radius: 10px; text-transform: uppercase; letter-spacing: 1px; }
  .name { font-size: 20px; font-weight: bold; margin: 10px 0 2px; }
  .terr { font-size: 11px; color: #55685B; }
  .qr { margin: 12px auto 6px; width: 170px; height: 170px; }
  .code { font-family: DejaVu Sans Mono, monospace; font-size: 11px; letter-spacing: 1px; }
  .foot { position: absolute; bottom: 14px; left: 20px; right: 20px; font-size: 8px; color: #55685B; text-align: center; }
  .logo-box { display: inline-block; background: #fff; padding: 6px 14px; border-radius: 8px; }
  .logo-box img { height: 44px; }
  .hdr-table { width: 100%; border-collapse: collapse; }
  .hdr-logo { width: 1%; white-space: nowrap; vertical-align: middle; padding: 0 16px 0 0; }
  .hdr-text { vertical-align: middle; padding: 0; }
</style>
</head>
<body>
  <div class="top">
    <table class="hdr-table"><tr><td class="hdr-logo"><div class="logo-box"><img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('images/logo.jpg'))) }}" alt="JNUAF"></div></td><td class="hdr-text"><div class="brand" style="font-size:22px">@if ($registration->isEventOnly()) {{ \Illuminate\Support\Str::limit($registration->eventTitle(), 30) }} @else <span class="o">JN</span>UAF {{ $registration->edition }} @endif</div><div class="sub">Journée du Numérique au Féminin</div></td></tr></table>
  </div>
  <div class="band"></div>
  <div class="body">
    <span class="profile">{{ $registration->profile->label() }}</span>
    <div class="name">{{ $registration->fullName() }}</div>
    <div class="terr">{{ $registration->territory->city }} · {{ $registration->territory->country }}</div>
    <img class="qr" src="{{ $qr }}" alt="QR code">
    <div class="code">{{ $registration->badge_code }}</div>
  </div>
  <div class="foot">Présentez ce QR code à l'entrée. Il ne contient aucune donnée personnelle.<br>Badge personnel et non transférable.</div>
</body>
</html>
