@extends('layouts.admin')
@section('title', 'Candidatures comité')

@section('content')
<div class="top"><div><h1>Candidatures au comité</h1><p class="muted" style="margin:0">{{ $applications->total() }} candidature(s)</p></div><a class="btn ghost" href="{{ route('admin.committee.export', request()->query()) }}">Exporter en CSV</a></div>

<p><a class="btn" href="{{ route('admin.grades.index') }}">Grades du comit&eacute;</a></p>
<form class="card filters" method="GET">
  <label style="flex:1 1 150px">Statut<select name="status"><option value="">Tous</option>@foreach ($statuses as $v => $l)<option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></label>
  <label style="flex:1 1 150px">Pays<select name="country"><option value="">Tous</option>@foreach (config('jnuaf.committee.countries') as $c)<option @selected(($filters['country'] ?? '') === $c)>{{ $c }}</option>@endforeach</select></label>
  <label style="flex:1 1 200px">Pôle<select name="pole"><option value="">Tous</option>@foreach (config('jnuaf.committee.poles') as $p)<option @selected(($filters['pole'] ?? '') === $p)>{{ $p }}</option>@endforeach</select></label>
  <button class="btn" type="submit">Filtrer</button>
</form>

<form id="bulk-form" method="POST" action="{{ route('admin.committee.bulk') }}" data-bulk-max="20" class="card" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:10px 14px">

  @csrf

  <span class="muted"><strong id="bulk-count">0</strong> candidature(s) s&eacute;lectionn&eacute;e(s)</span>

  <button class="btn sm ghost" type="submit" name="status" value="reviewing" data-bulk-confirm="Passer {n} candidature(s) en examen ?" disabled>Passer en examen</button>

  <button class="btn sm o" type="submit" name="status" value="accepted" data-bulk-confirm="Accepter {n} candidature(s) ? Un e-mail de d&eacute;cision sera envoy&eacute; &agrave; chaque personne." disabled>Accepter</button>

  <button class="btn sm ghost" type="submit" name="status" value="rejected" data-bulk-confirm="Refuser {n} candidature(s) ? Un e-mail de d&eacute;cision sera envoy&eacute; &agrave; chaque personne." disabled>Refuser</button>

</form>

<div class="card tbl">
  <table class="stack">
    <thead><tr><th><input type="checkbox" id="bulk-all" aria-label="Tout s&eacute;lectionner"></th><th>Candidat·e</th><th>Pays</th><th>Pôle</th><th>Pièces</th><th>Statut</th><th>Reçue le</th></tr></thead>
    <tbody>
    @forelse ($applications as $a)
      <tr>
        <td data-label=""><input type="checkbox" class="bulk-item" name="ids[]" value="{{ $a->id }}" form="bulk-form" aria-label="S&eacute;lectionner"></td>
        <td class="lead"><a href="{{ route('admin.committee.show', $a) }}"><b>{{ $a->full_name }}</b></a><br><span class="muted">{{ $a->email }}</span></td>
        <td data-label="Pays">{{ $a->country }}</td>
        <td data-label="Pôle">{{ $a->pole }}</td>
        <td data-label="Pièces"><a href="{{ route('admin.committee.download', [$a, 'cv']) }}" target="_blank">CV</a> · <a href="{{ route('admin.committee.download', [$a, 'lettre']) }}" target="_blank">Lettre</a></td>
        <td data-label="Statut">@php($cls = ['new' => 'o', 'reviewing' => 'g', 'accepted' => '', 'rejected' => 'r'][$a->status->value])<span class="pill {{ $cls }}">{{ $a->status->label() }}</span></td>
        <td data-label="Reçue le">{{ $a->created_at->format('d/m/Y') }}</td>
      </tr>
    @empty
      <tr><td colspan="7" class="muted">Aucune candidature.</td></tr>
    @endforelse
    </tbody>
  </table>
  {{ $applications->links('admin.partials.pager') }}
</div>
<script src="{{ asset('js/admin-bulk.js') }}?v=3" defer></script>
@endsection
