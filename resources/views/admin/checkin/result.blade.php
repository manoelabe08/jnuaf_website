@extends('layouts.admin')
@section('title', 'Badge scanné')

@section('content')
<div class="top"><h1>Badge scanné</h1><a href="{{ route('admin.checkin.index') }}">← Pointage</a></div>

<div class="scan-result {{ $state === 'ok' ? 'already' : $state }}" style="max-width:640px">
  @if ($registration)
    <strong>{{ $registration->fullName() }}</strong>
    {{ $registration->profile->label() }} · {{ $registration->territory->city }} · <code>{{ $registration->badge_code }}</code>
    @if ($registration->accessibility_needs)<br><b>Besoin spécifique :</b> {{ $registration->accessibility_needs }}@endif
    <p style="margin-bottom:0">{{ $state === 'ok' ? 'Badge valide, entrée non encore validée.' : $message }}</p>
  @else
    <strong>{{ $message }}</strong>
  @endif
</div>

@if ($state === 'ok')
  <form method="POST" action="{{ route('admin.checkin.store') }}" style="margin-top:16px">
    @csrf
    <input type="hidden" name="code" value="{{ $registration->badge_code }}">
    <button class="btn o" type="submit" autofocus>Valider l'entrée</button>
  </form>
@endif
@endsection
