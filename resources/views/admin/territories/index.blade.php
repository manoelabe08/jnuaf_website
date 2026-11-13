@extends('layouts.admin')
@section('title', 'Territoires')

@section('content')
<div class="top"><div><h1>Territoires</h1><p class="muted" style="margin:0">Un territoire désactivé disparaît du site (formulaire, programme) mais garde ses inscriptions. Capacité vide = places illimitées ; au-delà, les inscriptions passent en liste d'attente.</p></div></div>

<div class="card tbl" style="margin-bottom:16px">
  <table class="stack">
    <thead><tr><th>Territoire</th><th>Pays</th><th>Inscrites {{ config('jnuaf.edition') }}</th><th>Capacité</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @foreach ($territories as $t)
      <tr>
        <td class="lead"><b>{{ $t->city }}</b> <span class="muted">{{ $t->code }}</span>
          <details class="edit"><summary>Modifier</summary>
            <form class="form" method="POST" action="{{ route('admin.territories.update', $t) }}">
              @csrf @method('PUT')
              @include('admin.territories.fields', ['t' => $t])
              <button class="btn sm">Enregistrer</button>
            </form>
          </details>
        </td>
        <td data-label="Pays">{{ $t->country }}</td>
        <td data-label="Inscrites"><span>{{ $t->confirmed_count }}@if ($t->waitlisted_count) <span class="pill o">+ {{ $t->waitlisted_count }} en attente</span>@endif</span></td>
        <td data-label="Capacité"><span>
          @if ($t->capacity)
            {{ $t->capacity }} @if ($t->confirmed_count >= $t->capacity)<span class="pill r">complet</span>@endif
          @else<span class="muted">illimitée</span>@endif
        </span></td>
        <td data-label="Statut">{!! $t->is_active ? '<span class="pill">Actif</span>' : '<span class="pill g">Désactivé</span>' !!}</td>
        <td>@if ($t->total_count === 0)<form class="inline" method="POST" action="{{ route('admin.territories.destroy', $t) }}" data-confirm="Supprimer ce territoire ?">@csrf @method('DELETE')<button class="btn danger sm">Supprimer</button></form>@endif</td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Ajouter un territoire</h2>
  <form class="form" method="POST" action="{{ route('admin.territories.store') }}">
    @csrf
    @include('admin.territories.fields', ['t' => null])
    <button class="btn">Ajouter</button>
  </form>
</div>
@endsection
