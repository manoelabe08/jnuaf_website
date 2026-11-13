
@extends('layouts.admin')

@section('title', 'Confirmer le mot de passe')



@section('content')

<div class="top"><div><h1>Confirmez votre mot de passe</h1><p class="muted" style="margin:0">Cette action est sensible. Retapez votre mot de passe pour continuer ; la confirmation reste valable 15 minutes.</p></div></div>



<div class="card" style="max-width:480px;padding:20px">

  <form method="POST" action="{{ route('password.confirm') }}">

    @csrf

    <label for="password">Mot de passe</label>

    <input id="password" type="password" name="password" required autocomplete="current-password" autofocus>

    @error('password')<p style="color:#b00020">{{ $message }}</p>@enderror

    <button type="submit" style="margin-top:12px">Confirmer</button>

  </form>

</div>

@endsection

