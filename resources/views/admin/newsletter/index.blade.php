@extends('layouts.admin')
@section('title', 'Newsletter')
@section('content')
<div class="top"><div><h1>Newsletter</h1><p class="muted">{{ $subscribed }} abonné(s) actif(s). Chaque destinataire reçoit son propre email.</p></div>
<a class="btn" href="{{ route('admin.newsletter.subscribers') }}">Gérer les adresses</a></div>
<div class="card" style="margin-bottom:20px">
    <h2>Créer une campagne</h2>
    <form class="form" method="POST" action="{{ route('admin.newsletter.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.newsletter.fields', ['campaign' => null])
        <button class="btn" type="submit">Enregistrer le brouillon</button>
    </form>
</div>
<div class="card tbl">
    <h2>Campagnes</h2>
    <table><thead><tr><th>Campagne</th><th>État</th><th>Destinataires</th><th></th></tr></thead><tbody>
    @forelse($campaigns as $campaign)
    <tr><td>{{ $campaign->title }}<br><span class="muted">{{ $campaign->subject }}</span></td>
    <td>{{ ['draft'=>'Brouillon','scheduled'=>'Programmée','queued'=>'En file','sending'=>'En cours','completed'=>'Traitement terminé','cancelled'=>'Arrêtée'][$campaign->status] ?? $campaign->status }}</td>
    <td>{{ $campaign->deliveries_count }}</td><td><a class="btn sm" href="{{ route('admin.newsletter.show', $campaign) }}">Ouvrir</a></td></tr>
    @empty<tr><td colspan="4">Aucune campagne.</td></tr>@endforelse
    </tbody></table>
    {{ $campaigns->links() }}
</div>
@endsection
