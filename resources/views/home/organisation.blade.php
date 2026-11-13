<section id="qui-sommes-nous">
  <div class="wrap">
    <div class="about-grid">
      <div class="sec-head" style="margin-bottom:0">
        <p class="eyebrow">Qui sommes-nous</p>
        <h2>{{ config('jnuaf.organisation.name') }}, <span class="o">une organisation</span></h2>
        <svg class="underline" viewBox="0 0 180 12" aria-hidden="true"><path d="M2 9 C 50 2, 120 2, 178 7"/></svg>
        <div class="about-text">
          <p><b>{{ config('jnuaf.organisation.name') }}</b> est une {{ mb_strtolower(config('jnuaf.organisation.tagline')) }}. Elle agit toute l'année, en France et en Afrique, grâce à une gouvernance structurée et à des Points focaux sur chaque territoire.</p>
          <p>Notre mission : donner aux filles et aux femmes l'envie, les compétences et le réseau pour réussir dans le numérique, de la découverte des métiers jusqu'à l'emploi.</p>
        </div>
      </div>
      <div class="org-vs">
        <div class="vs-card">
          <span class="tag">L'organisation</span>
          <h3>{{ config('jnuaf.organisation.name') }}</h3>
          <p>La plateforme permanente : programmes, communauté, partenariats et déploiement territorial.</p>
        </div>
        <div class="vs-card o">
          <span class="tag">Le temps fort</span>
          <h3>JNUAF {{ config('jnuaf.edition') }}</h3>
          <p>La grande journée annuelle qui rassemble toute la communauté dans chaque territoire. <a href="#evenement">Découvrir →</a></p>
        </div>
      </div>
    </div>

    <div class="sec-head" style="margin-top:72px">
      <p class="eyebrow">Notre histoire</p>
      <h2>De 2021 <span class="o">à aujourd'hui</span></h2>
    </div>
    <ol class="timeline">
      @foreach (config('jnuaf.history') as $step)
        <li><span class="when">{{ $step['period'] }}</span><h3>{{ $step['title'] }}</h3><p>{{ $step['text'] }}</p></li>
      @endforeach
    </ol>

    <div class="sec-head" style="margin-top:72px">
      <p class="eyebrow">Notre modèle d'intervention</p>
      <h2>De la découverte <span class="o">à l'emploi</span></h2>
      <p class="lede">Un parcours continu, du premier contact avec le numérique jusqu'à l'insertion professionnelle.</p>
    </div>
    <ol class="model">
      @foreach (config('jnuaf.model') as $i => $m)
        <li><span class="num">{{ $i + 1 }}</span><h3>{{ $m['verb'] }}</h3><p>{{ $m['text'] }}</p></li>
      @endforeach
    </ol>
  </div>
</section>
