<section id="programme">
  <div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">Programme</p>
      <h2>Le déroulé <span class="o">de la journée</span></h2>
      <p class="lede">Programme type, commun aux territoires. Les horaires, salles et noms des intervenantes de chaque territoire sont publiés ici dès leur validation.</p>
    </div>
    <div class="prog-tabs" role="tablist" aria-label="Territoire">
      @foreach ($territories as $t)
        <button role="tab" type="button" id="tab-{{ $t->code }}" aria-controls="prog-{{ $t->code }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-t="{{ $t->code }}">{{ $t->city }}</button>
      @endforeach
    </div>
    @foreach ($territories as $t)
      <ol class="prog" id="prog-{{ $t->code }}" role="tabpanel" aria-labelledby="tab-{{ $t->code }}" @unless ($loop->first) hidden @endunless>
        @forelse ($programs[$t->code] as $item)
          <li>
            <span class="slot">{{ $item->slot }}</span>
            <div>
              <h3>{{ $item->title }}</h3>
              @if ($item->description)<p>{{ str_replace(':ville', $t->cityWithPreposition(), $item->description) }}</p>@endif
              @if ($item->room)<p>Salle : {{ $item->room }}</p>@endif
            </div>
            <span class="kind">{{ $item->kind }}</span>
          </li>
        @empty
          <li><span class="slot">À venir</span><div><h3>Programme en préparation</h3></div><span></span></li>
        @endforelse
      </ol>
    @endforeach
    <p class="gal-note">Programme indicatif, à confirmer par le Bureau de Pilotage.</p>
  </div>
</section>
