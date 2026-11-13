@extends('layouts.admin')
@section('title', 'Inscriptions')

@section('content')
<div class="top">
  <div><h1>Inscriptions</h1><p class="muted" style="margin:0">{{ $registrations->total() }} résultat(s)@unless (auth()->user()->isAdmin()) · territoire de {{ auth()->user()->territory?->city }}@endunless</p></div>
  <a class="btn ghost" href="{{ route('admin.registrations.export', request()->query()) }}">Exporter en CSV</a>
</div>

<form class="card filters" method="GET">
  <label style="flex:2 1 220px">Recherche<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, email ou code badge"></label>
  @if ($territories->isNotEmpty())
    <label style="flex:1 1 140px">Territoire<select name="territory"><option value="">Tous</option>@foreach ($territories as $t)<option value="{{ $t->id }}" @selected(($filters['territory'] ?? '') == $t->id)>{{ $t->city }}</option>@endforeach</select></label>
  @endif
  <label style="flex:1 1 140px">Profil<select name="profile"><option value="">Tous</option>@foreach ($profiles as $v => $l)<option value="{{ $v }}" @selected(($filters['profile'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></label>
  <label style="flex:1 1 130px">Statut<select name="status"><option value="">Tous</option><option value="confirmed" @selected(($filters['status'] ?? '') === 'confirmed')>Confirmée</option><option value="waitlisted" @selected(($filters['status'] ?? '') === 'waitlisted')>Liste d'attente</option><option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Annulée</option></select></label>
  <label style="flex:1 1 130px">Présence<select name="presence"><option value="">Toutes</option><option value="present" @selected(($filters['presence'] ?? '') === 'present')>Présentes</option><option value="absent" @selected(($filters['presence'] ?? '') === 'absent')>Non pointées</option></select></label>
  <button class="btn" type="submit">Filtrer</button>
  <a class="btn ghost" href="{{ route('admin.registrations.index') }}">Réinitialiser</a>
</form>

<div class="card tbl">
  <table class="stack">
    <thead><tr><th>Badge</th><th>Nom</th><th>Profil</th><th>Territoire</th><th>Contact</th><th>Statut</th><th>Inscrite le</th></tr></thead>
    <tbody>
    @forelse ($registrations as $r)
      <tr>
        <td data-label="Badge"><code>{{ $r->badge_code }}</code></td>
        <td class="lead"><a href="{{ route('admin.registrations.show', $r) }}"><b>{{ $r->fullName() }}</b></a>@if ($r->accessibility_needs) <span class="pill o" title="{{ $r->accessibility_needs }}">accessibilité</span>@endif</td>
        <td data-label="Profil"><span class="pill">{{ $r->profile->label() }}</span></td>
        <td data-label="Territoire">{{ $r->territory->city }}</td>
        <td data-label="Contact"><span>{{ $r->email }}<br><span class="muted">{{ $r->phone }}</span></span></td>
        <td data-label="Statut">
          @if ($r->isCancelled())<span class="pill r">Annulée</span>
          @elseif ($r->isWaitlisted())<span class="pill o">Liste d'attente</span>
          @elseif ($r->checked_in_at)<span class="pill">Présente {{ $r->checked_in_at->format('H:i') }}</span>
          @else<span class="pill g">Confirmée</span>@endif
        </td>
        <td data-label="Inscrite le">{{ $r->created_at->format('d/m/Y') }}</td>
      </tr>
    @empty
      <tr><td colspan="7" class="muted">Aucune inscription ne correspond à ces filtres.</td></tr>
    @endforelse
    </tbody>
  </table>
  {{ $registrations->links('admin.partials.pager') }}
</div>
@endsection
