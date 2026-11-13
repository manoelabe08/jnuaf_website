
<!doctype html>

<html lang="fr">

<head>

<meta charset="utf-8">

<style>

  @page { margin: 0; }

  body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #13281A; }

  .page { position: relative; width: 323px; height: 204px; overflow: hidden; background: #fff; }

  .break { page-break-after: always; }



  .top { position: absolute; top: 0; left: 0; right: 0; height: 40px; background: #fff; }

  .logo { position: absolute; left: 12px; top: 5px; height: 30px; }

  .brand { position: absolute; left: 12px; top: 11px; font-size: 16px; font-weight: bold; color: #14592A; }

  .brand span { color: #F26B1D; }

  .kind { position: absolute; right: 12px; top: 15px; font-size: 7px; letter-spacing: 2px; text-transform: uppercase; color: #14592A; font-weight: bold; }

  .orange { position: absolute; top: 40px; left: 0; right: 0; height: 3px; background: #F26B1D; }



  .photo { position: absolute; left: 14px; top: 54px; width: 63px; height: 84px; border: 2px solid #14592A; }

  .photo img { display: block; width: 63px; height: 84px; }

  .initials { width: 63px; height: 84px; background: #E3F1E5; color: #14592A; text-align: center; line-height: 84px; font-size: 22px; font-weight: bold; }



  .info { position: absolute; left: 98px; top: 52px; right: 12px; }

  .pill { display: inline-block; background: #FDEBDF; color: #B8470B; font-size: 6.5px; font-weight: bold; padding: 3px 7px; letter-spacing: 1px; text-transform: uppercase; }

  .name { font-size: 13px; font-weight: bold; line-height: 1.2; margin: 7px 0 3px; }

  .role { font-size: 8px; line-height: 1.35; }

  .terr { font-size: 8px; color: #55685B; margin-top: 3px; }



  .bottom { position: absolute; left: 0; right: 0; bottom: 0; height: 30px; background: #14592A; color: #fff; }

  .num { position: absolute; left: 14px; top: 10px; font-family: DejaVu Sans Mono, monospace; font-size: 9px; letter-spacing: 1px; }

  .valid { position: absolute; right: 12px; top: 11px; font-size: 7.5px; }



  .b-top { position: absolute; top: 0; left: 0; right: 0; height: 18px; background: #14592A; color: #fff; font-size: 7px; letter-spacing: 2px; text-transform: uppercase; padding: 5px 12px 0; }

  .qr { position: absolute; left: 14px; top: 30px; width: 84px; height: 84px; }

  .qr-cap { position: absolute; left: 14px; top: 117px; width: 84px; text-align: center; font-size: 5.5px; color: #55685B; }

  .b-text { position: absolute; left: 112px; top: 28px; right: 12px; font-size: 6.4px; line-height: 1.5; }

  .b-text p { margin: 0 0 5px; }

  .b-link { font-weight: bold; color: #14592A; }

  .sign { position: absolute; right: 12px; bottom: 28px; width: 100px; text-align: center; font-size: 5.8px; color: #55685B; line-height: 1.35; }

  .sign img { height: 28px; }

  .sign b { color: #13281A; }

  .b-bottom { position: absolute; left: 0; right: 0; bottom: 0; height: 22px; background: #E3F1E5; color: #14592A; font-size: 7px; }

  .b-num { position: absolute; left: 14px; top: 7px; font-family: DejaVu Sans Mono, monospace; letter-spacing: 1px; }

  .b-site { position: absolute; right: 12px; top: 7px; font-weight: bold; }

</style>

</head>

<body>

  @php

      $parts = preg_split('/\s+/', trim($card['name']));

      $initials = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr(count($parts) > 1 ? end($parts) : '', 0, 1));

      $signerName = trim((string) ($signer['name'] ?? ''));

      $signerTitle = trim((string) ($signer['title'] ?? ''));

  @endphp



  <div class="page break">

    <div class="top">

      @if (! empty($logo))

        <img class="logo" src="{{ $logo }}" alt="JNUAF">

      @else

        <div class="brand"><span>JN</span>UAF</div>

      @endif

      <div class="kind">Carte de membre</div>

    </div>

    <div class="orange"></div>



    <div class="photo">

      @if (! empty($card['photo']))

        <img src="{{ $card['photo'] }}" alt="">

      @else

        <div class="initials">{{ $initials }}</div>

      @endif

    </div>



    <div class="info">

      <span class="pill">{{ $card['category_label'] }}</span>

      <div class="name">{{ $card['name'] }}</div>

      <div class="role">{{ $card['role'] }}</div>

      <div class="terr">{{ $card['territory'] }}</div>

    </div>



    <div class="bottom">

      <div class="num">{{ $card['number'] }}</div>

      <div class="valid">Valide jusqu'au {{ $card['valid_until']->format('d/m/Y') }}</div>

    </div>

  </div>



  <div class="page">

    <div class="b-top">JNUAF.ORG · Carte de membre</div>



    <img class="qr" src="{{ $qr }}" alt="QR code de vérification">

    <div class="qr-cap">Scannez pour vérifier la validité de cette carte</div>



    <div class="b-text">

      <p>Cette carte atteste l'appartenance de son titulaire à JNUAF.ORG. Elle est personnelle et ne peut être cédée.</p>

      <p>Pour toute question sur l'utilisation de cette carte merci de consulter le site <span class="b-link">{{ $infoUrl }}</span></p>

    </div>



    @if (! empty($signature) || $signerName !== '')

      <div class="sign">

        @if (! empty($signature))

          <img src="{{ $signature }}" alt="Signature"><br>

        @endif

        @if ($signerName !== '')

          <b>{{ $signerName }}</b><br>

        @endif

        {{ $signerTitle }}

      </div>

    @endif



    <div class="b-bottom">

      <div class="b-num">{{ $card['number'] }}</div>

      <div class="b-site">#JNUAF.ORG</div>

    </div>

  </div>

</body>

</html>

