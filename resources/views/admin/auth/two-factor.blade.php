<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Vérification — JNUAF.ORG</title>
<style>
  body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f3f7f4;color:#1d2b22;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:20px}
  .box{width:100%;max-width:420px;background:#fff;border-radius:14px;padding:28px;box-shadow:0 6px 24px rgba(20,89,42,.12)}
  h1{margin:0 0 8px;font-size:22px;color:#14592A}
  p{line-height:1.5;margin:0 0 14px}
  label{display:block;font-weight:600;margin:14px 0 6px}
  input[type=text]{width:100%;box-sizing:border-box;padding:12px;border:1px solid #c9d6cd;border-radius:9px;font-size:20px;letter-spacing:4px;text-align:center}
  button.main{margin-top:16px;width:100%;padding:12px;border:0;border-radius:9px;background:#14592A;color:#fff;font-size:15px;font-weight:600;cursor:pointer}
  .err{background:#fdecea;color:#8a1c12;padding:10px 12px;border-radius:9px;margin-bottom:12px;font-size:14px}
  .ok{background:#E3F1E5;color:#14592A;padding:10px 12px;border-radius:9px;margin-bottom:12px;font-size:14px}
  .links{display:flex;justify-content:space-between;margin-top:14px}
  .links button{background:none;border:0;color:#14592A;text-decoration:underline;cursor:pointer;font-size:14px;padding:0}
  .hint{font-size:13px;color:#566}
</style>
</head>
<body>
<div class="box">
  <h1>Vérification en deux étapes</h1>
  @if($totp)
  <p>Saisissez le code à 6 chiffres affiché par votre application d’authentification (Google Authenticator, Microsoft Authenticator…).</p>
  @else
  <p>Un code à 6 chiffres a été envoyé à <b>{{ $maskedEmail }}</b>. Il est valable 10 minutes.</p>
  @endif

  @if(session('status'))<div class="ok">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="err">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

  <form method="POST" action="{{ route('two-factor.verify') }}">
    @csrf
    <label for="code">Code de vérification</label>
    <input id="code" type="text" name="code" required maxlength="20" autocomplete="one-time-code" autofocus>
    <div class="hint">Vous pouvez aussi saisir un code de secours (format XXXX-XXXX).</div>
    <button class="main" type="submit">Valider</button>
  </form>

  <div class="links">
    @if($emailFallback)<form method="POST" action="{{ route('two-factor.resend') }}">@csrf<button type="submit">{{ $totp ? 'Recevoir un code par email' : 'Renvoyer le code' }}</button></form>@else<span></span>@endif
    <form method="POST" action="{{ route('two-factor.cancel') }}">@csrf<button type="submit">Annuler</button></form>
  </div>
</div>
</body>
</html>
