<!doctype html>

<html lang="fr">

<body style="margin:0;padding:24px;background:#F4F8F4;font-family:Arial,Helvetica,sans-serif;color:#1d2b21">

<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;padding:28px">

  <h2 style="color:#14592A;margin-top:0">JNUAF &middot; Votre candidature au comit&eacute;</h2>

  <p>Bonjour {{ $application->full_name }},</p>

  @if ($proposal->from_pole && $proposal->from_pole !== 'Peu importe')

    <p>Merci pour votre candidature. Le p&ocirc;le &laquo;&nbsp;{{ $proposal->from_pole }}&nbsp;&raquo; que vous souhaitiez n'a plus de place disponible. Au vu de votre profil, nous vous proposons le p&ocirc;le &laquo;&nbsp;{{ $proposal->to_pole }}&nbsp;&raquo;.</p>

  @else

    <p>Merci pour votre candidature. Au vu de votre profil, nous vous proposons le p&ocirc;le &laquo;&nbsp;{{ $proposal->to_pole }}&nbsp;&raquo;.</p>

  @endif

  <p>Merci de nous dire si vous acceptez cette proposition :</p>

  <p><a href="{{ $url }}" style="display:inline-block;background:#14592A;color:#ffffff;padding:12px 22px;border-radius:999px;text-decoration:none;font-weight:bold">R&eacute;pondre &agrave; la proposition</a></p>

  <p style="font-size:13px;color:#5b6b60">Ce lien personnel est valable jusqu'au {{ $proposal->expires_at->format('d/m/Y') }}. Si vous ne r&eacute;pondez pas ou si vous refusez, notre &eacute;quipe reviendra vers vous pour trouver une autre solution.</p>

</div>

</body>

</html>

