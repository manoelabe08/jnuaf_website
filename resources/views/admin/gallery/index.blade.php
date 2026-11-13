@extends('layouts.admin')

@section('title', 'Galerie')

@section('content')

<div class="top">
    <div>
        <h1>Galerie</h1>

        <p class="muted" style="margin:0">
            Une entrée sans photo s'affiche comme un emplacement hachuré
            « photo à ajouter ». Ne publiez que des personnes ayant donné
            leur accord à l'image.
        </p>
    </div>
</div>

<div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:16px">
    <a class="btn" href="{{ route('admin.gallery-folders.index') }}">
        Lots / import par dossier
    </a>

    <form
        method="POST"
        action="{{ route('admin.gallery-folders.sync-all') }}"
        data-confirm="Importer les nouvelles images de tous les dossiers configurés ?"
        style="margin:0"
    >
        @csrf

        <button type="submit" class="btn">
            Synchroniser tous les dossiers
        </button>
    </form>
</div>

<form id="bulk-form" method="POST" action="{{ route('admin.gallery.bulk') }}" data-bulk-max="50" class="card" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:10px 14px;margin-bottom:16px">

  @csrf

  <span class="muted"><strong id="bulk-count">0</strong> photo(s) s&eacute;lectionn&eacute;e(s)</span>

  <button class="btn sm o" type="submit" name="op" value="publish" data-bulk-confirm="Publier {n} photo(s) ? V&eacute;rifiez l'accord &agrave; l'image des personnes." disabled>Publier</button>

  <button class="btn sm ghost" type="submit" name="op" value="hide" data-bulk-confirm="Masquer {n} photo(s) ?" disabled>Masquer</button>

  <button class="btn danger sm" type="submit" name="op" value="delete" data-bulk-confirm="Supprimer {n} photo(s) ? Les fichiers seront archiv&eacute;s." disabled>Supprimer</button>

</form>

<div class="card tbl" style="margin-bottom:16px">
    <table>
        <thead>
            <tr>
                <th><input type="checkbox" id="bulk-all" aria-label="Tout s&eacute;lectionner"></th><th>Photo</th>
                <th>Titre</th>
                <th>Édition</th>
                <th>Format</th>
                <th>Statut</th>
                <th></th>
            </tr>
        </thead>

        <tbody>
            @forelse ($photos as $p)
                <tr>
                    <td><input type="checkbox" class="bulk-item" name="ids[]" value="{{ $p->id }}" form="bulk-form" aria-label="S&eacute;lectionner"></td>
                    <td>
                        @if ($p->url())
                            <img
                                class="thumb"
                                src="{{ $p->url() }}"
                                alt=""
                            >
                        @else
                            <span class="thumb"></span>
                        @endif
                    </td>

                    <td>
                        <b>{{ $p->title }}</b>

                        <details class="edit">
                            <summary>
                                Modifier / remplacer la photo
                            </summary>

                            <form
                                class="form"
                                method="POST"
                                action="{{ route('admin.gallery.update', $p) }}"
                                enctype="multipart/form-data"
                            >
                                @csrf
                                @method('PUT')

                                @include('admin.gallery.fields', ['p' => $p])

                                <button type="submit" class="btn sm">
                                    Enregistrer
                                </button>
                            </form>
                        </details>
                    </td>

                    <td>{{ $p->edition }}</td>

                    <td>
                        {{ ['wide' => 'Large', 'tall' => 'Haute'][$p->size] ?? 'Standard' }}
                    </td>

                    <td>
                        @if ($p->is_published)
                            <span class="pill">Publiée</span>
                        @else
                            <span class="pill g">Masquée</span>
                        @endif
                    </td>

                    <td>
                        <form
                            class="inline"
                            method="POST"
                            action="{{ route('admin.gallery.destroy', $p) }}"
                            data-confirm="Supprimer cette photo ?"
                        >
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn danger sm">
                                Supprimer
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted">
                        Galerie vide.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Ajouter une photo</h2>

    <form
        class="form"
        method="POST"
        action="{{ route('admin.gallery.store') }}"
        enctype="multipart/form-data"
    >
        @csrf

        @include('admin.gallery.fields', ['p' => null])

        <button type="submit" class="btn">
            Ajouter
        </button>
    </form>
</div>

<script src="{{ asset('js/admin-bulk.js') }}?v=3" defer></script>
@endsection