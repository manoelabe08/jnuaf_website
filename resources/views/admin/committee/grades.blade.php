
@extends('layouts.admin')

@section('title', "Grades du comit\u{e9}")



@section('content')
  @include('admin.committee.invite-block')

<div class="top"><div><h1>Grades du comit&eacute;</h1><p class="muted" style="margin:0">{{ $applications->total() }} candidature(s)</p></div><a class="btn ghost" href="{{ route('admin.committee.index') }}">Candidatures</a></div>





@if ($errors->any())<div class="card"><p>{{ $errors->first() }}</p></div>@endif



<form class="card filters" method="GET">

  <label style="flex:1 1 150px">Statut<select name="status"><option value="">Tous</option>@foreach ($statuses as $v => $l)<option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>@endforeach</select></label>

  <button class="btn" type="submit">Filtrer</button>

</form>



<div class="card tbl">

  <table class="stack">

    <thead><tr><th>Photo</th><th>Candidat&middot;e</th><th>Localit&eacute;</th><th>Statut</th><th>Grade</th></tr></thead>

    <tbody>

    @forelse ($applications as $a)

      @php($ok = $a->status->value === 'accepted')

      <tr>

        <td data-label="Photo">@if ($a->photo_path)<img src="{{ route('admin.committee.photo', $a).'?v='.substr(md5((string) $a->photo_path), 0, 8) }}" alt="Photo de {{ $a->full_name }}" width="48" height="60" loading="lazy" style="object-fit:cover;border-radius:6px">@else<span class="muted">Sans photo</span>@endif</td>

        <td class="lead"><a href="{{ route('admin.committee.show', $a) }}"><b>{{ $a->full_name }}</b></a><br><span class="muted">{{ $a->pole }}</span></td>

        <td data-label="Localit&eacute;">{{ $cities[$a->territory_id] ?? "Non d\u{e9}finie" }}<br><span class="muted">{{ $a->country }}</span></td>

        <td data-label="Statut">{{ $a->status->label() }}</td>

        <td data-label="Grade">

          <span class="muted">Actuel : {{ $grades[$a->grade] ?? $a->grade }}</span>

          <form method="POST" action="{{ route('admin.grades.update', $a) }}" style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px">

            @csrf

            @method('PATCH')

            <select name="territory_id" @disabled(! $ok)><option value="">Ville&hellip;</option>@foreach ($cities as $id => $city)<option value="{{ $id }}" @selected((int) $a->territory_id === (int) $id)>{{ $city }}</option>@endforeach</select>

            <select name="grade" @disabled(! $ok)>@foreach ($grades as $k => $l)<option value="{{ $k }}" @selected($a->grade === $k)>{{ $l }}</option>@endforeach</select>

            <button class="btn" type="submit" @disabled(! $ok)>Enregistrer</button>

          </form>

          
@php($acct = $ok ? \App\Models\User::where('committee_application_id', $a->id)->first() : null)

@if ($ok && in_array((string) $a->grade, (array) config('jnuaf_access.grades', []), true))

  @if ($acct)

    <span class="muted">Compte : {{ $acct->suspended_at ? 'suspendu' : 'actif' }}</span>

  @elseif ($a->territory_id)

    <form method="POST" action="{{ route('admin.grades.account', $a) }}" data-confirm="Cr&eacute;er un compte de connexion pour {{ $a->full_name }} ?" style="margin-top:6px">

      @csrf

      <button class="btn" type="submit">Cr&eacute;er le compte</button>

    </form>

  @else

    <span class="muted">Choisissez d&rsquo;abord une ville.</span>

  @endif

@endif


@if (! $ok)<span class="muted">Candidature non accept&eacute;e</span>@endif

        </td>

      </tr>

    @empty

      <tr><td colspan="5" class="muted">Aucune candidature.</td></tr>

    @endforelse

    </tbody>

  </table>

  {{ $applications->links('admin.partials.pager') }}

</div>

@endsection

