@extends('admin.auth.layout')
@section('title', 'Connexion')

@section('form')
<form class="card form" method="POST" action="{{ route('login') }}">
  @csrf
  <h1><span style="color:var(--orange)">JN</span>UAF · Espace équipe</h1>
  <p class="muted" style="margin:0">Bureau de Pilotage et coordinations territoriales.</p>
  @if (session('status'))<div class="flash" style="margin:0">{{ session('status') }}</div>@endif
  @if ($errors->any())<div class="errors" style="margin:0">{{ $errors->first() }}</div>@endif
  <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
  <label>Mot de passe<input type="password" name="password" autocomplete="current-password" required></label>
  <label class="check"><input type="checkbox" name="remember" value="1"> Rester connectée</label>
  <button class="btn" type="submit">Se connecter</button>
  <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:.85rem">
    <a href="{{ route('home') }}" class="muted">← Retour au site</a>
    <a href="{{ route('password.request') }}">Mot de passe oublié ?</a>
  </div>
</form>
@endsection
