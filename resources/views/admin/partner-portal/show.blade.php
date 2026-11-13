@extends('layouts.admin')
@section('title', 'Accès partenaire')
@section('content')
<p><a class="btn" href="{{ route('admin.partner-portal.index') }}">Documents partenaires</a></p>
<h1>{{ $share->name }}</h1>
@if(session('status'))<p class="card">{{ session('status') }}</p>@endif
@if($errors->any())<div class="card">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="card" style="margin:16px 0">
<p>Email autorisé : {{ $share->email }}</p>
<p>État : {{ ['draft'=>'Brouillon','active'=>'Actif','revoked'=>'Invalidé / révoqué'][$share->status] ?? $share->status }}</p>
@if($share->revoked_reason)<p>Motif : {{ $share->revoked_reason }}</p>@endif
<p>Génération du lien : {{ $share->generation }} · Codes envoyés : {{ $share->code_count }}/3 · Erreurs : {{ $share->failed_attempts }}/3</p>
<p>Invitation : {{ $share->invited_at?->format('d/m/Y H:i') ?? 'Non envoyée' }}<br>
Page appelée : {{ $share->first_opened_at?->format('d/m/Y H:i') ?? 'Non' }}<br>
Dernière connexion : {{ $share->last_login_at?->format('d/m/Y H:i') ?? 'Aucune' }}</p>
<form method="POST" action="{{ route('admin.partner-portal.invite', $share) }}" data-confirm="Générer et envoyer un nouveau lien ? L’ancien lien et ses sessions seront invalidés.">
@csrf<button class="btn" type="submit">Générer et envoyer le lien par email</button>
</form>
@if($share->status === 'active')
<form method="POST" action="{{ route('admin.partner-portal.revoke', $share) }}" data-confirm="Révoquer cet accès ?" style="margin-top:12px">
@csrf<button class="btn danger" type="submit">Révoquer le lien</button>
</form>
@endif
<p class="muted">Le jeton brut n'est pas conservé en base. Pour retransmettre un accès, générez un nouveau lien.</p>
</div>
<div class="card" style="margin:16px 0">
<h2>Modifier les documents autorisés</h2>
<form class="form" method="POST" action="{{ route('admin.partner-portal.update', $share) }}">
@csrf @method('PUT')
<label>Fin du partage <input type="datetime-local" name="expires_at" value="{{ $share->expires_at->format('Y-m-d\TH:i') }}" required></label>
@foreach($documents as $document)
<label style="display:block"><input type="checkbox" name="documents[]" value="{{ $document->id }}" @checked($share->documents->contains('id', $document->id))> {{ $document->title }} {{ $document->active ? '' : '(désactivé)' }}</label>
@endforeach
<button class="btn" type="submit">Enregistrer les autorisations</button>
</form>
<p class="muted">Un document retiré est refusé aux demandes suivantes. Une copie déjà téléchargée ne peut pas être récupérée.</p>
</div>
<div class="card tbl">
<h2>Historique</h2>
<table><thead><tr><th>Date</th><th>Lien nº</th><th>Événement</th><th>Document / détail</th></tr></thead><tbody>
@forelse($events as $event)
<tr><td>{{ $event->created_at }}</td><td>{{ $event->generation }}</td><td>{{ $event->kind }}</td><td>{{ $event->document_title }}<br>{{ $event->detail }}</td></tr>
@empty<tr><td colspan="4">Aucun événement.</td></tr>@endforelse
</tbody></table>
{{ $events->links() }}
</div>
@endsection
