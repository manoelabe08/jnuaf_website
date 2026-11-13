@extends('partner-portal.layout')
@section('content')
<h1>Mes documents de partenariat</h1>
<p>Bienvenue {{ $share->name }}.</p>
@forelse($documents as $document)
<div class="card">
    <h2>{{ $document->title }}</h2>
    @if($document->description)<p style="white-space:pre-line">{{ $document->description }}</p>@endif
    <p class="muted">PDF · {{ number_format($document->bytes / 1024 / 1024, 2, ',', ' ') }} Mo</p>
    <div class="actions">
        <a class="btn" target="_blank" rel="noopener noreferrer" href="{{ route('partner-portal.file', [$token, $document->id, 'lire']) }}">Lire le PDF</a>
        <a class="btn" href="{{ route('partner-portal.file', [$token, $document->id, 'telecharger']) }}">Télécharger</a>
    </div>
</div>
@empty
<div class="card">Aucun document n'est actuellement disponible dans cet espace.</div>
@endforelse
<div class="card">
    <p>Pour les documents à compléter, téléchargez-les puis retournez-les au comité par email, selon les instructions du document.</p>
    <form method="POST" action="{{ route('partner-portal.logout', $token) }}">@csrf<button type="submit">Me déconnecter</button></form>
</div>
@endsection
