<section class="hero">
  <div class="wrap">
    <div>
      <span class="chevrons" aria-hidden="true">›››</span> <span class="kicker">{{ config('jnuaf.organisation.name') }} présente</span>
      <h1 class="hero-title"><span class="g">JNUAF</span><span class="o">{{ config('jnuaf.edition') }}</span></h1>
      <p class="hero-full">Journée du Numérique au Féminin</p>
      <p class="script tagline">Votre énergie, notre mission,<br>ensemble, <span class="o">faisons la différence !</span></p>
      <p class="lede">{{ config('jnuaf.organisation.name') }} agit toute l'année pour la place des femmes dans le numérique. Son grand temps fort, la JNUAF {{ config('jnuaf.edition') }} : mentors, interventions, ateliers et réseau, sur {{ $territories->count() }} territoires d'Afrique et d'Europe.</p>
      <div class="hero-ctas">
        <a class="btn btn-orange" href="{{ route('v2.register') }}">Je m'inscris →</a>
        <a class="btn btn-ghost" href="{{ route('v2.committee') }}">Rejoindre le comité</a>
        <a class="btn btn-ghost" href="{{ route('v2.partners') }}">Devenir partenaire</a>
      </div>
      <p class="status"><i aria-hidden="true"></i>Candidatures au comité d'organisation ouvertes · plan d'action 2026-2027</p>
    </div>
    <div class="hero-media">
      <div class="photo"><img src="{{ asset('images/hero.jpg') }}" alt="Membres de l'équipe JNUAF réunissant leurs mains autour d'une table de travail" width="694" height="590"></div>
      <div class="badge-nb"><b>NB :</b>Candidature féminine vivement recherchée !</div>
    </div>
  </div>
</section>

<div class="band"><div class="wrap"><span>Ensemble, construisons un événement mémorable !&nbsp;<span class="heart" aria-hidden="true">♡</span></span></div></div>
