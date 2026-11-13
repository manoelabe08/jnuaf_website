
@extends('layouts.admin')

@section('title', 'Bandeau partenaires & sponsors')



@section('content')

<div class="top"><div><h1>Bandeau partenaires &amp; sponsors</h1><p class="muted" style="margin:0">Les logos affichés défilent sur l'accueil, du plus petit numéro d'ordre au plus grand. Formats acceptés : PNG, JPG ou WebP, 2 Mo maximum.</p></div></div>



@if ($errors->any())

  <div class="card" style="padding:14px 18px;margin-bottom:16px;border-color:#b00020">

    @foreach ($errors->all() as $error)<p style="color:#b00020;margin:2px 0">{{ $error }}</p>@endforeach

  </div>

@endif



<div class="card" style="padding:18px;margin-bottom:16px">

  <h2 style="font-size:1.1rem;margin:0 0 12px">Ajouter un logo</h2>

  <form method="POST" action="{{ route('admin.sponsors.store') }}" enctype="multipart/form-data" style="display:grid;gap:10px;max-width:640px">

    @csrf

    <label>Nom *<input name="name" required maxlength="120" value="{{ old('name') }}"></label>

    <label>Site web (facultatif)<input name="url" type="url" placeholder="https://" value="{{ old('url') }}"></label>

    <label>Catégorie *

      <select name="category" required>

        @foreach ($categories as $key => $label)<option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>@endforeach

      </select>

    </label>

    <label>Ordre d'affichage<input name="position" type="number" min="0" max="9999" value="{{ old('position', 0) }}"></label>

    <label>Logo *<input name="logo" type="file" accept="image/png,image/jpeg,image/webp" required></label>

    <button type="submit">Ajouter au bandeau</button>

  </form>

</div>



<div class="card tbl">

  <table class="stack">

    <thead><tr><th>Logo</th><th>Nom, site et nouveau fichier</th><th>Catégorie</th><th>Ordre</th><th>Affiché</th><th></th></tr></thead>

    <tbody>

    @forelse ($logos as $logo)

      <tr>

        <td data-label="Logo">

          <img src="{{ route('sponsors.logo', $logo) }}?v={{ $logo->updated_at->timestamp }}" alt="{{ $logo->name }}" style="max-width:120px;max-height:48px;background:#fff;padding:4px;border:1px solid var(--line);border-radius:6px">

          <form id="f-{{ $logo->id }}" method="POST" action="{{ route('admin.sponsors.update', $logo) }}" enctype="multipart/form-data">@csrf @method('PUT')</form>

        </td>

        <td data-label="Nom et site">

          <input form="f-{{ $logo->id }}" name="name" value="{{ $logo->name }}" required maxlength="120">

          <input form="f-{{ $logo->id }}" name="url" type="url" value="{{ $logo->url }}" placeholder="https://">

          <input form="f-{{ $logo->id }}" name="logo" type="file" accept="image/png,image/jpeg,image/webp">

        </td>

        <td data-label="Catégorie">

          <select form="f-{{ $logo->id }}" name="category">

            @foreach ($categories as $key => $label)<option value="{{ $key }}" @selected($logo->category === $key)>{{ $label }}</option>@endforeach

          </select>

        </td>

        <td data-label="Ordre"><input form="f-{{ $logo->id }}" name="position" type="number" min="0" max="9999" value="{{ $logo->position }}" style="width:80px"></td>

        <td data-label="Affiché"><label><input form="f-{{ $logo->id }}" type="checkbox" name="is_active" value="1" @checked($logo->is_active)> Oui</label></td>

        <td data-label="Actions">

          <button form="f-{{ $logo->id }}" type="submit">Enregistrer</button>

          <details style="margin-top:8px">

            <summary>Supprimer</summary>

            <form method="POST" action="{{ route('admin.sponsors.destroy', $logo) }}">@csrf @method('DELETE')<button type="submit">Confirmer la suppression</button></form>

          </details>

        </td>

      </tr>

    @empty

      <tr><td colspan="6" class="muted">Aucun logo pour le moment : le bandeau n'apparaît pas sur l'accueil tant qu'il est vide.</td></tr>

    @endforelse

    </tbody>

  </table>

</div>

@endsection

