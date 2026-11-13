@extends('layouts.admin')

@section('title', 'Inscrits')



@section('content')

<div class="top">

  <div><h1>{{ $event->title }}</h1><p class="muted" style="margin:0">{{ $event->typeLabel() }} &middot; {{ $event->starts_at->format('d/m/Y H:i') }}@if ($event->territory) &middot; {{ $event->territory->city }}@endif &middot; <a href="{{ route('admin.events.index') }}">&larr; &Eacute;v&eacute;nements</a></p></div>

  <a class="btn ghost" href="{{ route('admin.events.registrations.export', array_merge(['event' => $event->id], request()->query())) }}">Exporter en CSV</a>

</div>



<div class="card" style="padding:14px 18px">

  <b>{{ $stats['confirmed'] }}</b>@if ($event->capacity) / {{ $event->capacity }}@endif confirm&eacute;e(s) &middot; <b>{{ $stats['waitlisted'] }}</b> en liste d'attente &middot; <b>{{ $stats['cancelled'] }}</b> annul&eacute;e(s) &middot; <b>{{ $stats['present'] }}</b> point&eacute;e(s)

  &nbsp; <a class="btn sm ghost" href="{{ route('admin.checkin.index') }}">Ouvrir le pointage</a>

</div>



<form class="card filters" method="GET">

  <label style="flex:2 1 220px">Recherche<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, email ou code badge"></label>

  <label style="flex:1 1 130px">Statut<select name="status"><option value="">Tous</option><option value="confirmed" @selected(($filters['status'] ?? '') === 'confirmed')>Confirm&eacute;e</option><option value="waitlisted" @selected(($filters['status'] ?? '') === 'waitlisted')>Liste d'attente</option><option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Annul&eacute;e</option></select></label>

  <label style="flex:1 1 130px">Pr&eacute;sence<select name="presence"><option value="">Toutes</option><option value="present" @selected(($filters['presence'] ?? '') === 'present')>Pr&eacute;sentes</option><option value="absent" @selected(($filters['presence'] ?? '') === 'absent')>Non point&eacute;es</option></select></label>

  <button class="btn" type="submit">Filtrer</button>

  <a class="btn ghost" href="{{ route('admin.events.registrations', $event) }}">R&eacute;initialiser</a>

</form>



<div class="card tbl">

  <table class="stack">

    <thead><tr><th>Badge</th><th>Nom</th><th>Profil</th><th>Territoire</th><th>Contact</th><th>Statut</th><th>Inscrit(e) le</th></tr></thead>

    <tbody>

    @forelse ($registrations as $r)

      <tr>

        <td data-label="Badge"><code>{{ $r->badge_code }}</code></td>

        <td class="lead"><a href="{{ route('admin.registrations.show', $r) }}"><b>{{ $r->fullName() }}</b></a>@if ($r->accessibility_needs) <span class="pill o" title="{{ $r->accessibility_needs }}">accessibilit&eacute;</span>@endif</td>

        <td data-label="Profil"><span class="pill">{{ $r->profile->label() }}</span></td>

        <td data-label="Territoire">{{ $r->territory->city }}</td>

        <td data-label="Contact"><span>{{ $r->email }}<br><span class="muted">{{ $r->phone }}</span></span></td>

        <td data-label="Statut">

          @if ($r->isCancelled())<span class="pill r">Annul&eacute;e</span>

          @elseif ($r->isWaitlisted())<span class="pill o">Liste d'attente</span>

          @elseif ($r->checked_in_at)<span class="pill">Pr&eacute;sente {{ $r->checked_in_at->format('H:i') }}</span>

          @else<span class="pill g">Confirm&eacute;e</span>@endif

        </td>

        <td data-label="Inscrit(e) le">{{ $r->created_at->format('d/m/Y') }}</td>

      </tr>

    @empty

      <tr><td colspan="7" class="muted">Aucune inscription ne correspond &agrave; ces filtres.</td></tr>

    @endforelse

    </tbody>

  </table>

  {{ $registrations->links('admin.partials.pager') }}

</div>



@if ($event->starts_at->isPast())

  <form class="card" method="POST" action="{{ route('admin.events.attestations', $event) }}" style="padding:14px 18px">

    @csrf

    <h3 style="margin-top:0">Attestations</h3>

    <p class="muted">{{ $pending }} personne(s) point&eacute;e(s) n'ont pas encore re&ccedil;u leur attestation.</p>

    <label><input type="checkbox" name="confirm" value="1" required> Je confirme l'envoi des attestations par email.</label>

    <button class="btn o" type="submit" @disabled($pending === 0)>Envoyer les attestations</button>

  </form>

@else

  <p class="muted">Les attestations pourront &ecirc;tre envoy&eacute;es apr&egrave;s le d&eacute;but de l'&eacute;v&eacute;nement.</p>

@endif

@endsection

