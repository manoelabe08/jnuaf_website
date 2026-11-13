@extends('layouts.admin')
@section('title', 'Programme')

@section('content')
<div class="top"><div><h1>Programme</h1><p class="muted" style="margin:0">Un territoire sans créneau propre affiche le programme type. Dans les descriptions, <code>:ville</code> est remplacé par « d'Abidjan », « de Dakar »…</p></div></div>

<form class="card filters" method="GET">
  <label style="flex:1 1 240px">Programme affiché
    <select name="territory" data-autosubmit>
      <option value="">Programme type (commun)</option>
      @foreach ($territories as $t)<option value="{{ $t->id }}" @selected($territoryId === $t->id)>{{ $t->city }}</option>@endforeach
    </select>
  </label>
</form>

<div class="card tbl" style="margin-bottom:16px">
  <table>
    <thead><tr><th>#</th><th>Créneau</th><th>Titre</th><th>Type</th><th></th></tr></thead>
    <tbody>
    @forelse ($items as $item)
      <tr>
        <td>{{ $item->position }}</td><td>{{ $item->slot }}</td>
        <td><b>{{ $item->title }}</b><br><span class="muted">{{ $item->description }}</span>
          <details class="edit"><summary>Modifier</summary>
            <form class="form" method="POST" action="{{ route('admin.program.update', $item) }}">
              @csrf @method('PUT')
              @include('admin.program.fields', ['item' => $item])
              <button class="btn sm">Enregistrer</button>
            </form>
          </details>
        </td>
        <td><span class="pill">{{ $item->kind }}</span></td>
        <td><form class="inline" method="POST" action="{{ route('admin.program.destroy', $item) }}" data-confirm="Supprimer ce créneau ?">@csrf @method('DELETE')<button class="btn danger sm">Supprimer</button></form></td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted">Aucun créneau propre : ce territoire affiche le programme type.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Ajouter un créneau</h2>
  <form class="form" method="POST" action="{{ route('admin.program.store') }}">
    @csrf
    @include('admin.program.fields', ['item' => null])
    <button class="btn">Ajouter</button>
  </form>
</div>
@endsection
