
@extends('layouts.v2')

@section('title', 'Utilisation des cookies · JNUAF')



@section('content')

<section class="cookie-page">

  <div class="wrap">

    <h1>Utilisation des cookies</h1>

    <p class="cookie-lead">Dernière mise à jour : 27 octobre 2026.</p>



    <p>Le site de la JNUAF n'utilise que des cookies techniques, c'est-à-dire indispensables à son fonctionnement. Nous n'utilisons aucun cookie publicitaire, aucun outil de mesure d'audience et aucun contenu intégré d'un réseau social ou d'un service tiers. Nos polices de caractères et nos images sont hébergées sur le site lui-même.</p>



    <h2>Les cookies déposés par le site</h2>

    <div class="cookie-scroll">

      <table class="cookie-table">

        <thead>

          <tr><th>Nom</th><th>À quoi il sert</th><th>Durée</th></tr>

        </thead>

        <tbody>

          <tr>

            <td><code>{{ $sessionCookie }}</code></td>

            <td>Garde votre navigation cohérente d'une page à l'autre, par exemple pendant le remplissage d'un formulaire ou dans l'espace équipe.</td>

            <td>{{ $sessionMinutes }} minutes</td>

          </tr>

          <tr>

            <td><code>XSRF-TOKEN</code></td>

            <td>Protège les formulaires contre les envois frauduleux faits à votre insu.</td>

            <td>{{ $sessionMinutes }} minutes</td>

          </tr>

          <tr>

            <td><code>remember_web_…</code></td>

            <td>Vous évite de vous reconnecter à l'espace équipe. Il n'est déposé que si vous cochez « Rester connectée » lors de la connexion.</td>

            <td>{{ $rememberDays ? $rememberDays.' jours' : 'Durée limitée' }}</td>

          </tr>

          <tr>

            <td><code>jnuaf_cookie_notice</code></td>

            <td>Mémorise que vous avez pris connaissance du bandeau d'information, pour ne pas vous le réafficher à chaque page.</td>

            <td>6 mois</td>

          </tr>

        </tbody>

      </table>

    </div>



    <h2>Faut-il votre accord ?</h2>

    <p>Non. Ces cookies sont strictement nécessaires au fonctionnement du site ou à un service que vous demandez vous-même. Ils ne servent ni à vous suivre d'un site à l'autre, ni à établir un profil, ni à faire de la publicité. C'est pourquoi le bandeau se contente de vous informer.</p>



    <h2>Les gérer ou les supprimer</h2>

    <p>Vous pouvez à tout moment effacer les cookies depuis les réglages de votre navigateur (rubrique « Confidentialité » ou « Cookies »). Si vous le faites, vous serez déconnectée de l'espace équipe et le bandeau d'information s'affichera de nouveau. Bloquer totalement les cookies techniques peut empêcher le bon fonctionnement des formulaires.</p>



    <h2>Si cela change</h2>

    <p>Si nous ajoutons un jour un service qui dépose d'autres cookies, par exemple une mesure d'audience ou une vidéo intégrée, nous vous demanderons votre accord avant tout dépôt, avec un choix « Accepter » et « Refuser » aussi simple l'un que l'autre, et nous mettrons cette page à jour.</p>



    <h2>Une question ?</h2>

    <p>Écrivez-nous à <a href="mailto:team@jnuaf.org">team@jnuaf.org</a>. Retrouvez aussi notre <a href="{{ route('home') }}#confidentialite">politique de confidentialité</a>.</p>

  </div>

</section>

@endsection


<!-- jnuaf-s95 -->