
@extends('layouts.admin')

@section('title', 'Cartes de membre')



@section('content')



<div class="card" style="margin-bottom:16px">

  <h2>Photos des membres</h2>

  <p class="muted">Les personnes sans photo re&ccedil;oivent un mail avec un lien personnel &agrave; usage unique pour d&eacute;poser une photo d&#39;identit&eacute; pr&eacute;sentable. Chaque clic envoie 12 demandes au plus ; recliquez pour envoyer les suivantes.</p>

  <div style="display:flex;gap:8px;flex-wrap:wrap">

    <form method="POST" action="{{ route('admin.cards.photos.request') }}">

      @csrf

      <input type="hidden" name="scope" value="all">

      <input type="hidden" name="mode" value="dry">

      <button class="btn ghost" type="submit">Simuler (voir qui serait relanc&eacute;)</button>

    </form>

    <form method="POST" action="{{ route('admin.cards.photos.request') }}" data-confirm="Envoyer jusqu&#39;&agrave; 12 demandes de photo par mail ?">

      @csrf

      <input type="hidden" name="scope" value="all">

      <input type="hidden" name="mode" value="send">

      <button class="btn" type="submit">Envoyer les demandes de photo</button>

    </form>

  </div>

</div>

<div class="top">

  <div>

    <h1>Cartes de membre</h1>

    <p class="muted" style="margin:0">Bureau de Pilotage, points focaux, comité d'organisation, intervenantes et mentors.</p>

  </div>

  <a class="btn o" href="{{ route('admin.cards.create') }}">Émettre une carte</a>

</div>







@if ($errors->any())

  <div class="card" role="alert" style="border:1px solid #B3261E;margin-bottom:16px">

    @foreach ($errors->all() as $message)

      <div>{{ $message }}</div>

    @endforeach

  </div>

@endif


<form class="card form" method="GET" action="{{ route('admin.cards.index') }}" style="margin-bottom:16px">

  <label>Rechercher (nom ou numéro)

    <input type="search" name="q" value="{{ $q }}" maxlength="100">

  </label>

  <label>Catégorie

    <select name="categorie">

      <option value="">Toutes</option>

      @foreach (\App\Services\MemberCardService::CATEGORIES as $code => $label)

        <option value="{{ $code }}" {{ $category === $code ? 'selected' : '' }}>{{ $label }}</option>

      @endforeach

    </select>

  </label>

  <button class="btn ghost" type="submit">Filtrer</button>

</form>



<div class="card">

  <table class="stack">

    <thead>

      <tr><th>Numéro</th><th>Nom</th><th>Catégorie</th><th>Territoire</th><th>Valide jusqu'au</th><th>Statut</th><th></th></tr>

    </thead>

    <tbody>

      @forelse ($cards as $card)

        <tr>

          <td data-label="Numéro"><code>{{ $card->number }}</code></td>

          <td data-label="Nom">{{ $card->full_name }}</td>

          <td data-label="Catégorie"><span class="pill">{{ \App\Services\MemberCardService::CATEGORIES[$card->category] ?? $card->category }}</span></td>

          <td data-label="Territoire">{{ $card->territory ? $card->territory->city : 'Siège' }}</td>

          <td data-label="Valide jusqu'au">{{ $card->valid_until->format('d/m/Y') }}</td>

          <td data-label="Statut">{{ $card->statusLabel() }}</td>

          <td>

            <a class="btn ghost" href="{{ route('admin.cards.pdf', $card) }}" target="_blank" rel="noopener">PDF</a>

            
<form method="POST" action="{{ route('admin.cards.photo', $card) }}" enctype="multipart/form-data" style="display:inline-block;margin:4px 0">

                @csrf

                <input type="file" name="photo" accept="image/jpeg,image/png" required style="max-width:150px">

                <button class="btn ghost" type="submit">{{ $card->photo_path ? 'Changer la photo' : 'Ajouter la photo' }}</button>

              </form>

              

@php

                $canSend = $card->isActive() && $card->email;

                $canRenew = ! $card->isRevoked() && $card->valid_until->copy()->subDays(60)->isPast();

              @endphp

              <a class="btn ghost" href="{{ app(\App\Services\MemberCardService::class)->verifyUrl($card->verify_token) }}" target="_blank" rel="noopener">Vérifier</a>

              @if ($canSend)

                <form method="POST" action="{{ route('admin.cards.send', $card) }}" style="display:inline" data-confirm="Envoyer cette carte par email à son titulaire ?">

                  @csrf

                  <button class="btn ghost" type="submit">{{ $card->sent_at ? 'Renvoyer par email' : 'Envoyer par email' }}</button>

                </form>

              @endif

              @if ($canRenew)

                <form method="POST" action="{{ route('admin.cards.renew', $card) }}" style="display:inline" data-confirm="Renouveler cette carte ?">

                  @csrf

                  <button class="btn ghost" type="submit">Renouveler</button>

                </form>

              @endif

              
@if ($card->isActive())

              <form method="POST" action="{{ route('admin.cards.revoke', $card) }}" style="display:inline" data-confirm="Révoquer cette carte ? Le QR code ne sera plus valide.">

                @csrf

                <button class="btn ghost" type="submit">Révoquer</button>

              </form>

            @endif

          </td>

        </tr>

      @empty

        <tr><td class="muted">Aucune carte émise pour l'instant.</td></tr>

      @endforelse

    </tbody>

  </table>



  <p style="margin-top:14px">

    @if ($cards->previousPageUrl()) <a class="btn ghost" href="{{ $cards->previousPageUrl() }}">← Précédent</a> @endif

    @if ($cards->nextPageUrl()) <a class="btn ghost" href="{{ $cards->nextPageUrl() }}">Suivant →</a> @endif

  </p>

</div>

@endsection

