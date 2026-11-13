<section id="territoires" class="terr">
  <div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">Notre déploiement territorial</p>
      <h2>Inscrivez-vous <span class="o">près de chez vous</span></h2>
      <p class="lede">En France, en Côte d'Ivoire, au Sénégal et au Maroc, un Point focal coordonne localement la mobilisation, les partenariats et les bénévoles. Chaque inscription est rattachée à un territoire : votre coordination locale vous accompagne avant et pendant l'événement.</p>
    </div>
    <div class="terr-grid">
      @foreach ($territories as $t)
        <article class="t-card"><span class="t-code">{{ $t->code }}</span><span class="t-city">{{ $t->city }}</span><span class="t-country">{{ $t->country }}</span></article>
      @endforeach
    </div>
  </div>
</section>
