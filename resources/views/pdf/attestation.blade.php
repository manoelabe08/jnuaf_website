
<!doctype html>

<html lang="fr">

<head>

<meta charset="utf-8">

<style>

  @page { margin: 0; }

  body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #13281A; }

  .frame { position: absolute; top: 22px; left: 22px; right: 22px; bottom: 22px; border: 3px solid #14592A; }

  .inner { position: absolute; top: 32px; left: 32px; right: 32px; bottom: 32px; border: 1px solid #F26B1D; }

  .band { position: absolute; top: 32px; left: 32px; width: 16px; bottom: 32px; background: #14592A; }

  .content { position: absolute; top: 56px; left: 110px; right: 80px; text-align: center; }

  .logo { height: 66px; }

  .brand { font-size: 34px; font-weight: bold; letter-spacing: 2px; color: #14592A; }

  .brand .o { color: #F26B1D; }

  .edition { margin-top: 8px; font-size: 12px; letter-spacing: 3px; color: #55685B; text-transform: uppercase; }

  h1 { font-size: 38px; margin: 30px 0 6px; color: #14592A; letter-spacing: 3px; text-transform: uppercase; }

  .rule { width: 120px; height: 3px; background: #F26B1D; margin: 0 auto 28px; }

  .lead { font-size: 15px; color: #55685B; }

  .name { font-size: 34px; font-weight: bold; margin: 14px 0; }

  .text { font-size: 15px; line-height: 1.6; color: #13281A; margin: 0 40px; }

  .foot { position: absolute; bottom: 62px; left: 110px; right: 80px; font-size: 12px; color: #55685B; }

  .foot table { width: 100%; border-collapse: collapse; }

  .foot td { vertical-align: bottom; }

  .sign { width: 300px; text-align: center; }

  .sig { height: 64px; }

  .sig-space { height: 64px; }

  .sig-line { border-top: 1px solid #13281A; margin: 4px 20px 6px; }

  .sign b { color: #13281A; font-size: 13px; }

  .code { font-family: DejaVu Sans Mono, monospace; font-size: 10px; letter-spacing: 1px; }

</style>

</head>

<body>

  @php

      $signerName = trim((string) ($signer['name'] ?? ''));

      $signerTitle = trim((string) ($signer['title'] ?? ''));

  @endphp



  <div class="frame"></div>

  <div class="inner"></div>

  <div class="band"></div>



  <div class="content">

    @if (! empty($logo))

      <img class="logo" src="{{ $logo }}" alt="JNUAF">

    @else

      <div class="brand"><span class="o">JN</span>UAF</div>

    @endif

    <div class="edition">@if ($registration->isEventOnly()) {{ $registration->eventTitle() }} @else &Eacute;dition {{ $registration->edition }} @endif · Journée du Numérique au Féminin</div>



    <h1>Attestation de participation</h1>

    <div class="rule"></div>

    <div class="lead">Le Bureau de Pilotage de la JNUAF atteste que</div>

    <div class="name">{{ $registration->fullName() }}</div>

    <div class="text">

      a participé en tant que <b>{{ mb_strtolower($registration->profile->label()) }}</b> @if ($registration->isEventOnly()) &agrave; <b>{{ $registration->eventTitle() }}</b>, @else &agrave; la Journ&eacute;e du Num&eacute;rique au F&eacute;minin {{ $registration->edition }}, @endif sur le territoire <b>{{ $registration->territory->cityWithPreposition() }} ({{ $registration->territory->country }})</b>,

      le {{ $eventDate->translatedFormat('j F Y') }}.

    </div>

  </div>



  <div class="foot">

    <table><tr>

      <td>Fait pour servir et valoir ce que de droit.<br><span class="code">Réf. {{ $registration->badge_code }}</span></td>

      <td class="sign">

        @if (! empty($signature))

          <img class="sig" src="{{ $signature }}" alt="Signature">

        @else

          <div class="sig-space"></div>

        @endif

        <div class="sig-line"></div>

        <b>{{ $signerName !== '' ? $signerName : 'Le Bureau de Pilotage' }}</b><br>

        @if ($signerTitle !== '') {{ $signerTitle }}<br> @endif

        #JNUAF.ORG

      </td>

    </tr></table>

  </div>

</body>

</html>

