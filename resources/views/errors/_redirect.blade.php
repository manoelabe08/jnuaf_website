
<!doctype html>

<html lang="fr">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<meta name="robots" content="noindex,nofollow">

<title>{{ $code }} &middot; {!! $title !!} &middot; JNUAF</title>

<style>

:root{--g:#14592A;--bg:#F4F8F4;--o:#E8761B}

*{box-sizing:border-box}

body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--bg);font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1d2b21;padding:24px}

main{max-width:520px;width:100%;background:#fff;border-radius:18px;padding:36px 28px;text-align:center;box-shadow:0 8px 30px rgba(20,89,42,.12)}

img{height:56px;width:auto;margin-bottom:12px}

.code{font-size:4.5rem;font-weight:900;line-height:1;color:var(--g);margin:6px 0}

h1{font-size:1.4rem;margin:8px 0}

p{line-height:1.55;margin:10px 0}

.actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:18px}

.btn{display:inline-block;padding:11px 18px;border-radius:10px;border:2px solid var(--g);background:var(--g);color:#fff;text-decoration:none;font:inherit;font-weight:700;cursor:pointer}

.btn.ghost{background:#fff;color:var(--g)}

.btn:focus-visible{outline:3px solid var(--o);outline-offset:2px}

</style>

</head>

<body>

<main data-redirect="https://jnuaf.org/me/public" data-seconds="{{ $seconds ?? 10 }}">

<img src="{{ asset('images/logo.jpg') }}" alt="JNUAF" width="56" height="56">

<div class="code" aria-hidden="true">{{ $code }}</div>

<h1>Erreur {{ $code }} &ndash; {!! $title !!}</h1>

<p>{!! $message !!}</p>

@if (! empty($auto))

<p id="redirect-line" hidden>Redirection vers le site principal dans <strong id="countdown">{{ $seconds ?? 10 }}</strong> secondes.</p>

@endif

<div class="actions">

<a class="btn" href="https://jnuaf.org/me/public" id="go">@if (! empty($auto))Retourner maintenant @else Retourner &agrave; l&#39;accueil @endif</a>

@if (! empty($auto))

<button class="btn ghost" type="button" id="stay" hidden>Rester sur cette page</button>

@endif

</div>

</main>

@if (! empty($auto))

<script src="{{ asset('js/error-redirect.js') }}?v=1" defer></script>

@endif

</body>

</html>


{{-- jnuaf-s47 --}}
