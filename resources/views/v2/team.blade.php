@extends('layouts.v2')



@section('title', "\u{c9}quipe")



@section('content')

<section><div class="wrap"><div class="sec-head"><p class="eyebrow">&Eacute;quipe</p><h2>Les visages de la <span class="o">JNUAF</span></h2><p class="lede">Les membres du comit&eacute; d&rsquo;organisation, par pays et par ville.</p></div>

@forelse ($groups as $country => $cities)

  <h3 style="margin:32px 0 8px">{{ $country }}</h3>

  @foreach ($cities as $city => $members)

    @if ($city !== '')<h4 class="muted" style="margin:16px 0 8px">{{ $city }}</h4>@endif

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:16px">

      @foreach ($members as $m)

        <figure style="margin:0">

          @if (! empty($m['photo']))
            <img src="{{ route('v2.team.photo', [$m['id'], $m['v']]) }}" alt="Portrait de {{ $m['name'] }}" width="300" height="375" loading="lazy" style="width:100%;height:auto;aspect-ratio:4/5;object-fit:cover;border-radius:10px">
          @else
            <div style="width:100%;aspect-ratio:4/5;display:flex;align-items:center;justify-content:center;background:#E3F1E5;border-radius:10px;font-weight:700;color:#14592A">{{ mb_strtoupper(mb_substr($m['name'], 0, 1)) }}</div>
          @endif

          <figcaption><strong>{{ $m['name'] }}</strong><br><span class="muted">{{ $m['pole'] }}</span></figcaption>

        </figure>

      @endforeach

    </div>

  @endforeach

@empty

  <p class="muted">L&rsquo;&eacute;quipe sera pr&eacute;sent&eacute;e ici tr&egrave;s bient&ocirc;t.</p>

@endforelse

</div></section>

@endsection

