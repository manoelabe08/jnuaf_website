<section id="intervenantes" class="terr">
  <div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">Intervenantes &amp; mentors</p>
      <h2>Elles prendront <span class="o">la parole</span></h2>
      <p class="lede">Les profils sont dévoilés au fil des confirmations. Vous êtes experte du numérique ? Proposez une intervention ou un mentorat.</p>
    </div>
    <div class="speakers">
      @forelse ($speakers as $s)
        <article class="spk">
          <div @class(['avatar', 'o' => $loop->even, 'has-photo' => $s->photoUrl()])>
            @if ($s->photoUrl())<img src="{{ $s->photoUrl() }}" alt="Portrait de {{ $s->name }}" loading="lazy" width="132" height="132">@else<span aria-hidden="true">{{ $s->initials() }}</span>@endif
          </div>
          <h3 class="spk-name">{{ $s->name }}</h3>
          <span class="spk-role">{{ $s->session }}@if ($s->territory) · {{ $s->territory->city }}@endif</span>
          @if ($s->bio)
            <p class="spk-bio">{{ $s->bio }}</p>
            {{-- Bio courte : déjà lisible en entier sur la carte, pas besoin de la fenêtre. --}}
            @if ($s->hasLongBio())
            <button type="button" class="spk-more" data-dialog="spk-{{ $s->id }}" aria-haspopup="dialog">Lire la bio <span aria-hidden="true">→</span></button>
            @endif
          @endif
        </article>
      @empty
        @foreach ([['Intervenante', 'Panel d\'ouverture'], ['Intervenante', 'Atelier data'], ['Mentor', 'Mentorat carrière'], ['Mentor', 'Entrepreneuriat']] as [$who, $session])
          <article class="spk"><div @class(['avatar', 'o' => $loop->even]) aria-hidden="true">?</div><h3 class="spk-name">{{ $who }} à annoncer</h3><span class="spk-role">{{ $session }}</span></article>
        @endforeach
      @endforelse
      <a class="spk spk-cta" href="{{ route('v2.register') }}" data-profile="intervenante"><b>Votre profil ici ?</b><span>Proposer une intervention →</span></a>
    </div>
  </div>

  {{-- Biographies complètes : une fenêtre par intervenante, ouverte par « Lire la bio ». --}}
  @foreach ($speakers->filter(fn ($s) => $s->hasLongBio()) as $s)
    <dialog class="spk-dialog" id="spk-{{ $s->id }}" aria-labelledby="spk-{{ $s->id }}-name">
      <div class="spk-dialog-inner">
        <div @class(['spk-dialog-photo', 'o' => $loop->even])>
          @if ($s->photoUrl())<img src="{{ $s->photoUrl() }}" alt="Portrait de {{ $s->name }}" loading="lazy" width="600" height="600">@else<span aria-hidden="true">{{ $s->initials() }}</span>@endif
        </div>
        <div class="spk-dialog-body">
          <p class="eyebrow">{{ $s->session }}</p>
          <h3 id="spk-{{ $s->id }}-name">{{ $s->name }}</h3>
          @if ($s->territory)<p class="spk-dialog-place">{{ $s->territory->city }} · {{ $s->territory->country }}</p>@endif
          <div class="spk-dialog-bio">{!! nl2br(e($s->bio)) !!}</div>
        </div>
        <button type="button" class="spk-close" data-close aria-label="Fermer la biographie de {{ $s->name }}">✕</button>
      </div>
    </dialog>
  @endforeach
</section>
