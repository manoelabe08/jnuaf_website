<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>{{ $title }} — JNUAF.ORG</title>
<style>
  body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f3f7f4;color:#1d2b22;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:20px}
  .box{width:100%;max-width:480px;background:#fff;border-radius:14px;padding:28px;box-shadow:0 6px 24px rgba(20,89,42,.12)}
  h1{margin:0 0 14px;font-size:21px;color:#14592A}
  p{line-height:1.5;margin:0 0 14px}
  dl{margin:0 0 16px;padding:12px 14px;background:#E3F1E5;border-radius:10px}
  dt{font-size:12px;color:#456;margin-top:8px}
  dt:first-child{margin-top:0}
  dd{margin:2px 0 0;font-weight:600;word-break:break-all}
  .row{display:flex;gap:10px;flex-wrap:wrap}
  button{padding:12px 18px;border:0;border-radius:9px;background:#14592A;color:#fff;font-size:15px;font-weight:600;cursor:pointer}
  button.no{background:#fff;color:#8a1c12;border:1px solid #8a1c12}
  .bad{color:#8a1c12}
</style>
</head>
<body>
<div class="box">
  <h1>{{ $title }}</h1>

  @if($mode === 'result')
    <p class="{{ $ok ? '' : 'bad' }}">{{ $message }}</p>
  @else
    <dl>
      @foreach($details as $label => $value)
      <dt>{{ $label }}</dt><dd>{{ $value }}</dd>
      @endforeach
    </dl>
    <form method="POST" action="{{ $action }}">
      @csrf
      @if($mode === 'confirm')
        <p>Confirmez que cette nouvelle adresse vous appartient.</p>
        <button type="submit">Confirmer cette adresse</button>
      @else
        <p>Sans votre décision, la demande expire et l'ancienne adresse reste active.</p>
        <div class="row">
          <button type="submit" name="decision" value="approve">Accepter</button>
          <button type="submit" name="decision" value="refuse" class="no">Refuser</button>
        </div>
      @endif
    </form>
  @endif
</div>
</body>
</html>
