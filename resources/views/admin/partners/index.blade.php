@extends('layouts.admin')
@section('title', 'Demandes partenaires')

@section('content')
<div class="top"><div><h1>Demandes de partenariat</h1><p class="muted" style="margin:0">{{ $inquiries->total() }} demande(s) reçue(s) via le site</p></div><a class="btn ghost" href="{{ route('admin.partners.export', request()->query()) }}">Exporter en CSV</a></div>

<form class="card filters" method="GET">
  <label style="flex:0 1 220px">Statut<select name="status"><option value="">Tous</option>@foreach ($statuses as $v => $l)<option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></label>
  <button class="btn" type="submit">Filtrer</button>
</form>

<form id="bulk-form" method="POST" action="{{ route('admin.partners.bulk') }}" data-bulk-max="50" class="card" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:10px 14px">

  @csrf

  <span class="muted"><strong id="bulk-count">0</strong> demande(s) s&eacute;lectionn&eacute;e(s)</span>

  <button class="btn sm ghost" type="submit" name="status" value="discussion" data-bulk-confirm="Passer {n} demande(s) en discussion ?" disabled>En discussion</button>

  <button class="btn sm o" type="submit" name="status" value="signed" data-bulk-confirm="Marquer {n} demande(s) comme sign&eacute;e(s) ?" disabled>Sign&eacute;e</button>

  <button class="btn sm ghost" type="submit" name="status" value="declined" data-bulk-confirm="Marquer {n} demande(s) comme d&eacute;clin&eacute;e(s) ?" disabled>D&eacute;clin&eacute;e</button>

</form>

<div class="card tbl">
  <table class="stack">
    <thead><tr><th><input type="checkbox" id="bulk-all" aria-label="Tout s&eacute;lectionner"></th><th>Organisation</th><th>Contact</th><th>Pays</th><th>Formes souhaitées</th><th>Statut</th><th>Reçue le</th></tr></thead>
    <tbody>
    @forelse ($inquiries as $i)
      <tr>
        <td data-label=""><input type="checkbox" class="bulk-item" name="ids[]" value="{{ $i->id }}" form="bulk-form" aria-label="S&eacute;lectionner"></td>
        <td class="lead"><a href="{{ route('admin.partners.show', $i) }}"><b>{{ $i->organization }}</b></a></td>
        <td data-label="Contact"><span>{{ $i->contact_name }}<br><span class="muted">{{ $i->email }}</span></span></td>
        <td data-label="Pays">{{ $i->country }}</td>
        <td data-label="Formes"><span>@foreach ($i->typeLabels() as $label)<span class="pill g" style="margin:0 4px 4px 0">{{ $label }}</span>@endforeach</span></td>
        <td data-label="Statut">@php($cls = ['new' => 'o', 'discussion' => 'g', 'signed' => '', 'declined' => 'r'][$i->status->value])<span class="pill {{ $cls }}">{{ $i->status->label() }}</span></td>
        <td data-label="Reçue le">{{ $i->created_at->format('d/m/Y') }}</td>
      </tr>
    @empty
      <tr><td colspan="7" class="muted">Aucune demande.</td></tr>
    @endforelse
    </tbody>
  </table>
  {{ $inquiries->links('admin.partials.pager') }}
</div>
<script src="{{ asset('js/admin-bulk.js') }}?v=3" defer></script>
@endsection
