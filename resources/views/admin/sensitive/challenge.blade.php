@extends('layouts.admin')

@section('title', 'Confirmation')



@section('content')

<div class="top">

  <div><h1>Confirmation requise</h1><p class="muted" style="margin:0">Action sensible (niveau {{ $challenge['level'] }}) : {{ $challenge['label'] }}</p></div>

</div>

<div class="card" style="max-width:520px;padding:18px">

  @if (! $hasTotp)

    <p>Pour confirmer cette action, activez d'abord votre application d'authentification.</p>

    <a class="btn o" href="{{ route('admin.profile') }}">Ouvrir mon profil</a>

  @else

    <form method="POST" action="{{ route('admin.sensitive.verify') }}">

      @csrf

      <label>Code &agrave; 6 chiffres de votre application d'authentification<input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="12" required autofocus></label>

      @error('code')<span class="err" style="color:#B42318;font-weight:600">{{ $message }}</span>@enderror

      @if ($challenge['level'] >= 2 && ! $isSuper)

        <p class="muted">Cette action n&eacute;cessite ensuite la validation du Bureau de Pilotage.</p>

      @endif

      <button class="btn o" type="submit">Confirmer</button>

    </form>

  @endif

</div>

@endsection

