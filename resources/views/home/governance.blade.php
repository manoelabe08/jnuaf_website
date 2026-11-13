<section id="organisation">
  <div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">Notre organisation</p>
      <h2>Gouvernance <span class="o">et équipes</span></h2>
      <p class="lede">{{ config('jnuaf.organisation.name') }} s'appuie sur des statuts et un règlement intérieur, avec trois niveaux de responsabilité et sept équipes opérationnelles.</p>
    </div>
    <ol class="gov">
      @foreach (config('jnuaf.governance') as $g)
        <li><span class="level">{{ $g['level'] }}</span><h3>{{ $g['body'] }}</h3><p>{{ $g['text'] }}</p></li>
      @endforeach
    </ol>
    <h3 class="teams-title">Équipes opérationnelles</h3>
    <div class="org">
      @foreach (config('jnuaf.teams') as $team => $text)
        <div><h3>{{ $team }}</h3><p>{{ $text }}</p></div>
      @endforeach
    </div>
    <p class="gal-note">Envie de vous engager dans l'une de ces équipes ou comme Point focal ? <a href="#comite">Candidatez au comité</a>.</p>
  </div>
</section>
