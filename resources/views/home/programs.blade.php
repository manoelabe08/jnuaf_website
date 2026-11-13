<section id="programmes" class="terr">
  <div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">Nos programmes</p>
      <h2>Toute l'année, <span class="o">pas seulement un jour</span></h2>
      <p class="lede">Chaque programme sert une étape du modèle d'intervention. La JNUAF {{ config('jnuaf.edition') }} en est le grand temps fort.</p>
    </div>
    <div class="prog-cards">
      @foreach (config('jnuaf.programs') as $p)
        <article class="p-card"><span class="tag">{{ $p['tag'] }}</span><h3>{{ $p['name'] }}</h3><p class="muted-p">{{ $p['text'] }}</p></article>
      @endforeach
    </div>
  </div>
</section>
