@extends('layouts.admin')



@section('title', 'Versions du site')



@section('content')

<div class="page-head">

  <h1>Versions du site</h1>

  <p class="muted">Liste des pages du site, version en ligne, versions anciennes.</p>

</div>



@if ($versions->isEmpty())

  <p class="muted">Aucune version enregistrée pour le moment.</p>

@else

  @foreach ($versions as $pageKey => $items)

    <div class="card">

      <h3>{{ $pageKey }}</h3>

      <ul>

        @foreach ($items as $v)

          <li>

            <strong>{{ $v->version }}</strong>
            @if ($v->status === 'draft')
              <form method="POST" action="{{ route('admin.versions.publish', $v->id) }}" style="display:inline">@csrf<button type="submit" class="btn btn-green">Publier</button></form>
            @endif

            &middot; {{ $v->status }}

            @if ($v->published_at)

              &middot; publi&eacute;e le {{ \Illuminate\Support\Carbon::parse($v->published_at)->format('d/m/Y H:i') }}

            @endif

          </li>

        @endforeach

      </ul>

    </div>

  @endforeach

@endif

@endsection

