<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive"><meta name="referrer" content="no-referrer">
@include('partials.favicons')
<title>Newsletter · JNUAF</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#F7FAF6;color:#13281A;font:16px/1.7 Arial,Helvetica,sans-serif}
main{width:min(760px,calc(100% - 32px));margin:36px auto}.card{background:white;padding:30px;border:1px solid #DCE6DD;border-radius:16px;margin:20px 0}
header{padding-bottom:18px;border-bottom:4px solid #F26B1D;text-align:center}header img{width:100px;height:auto}h1,h2{color:#14592A;line-height:1.3}h1{font-size:28px;overflow-wrap:anywhere}
a{color:#14592A}.btn,button{display:inline-block;padding:12px 20px;border:0;border-radius:99px;background:#F26B1D;color:white;font:700 15px Arial;text-decoration:none;cursor:pointer}
:focus-visible{outline:3px solid #F26B1D;outline-offset:4px}.muted,footer{font-size:13px;color:#55685B}.alert{padding:16px;background:#E3F1E5;border-radius:10px}
@media(max-width:600px){.card{padding:22px}h1{font-size:24px}}
</style>
</head>
<body><main>
<header>
    @if(is_file(public_path('images/mail-logo.png')))
        <img src="{{ asset('images/mail-logo.png') }}" alt="JNUAF">
    @elseif(is_file(public_path('images/logo.jpg')))
        <img src="{{ asset('images/logo.jpg') }}" alt="JNUAF">
    @else
        <p style="font-size:26px;font-weight:bold;color:#14592A">JNUAF</p>
    @endif
</header>
@yield('content')
<footer>
    <p>Journée du Numérique au Féminin · <a href="{{ route('home') }}">Site JNUAF</a></p>
    <p>Contact : <a href="mailto:{{ config('partner_portal.contact_email') }}">{{ config('partner_portal.contact_email') }}</a> · <a href="{{ route('home') }}#confidentialite">Confidentialité</a></p>
</footer>
</main></body></html>
