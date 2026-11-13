@extends('layouts.admin')
@section('title', 'Documents partenaires')
@section('content')
<h1>Documents partenaires</h1>
@if(session('status'))<p class="card">{{ session('status') }}</p>@endif
@if($errors->any())<div class="card">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($storageError)<div class="card"><h2>Stockage privé à configurer</h2><p>{{ $storageError }}</p><p>Ne déposez aucun document dans public/.</p></div>@endif
@if(!config('partner_portal.admin_emails'))<p class="card">Attention : PARTNER_ADMIN_EMAILS n'est pas configuré. Aucune alerte admin ne pourra être envoyée.</p>@endif
<p class="muted">{{ $pendingNotices }} notification(s) email en attente. La tâche planifiée doit fonctionner.</p>
<div class="card" style="margin:16px 0">
<h2>Ajouter un PDF privé</h2>
<form class="form" method="POST" action="{{ route('admin.partner-portal.upload') }}" enctype="multipart/form-data">
    @csrf
    <label>Titre <input name="title" required maxlength="255" value="{{ old('title') }}"></label>
    <label>Description / instructions <textarea name="description" maxlength="3000">{{ old('description') }}</textarea></label>
    <label>PDF — 20 Mo maximum <input type="file" name="pdf" accept="application/pdf,.pdf" required></label>
    <button class="btn" type="submit">Ajouter le PDF</button>
</form>
</div>
<div class="card" style="margin:16px 0">
<h2>Documents disponibles</h2>
@forelse($documents as $document)
    <div style="border-bottom:1px solid #ddd;padding:12px 0">
        <p>{{ $document->title }} — {{ $document->active ? 'Actif' : 'Désactivé' }}</p>
        <a class="btn sm" target="_blank" rel="noopener noreferrer" href="{{ route('admin.partner-portal.preview', $document) }}">Aperçu administrateur</a>
        <form method="POST" action="{{ route('admin.partner-portal.toggle', $document) }}" style="display:inline">
            @csrf @method('PATCH')
            <button type="submit" class="btn sm">{{ $document->active ? 'Désactiver partout' : 'Réactiver' }}</button>
        </form>
    </div>
@empty<p>Aucun PDF ajouté.</p>@endforelse
</div>
<div class="card" style="margin:16px 0">
<h2>Créer un espace individuel</h2>
<form class="form" method="POST" action="{{ route('admin.partner-portal.store') }}">
    @csrf
    <label>Nom du partenaire / organisme <input name="name" value="{{ old('name') }}" maxlength="255" required></label>
    <label>Email autorisé <input name="email" type="email" value="{{ old('email') }}" maxlength="255" required></label>
    <p class="muted">L'adresse doit exister dans users ou registrations (inscription non anonymisée).</p>
    <label>Fin du partage <input name="expires_at" type="datetime-local" value="{{ old('expires_at', now()->addDays(30)->format('Y-m-d\TH:i')) }}" required></label>
    <p>Documents autorisés :</p>
    @foreach($documents as $document)
        <label style="display:block"><input type="checkbox" name="documents[]" value="{{ $document->id }}"> {{ $document->title }} {{ $document->active ? '' : '(désactivé)' }}</label>
    @endforeach
    <button type="submit" class="btn">Créer l'espace</button>
</form>
</div>
<div class="card tbl">
<h2>Suivi des espaces</h2>
<p class="muted">Une page appelée peut correspondre à un robot de messagerie. Seule une connexion réussie confirme l'utilisation d'un code.</p>
<table><thead><tr><th>Partenaire</th><th>État</th><th>Page appelée</th><th>Dernière connexion</th><th></th></tr></thead><tbody>
@forelse($shares as $share)
<tr>
<td>{{ $share->name }}<br>{{ $share->email }}</td>
<td>{{ ['draft'=>'Brouillon','active'=>'Actif','revoked'=>'Invalidé / révoqué'][$share->status] ?? $share->status }}<br>{{ $share->revoked_reason }}</td>
<td>{{ $share->first_opened_at?->format('d/m/Y H:i') ?? 'Non' }}</td>
<td>{{ $share->last_login_at?->format('d/m/Y H:i') ?? 'Aucune' }}</td>
<td><a class="btn sm" href="{{ route('admin.partner-portal.show', $share) }}">Gérer / historique</a></td>
</tr>
@empty<tr><td colspan="5">Aucun espace.</td></tr>@endforelse
</tbody></table>
{{ $shares->links() }}
</div>
@endsection
