@extends('layouts.admin')
@section('title', 'Tableau de bord')

@section('content')
<div class="top">
  <div><h1>Tableau de bord</h1><p class="muted" style="margin:0">Édition {{ config('jnuaf.edition') }} · événement le {{ \Illuminate\Support\Carbon::parse(config('jnuaf.event_date'))->translatedFormat('d F Y') }}</p></div>
  <a class="btn o" href="{{ route('admin.checkin.index') }}">Ouvrir le pointage</a>
</div>

<div class="grid g4" style="margin-bottom:16px">
  <div class="card stat"><b>{{ $total }}</b><span>inscriptions confirmées</span></div>
  <div class="card stat o"><b>{{ $present }}</b><span>présentes (pointées)</span></div>
  <div class="card stat"><b>{{ $imageConsent }}</b><span>accords droit à l'image</span></div>
  @if ($newApplications !== null)
    <a class="card stat o" style="text-decoration:none;color:inherit" href="{{ route('admin.committee.index', ['status' => 'new']) }}"><b>{{ $newApplications }}</b><span>candidatures comité à traiter</span></a>
  @else
    <div class="card stat"><b>{{ $cancelled }}</b><span>annulations</span></div>
  @endif
</div>

@if ($newPartners)
  <a class="flash" style="display:block;text-decoration:none" href="{{ route('admin.partners.index', ['status' => 'new']) }}">{{ $newPartners }} nouvelle(s) demande(s) de partenariat à traiter →</a>
@endif

<div class="grid g2" style="margin-bottom:16px">
  <div class="card">
    <h2>Par territoire</h2>
    <div class="tbl"><table>
      <thead><tr><th>Territoire</th><th>Inscrites</th><th>Présentes</th><th class="hide-sm" style="width:35%">Taux de présence</th></tr></thead>
      <tbody>
      @foreach ($territories as $t)
        @php($row = $byTerritory[$t->id] ?? null)
        @php($n = (int) ($row->total ?? 0))
        @php($p = (int) ($row->present ?? 0))
        <tr>
          <td><a href="{{ route('admin.registrations.index', ['territory' => $t->id]) }}">{{ $t->city }}</a> <span class="muted">{{ $t->code }}</span></td>
          <td>{{ $n }}</td><td>{{ $p }}</td>
          <td class="hide-sm"><div class="bar" title="{{ $n ? round($p / $n * 100) : 0 }} %"><i style="width:{{ $n ? round($p / $n * 100) : 0 }}%"></i></div></td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  </div>
  <div class="card">
    <h2>Par profil</h2>
    <div class="tbl"><table>
      <tbody>
      @foreach ($byProfile as $label => $count)
        <tr><td>{{ $label }}</td><td style="width:60px;text-align:right"><b>{{ $count }}</b></td>
          <td class="hide-sm" style="width:45%"><div class="bar"><i style="width:{{ $total ? round($count / $total * 100) : 0 }}%;background:var(--orange)"></i></div></td></tr>
      @endforeach
      </tbody>
    </table></div>
  </div>
</div>

<div class="card">
  <div class="top" style="margin-bottom:8px"><h2 style="margin:0">Dernières inscriptions</h2><a href="{{ route('admin.registrations.index') }}">Tout voir →</a></div>
  <div class="tbl"><table class="stack">
    <thead><tr><th>Nom</th><th>Profil</th><th>Territoire</th><th>Date</th></tr></thead>
    <tbody>
    @forelse ($latest as $r)
      <tr><td class="lead"><a href="{{ route('admin.registrations.show', $r) }}"><b>{{ $r->fullName() }}</b></a></td><td data-label="Profil"><span class="pill">{{ $r->profile->label() }}</span></td><td data-label="Territoire">{{ $r->territory->city }}</td><td data-label="Date">{{ $r->created_at->format('d/m H:i') }}</td></tr>
    @empty
      <tr><td colspan="4" class="muted">Aucune inscription pour le moment.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
@endsection
