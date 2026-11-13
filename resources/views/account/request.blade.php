<!doctype html>

<html lang="fr">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<meta name="robots" content="noindex,nofollow">

<title>Mon espace &middot; JNUAF</title>

<style>

:root{--g:#14592A;--bg:#F4F8F4;--o:#E8761B}

*{box-sizing:border-box}

body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--bg);font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1d2b21;padding:20px}

main{max-width:460px;width:100%;background:#fff;border-radius:18px;padding:34px 28px;text-align:center;box-shadow:0 8px 30px rgba(20,89,42,.12)}

img{height:56px;width:auto;margin-bottom:12px}

h1{font-size:1.4rem;margin:8px 0}

p{line-height:1.55;margin:10px 0}

input{width:100%;padding:12px 14px;border:1.5px solid #cfdacf;border-radius:10px;font:inherit;margin-top:10px}

input:focus-visible,button:focus-visible{outline:3px solid var(--o);outline-offset:2px}

button{margin-top:14px;width:100%;padding:12px 20px;border-radius:999px;border:2px solid var(--g);background:var(--g);color:#fff;font:inherit;font-weight:700;cursor:pointer}

.ok{background:#E8F1EA;color:var(--g);border-radius:10px;padding:12px;margin-top:14px}

.warn{background:#FDECEA;color:#B42318;border-radius:10px;padding:12px;margin-top:14px}

.muted{font-size:.85rem;color:#5b6b60}

</style>

</head>

<body>

<main>

<img src="{{ asset('images/logo.jpg') }}" alt="JNUAF" width="56" height="56">

<h1>Mon espace JNUAF</h1>

<p>Saisissez l'adresse e-mail de votre compte. Nous vous enverrons un lien de connexion personnel.</p>

@if ($sent)

  <div class="ok">Si un compte existe pour cette adresse, un lien de connexion vient d'&ecirc;tre envoy&eacute;. Il est valable 15 minutes.</div>

@endif

@if ($expired)

  <div class="warn">Ce lien n'est plus valable. Demandez-en un nouveau ci-dessous.</div>

@endif

@if ($errors->any())

  <div class="warn">{{ $errors->first() }}</div>

@endif

<form method="POST" action="{{ route('account.send') }}">

  @csrf

  <input type="email" name="email" value="{{ old('email') }}" placeholder="Votre adresse e-mail" autocomplete="email" required>

  <button type="submit">Recevoir mon lien de connexion</button>

</form>

<p class="muted">Ce lien est personnel et ne fonctionne qu'une seule fois.</p>

</main>

</body>

</html>

