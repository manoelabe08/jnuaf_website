@extends('layouts.admin')
@section('title', 'Mon profil')

@section('content')
<div class="top"><div><h1>Mon profil</h1><p class="muted" style="margin:0">Vos informations et la sécurité de votre accès.</p></div></div>

<div class="grid g2">
  <div class="card">
    <h2>Identité</h2>
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
      @if($user->avatar_path)
        <img src="{{ $user->avatarUrl() }}" alt="Photo de profil" width="72" height="72" style="border-radius:50%;object-fit:cover">
      @else
        <span style="width:72px;height:72px;border-radius:50%;background:#E3F1E5;color:#14592A;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:28px">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
      @endif
      <div><b>{{ $user->name }}</b><br><span class="muted">{{ $user->role->label() }}@if($user->territory) · {{ $user->territory->city }}@endif</span></div>
    </div>

    <form class="form" method="POST" action="{{ route('admin.profile.photo') }}" enctype="multipart/form-data">
      @csrf
      <label>Photo de profil<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required></label>
      <p class="muted">JPG, PNG ou WebP, 4 Mo maximum. L'image est recadrée en carré.</p>
      <button class="btn sm" type="submit">Enregistrer la photo</button>
    </form>
    @if($user->avatar_path)
    <form method="POST" action="{{ route('admin.profile.photo.remove') }}" style="margin-top:8px" data-confirm="Supprimer votre photo de profil ?">
      @csrf @method('DELETE')
      <button class="btn danger sm" type="submit">Supprimer la photo</button>
    </form>
    @endif

    <form class="form" method="POST" action="{{ route('admin.profile.update') }}" style="margin-top:18px">
      @csrf
      <label>Nom et prénoms<input name="name" value="{{ old('name', $user->name) }}" required minlength="2" maxlength="120"></label>
      <button class="btn sm" type="submit">Enregistrer</button>
    </form>
  </div>

  <div class="card">
    <h2>Adresse email</h2>
    <p><b>{{ $user->email }}</b></p>
    
@if($emailChange)

    <p>Demande en cours vers <b>{{ $emailChange->new_email }}</b> :

      {{ $emailChange->status === 'pending_approval' ? 'en attente de validation par le Bureau de Pilotage.' : 'en attente de confirmation de la nouvelle adresse (consultez sa boîte mail).' }}

    </p>

    <p class="muted">Valable jusqu'au {{ $emailChange->expires_at->timezone(config('jnuaf_newsletter.timezone', 'Europe/Paris'))->format('d/m/Y à H:i') }}. Votre adresse actuelle reste active.</p>

    <form method="POST" action="{{ route('admin.profile.email.cancel') }}" data-confirm="Annuler cette demande ?">

      @csrf

      <button class="btn danger sm" type="submit">Annuler la demande</button>

    </form>

    @else

    <form class="form" method="POST" action="{{ route('admin.profile.email') }}">

      @csrf

      <label>Nouvelle adresse email<input type="email" name="new_email" value="{{ old('new_email') }}" required maxlength="150"></label>

      <label>Mot de passe actuel<input type="password" name="current_password" required autocomplete="current-password"></label>

      <p class="muted">

        Un lien de confirmation est envoyé à la nouvelle adresse, et un avis à l'ancienne.

        @if($user->isAdmin())

        Votre compte étant à accès complet, la modification doit ensuite être validée par le Bureau de Pilotage ; sans validation, la demande reste en suspens.

        @endif

      </p>

      <button class="btn sm" type="submit">Demander le changement</button>

    </form>

    @endif


    <h2 style="margin-top:22px">Mot de passe</h2>
    <form class="form" method="POST" action="{{ route('admin.password.change.update') }}">
      @csrf
      <input type="hidden" name="from" value="profile">
      <label>Mot de passe actuel<input type="password" name="current_password" required autocomplete="current-password"></label>
      <label>Nouveau mot de passe<input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
      <label>Confirmer le nouveau mot de passe<input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" data-nopaste></label>
      <p class="muted">12 caractères minimum, sans règle de composition : une phrase de passe longue est idéale. Les mots de passe courants ou déjà compromis sont refusés, ainsi que toute suite de 4 caractères de l’ancien.</p>
      <button class="btn sm" type="submit">Changer le mot de passe</button>
    </form>
  </div>
</div>

<div class="card" style="margin-top:16px">
  <h2>Sécurité</h2>
  @if($canEndSessions)
  <form class="form" method="POST" action="{{ route('admin.profile.sessions') }}" data-confirm="Fermer toutes vos autres sessions ouvertes ?">
    @csrf
    <label>Mot de passe pour confirmer<input type="password" name="current_password" required autocomplete="current-password"></label>
    <button class="btn sm" type="submit">Me déconnecter des autres appareils</button>
  </form>
  @else
  <p class="muted">La déconnexion des autres appareils n'est pas disponible avec la configuration actuelle des sessions.</p>
  @endif
  
