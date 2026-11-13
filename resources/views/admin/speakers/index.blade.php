@extends('layouts.admin')
@section('title', 'Intervenantes')

@section('content')
<div class="top"><div><h1>Intervenantes &amp; mentors</h1><p class="muted" style="margin:0">Seules les fiches publiées apparaissent sur le site. Sans fiche publiée, le site affiche des emplacements « à annoncer ».</p></div></div>

<div class="card tbl" style="margin-bottom:16px">
  <table>
    <thead><tr><th>Photo</th><th>Nom</th><th>Session</th><th>Territoire</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @forelse ($speakers as $s)
      <tr>
        <td>@if ($s->photoUrl())<img class="thumb" src="{{ $s->photoUrl() }}" alt="">@else<span class="thumb" style="display:grid;place-items:center;font-weight:900;color:var(--green)">{{ $s->initials() }}</span>@endif</td>
        <td><b>{{ $s->name }}</b>
          <details class="edit"><summary>Modifier</summary>
            <form class="form" method="POST" action="{{ route('admin.speakers.update', $s) }}" enctype="multipart/form-data">
              @csrf @method('PUT')
              @include('admin.speakers.fields', ['s' => $s])
              <button class="btn sm">Enregistrer</button>
            </form>
          </details>
        </td>
        <td>{{ $s->session }}</td>
        <td>{{ $s->territory?->city ?? 'Tous' }}</td>
        <td>{!! $s->is_published ? '<span class="pill">Publiée</span>' : '<span class="pill g">Brouillon</span>' !!}</td>
        <td><form class="inline" method="POST" action="{{ route('admin.speakers.destroy', $s) }}" data-confirm="Supprimer cette fiche ?">@csrf @method('DELETE')<button class="btn danger sm">Supprimer</button></form></td>
      </tr>
    @empty
      <tr><td colspan="6" class="muted">Aucune fiche.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Ajouter une intervenante ou une mentor</h2>
  <form class="form" method="POST" action="{{ route('admin.speakers.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.speakers.fields', ['s' => null])
    <button class="btn">Ajouter</button>
  </form>
</div>
@endsection
