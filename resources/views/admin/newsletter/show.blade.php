@extends('layouts.admin')
@section('title', 'Campagne newsletter')
@section('content')
<div class="top"><h1>{{ $campaign->title }}</h1><a class="btn ghost" href="{{ route('admin.newsletter.index') }}">Toutes les campagnes</a></div>
<div class="card" style="margin-bottom:20px">
    <h2>État et suivi</h2>
    <p>{{ ['draft'=>'Brouillon','scheduled'=>'Programmée','queued'=>'En file','sending'=>'En cours','completed'=>'Traitement terminé','cancelled'=>'Arrêtée'][$campaign->status] ?? $campaign->status }}</p>
    <p>{{ $counts->get('sent',0) }} accepté(s) par le transport · {{ $counts->get('pending',0) }} en attente · {{ $counts->get('processing',0) }} en traitement · {{ $counts->get('failed',0) }} en échec · {{ $counts->get('uncertain',0) }} incertain(s) · {{ $counts->get('skipped',0) }} ignoré(s).</p>
    <p class="muted">L'acceptation SMTP ne prouve ni la réception dans la boîte ni la lecture. Aucun pixel de suivi n'est ajouté.</p>
    <a class="btn ghost" href="{{ route('admin.newsletter.show', $campaign) }}">Actualiser le suivi</a>
</div>
@if($campaign->status === 'draft')
<div class="card" style="margin-bottom:20px">
    <h2>Modifier le brouillon</h2>
    <form class="form" method="POST" action="{{ route('admin.newsletter.update', $campaign) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.newsletter.fields')
        <button class="btn" type="submit">Enregistrer les modifications</button>
    </form>
</div>
@endif
<div class="card" style="margin-bottom:20px">
    <h2>Pièces jointes</h2>
    @forelse($campaign->attachments as $attachment)
        <div style="margin-bottom:12px">
            <a href="{{ route('admin.newsletter.file', [$campaign, $attachment]) }}">{{ $attachment->download_name }}</a>
            <span class="muted">({{ number_format($attachment->bytes / 1048576, 2, ',', ' ') }} Mo)</span>
            @if($campaign->status === 'draft')
            <form class="inline" method="POST" action="{{ route('admin.newsletter.file.delete', [$campaign, $attachment]) }}" data-confirm="Retirer cette pièce jointe du brouillon ?">
                @csrf @method('DELETE')<button class="btn danger sm" type="submit">Retirer</button>
            </form>
            @endif
        </div>
    @empty<p class="muted">Aucune pièce jointe.</p>@endforelse
</div>
<div class="card" style="margin-bottom:20px">
    <h2>Aperçu du message enregistré</h2>
    <p class="muted">Enregistrez vos modifications avant de tester. Les liens personnels ne sont pas ajoutés aux tests.</p>
    <iframe title="Aperçu de la newsletter" src="{{ route('admin.newsletter.preview', $campaign) }}" style="width:100%;height:600px;border:1px solid var(--line);border-radius:10px;background:white"></iframe>
    <form method="POST" action="{{ route('admin.newsletter.test', $campaign) }}" style="margin-top:14px">
        @csrf<button class="btn ghost" type="submit">Envoyer un test à {{ auth()->user()->email }}</button>
    </form>
</div>
@if($campaign->status === 'draft')
<div class="card" style="margin-bottom:20px">
    <h2>Lancer la campagne</h2>
    <p>{{ $eligible }} abonné(s) actif(s) correspondent actuellement aux sources choisies.</p>
    <p class="muted">L'envoi sera réalisé individuellement, par petits lots. Le contenu et les pièces seront figés après validation. Vérifiez d'abord votre email de test.</p>
    <form class="form" method="POST" action="{{ route('admin.newsletter.queue', $campaign) }}" data-confirm="Confirmer la mise en file de cette campagne ? Les abonnés seront sélectionnés à la validation et le contenu ne sera plus modifiable.">
        @csrf
        <label class="check"><input type="checkbox" name="confirm" value="1" required> J'ai vérifié le message et je confirme son envoi aux abonnés autorisés.</label>
        <button class="btn o" type="submit">Envoyer la newsletter</button>
    </form>
</div>
@endif

@if($campaign->status === 'draft')

<form class="form" method="POST" action="{{ route('admin.newsletter.schedule', $campaign) }}" data-confirm="Programmer cet envoi ? Le contenu ne sera plus modifiable tant que la programmation est active.">

    @csrf

    <h3>Programmer l’envoi</h3>

    <label>Date et heure d’envoi ({{ config('jnuaf_newsletter.timezone', 'Europe/Paris') }})

        <input type="datetime-local" name="scheduled_at" required value="{{ old('scheduled_at') }}" min="{{ now()->timezone(config('jnuaf_newsletter.timezone', 'Europe/Paris'))->addMinutes(3)->format('Y-m-d\TH:i') }}">

    </label>

    <p class="muted">Les abonnés sont sélectionnés au moment de l’envoi. Vous pourrez annuler la programmation tant que l’envoi n’a pas démarré.</p>

    <button class="btn" type="submit">Programmer</button>

</form>

@endif



@if($campaign->status === 'scheduled')

<p><strong>Envoi programmé le {{ $campaign->scheduled_at?->timezone(config('jnuaf_newsletter.timezone', 'Europe/Paris'))->format('d/m/Y à H:i') }}</strong> ({{ config('jnuaf_newsletter.timezone', 'Europe/Paris') }}).</p>

<form method="POST" action="{{ route('admin.newsletter.unschedule', $campaign) }}" data-confirm="Annuler la programmation ? La campagne redeviendra un brouillon.">

    @csrf

    <button class="btn" type="submit">Annuler la programmation</button>

</form>

@endif



