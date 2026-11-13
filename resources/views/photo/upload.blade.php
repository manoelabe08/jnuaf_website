<!doctype html>

<html lang="fr">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<meta name="robots" content="noindex,nofollow">

<title>Envoyer ma photo &middot; JNUAF</title>

<style>

:root{--g:#14592A;--bg:#F4F8F4;--o:#E8761B}

*{box-sizing:border-box}

body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--bg);font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1d2b21;padding:24px}

main{max-width:520px;width:100%;background:#fff;border-radius:18px;padding:32px 28px;box-shadow:0 8px 30px rgba(20,89,42,.12)}

img.logo{height:56px;width:auto;margin-bottom:10px}

h1{font-size:1.4rem;margin:6px 0 10px;color:var(--g)}

p{line-height:1.55;margin:10px 0}

label{display:block;font-weight:700;margin:16px 0 6px}

input[type=file]{width:100%;padding:10px;border:2px dashed var(--g);border-radius:10px;background:#f8fbf8}

.consent{display:flex;gap:10px;align-items:flex-start;font-weight:400;font-size:.92rem}

.btn{display:inline-block;margin-top:18px;padding:12px 20px;border-radius:10px;border:2px solid var(--g);background:var(--g);color:#fff;font:inherit;font-weight:700;cursor:pointer}

.btn:focus-visible,input:focus-visible{outline:3px solid var(--o);outline-offset:2px}

.err{background:#fdecea;color:#b42318;border-radius:10px;padding:10px 14px;margin:12px 0}

.tips{font-size:.92rem;color:#4a5a4f}

</style>

</head>

<body>

<main>

<img class="logo" src="{{ asset('images/logo.jpg') }}" alt="JNUAF" width="56" height="56">

@if ($state === 'done')

<h1>Merci{{ $first !== '' ? ' '.$first : '' }} !</h1>

<p>Votre photo a bien &eacute;t&eacute; enregistr&eacute;e. Elle servira uniquement &agrave; votre carte de membre et &agrave; votre profil. Vous pouvez fermer cette page.</p>

@elseif ($state === 'invalid')

<h1>Lien invalide ou expir&eacute;</h1>

<p>Ce lien n&#39;est plus valable (d&eacute;j&agrave; utilis&eacute; ou expir&eacute;). Contactez le Bureau de Pilotage pour en recevoir un nouveau.</p>

@else

<h1>Bonjour{{ $first !== '' ? ' '.$first : '' }}</h1>

<p>Pour pr&eacute;parer votre carte de membre et votre profil, envoyez votre photo ci-dessous.</p>

<p class="tips">Portrait net, visage d&eacute;gag&eacute; et de face, fond clair, format vertical si possible.</p>


@if (! empty($reason))

<div class="err" role="alert"><strong>Votre pr&eacute;c&eacute;dente photo n&#39;a pas &eacute;t&eacute; retenue :</strong> {{ $reason }}<br>Merci d&#39;envoyer une photo d&#39;identit&eacute; pr&eacute;sentable : elle sert &agrave; votre carte de membre.</div>

@endif


@if ($errors->any())

<div class="err" role="alert">{{ $errors->first() }}</div>

@endif

<form method="POST" action="{{ route('photo.store', ['token' => $token]) }}" enctype="multipart/form-data">

@csrf

<label for="photo">Votre photo (JPG, PNG ou WebP, 8 Mo au plus)</label>

<input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required>

<label class="consent" for="consent"><input id="consent" type="checkbox" name="consent" value="1" required> <span>J&#39;autorise la JNUAF &agrave; utiliser cette photo pour ma carte de membre et mon profil. Je peux en demander la suppression &agrave; tout moment.</span></label>

@if (! empty($askCv))
<label for="cv">Votre CV (PDF, 4 Mo au plus, facultatif)</label>
<input id="cv" name="cv" type="file" accept="application/pdf">
@endif
<button class="btn" type="submit">Envoyer ma photo</button>

</form>

@endif

</main>

</body>

</html>

