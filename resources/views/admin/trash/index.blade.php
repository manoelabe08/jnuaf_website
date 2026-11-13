@extends('layouts.admin')

@section('title', 'Corbeille')

@section('content')

<div class="top">

  <div>

    <h1>Corbeille</h1>

    <p class="muted" style="margin:0">&Eacute;l&eacute;ments supprim&eacute;s, restaurables pendant 45 jours puis purg&eacute;s automatiquement.</p>

  </div>

</div>

<form id="bulk-form" method="POST" action="{{ route('admin.trash.bulk') }}" class="card" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:10px 14px">

  @csrf

  <span class="muted"><strong id="bulk-count">0</strong> &eacute;l&eacute;ment(s) s&eacute;lectionn&eacute;(s)</span>

  <button class="btn sm o" type="submit" name="action" value="restore" disabled>Restaurer</button>

  <button class="btn sm ghost" type="submit" name="action" value="purge" disabled>Supprimer d&eacute;finitivement</button>

</form>

<div class="card tbl">

  <table class="stack">

    <thead><tr><th><input type="checkbox" id="bulk-all" aria-label="Tout s&eacute;lectionner"></th><th>Supprim&eacute; le</th><th>Type</th><th>&Eacute;l&eacute;ment</th><th>Par</th><th>Purge dans</th><th></th></tr></thead>

    <tbody>

    @forelse ($items as $i)

      <tr>

        <td data-label=""><input type="checkbox" class="bulk-item" name="ids[]" value="{{ $i->id }}" form="bulk-form" aria-label="S&eacute;lectionner"></td>
        <td data-label="Supprim&eacute; le">{{ \Illuminate\Support\Carbon::parse($i->deleted_at)->format('d/m/Y H:i') }}</td>

        <td data-label="Type">{{ $labels[$i->model] ?? $i->model }}</td>

        <td data-label="&Eacute;l&eacute;ment" class="lead">{{ $i->label }}</td>

        <td data-label="Par">{{ $names[$i->deleted_by] ?? '-' }}</td>

        <td data-label="Purge dans">{{ max(0, (int) ceil(now()->diffInDays(\Illuminate\Support\Carbon::parse($i->purge_at), false))) }} j</td>

        <td>

          <form method="POST" action="{{ route('admin.trash.restore', $i->id) }}" style="display:inline">@csrf<button class="btn sm o" type="submit">Restaurer</button></form>

          <form method="POST" action="{{ route('admin.trash.purge', $i->id) }}" style="display:inline">@csrf @method('DELETE')<button class="btn sm ghost" type="submit">Supprimer d&eacute;finitivement</button></form>

        </td>

      </tr>

    @empty

      <tr><td colspan="7" class="muted">La corbeille est vide.</td></tr>

    @endforelse

    </tbody>

  </table>

</div>

<script src="{{ asset('js/admin-bulk.js') }}?v=1" defer></script>
@endsection