@if(in_array($campaign->status, ['queued','sending'], true))

<div class="card" style="margin-bottom:20px">
    <form method="POST" action="{{ route('admin.newsletter.cancel', $campaign) }}" data-confirm="Arrêter les envois qui ne sont pas encore commencés ?">
        @csrf<button class="btn danger" type="submit">Arrêter les envois restants</button>
    </form>
</div>
@endif

@if(in_array($campaign->status, ['queued','sending'], true))

<div class="card" style="margin-bottom:20px">

    <h2>Envoi acc&eacute;l&eacute;r&eacute;</h2>

    <p>{{ $counts->get('pending',0) }} en attente. Cette action ignore les plafonds internes (minute, heure, jour). Les limites de LWS restent actives&nbsp;: si le fournisseur refuse, les envois restants repartent en attente et une pause de 60 minutes s'applique.</p>

    @if((int) \Illuminate\Support\Facades\Cache::get('jnuaf-newsletter-force', 0) > 0)

    <p><strong>For&ccedil;age en cours&nbsp;: {{ (int) \Illuminate\Support\Facades\Cache::get('jnuaf-newsletter-force', 0) }} message(s) restant(s).</strong></p>

    @endif

    @if(\Illuminate\Support\Facades\Cache::has('jnuaf-newsletter-cooldown'))

    <p><strong>Pause fournisseur active</strong> (LWS a refus&eacute; des envois).</p>

    @endif

    <form class="form" method="POST" action="{{ route('admin.newsletter.force', $campaign) }}" data-confirm="Forcer l'envoi maintenant, hors plafonds internes ?">

        @csrf

        <label>Nombre de messages &agrave; forcer <input type="number" name="count" value="500" min="1" max="500" required></label>

        <label class="check"><input type="checkbox" name="confirm_force" value="1" required> Je comprends que LWS peut bloquer l'envoi pendant une heure si son plafond est d&eacute;pass&eacute;.</label>

        <button class="btn o" type="submit">Forcer l'envoi</button>

    </form>

</div>

@endif


@if($counts->get('failed',0) + $counts->get('uncertain',0) > 0)
<div class="card" style="margin-bottom:20px">
    <h2>Relance manuelle</h2>
    <p>Un incident peut survenir après acceptation d'un message par le serveur mais avant sa confirmation dans la base. Une relance peut donc produire un doublon. Vérifiez les journaux avant de continuer.</p>
    <form class="form" method="POST" action="{{ route('admin.newsletter.retry', $campaign) }}">
        @csrf
        <label class="check"><input type="checkbox" name="confirm_retry" value="1" required> J'ai vérifié les incidents et j'accepte le risque de doublon.</label>
        <button class="btn ghost" type="submit">Relancer les envois échoués / incertains</button>
    </form>
</div>
@endif
<div class="card tbl">
    <h2>Détail des destinataires</h2>


<form method="GET" action="{{ route('admin.newsletter.show', $campaign) }}" style="display:flex;gap:8px;flex-wrap:wrap;margin:10px 0">

    <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher une adresse" aria-label="Rechercher une adresse">

    <select name="etat" aria-label="Filtrer par &eacute;tat">

        <option value="">Tous les &eacute;tats</option>

        @foreach(['pending'=>'En attente (prochains envois)','sent'=>'Accept&eacute;s par le transport','processing'=>'En traitement','failed'=>'&Eacute;checs','uncertain'=>'&Agrave; v&eacute;rifier','skipped'=>'Ignor&eacute;s'] as $value => $label)

        <option value="{{ $value }}" @selected(request('etat') === $value)>{!! $label !!}</option>

        @endforeach

    </select>

    <button class="btn" type="submit">Filtrer</button>

    <a class="btn ghost" href="{{ route('admin.newsletter.show', $campaign) }}">R&eacute;initialiser</a>

</form>

<p class="muted">Ordre d'affichage&nbsp;: messages d&eacute;j&agrave; trait&eacute;s dans l'ordre d'envoi, puis file d'attente dans l'ordre des prochains envois (prioritaires en premier).</p>

    <table><thead><tr><th>Email</th><th>État</th><th>Tentatives</th><th>Information</th><th>Action</th></tr></thead><tbody>
    @forelse($deliveries as $delivery)
        <tr>
<td>{{ $delivery->email }}@if(isset($eta[$delivery->id]))<br><span class="muted">@if($eta[$delivery->id]['seconds'] !== null)Temps approximatif restant&nbsp;: {{ \App\Services\NewsletterEta::format($eta[$delivery->id]['seconds']) }} &middot; position {{ $eta[$delivery->id]['position'] }}@else Hors de port&eacute;e de l'estimation @endif</span>@if($delivery->prioritized_at)<br><strong>Prioritaire</strong>@endif @endif</td>
<td>{{ ['pending'=>'En attente','processing'=>'En traitement','sent'=>'Accepté par le transport','failed'=>'Échec','uncertain'=>'À vérifier','skipped'=>'Ignoré'][$delivery->status] ?? $delivery->status }}</td><td>{{ $delivery->attempts }}</td><td>{{ $delivery->error }}<br>
{{ $delivery->sent_at?->format('d/m/Y H:i') }}</td><td>@if(isset($eta[$delivery->id]) && ! $delivery->prioritized_at)<form method="POST" action="{{ route('admin.newsletter.prioritize', [$campaign, $delivery]) }}" data-confirm="Passer ce destinataire en t&ecirc;te de file ?">@csrf<button class="btn ghost sm" type="submit">Passer en t&ecirc;te</button></form>@endif</td></tr>

    @empty<tr><td colspan="5">Destinataires figés lors de la mise en envoi.</td></tr>@endforelse
    </tbody></table>
    {{ $deliveries->links() }}
</div>
@endsection
