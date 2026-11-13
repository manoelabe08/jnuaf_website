<!doctype html>

<html lang="fr">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<meta name="robots" content="noindex,nofollow">

<title>Proposition de p&ocirc;le &middot; JNUAF</title>

<style>

:root{--g:#14592A;--bg:#F4F8F4;--o:#E8761B}

*{box-sizing:border-box}

body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--bg);font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1d2b21;padding:20px}

main{max-width:540px;width:100%;background:#fff;border-radius:18px;padding:34px 28px;text-align:center;box-shadow:0 8px 30px rgba(20,89,42,.12)}

img{height:56px;width:auto;margin-bottom:12px}

h1{font-size:1.4rem;margin:8px 0}

p{line-height:1.55;margin:10px 0}

.pole{display:inline-block;background:#E8F1EA;color:var(--g);padding:6px 14px;border-radius:999px;font-weight:700}

.actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:20px}

button{padding:12px 20px;border-radius:999px;border:2px solid var(--g);background:var(--g);color:#fff;font:inherit;font-weight:700;cursor:pointer}

button.ghost{background:#fff;color:var(--g)}

button:focus-visible{outline:3px solid var(--o);outline-offset:2px}

</style>

</head>

<body>

<main>

<img src="{{ asset('images/logo.jpg') }}" alt="JNUAF" width="56" height="56">

@if ($state === 'ask')

  <h1>Bonjour {{ $application->full_name }}</h1>

  @if ($proposal->from_pole && $proposal->from_pole !== 'Peu importe')

    <p>Le p&ocirc;le &laquo;&nbsp;{{ $proposal->from_pole }}&nbsp;&raquo; que vous souhaitiez n'a plus de place disponible.</p>

  @endif

  <p>Nous vous proposons de rejoindre le p&ocirc;le :</p>

  <p><span class="pole">{{ $proposal->to_pole }}</span></p>

  <form method="POST" action="{{ route('committee.pole.answer', request()->route('token')) }}">

    @csrf

    <div class="actions">

      <button type="submit" name="choice" value="accept">J'accepte ce p&ocirc;le</button>

      <button type="submit" name="choice" value="decline" class="ghost">Je refuse</button>

    </div>

  </form>

  <p style="font-size:.85rem;color:#5b6b60">Ce lien est personnel et valable jusqu'au {{ $proposal->expires_at->format('d/m/Y') }}.</p>

@elseif ($state === 'accepted')

  <h1>Merci, {{ $application->full_name }} !</h1>

  <p>Votre p&ocirc;le est enregistr&eacute; : <span class="pole">{{ $proposal->to_pole }}</span></p>

  <p>Il appara&icirc;tra sur votre profil et sur votre badge.</p>

@elseif ($state === 'declined')

  <h1>Merci pour votre r&eacute;ponse</h1>

  <p>Nous avons bien not&eacute; que vous ne souhaitez pas ce p&ocirc;le. Un membre de l'&eacute;quipe reviendra vers vous pour trouver une autre solution.</p>

@else

  <h1>Lien non valide</h1>

  <p>Ce lien n'est plus valide : il a d&eacute;j&agrave; &eacute;t&eacute; utilis&eacute; ou il a expir&eacute;. Si vous avez une question, r&eacute;pondez simplement au dernier e-mail que vous avez re&ccedil;u de notre &eacute;quipe.</p>

@endif

</main>

</body>

</html>

