@php($legal = fn (string $key, string $fallback) => config('jnuaf.legal.'.$key) ?: $fallback)
<section id="confidentialite" style="padding-top:0">
  <div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">Vos données</p>
      <h2>Confidentialité <span class="o">et transparence</span></h2>
      <p class="lede">Ce que nous collectons, pourquoi, et ce que vous pouvez demander.</p>
    </div>
    <div class="privacy">
      <div><h3>Ce que nous collectons</h3><p>Identité, coordonnées, territoire et les informations liées à votre profil. Rien de plus.</p></div>
      <div><h3>Pourquoi</h3><p>Gérer votre inscription, vous envoyer confirmation, badge et rappels, mesurer la participation.</p></div>
      <div><h3>Qui y a accès</h3><p>Seule la coordination de votre territoire et le Bureau de Pilotage. Aucune revente, aucun partage commercial.</p></div>
      <div><h3>Vos droits</h3><p>Consultation, rectification et suppression directement depuis le lien personnel reçu par email. Votre consentement à l'image peut être retiré à tout moment.</p></div>
    </div>
    <div class="faq" style="margin-top:28px">
      <details><summary>Politique de confidentialité</summary>
        <p><b>Responsable du traitement :</b> {{ $legal('controller', 'Bureau de Pilotage de la JNUAF [structure juridique et adresse à compléter]') }}</p>
        <p><b>Base légale :</b> votre consentement, recueilli lors de l'inscription.</p>
        <p><b>Durée de conservation :</b> inscriptions anonymisées {{ config('jnuaf.privacy.retention_months') }} mois après l'événement (seules des statistiques sans nom sont conservées) ; candidatures au comité supprimées, avec leurs pièces jointes, {{ config('jnuaf.privacy.committee_retention_months') }} mois après la décision.</p>
        <p><b>Sécurité :</b> connexion HTTPS, accès par rôle et par territoire, journalisation des exports, pièces jointes stockées hors de l'espace public.</p>
        <p><b>Cadre :</b> la collecte couvre la Côte d'Ivoire, le Sénégal, la France et le Maroc. Chaque pays a sa propre législation sur les données personnelles ; la politique définitive est validée par le Bureau de Pilotage.</p>
        <p><b>Contact :</b> @if (config('jnuaf.legal.privacy_email'))<a href="mailto:{{ config('jnuaf.legal.privacy_email') }}">{{ config('jnuaf.legal.privacy_email') }}</a>@else[adresse email dédiée à la protection des données, à compléter]@endif</p>
      </details>
      <details><summary>Mentions légales</summary>
        <p><b>Éditeur :</b> {{ $legal('publisher', 'JNUAF, Journée du Numérique au Féminin [forme juridique, siège et numéro d\'enregistrement à compléter]') }}</p>
        <p><b>Directeur·rice de la publication :</b> {{ $legal('director', '[à compléter]') }}</p>
        <p><b>Hébergeur :</b> {{ $legal('host', '[nom, adresse et contact de l\'hébergeur à compléter]') }}</p>
        <p><b>Contact :</b> {{ collect(config('jnuaf.contacts'))->map(fn ($c) => $c['label'].' '.$c['phone'])->implode(' · ') }}</p>
      </details>
    </div>
  </div>
</section>

<section id="faq" style="padding-top:0">
  <div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">Questions fréquentes</p>
      <h2>Bon <span class="o">à savoir</span></h2>
    </div>
    <div class="faq">
      <details open><summary>Comment rejoindre le comité d'organisation ?</summary><p>Déposez votre CV et une lettre de motivation (PDF) dans le formulaire de la section <a href="#comite">Comité</a>, ou envoyez-les par WhatsApp. Les candidatures féminines sont vivement encouragées.</p></details>
      <details><summary>Dois-je créer un compte pour m'inscrire ?</summary><p>Non. Le formulaire est public : vous le remplissez une fois et recevez votre confirmation par email, avec un lien personnel pour modifier ou annuler.</p></details>
      <details><summary>L'inscription est-elle payante ?</summary><p>Aucun paiement n'est demandé lors de l'inscription. Toute évolution sera annoncée sur ce site.</p></details>
      <details><summary>Comment fonctionne le badge ?</summary><p>Votre badge PDF avec un QR code personnel est joint à l'email de confirmation. Le QR code ne contient aucune donnée personnelle en clair : il sert uniquement à valider votre entrée.</p></details>
      <details><summary>Que deviennent mes données ?</summary><p>Elles servent uniquement à l'organisation de l'événement et ne sont visibles que par les équipes concernées. Vous pouvez les consulter ou les supprimer depuis votre lien personnel.</p></details>
    </div>
  </div>
</section>
