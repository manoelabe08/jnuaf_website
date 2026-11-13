
<!doctype html>

<html lang="fr">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Carte de membre · Informations d'utilisation · JNUAF</title>

<style>

  body { margin: 0; background: #F7FAF6; font-family: Arial, Helvetica, sans-serif; color: #13281A; line-height: 1.65; }

  .wrap { max-width: 760px; margin: 0 auto; padding: 28px 18px 48px; }

  .logo { display: block; margin: 0 auto 20px; height: 70px; width: auto; background: #fff; padding: 4px 12px; border-radius: 10px; }

  h1 { margin: 0 0 6px; color: #14592A; font-size: 28px; line-height: 1.3; }

  .lead { margin: 0 0 26px; color: #55685B; }

  section { background: #fff; border: 1px solid #DCE6DD; border-radius: 14px; padding: 18px 22px; margin-bottom: 14px; }

  h2 { margin: 0 0 8px; color: #14592A; font-size: 19px; }

  p, li { font-size: 15px; }

  p { margin: 0 0 10px; }

  ul { margin: 0 0 10px; padding-left: 20px; }

  a { color: #14592A; }

  .bar { height: 5px; background: #F26B1D; border-radius: 3px; margin: 0 0 18px; }

</style>

</head>

<body>

<div class="wrap">

  <img class="logo" src="{{ asset('images/logo.jpg') }}" alt="JNUAF">

  <div class="bar"></div>

  <h1>Carte de membre JNUAF</h1>

  <p class="lead">Informations d'utilisation de la carte de membre de JNUAF.ORG.</p>



  <section>

    <h2>À quoi sert la carte</h2>

    <p>La carte de membre atteste l'appartenance de son titulaire à JNUAF.ORG. Elle est délivrée aux membres du Bureau de Pilotage, aux points focaux, aux membres du comité d'organisation, ainsi qu'aux intervenantes et mentors.</p>

  </section>



  <section>

    <h2>Durée de validité</h2>

    <ul>

      <li>Bureau de Pilotage, points focaux et comité d'organisation : 3 ans.</li>

      <li>Intervenantes et mentors : 1 an, renouvelable.</li>

    </ul>

    <p>La date de fin de validité figure au recto de la carte.</p>

  </section>



  <section>

    <h2>Utiliser la carte</h2>

    <ul>

      <li>La carte est personnelle et ne peut pas être cédée ni prêtée.</li>

      <li>Elle peut être présentée sur demande lors des événements et activités de JNUAF.ORG.</li>

      <li>Elle ne doit être ni modifiée ni reproduite. Évitez de publier une photo de votre carte, car son QR code donne accès à votre nom et à votre rôle.</li>

    </ul>

  </section>



  <section>

    <h2>Vérifier une carte</h2>

    <p>Le QR code au verso ouvre une page qui indique si la carte est valide, expirée ou révoquée, avec le nom du titulaire, sa catégorie, son territoire, son numéro et sa date de validité. Cette page n'affiche ni photo, ni adresse email, ni téléphone.</p>

    <p>Une carte indiquée comme expirée ou révoquée ne doit plus être acceptée comme justificatif.</p>

  </section>



  <section>

    <h2>Perte, vol ou changement de situation</h2>

    <p>En cas de perte ou de vol, écrivez-nous sans attendre à <a href="mailto:{{ $contact }}">{{ $contact }}</a> : la carte sera révoquée et une nouvelle carte pourra être émise.</p>

  </section>



  <section>

    <h2>Vos données</h2>

    <p>Pour établir la carte, nous utilisons votre nom, votre rôle, votre territoire, votre adresse email (pour vous envoyer la carte) et, uniquement avec votre accord, votre photo. Ces données servent uniquement à émettre, envoyer et vérifier la carte.</p>

    <p>Votre photo et les données de votre carte sont supprimées au plus tard {{ $retentionMonths }} mois après la fin de sa validité. Vous pouvez demander à tout moment l'accès à ces données, leur rectification ou leur suppression en écrivant à <a href="mailto:{{ $contact }}">{{ $contact }}</a>.</p>

  </section>



  <section>

    <h2>Une question ?</h2>

    <p>Pour toute question sur l'utilisation de cette carte, écrivez-nous à <a href="mailto:{{ $contact }}">{{ $contact }}</a>.</p>

  </section>

</div>

</body>

</html>

