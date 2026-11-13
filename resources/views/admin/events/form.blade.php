@extends('layouts.admin')

@section('title', 'Evenement')

@section('content')

<div class="top"><div><h1>@if($event->exists)Modifier l'&eacute;v&eacute;nement @else Nouvel &eacute;v&eacute;nement @endif</h1></div><a class="btn sm" href="{{ route('admin.events.index') }}">Retour</a></div>

<div class="card">

  <form class="form" method="POST" action="{{ $event->exists ? route('admin.events.update', $event) : route('admin.events.store') }}">

    @csrf

    @if($event->exists)@method('PUT')@endif

    <label>Titre<input name="title" value="{{ old('title', $event->title) }}" required maxlength="150"></label>

    <label>Type<select name="type">@foreach(\App\Models\Event::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('type', $event->type) === $k)>{{ $v }}</option>@endforeach</select></label>

    <label>Territoire<select name="territory_id"><option value="">Aucun (en ligne ou plusieurs sites)</option>@foreach($territories as $t)<option value="{{ $t->id }}" @selected((int) old('territory_id' , $event->territory_id) === (int) $t->id)>{{ $t->city }} ({{ $t->code }})</option>@endforeach</select></label>
    <label>Lieu<input name="location" value="{{ old('location', $event->location) }}" maxlength="190"></label>
    <label>D&eacute;but<input type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}" required></label>
    <label>Fin (facultatif)<input type="datetime-local" name="ends_at" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}"></label>
    <label>Capacit&eacute; (facultatif)<input type="number" name="capacity" min="1" value="{{ old('capacity', $event->capacity) }}"></label>
    <label>Description<textarea name="description" maxlength="2000">{{ old('description', $event->description) }}</textarea></label>
    <label class="consent"><input type="checkbox" name="registration_open" value="1" @checked(old('registration_open', $event->registration_open ?? true))> Inscriptions ouvertes</label>
    <label class="consent"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $event->is_published))> Publier la page publique de l'&eacute;v&eacute;nement</label>
    <button class="btn" type="submit">Enregistrer</button>
  </form>
  @if($event->exists && $event->type !== 'jnuaf' && ! $event->registrations()->exists())
  <form method="POST" action="{{ route('admin.events.destroy', $event) }}" data-confirm="Supprimer cet &eacute;v&eacute;nement ?" style="margin-top:16px">
    @csrf
    @method('DELETE')
    <button class="btn sm" type="submit">Supprimer</button>
  </form>
  @endif
</div>
@endsection
