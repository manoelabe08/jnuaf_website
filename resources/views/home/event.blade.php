<section id="evenement">
  <div class="wrap">
    <div class="about-grid">
      <div class="sec-head" style="margin-bottom:0">
        <p class="eyebrow">JNUAF {{ config('jnuaf.edition') }} · le grand temps fort</p>
        <h2>La journée <span class="o">au féminin</span></h2>
        <svg class="underline" viewBox="0 0 180 12" aria-hidden="true"><path d="M2 9 C 50 2, 120 2, 178 7"/></svg>
        <div class="about-text">
          <p>Chaque année, la Journée du Numérique au Féminin réunit étudiantes, professionnelles, mentors et intervenantes autour d'une même ambition : donner aux femmes toute leur place dans le numérique.</p>
          <p>L'édition {{ config('jnuaf.edition') }} est organisée par {{ config('jnuaf.organisation.name') }} : le Bureau de Pilotage coordonne, les Points focaux l'organisent sur chacun des territoires.</p>
        </div>
      </div>
      <div class="figures">
        <div class="fig"><b>{{ $territories->count() }}</b><span>territoires</span></div>
        <div class="fig"><b>{{ count($profiles) }}</b><span>profils d'inscription</span></div>
        <div class="fig"><b>1</b><span>badge QR par personne</span></div>
      </div>
    </div>

    <div class="values">
      <div class="value"><span class="ico"><svg viewBox="0 0 24 24"><circle cx="8" cy="7" r="3"/><circle cx="16" cy="7" r="3"/><path d="M2 20c0-3.5 2.7-6 6-6s6 2.5 6 6M12 20c0-3.5 1.8-6 4-6s6 2.5 6 6"/></svg></span><div><h3>S'engager</h3><p>pour une cause utile et inspirante.</p></div></div>
      <div class="value"><span class="ico"><svg viewBox="0 0 24 24"><path d="M3 12l4-4 5 3 5-3 4 4-9 7z"/><path d="M8 13l3 3M11 11l3 3"/></svg></span><div><h3>Développer</h3><p>vos compétences et votre réseau.</p></div></div>
      <div class="value"><span class="ico"><svg viewBox="0 0 24 24"><circle cx="12" cy="5" r="2.5"/><circle cx="5" cy="18" r="2.5"/><circle cx="19" cy="18" r="2.5"/><path d="M12 8v4M12 12l-5 4M12 12l5 4"/></svg></span><div><h3>Collaborer</h3><p>avec une équipe dynamique et passionnée.</p></div></div>
      <div class="value"><span class="ico"><svg viewBox="0 0 24 24"><path d="M14 4c3-1 6-1 6-1s0 3-1 6l-6 6-5-5z"/><path d="M8 10l-4 1 2 3M14 16l-1 4-3-2M5 19c1-2 2-3 3-3"/></svg></span><div><h3>Faire la différence</h3><p>pour promouvoir le numérique au féminin.</p></div></div>
    </div>
  </div>
</section>
