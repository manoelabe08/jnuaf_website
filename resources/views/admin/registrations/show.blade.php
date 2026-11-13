@extends('layouts.admin')
@section('title', $registration->fullName())

@section('content')
@php($r = $registration)
<div class="top">
  <div><a href="{{ route('admin.registrations.index') }}">← Inscriptions</a><h1>{{ $r->fullName() }}</h1></div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a class="btn ghost" href="{{ route('admin.registrations.badge', $r) }}" target="_blank">Voir le badge</a>
    @if ($r->canGetAttestation())
      <a class="btn ghost" href="{{ route('admin.registrations.attestation', $r) }}" target="_blank">Attestation</a>
    @endif
    @if ($r->isConfirmed())
      <form class="inline" method="POST" action="{{ route('admin.registrations.resend', $r) }}">@csrf<button class="btn">Renvoyer le badge par email</button></form>
    @endif
  </div>
</div>

<div class="grid g2">
  <div class="card">
    <h2>Informations</h2>
    <dl class="kv">
      <dt>Badge</dt><dd><code>{{ $r->badge_code }}</code></dd>
      <dt>Profil</dt><dd>{{ $r->profile->label() }}</dd>
      <dt>Statut</dt><dd>{{ $r->status->label() }}</dd>
      <dt>Email</dt><dd><a href="mailto:{{ $r->email }}">{{ $r->email }}</a></dd>
      <dt>Téléphone</dt><dd>{{ $r->phone }}</dd>
      <dt>Territoire</dt><dd>{{ $r->territory->city }} ({{ $r->territory->country }})</dd>
      <dt>Ville</dt><dd>{{ $r->city }}</dd>
      @foreach (['organization' => 'Structure', 'motivation' => 'Motivation', 'skills' => 'Compétences', 'availability' => 'Disponibilités', 'experience' => 'Expérience', 'expertise' => 'Expertise', 'bio' => 'Biographie', 'topic' => 'Thématique', 'accessibility_needs' => 'Accessibilité'] as $field => $label)
        @if ($r->$field)<dt>{{ $label }}</dt><dd>{{ $r->$field }}</dd>@endif
      @endforeach
    </dl>
  </div>
  <div class="grid" style="align-content:start">
    <div class="card">
      <h2>Suivi</h2>
      <dl class="kv">
        <dt>Inscrite le</dt><dd>{{ $r->created_at->format('d/m/Y H:i') }}</dd>
        <dt>Badge envoyé</dt><dd>{{ $r->badge_sent_at?->format('d/m/Y H:i') ?? '—' }}</dd>
        <dt>Rappel envoyé</dt><dd>{{ $r->reminder_sent_at?->format('d/m/Y H:i') ?? '—' }}</dd>
        <dt>Présence</dt><dd>{{ $r->checked_in_at ? $r->checked_in_at->format('d/m/Y H:i').' par '.($r->checkedInBy?->name ?? '—') : 'Non pointée' }}</dd>
        <dt>Données</dt><dd>Consentement le {{ $r->consented_at->format('d/m/Y') }}</dd>
        <dt>Droit à l'image</dt><dd>{{ $r->consent_image ? 'Accordé' : 'Refusé' }}</dd>
      </dl>
    </div>
    <div class="card form">
      <h2>Statut</h2>
      <form class="form" method="POST" action="{{ route('admin.registrations.update', $r) }}">
        @csrf @method('PATCH')
        <label>Statut de l'inscription
          <select name="status">@foreach (\App\Enums\RegistrationStatus::cases() as $s)<option value="{{ $s->value }}" @selected($r->status === $s)>{{ $s->label() }}</option>@endforeach</select>
        </label>
        <button class="btn">Enregistrer</button>
      </form>
      <form method="POST" action="{{ route('admin.registrations.destroy', $r) }}" data-confirm="Supprimer définitivement cette inscription et ses données ?">
        @csrf @method('DELETE')
        <button class="btn danger">Supprimer définitivement (demande d'effacement)</button>
      </form>
    </div>
  </div>
</div>

@php($__reg = request()->route('registration'))

@if ($__reg && ($__reg->photo_path || $__reg->cv_path))

<div class="card" style="margin-top:16px">

  <h2>Photo et CV</h2>

  <p>@if ($__reg->photo_path)<a class="btn ghost" href="{{ route('admin.registrations.file', [$__reg, 'photo']) }}" target="_blank">Ouvrir la photo</a> @endif @if ($__reg->cv_path)<a class="btn ghost" href="{{ route('admin.registrations.file', [$__reg, 'cv']) }}" target="_blank">Ouvrir le CV</a>@endif</p>

</div>

@endif


@endsection
