
<!doctype html>

<html lang="fr">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="robots" content="noindex,nofollow">

<title>Changer mon mot de passe — JNUAF.ORG</title>

<style>

  body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f3f7f4;color:#1d2b22;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:20px}

  .box{width:100%;max-width:440px;background:#fff;border-radius:14px;padding:28px;box-shadow:0 6px 24px rgba(20,89,42,.12)}

  h1{margin:0 0 8px;font-size:22px;color:#14592A}

  p{line-height:1.5;margin:0 0 14px}

  label{display:block;font-weight:600;margin:14px 0 6px}

  input{width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid #c9d6cd;border-radius:9px;font-size:15px}

  button{margin-top:20px;width:100%;padding:12px;border:0;border-radius:9px;background:#14592A;color:#fff;font-size:15px;font-weight:600;cursor:pointer}

  .err{background:#fdecea;color:#8a1c12;padding:10px 12px;border-radius:9px;margin-bottom:12px;font-size:14px}

  .hint{font-size:13px;color:#566;margin-top:6px}

  .out{margin-top:16px;text-align:center}

  .out button{background:none;color:#14592A;width:auto;padding:4px;font-weight:500;text-decoration:underline;margin:0}

</style>

</head>

<body>

<div class="box">

  <h1>{{ $forced ? 'Choisissez votre mot de passe' : 'Changer mon mot de passe' }}</h1>

  @if($forced)

  <p>Pour la sécurité de votre accès administrateur, vous devez remplacer le mot de passe temporaire avant de continuer.</p>
  @endif

  @if($errors->any())
  <div class="err">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
  @endif

  <form method="POST" action="{{ route('admin.password.change.update') }}">
    @csrf
    <label for="current_password">Mot de passe actuel</label>
    <input id="current_password" type="password" name="current_password" required autocomplete="current-password">

    <label for="password">Nouveau mot de passe</label>
    <input id="password" type="password" name="password" required minlength="12" autocomplete="new-password">
    <div class="hint">12 caractères minimum, sans règle de composition : une phrase de passe longue est idéale. Les mots de passe courants ou déjà compromis sont refusés, ainsi que toute suite de 4 caractères de l’ancien.</div>

    <label for="password_confirmation">Confirmer le nouveau mot de passe</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" data-nopaste>

    <button type="submit">Enregistrer</button>
  </form>

  <form class="out" method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit">Me déconnecter</button>
  </form>
</div>
<script src="{{ asset('js/admin-ui.js') }}?v2" defer></script>
</body>
</html>
