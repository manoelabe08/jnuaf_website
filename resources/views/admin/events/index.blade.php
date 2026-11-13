@extends('layouts.admin')

@section('title', 'Evenements')

@section('content')

<div class="top">

  <div><h1>&Eacute;v&eacute;nements</h1><p class="muted" style="margin:0">Formations, rencontres et grands rendez-vous. Chaque &eacute;v&eacute;nement a ses dates, ses inscriptions et ses attestations.</p></div>

  <a class="btn o" href="{{ route('admin.events.create') }}">Nouvel &eacute;v&eacute;nement</a>

</div>

<div class="card">

  <div class="tbl">

    <table class="stack">

      <thead><tr><th>Titre</th><th>Type</th><th>Date</th><th>Territoire</th><th>Inscrits</th><th>Statut</th><th></th></tr></thead>

      <tbody>

      @forelse($events as $e)

        <tr>

          <td class="lead">{{ $e->title }}</td>

          <td data-label="Type"><span class="pill">{{ $e->typeLabel() }}</span></td>

          <td data-label="Date">{{ $e->starts_at->format('d/m/Y H:i') }}</td>

          <td data-label="Territoire">@if($e->territory){{ $e->territory->city }}@else&mdash;@endif</td>

          <td data-label="Inscrits"><a href="{{ route('admin.events.registrations', $e) }}">{{ $e->registrations_count }}@if($e->capacity) / {{ $e->capacity }}@endif</a></td>

          <td data-label="Statut">@if($e->registration_open)Ouvert @else Ferm&eacute; @endif @if($e->is_published)&middot; Publi&eacute;@endif</td>

          <td><a class="btn sm" href="{{ route('admin.events.edit', $e) }}">Modifier</a></td>

        </tr>

      @empty

        <tr><td colspan="7" class="muted">Aucun &eacute;v&eacute;nement.</td></tr>

      @endforelse

      </tbody>

    </table>

  </div>

</div>

@endsection

