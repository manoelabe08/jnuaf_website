@extends('layouts.admin')
@section('title', $inquiry->organization)

@section('content')
@php($i = $inquiry)
<div class="top"><div><a href="{{ route('admin.partners.index') }}">← Demandes partenaires</a><h1>{{ $i->organization }}</h1></div></div>

<div class="grid g2">
  <div class="card">
    <h2>Demande</h2>
    <dl class="kv">
      <dt>Contact</dt><dd>{{ $i->contact_name }}@if ($i->job_title) · {{ $i->job_title }}@endif</dd>
      <dt>Email</dt><dd><a href="mailto:{{ $i->email }}">{{ $i->email }}</a></dd>
      @if ($i->phone)<dt>Téléphone</dt><dd>{{ $i->phone }}</dd>@endif
      <dt>Pays</dt><dd>{{ $i->country }}</dd>
      <dt>Formes souhaitées</dt><dd>{{ implode("\n", $i->typeLabels()) }}</dd>
      <dt>Message</dt><dd style="font-weight:400">{{ $i->message }}</dd>
      <dt>Reçue le</dt><dd>{{ $i->created_at->format('d/m/Y H:i') }}</dd>
      <dt>Dernier suivi</dt><dd>{{ $i->handled_at ? $i->handled_at->format('d/m/Y H:i').' par '.($i->handler?->name ?? '—') : '—' }}</dd>
    </dl>
  </div>
  <div class="card">
    <h2>Suivi</h2>
    <form class="form" method="POST" action="{{ route('admin.partners.update', $i) }}">
      @csrf @method('PATCH')
      <label>Statut<select name="status">@foreach ($statuses as $v => $l)<option value="{{ $v }}" @selected($i->status->value === $v)>{{ $l }}</option>@endforeach</select></label>
      <label>Notes internes<textarea name="notes" maxlength="3000">{{ old('notes', $i->notes) }}</textarea></label>
      <button class="btn">Enregistrer</button>
    </form>
    <form method="POST" action="{{ route('admin.partners.destroy', $i) }}" data-confirm="Supprimer cette demande ?" style="margin-top:14px">
      @csrf @method('DELETE')
      <button class="btn danger">Supprimer la demande</button>
    </form>
  </div>
</div>
@endsection
