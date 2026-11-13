@extends('layouts.admin')
@section('title', 'Paramètres')

@section('content')
<div class="top"><div><h1>Paramètres</h1><p class="muted" style="margin:0">Ces réglages s'appliquent immédiatement au site, sans modifier le code.</p></div></div>

<form class="grid g2" method="POST" action="{{ route('admin.settings.update') }}">
  @csrf @method('PUT')

  <div class="grid" style="align-content:start">
    <div class="card form">
      <h2>Événement et inscriptions</h2>
      <label>Date de l'événement
        <input type="date" name="event_date" value="{{ old('event_date', $values['event_date']) }}" required>
      </label>
      <p class="muted" style="margin:0;font-size:.85rem">Sert aux rappels (envoyés à partir de J-{{ config('jnuaf.reminder_days_before') }}), aux emails et au calcul de la durée de conservation.</p>
      <label class="check"><input type="checkbox" name="registrations_open" value="1" @checked(old('registrations_open', $values['registrations_open']))> Inscriptions ouvertes</label>
      <p class="muted" style="margin:0;font-size:.85rem">Décochée : le formulaire d'inscription est remplacé par « Les inscriptions sont fermées ».</p>
    </div>

    <div class="card form">
      <h2>Notifications</h2>
      <label>Adresse supplémentaire à prévenir
        <input type="email" name="notify_email" value="{{ old('notify_email', $values['notify_email']) }}" placeholder="bureau@jnuaf.org">
      </label>
      <p class="muted" style="margin:0;font-size:.85rem">Reçoit, comme les comptes administrateurs, une alerte à chaque candidature au comité et à chaque demande de partenariat.</p>
    </div>

    <div class="card form">
      <h2>Conservation des données</h2>
      <div class="row">
        <label>Inscriptions : anonymisées après (mois)
          <input type="number" name="retention_months" min="1" max="60" value="{{ old('retention_months', $values['retention_months']) }}" required>
        </label>
        <label>Candidatures comité : supprimées après décision (mois)
          <input type="number" name="committee_retention_months" min="1" max="60" value="{{ old('committee_retention_months', $values['committee_retention_months']) }}" required>
        </label>
      </div>
      <p class="muted" style="margin:0;font-size:.85rem">Appliqué chaque nuit automatiquement. Ces durées sont affichées dans la politique de confidentialité du site.</p>
    </div>
  </div>

  <div class="card form" style="align-content:start">
    <h2>Mentions légales et confidentialité</h2>
    <p class="muted" style="margin:0;font-size:.85rem">Affichées dans la section « Vos données » du site. Un champ vide apparaît comme « [à compléter] ».</p>
    <label>Responsable du traitement (structure, adresse)
      <textarea name="legal_controller" maxlength="500">{{ old('legal_controller', $values['legal_controller']) }}</textarea>
    </label>
    <label>Email dédié à la protection des données
      <input type="email" name="legal_privacy_email" value="{{ old('legal_privacy_email', $values['legal_privacy_email']) }}" placeholder="donnees@jnuaf.org">
    </label>
    <label>Éditeur du site (forme juridique, siège, n° d'enregistrement)
      <textarea name="legal_publisher" maxlength="500">{{ old('legal_publisher', $values['legal_publisher']) }}</textarea>
    </label>
    <label>Directeur·rice de la publication
      <input name="legal_director" value="{{ old('legal_director', $values['legal_director']) }}" maxlength="150">
    </label>
    <label>Hébergeur (nom, adresse, contact)
      <textarea name="legal_host" maxlength="500" placeholder="Ex. LWS : raison sociale, adresse, téléphone">{{ old('legal_host', $values['legal_host']) }}</textarea>
    </label>
    <button class="btn" type="submit">Enregistrer les paramètres</button>
  </div>
</form>
@endsection
