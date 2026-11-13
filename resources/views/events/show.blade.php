@extends('layouts.v2')

@section('title', $event->title . ' | JNUAF')

@section('content')

<section style="padding:48px 0">

  <div class="wrap" style="max-width:760px">

    <p class="eyebrow">{{ $event->typeLabel() }}</p>

    <h1>{{ $event->title }}</h1>

    <p>

      @if ($event->starts_at)<b>{{ ucfirst($event->starts_at->translatedFormat('l j F Y')) }}</b> &agrave; {{ $event->starts_at->format('H:i') }}<br>@endif

      @if ($event->location)Lieu : {{ $event->location }}<br>@endif

      @if ($event->territory)Territoire : {{ $event->territory->city }} ({{ $event->territory->country }})@endif

    </p>

    @if (session('registration'))

      @php($info = session('registration'))

      <div class="flash" role="status" style="margin:16px 0;padding:14px;border-radius:10px;background:#E3F1E5">

        @if ($info['waitlisted'])

          Merci {{ $info['first_name'] }}, l'&eacute;v&eacute;nement est complet : vous &ecirc;tes sur liste d'attente. Un email vous a &eacute;t&eacute; envoy&eacute; &agrave; {{ $info['email'] }}.

        @else

          Merci {{ $info['first_name'] }}, votre inscription est confirm&eacute;e. Votre badge a &eacute;t&eacute; envoy&eacute; &agrave; {{ $info['email'] }}.

        @endif

      </div>

    @endif

    @if ($event->description)

      <div>{!! nl2br(e($event->description)) !!}</div>

    @endif

    @if ($closed)

      <p><b>Les inscriptions sont ferm&eacute;es.</b></p>

    @else

      @if ($remaining !== null)<p>{{ $remaining }} place(s) restante(s).</p>@endif

      @if ($errors->any())

        <div class="flash" role="alert" style="margin:16px 0;padding:14px;border-radius:10px;background:#FDEBDF"><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>

      @endif

      <form method="POST" action="{{ route('events.register', $event->slug) }}" style="display:grid;gap:12px;margin-top:18px">

        @csrf

        <label>Nom<input type="text" name="last_name" value="{{ old('last_name') }}" required style="width:100%;padding:10px;border:1px solid #cfd8d1;border-radius:8px"></label>

        <label>Pr&eacute;nom<input type="text" name="first_name" value="{{ old('first_name') }}" required style="width:100%;padding:10px;border:1px solid #cfd8d1;border-radius:8px"></label>

<label>Email<input type="email" name="email" value="{{ old('email') }}" required style="width:100%;padding:10px;border:1px solid #cfd8d1;border-radius:8px"></label>
        <label>T&eacute;l&eacute;phone (avec indicatif)<input type="text" name="phone" value="{{ old('phone') }}" required style="width:100%;padding:10px;border:1px solid #cfd8d1;border-radius:8px"></label>
        <label>Ville<input type="text" name="city" value="{{ old('city') }}" required style="width:100%;padding:10px;border:1px solid #cfd8d1;border-radius:8px"></label>
        @if ($territories->isNotEmpty())
          <label>Territoire
            <select name="territory_id" required style="width:100%;padding:10px;border:1px solid #cfd8d1;border-radius:8px">
              <option value="">Choisir&hellip;</option>
              @foreach ($territories as $t)<option value="{{ $t->id }}" @selected(old('territory_id') == $t->id)>{{ $t->city }} ({{ $t->country }})</option>@endforeach
            </select>
          </label>
        @endif
        <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Site web<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <label><input type="checkbox" name="consent_data" value="1" required> J'accepte que mes donn&eacute;es soient trait&eacute;es pour l'organisation de cet &eacute;v&eacute;nement.</label>
        <button class="btn btn-orange" type="submit">Je m'inscris</button>
      </form>
    @endif
  </div>
</section>
@endsection

<!-- jnuaf-s95 -->