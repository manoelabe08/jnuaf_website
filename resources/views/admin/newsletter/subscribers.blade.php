@extends('layouts.admin')
@section('title', 'Abonnés newsletter')
@section('content')
<div class="top"><h1>Adresses newsletter</h1><a class="btn ghost" href="{{ route('admin.newsletter.index') }}">Campagnes</a></div>
<div class="card" style="margin-bottom:20px">
    <h2>Ajouter une adresse</h2>
    <form class="form" method="POST" action="{{ route('admin.newsletter.subscribers.store') }}">
        @csrf
        <label>Nom (facultatif)<input name="name" maxlength="255" value="{{ old('name') }}"></label>
        <label>Email<input name="email" type="email" maxlength="254" required value="{{ old('email') }}"></label>
        <label class="check"><input type="checkbox" name="subscribe" value="1"> Activer son abonnement (sinon, adresse en attente).</label>
        <label>Preuve / contexte de l'autorisation<textarea name="consent_note" maxlength="2000" placeholder="Ex. Demande écrite de la personne le ...">{{ old('consent_note') }}</textarea></label>
        <label class="check"><input type="checkbox" name="confirm_consent" value="1"> Je confirme disposer de l'autorisation nécessaire, y compris pour une réactivation.</label>
        <button class="btn" type="submit">Enregistrer l'adresse</button>
    </form>
</div>
<div class="card" style="margin-bottom:20px">
    <h2>Recenser les adresses du site</h2>
    <p>Import sans email envoyé et sans abonnement automatique. Les nouvelles adresses seront « En attente ». Une désinscription existante n'est jamais réactivée par cet import.</p>
    <form class="form" method="POST" action="{{ route('admin.newsletter.import') }}">
        @csrf
        <label class="check"><input type="checkbox" name="sources[]" value="users" checked> Comptes users</label>
        <label class="check"><input type="checkbox" name="sources[]" value="registrations" checked> Inscriptions non anonymisées</label>
        <button class="btn ghost" type="submit">Recenser / mettre à jour les sources</button>
    </form>
</div>
<div class="card">
    <h2>Liste des adresses</h2>
    
<form class="filters" method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">

        <label>Rechercher<input name="q" maxlength="200" value="{{ request('q') }}"></label>

        <label>Statut<select name="status">

            <option value="">Tous</option>

            @foreach(['subscribed'=>'Abonné','pending'=>'En attente','unsubscribed'=>'Désinscrit','bounced'=>'Rebondie'] as $value=>$label)

            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }} ({{ $counts[$value] ?? 0 }})</option>

            @endforeach

        </select></label>

        <label>Source<select name="source">

            <option value="">Toutes</option>

            @foreach($sourceList as $src)

            <option value="{{ $src }}" @selected(request('source') === $src)>{{ $src }}</option>

            @endforeach

        </select></label>

        <label>Inscrit du<input type="date" name="from" value="{{ request('from') }}"></label>

        <label>au<input type="date" name="to" value="{{ request('to') }}"></label>

        <label>Nom<select name="name">

            <option value="">Indifférent</option>

            <option value="with" @selected(request('name') === 'with')>Avec nom</option>

            <option value="without" @selected(request('name') === 'without')>Sans nom</option>

        </select></label>

        <label>Trier par<select name="sort">

            @foreach(['newest'=>'Plus récentes','oldest'=>'Plus anciennes','email'=>'Email (A-Z)','name'=>'Nom (A-Z)'] as $value=>$label)

            <option value="{{ $value }}" @selected(request('sort', 'newest') === $value)>{{ $label }}</option>

            @endforeach

        </select></label>

        <button class="btn" type="submit">Filtrer</button>

        @if(collect(['q','status','source','from','to','name','sort'])->contains(fn ($k) => request()->filled($k)))

        <a class="btn" href="{{ route('admin.newsletter.subscribers') }}">Réinitialiser</a>

        @endif
        <a class="btn ghost" href="{{ route('admin.newsletter.subscribers.export', request()->query()) }}">Extraire (CSV)</a>

    </form>

    <p class="muted">{{ $subscribers->total() }} adresse(s) trouvée(s).</p>

    @forelse($subscribers as $subscriber)
        <div style="border-top:1px solid var(--line);padding:16px 0">
            <p><b>{{ $subscriber->email }}</b> — {{ $subscriber->name }}</p>
            <p class="muted">État : {{ ['pending'=>'En attente','subscribed'=>'Abonné','unsubscribed'=>'Désinscrit','bounced'=>'Rebondie'][$subscriber->status] ?? $subscriber->status }} · Sources : {{ implode(', ', $subscriber->sources ?? []) }}</p>
            @if($subscriber->bounce_reason)<p class="muted">Rebond : {{ $subscriber->bounce_reason }}@if($subscriber->bounced_at) ({{ $subscriber->bounced_at->format('d/m/Y H:i') }})@endif</p>@endif
            <details class="edit"><summary>Modifier le statut</summary>
                <form class="form" method="POST" action="{{ route('admin.newsletter.subscribers.update', $subscriber) }}">
                    @csrf @method('PUT')
                    <label>Statut<select name="status">
                        @foreach(['pending'=>'En attente','subscribed'=>'Abonné','unsubscribed'=>'Désinscrit','bounced'=>'Rebondie'] as $value=>$label)
                        <option value="{{ $value }}" @selected($subscriber->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select></label>
                    <label>Preuve / contexte de l'autorisation<textarea name="consent_note" maxlength="2000">{{ $subscriber->consent_note }}</textarea></label>
                    <label class="check"><input type="checkbox" name="confirm_consent" value="1"> Je confirme disposer d'une autorisation pour activer ou réactiver cet abonnement.</label>
                    <button class="btn sm" type="submit">Enregistrer</button>
                </form>
            </details>
        </div>
    @empty<p>Aucune adresse.</p>@endforelse
    {{ $subscribers->links() }}
</div>
@endsection
