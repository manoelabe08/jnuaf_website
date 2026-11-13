
<!doctype html>

<html lang="fr">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<meta name="robots" content="noindex,nofollow">

<meta name="referrer" content="no-referrer">

<title>Vérification de carte · JNUAF</title>

<style>

  body { margin: 0; background: #F7FAF6; font-family: Arial, Helvetica, sans-serif; color: #13281A; }

  .wrap { max-width: 520px; margin: 0 auto; padding: 24px 16px; }

  .logo { display: block; margin: 0 auto 18px; height: 64px; width: auto; background: #fff; padding: 4px 10px; border-radius: 10px; }

  .card { background: #fff; border: 1px solid #DCE6DD; border-radius: 14px; overflow: hidden; }

  .banner { padding: 18px 20px; font-size: 20px; font-weight: bold; color: #fff; }

  .ok { background: #1E7B3A; }

  .warn { background: #8A4B00; }

  .bad { background: #B3261E; }

  .none { background: #55685B; }

  dl { margin: 0; padding: 8px 20px 18px; }

  dt { margin-top: 14px; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; color: #55685B; }

  dd { margin: 3px 0 0; font-size: 17px; overflow-wrap: anywhere; }

  .note { padding: 0 20px 18px; font-size: 13px; line-height: 1.55; color: #55685B; }

  .foot { margin-top: 16px; text-align: center; font-size: 13px; }

  a { color: #14592A; }

</style>

</head>

<body>

@php

    $state = $card === null ? 'none' : ($card->isRevoked() ? 'bad' : ($card->isExpired() ? 'warn' : 'ok'));

    $title = ['ok' => 'Carte valide', 'warn' => 'Carte expirée', 'bad' => 'Carte révoquée', 'none' => 'Carte introuvable'][$state];

@endphp

<div class="wrap">

  <img class="logo" src="{{ asset('images/logo.jpg') }}" alt="JNUAF">



  <main class="card" role="status">

    <div class="banner {{ $state }}">{{ $title }}</div>



    @if ($card)

      <dl>

        <dt>Titulaire</dt>

        <dd>{{ $card->full_name }}</dd>

        <dt>Catégorie</dt>

        <dd>{{ \App\Services\MemberCardService::CATEGORIES[$card->category] ?? $card->category }}</dd>

        <dt>Territoire</dt>

        <dd>{{ $card->territory ? $card->territory->city.' · '.$card->territory->country : 'Siège' }}</dd>

        <dt>Numéro de membre</dt>

        <dd>{{ $card->number }}</dd>

        <dt>{{ $card->isExpired() ? 'Expirée le' : 'Valable jusqu\'au' }}</dt>

        <dd>{{ $card->valid_until->format('d/m/Y') }}</dd>

      </dl>

      <p class="note">

        @if ($state === 'ok')

          Comparez ces informations avec la carte présentée.

        @else

          Cette carte ne doit plus être acceptée comme justificatif.

        @endif

      </p>

    @else

      <p class="note" style="padding-top:18px">Aucune carte ne correspond à ce code. Vérifiez que le QR code est bien celui d'une carte JNUAF.</p>

    @endif

  </main>



  <p class="foot"><a href="{{ url('/carte-info') }}">Informations sur la carte de membre</a></p>

</div>

</body>

</html>

