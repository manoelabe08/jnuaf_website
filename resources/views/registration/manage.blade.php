@extends('layouts.v2')

@section('title', 'Mon inscription · '.$registration->eventTitle())

@section('content')
<section>
  <div class="wrap manage">
    <div class="sec-head">
      <p class="eyebrow">Mon inscription</p>
      <h2>Bonjour <span class="o">{{ $registration->first_name }}</span></h2>
      <p class="lede">Retrouvez ici les informations que vous nous avez confiées. Vous pouvez les modifier, annuler votre venue ou les supprimer définitivement.</p>
    </div>

    @if (session('status'))<div class="confirm" role="status" style="margin-bottom:20px"><strong>{{ session('status') }}</strong></div>@endif

    <div class="form-card" style="margin-bottom:24px">
      <dl class="kv">
        <dt>Profil</dt><dd>{{ $registration->profile->label() }}</dd>
        <dt>Statut</dt><dd>{{ $registration->status->label() }}</dd>
        <dt>Nom</dt><dd>{{ $registration->fullName() }}</dd>
        <dt>Email</dt><dd>{{ $registration->email }}</dd>
        <dt>&Eacute;v&eacute;nement</dt><dd>{{ $registration->eventTitle() }}</dd>
        <dt>Territoire</dt><dd>{{ $registration->territory->city }} ({{ $registration->territory->country }})</dd>
        <dt>Badge</dt><dd><code>{{ $registration->badge_code }}</code></dd>
        @if ($registration->organization)<dt>Structure</dt><dd>{{ $registration->organization }}</dd>@endif
        @if ($registration->skills)<dt>Compétences</dt><dd>{{ $registration->skills }}</dd>@endif
        @if ($registration->expertise)<dt>Expertise</dt><dd>{{ $registration->expertise }}</dd>@endif
        @if ($registration->topic)<dt>Thématique</dt><dd>{{ $registration->topic }}</dd>@endif
        <dt>Inscrite le</dt><dd>{{ $registration->created_at->translatedFormat('d F Y à H:i') }}</dd>
      </dl>
      @if ($registration->isConfirmed())
        <a class="btn btn-green" href="{{ route('registration.badge', $token) }}">Télécharger mon badge (PDF)</a>
      @elseif ($registration->isWaitlisted())
        <p class="note-dark"><b>Vous êtes sur liste d'attente.</b> Dès qu'une place se libère à {{ $registration->territory->city }}, elle vous est attribuée automatiquement et votre badge vous est envoyé par email.</p>
      @endif
      @if ($registration->canGetAttestation())
        <a class="btn btn-ghost" href="{{ route('registration.attestation', $token) }}">Télécharger mon attestation de participation</a>
      @endif
    </div>

    @unless ($registration->isCancelled())
      <form class="form-card" method="POST" action="{{ route('registration.update', $token) }}" style="margin-bottom:24px">
        @csrf @method('PATCH')
        <h3>Modifier mes informations</h3>
        <div class="row">
          <div @class(['field', 'invalid' => $errors->has('phone')])><label for="m-tel">Téléphone *</label><input id="m-tel" name="phone" value="{{ old('phone', $registration->phone) }}" required><span class="err">@error('phone'){{ $message }}@enderror</span></div>
          <div @class(['field', 'invalid' => $errors->has('city')])><label for="m-ville">Ville *</label><input id="m-ville" name="city" value="{{ old('city', $registration->city) }}" required><span class="err">@error('city'){{ $message }}@enderror</span></div>
        </div>
        <div class="field"><label for="m-access">Besoins spécifiques (accessibilité)</label><input id="m-access" name="accessibility_needs" value="{{ old('accessibility_needs', $registration->accessibility_needs) }}"></div>
        <label class="consent"><input type="checkbox" name="consent_image" value="1" @checked(old('consent_image', $registration->consent_image))> <span>J'autorise la publication de photos et vidéos sur lesquelles j'apparais.</span></label>
        <button class="btn btn-orange" type="submit">Enregistrer</button>
      </form>

      <form class="form-card" method="POST" action="{{ route('registration.cancel', $token) }}" onsubmit="return confirm('Annuler votre inscription ? Votre badge ne sera plus valide.')" style="margin-bottom:24px">
        @csrf
        <h3>Je ne pourrai pas venir</h3>
        <p class="note-dark">Votre place est libérée et votre badge désactivé. Vos données restent conservées pour les statistiques jusqu'à leur suppression.</p>
        <button class="btn btn-ghost danger" type="submit">Annuler mon inscription</button>
      </form>
    @endunless

    <form class="form-card" method="POST" action="{{ route('registration.destroy', $token) }}" onsubmit="return confirm('Supprimer définitivement toutes vos données ? Cette action est irréversible.')">
      @csrf @method('DELETE')
      <h3>Supprimer mes données</h3>
      <p class="note-dark">Toutes les informations liées à cette inscription sont effacées immédiatement. Cette action est irréversible.</p>
      <button class="btn btn-ghost danger" type="submit">Supprimer définitivement</button>
    </form>
  </div>
</section>
@endsection

<!-- jnuaf-s95 -->