
@php($logos = \App\Models\SponsorLogo::active()->get())

@if ($logos->isNotEmpty())

@php($animated = $logos->count() >= 5)

@php($passes = $animated ? [false, true] : [false])

<section id="soutiens" class="sponsors-band" aria-label="Nos partenaires et sponsors">

  <div class="wrap">

    <p class="eyebrow">Ils nous soutiennent</p>

    <div @class(['sponsors-marquee', 'is-animated' => $animated])>

      <ul class="sponsors-track" @if ($animated) style="--sponsors-duration: {{ max(24, $logos->count() * 6) }}s" @endif>

        @foreach ($passes as $dup)

          @foreach ($logos as $logo)

            <li @class(['is-dup' => $dup]) @if ($dup) aria-hidden="true" @endif>

              @if ($logo->url)

                <a href="{{ $logo->url }}" target="_blank" rel="noopener noreferrer" title="{{ $logo->name }}" @if ($dup) tabindex="-1" @endif><img src="{{ route('sponsors.logo', $logo) }}?v={{ $logo->updated_at->timestamp }}" alt="{{ $dup ? '' : $logo->name }}" height="56" loading="lazy"></a>

              @else

                <span title="{{ $logo->name }}"><img src="{{ route('sponsors.logo', $logo) }}?v={{ $logo->updated_at->timestamp }}" alt="{{ $dup ? '' : $logo->name }}" height="56" loading="lazy"></span>

              @endif

            </li>

          @endforeach

        @endforeach

      </ul>

    </div>

  </div>

</section>

@endif

