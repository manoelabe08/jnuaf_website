<!doctype html>

<html lang="fr">

<body style="margin:0;padding:24px;background:#F4F8F4;font-family:Arial,Helvetica,sans-serif;color:#1d2b21">

<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;padding:28px">

  <h2 style="color:#14592A;margin-top:0">JNUAF &middot; Votre lien de connexion</h2>

  <p>Bonjour {{ $user->name }},</p>

  <p>Vous avez demand&eacute; &agrave; vous connecter &agrave; votre espace. Cliquez sur le bouton ci-dessous pour continuer :</p>

  <p><a href="{{ $url }}" style="display:inline-block;background:#14592A;color:#ffffff;padding:12px 22px;border-radius:999px;text-decoration:none;font-weight:bold">Me connecter</a></p>

  <p style="font-size:13px;color:#5b6b60">Ce lien est personnel, valable {{ $minutes }} minutes et utilisable une seule fois. Vous devrez ensuite saisir votre mot de passe. Si vous n'&ecirc;tes pas &agrave; l'origine de cette demande, ignorez simplement ce message.</p>

</div>

</body>

</html>

