@extends('admin.auth.layout')
@section('title', 'Mot de passe oublié')

@section('form')
<form class="card form" method="POST" action="{{ route('password.email') }}">
  @csrf
  <h1>Mot de passe oublié</h1>
  <p class="muted" style="margin:0">Indiquez l'email de votre compte : vous recevrez un lien pour choisir un nouveau mot de passe.</p>
  @if (session('status'))<div class="flash" style="margin:0">{{ session('status') }}</div>@endif
  @if ($errors->any())<div class="errors" style="margin:0">{{ $errors->first() }}</div>@endif
  <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
  <button class="btn" type="submit">Envoyer le lien</button>
  <a href="{{ route('login') }}" class="muted" style="font-size:.85rem">← Retour à la connexion</a>
</form>
@endsection
