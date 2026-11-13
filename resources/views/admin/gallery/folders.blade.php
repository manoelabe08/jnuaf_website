@extends('layouts.admin')
@section('title', 'Lots de galerie')
@section('content')
<div class="top"><div><h1>Lots de galerie</h1><p class="muted">Déposez vos images dans un dossier, puis importez-les avec les mêmes attributs.</p></div></div>
<p><a class="btn" href="{{ route('admin.gallery.index') }}">Retour à la galerie</a></p>
@if($errors->any())<div class="card" style="margin-bottom:16px;color:#b42318"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card" style="margin-bottom:24px">
  <h2>Créer un lot</h2>
  <form class="form" method="POST" action="{{ route('admin.gallery-folders.store') }}">
    @csrf
    <div class="field"><label>Sous-dossier (exemple : par1)</label><input name="folder" value="{{ old('folder') }}" required pattern="[a-z0-9][a-z0-9_-]{0,39}" maxlength="40"><small>Minuscules, chiffres, tirets ou underscores. Dossier créé sous public/images/galerie/.</small></div>
    @include('admin.gallery.folder-fields', ['lot' => null])
    <button class="btn">Créer le lot</button>
  </form>
</div>
@forelse($lots as $folder => $lot)
<div class="card" style="margin-bottom:20px">
  <h2>{{ $folder }}</h2>
  <p style="overflow-wrap:anywhere"><code>htdocs/me/public/images/galerie/{{ $folder }}/</code></p>
  <form class="form" method="POST" action="{{ route('admin.gallery-folders.update', $folder) }}">
    @csrf @method('PUT')
    @include('admin.gallery.folder-fields', ['lot' => $lot])
    <label style="display:block;margin:12px 0">
    <input
        type="checkbox"
        name="apply_existing"
        value="1"
    >

    Appliquer également l’édition, le titre, le format et la publication
    aux photos déjà importées de ce dossier.
</label>

<p class="muted" style="margin-top:0">
    Attention : cette option remplace aussi les modifications individuelles
    de ces quatre champs pour les photos du lot.
</p>
    <button class="btn">Enregistrer les paramètres</button>
  </form>
  <form method="POST" action="{{ route('admin.gallery-folders.sync', $folder) }}" style="margin-top:16px" data-confirm="Importer les nouvelles photos avec les paramètres enregistrés de ce lot ?">
    @csrf
    <button class="btn">Synchroniser ce dossier</button>
  </form>
  <p class="muted">Enregistrez les paramètres avant de synchroniser. Attendez au moins 60 secondes après le transfert. Maximum 100 ajouts par passage.</p>
</div>
@empty
<p>Aucun lot configuré.</p>
@endforelse
@endsection