<h3 style="margin:18px 0 6px">Appareils connus</h3>
  @forelse($devices ?? [] as $device)
  <form method="POST" action="{{ route('admin.profile.device.forget', $device->id) }}" style="display:flex;gap:10px;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid #DCE6DD" data-confirm="Retirer cet appareil de la liste ?">
    @csrf
    @method('DELETE')
    <span>{{ $device->label }} <span class="muted">· dernière connexion {{ $device->last_seen_at?->timezone('Europe/Paris')->format('d/m/Y H:i') }} · IP {{ $device->last_ip }}</span></span>
    <button class="btn sm" type="submit">Retirer</button>
  </form>
  @empty
  <p class="muted">Aucun appareil enregistré pour le moment.</p>
  @endforelse
  <p class="muted">Un appareil est reconnu par son navigateur et son système. Si vous en retirez un, sa prochaine connexion déclenchera une nouvelle alerte par email.</p>
@php($mandatory2fa = $user->isAdmin() && config('jnuaf_security.two_factor_admin_required', true))

  
<h3 style="margin:18px 0 8px">Application d’authentification</h3>

  @if($user->hasTotp())

    <p>Active (Google Authenticator, Microsoft Authenticator…) depuis le {{ $user->totp_enabled_at->timezone(config('jnuaf_newsletter.timezone', 'Europe/Paris'))->format('d/m/Y') }}. Le code de l'application est demandé à chaque connexion.</p>

    <form class="form" method="POST" action="{{ route('admin.profile.totp.disable') }}" data-confirm="Désactiver l’application d’authentification ?">

      @csrf

      <label>Mot de passe pour confirmer<input type="password" name="current_password" required autocomplete="current-password"></label>

      <button class="btn danger sm" type="submit">Désactiver l'application</button>

    </form>

  @elseif($totpKey)

    <p><b>1.</b> Ouvrez Google Authenticator ou Microsoft Authenticator, puis ajoutez un compte en scannant ce QR code.</p>

    @if($totpQr)

    <div style="width:220px;max-width:100%;background:#fff;padding:6px;border:1px solid #d6e0d9;border-radius:10px">{!! $totpQr !!}</div>

    @endif

    <p class="muted">Impossible de scanner ? Saisissez cette clé manuellement (type « basé sur l'heure ») :<br><code style="font-size:15px;word-break:break-all">{{ $totpKey }}</code></p>

    <p><b>2.</b> Saisissez le code à 6 chiffres affiché pour confirmer.</p>

    <form class="form" method="POST" action="{{ route('admin.profile.totp.enable') }}">

      @csrf

      <label>Code de l'application<input type="text" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="10"></label>

      <button class="btn sm" type="submit">Confirmer et activer</button>

    </form>

    <form method="POST" action="{{ route('admin.profile.totp.cancel') }}" style="margin-top:8px">@csrf<button class="btn sm" type="submit">Annuler</button></form>

  @else

    <p class="muted">Plus pratique et plus sûr qu'un code par email : un code qui change toutes les 30 secondes dans votre téléphone.</p>

    <form class="form" method="POST" action="{{ route('admin.profile.totp.start') }}">

      @csrf

      <label>Mot de passe pour confirmer<input type="password" name="current_password" required autocomplete="current-password"></label>

      <button class="btn sm" type="submit">Configurer une application</button>

    </form>

  @endif



  <h3 style="margin:18px 0 8px">Double authentification par email</h3>




  @if(session('recovery_codes'))

  <div class="flash" role="status">

    <strong>Vos codes de secours</strong> — notez-les maintenant, ils ne seront plus affichés. Chaque code ne fonctionne qu'une fois.

    <ul style="columns:2;margin:8px 0 0;padding-left:18px;font-family:Consolas,Menlo,monospace;font-size:16px">

      @foreach(session('recovery_codes') as $code)<li>{{ $code }}</li>@endforeach

    </ul>

  </div>

  @endif



  @if($mandatory2fa)

    <p>Obligatoire pour les comptes à accès complet : à chaque connexion, un code à 6 chiffres est envoyé à <b>{{ $user->email }}</b>.</p>

  @elseif($user->two_factor_enabled)

    <p>Activée : à chaque connexion, un code à 6 chiffres est envoyé à <b>{{ $user->email }}</b>.</p>

    <form class="form" method="POST" action="{{ route('admin.profile.2fa.disable') }}" data-confirm="Désactiver la double authentification ?">

      @csrf

      <label>Mot de passe pour confirmer<input type="password" name="current_password" required autocomplete="current-password"></label>

      <button class="btn danger sm" type="submit">Désactiver</button>

    </form>

  @else

    <p class="muted">Désactivée. Activez-la pour recevoir un code par email à chaque connexion.</p>

    <form class="form" method="POST" action="{{ route('admin.profile.2fa.enable') }}">

      @csrf

      <label>Mot de passe pour confirmer<input type="password" name="current_password" required autocomplete="current-password"></label>

      <button class="btn sm" type="submit">Activer la double authentification</button>

    </form>

  @endif



  @if($mandatory2fa || $user->two_factor_enabled)

  <p class="muted" style="margin-top:14px">Codes de secours restants : {{ count((array) $user->two_factor_recovery) }}</p>

  <form class="form" method="POST" action="{{ route('admin.profile.2fa.codes') }}" data-confirm="Générer de nouveaux codes ? Les anciens ne fonctionneront plus.">

    @csrf

    <label>Mot de passe pour confirmer<input type="password" name="current_password" required autocomplete="current-password"></label>

    <button class="btn sm" type="submit">Générer de nouveaux codes de secours</button>

  </form>

  @endif

</div>
@endsection
