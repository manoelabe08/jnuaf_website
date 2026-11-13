@extends('layouts.admin')



@section('title', 'Pages publiques')



@section('content')

<div class="page-head"><h1>Pages publiques</h1></div>
  <p><a class="btn" href="{{ route('admin.versions.index') }}">Versions du site</a></p>



<div class="card">

  <p>Une page masquée renvoie une erreur 404 aux visiteurs. Les administrateurs peuvent la prévisualiser (bandeau « Brouillon »).</p>

  <table style="width:100%;border-collapse:collapse">

    <thead><tr><th align="left">Page</th><th align="left">Adresse</th><th align="left">État</th><th></th></tr></thead>

    <tbody>

    @foreach ($pages as $page)

      <tr>

        <td>{{ $page->label }}</td>

        <td><a href="{{ url($paths[$page->page_key] ?? '/') }}" target="_blank" rel="noopener">{{ $paths[$page->page_key] ?? '/' }}</a></td>

        <td>{{ $page->status === 'published' ? 'Publiée' : 'Brouillon' }}</td>

        <td>

          @if ($isSuper && $page->page_key !== 'home')

            <form method="POST" action="{{ route('admin.pages.toggle', $page->page_key) }}" style="display:inline">

              @csrf

              <button class="btn" type="submit">{{ $page->status === 'published' ? 'Masquer' : 'Publier' }}</button>

            </form>

          @endif

        </td>

      </tr>

    @endforeach

    </tbody>

  </table>

</div>



@php($home = $pages->firstWhere('page_key', 'home'))

@php($isNew = ($home->variant ?? 'old') === 'new')

<div class="card" style="margin-top:16px">

  <h2>Page d'accueil</h2>

  <p>Version actuelle : <strong>{{ $isNew ? 'Nouvelle (multipages)' : 'Ancienne (une page)' }}</strong>.

     Aperçu admin : <a href="{{ url('/nouvel-accueil') }}" target="_blank" rel="noopener">nouvelle</a> · <a href="{{ url('/old_index') }}" target="_blank" rel="noopener">ancienne (/old_index)</a>.</p>

  @if ($isSuper)

    <form method="POST" action="{{ route('admin.pages.home') }}" data-confirm="Changer la page d'accueil du site public ?">

      @csrf

      <input type="hidden" name="variant" value="{{ $isNew ? 'old' : 'new' }}">

      <button class="btn btn-green" type="submit">{{ $isNew ? 'Revenir à l\'ancienne page' : 'Activer la nouvelle page' }}</button>

    </form>

  @else

    <p class="fine">Seul le compte Pilotage peut modifier ces réglages.</p>

  @endif

</div>

@endsection

