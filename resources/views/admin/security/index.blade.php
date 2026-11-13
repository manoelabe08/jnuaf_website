
@extends('layouts.admin')

@section('title', 'Journal de sécurité')



@section('content')

<div class="top"><div><h1>Journal de sécurité</h1><p class="muted" style="margin:0">Actions sensibles sur les comptes : qui, quand, depuis quelle adresse.</p></div></div>



<form method="GET" style="display:flex;gap:8px;margin-bottom:16px">

  <input type="text" name="action" value="{{ request('action') }}" placeholder="Filtrer par action (ex. Compte créé)">

  <button type="submit">Filtrer</button>

</form>



<div class="card tbl">

  <table class="stack">

    <thead><tr><th>Date</th><th>Acteur</th><th>Action</th><th>Détails</th><th>Adresse IP</th></tr></thead>

    <tbody>

    @forelse ($events as $event)

      <tr>

        <td data-label="Date">{{ $event->created_at->format('d/m/Y H:i') }}</td>

        <td data-label="Acteur">{{ $event->user?->email ?? '—' }}</td>

        <td data-label="Action">{{ $event->action }}</td>

        <td data-label="Détails"><small class="muted">{{ json_encode($event->details, JSON_UNESCAPED_UNICODE) }}</small></td>

        <td data-label="Adresse IP">{{ $event->ip }}</td>

      </tr>

    @empty

      <tr><td colspan="5" class="muted">Aucun événement enregistré pour le moment.</td></tr>

    @endforelse

    </tbody>

  </table>

</div>



<div style="display:flex;gap:16px;margin-top:12px">

  @if ($events->previousPageUrl())<a href="{{ $events->previousPageUrl() }}">← Plus récents</a>@endif

  @if ($events->nextPageUrl())<a href="{{ $events->nextPageUrl() }}">Plus anciens →</a>@endif

</div>

@endsection

