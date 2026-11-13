@extends('admin.auth.layout')
@section('title', 'Nouveau mot de passe')

@section('form')
<form class="card form" method="POST" action="{{ route('password.update') }}">
  @csrf
  <input type="hidden" name="token" value="{{ $token }}">
  <h1>Nouveau mot de passe</h1>
  @if ($errors->any())<div class="errors" style="margin:0"><ul style="margin:0;padding-left:18px">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  <label>Email<input type="email" name="email" value="{{ old('email', $email) }}" autocomplete="username" required></label>
  <label>Nouveau mot de passe (10 caractères min., lettres et chiffres)<input type="password" name="password" autocomplete="new-password" minlength="12" required autofocus></label>
  <label>Confirmation<input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required data-nopaste></label>
  <button class="btn" type="submit">Enregistrer</button>
</form>
@endsection
