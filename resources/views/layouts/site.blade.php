<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @if (config('jnuaf.noindex'))<meta name="robots" content="noindex,nofollow">@endif
  @php($pageTitle = trim($__env->yieldContent('title')) ?: 'JNUAF '.config('jnuaf.edition').' · Journée du Numérique au Féminin')
  @php($pageDescription = '#JNUAF.ORG agit pour la place des femmes dans le numérique, en France et en Afrique : Tournée du Numérique, mentorat, formations et la JNUAF '.config('jnuaf.edition').', son grand temps fort.')
  @include('partials.favicons')
  <title>{{ $pageTitle }}</title>
  <meta name="description" content="{{ $pageDescription }}">
  <link rel="canonical" href="{{ url()->current() }}">
  {{-- Aperçu lors d'un partage (WhatsApp, LinkedIn, Facebook, X…) --}}
  <meta property="og:type" content="website">
  <meta property="og:locale" content="fr_FR">
  <meta property="og:site_name" content="#JNUAF.ORG">
  <meta property="og:title" content="{{ $pageTitle }}">
  <meta property="og:description" content="{{ $pageDescription }}">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta property="og:image" content="{{ asset('images/partage.jpg') }}">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="Équipe JNUAF réunissant leurs mains : Journée du Numérique au Féminin {{ config('jnuaf.edition') }}">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}?v=1">
  <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=13">
  <link rel="stylesheet" href="{{ asset('css/cookie-banner.css') }}?v=1">
  <link rel="stylesheet" href="{{ asset('css/sponsors.css') }}?v=1">
</head>
<body>

<header class="nav">
  <div class="wrap">
    <a class="brand" href="{{ route('home') }}#top" aria-label="JNUAF, accueil">
      <img class="brand-logo" src="{{ asset('images/logo.jpg') }}" alt="JNUAF" width="200" height="200">
    </a>
    <nav id="mainNav" aria-label="Navigation principale">
      <ul>
        <li><a href="{{ route('home') }}#qui-sommes-nous">Qui sommes-nous</a></li>
        <li><a href="{{ route('home') }}#programmes">Programmes</a></li>
        <li><a href="{{ route('home') }}#evenement">JNUAF {{ config('jnuaf.edition') }}</a></li>
        <li><a href="{{ route('home') }}#comite">Comité</a></li>
        <li><a href="{{ route('home') }}#partenaires">Partenaires</a></li>
        <li><a href="{{ route('home') }}#faq">FAQ</a></li>
      </ul>
    </nav>
    <div class="nav-actions">
      <button type="button" id="theme-toggle" aria-label="Changer le mode d'affichage" title="Mode sombre ou clair" style="background:none;border:1.5px solid var(--line);border-radius:999px;padding:6px 10px;color:var(--ink);font:inherit;cursor:pointer">&#9790;</button>
      <a class="btn btn-orange" href="{{ route('home') }}#inscription">S'inscrire</a>
      <button class="burger" type="button" aria-controls="mainNav" aria-expanded="false" aria-label="Ouvrir le menu"><span></span></button>
    </div>
  </div>
</header>

@if (session('flash'))
  <div class="flash" role="status"><div class="wrap">{{ session('flash') }}</div></div>
@endif

<main id="top">
  @yield('content')
</main>

<footer>
  <div class="wrap">
    <div style="display:grid;gap:12px">
      <span class="brand"><img class="brand-logo" src="{{ asset('images/logo.jpg') }}" alt="JNUAF" width="200" height="200"></span>
      <p style="opacity:.8;max-width:42ch">
        @foreach (config('jnuaf.contacts') as $c)
          {{ $c['label'] }} : <a class="call-link" href="tel:{{ preg_replace('/[^\d+]/', '', $c['phone']) }}" data-call="{{ $c['phone'] }}">{{ $c['phone'] }}</a>@if (! $loop->last)<br>@endif
        @endforeach
      </p>

      <x-social-links />

    </div>
    <div><h4>{{ config('jnuaf.organisation.name') }}</h4><ul><li><a href="{{ route('home') }}#qui-sommes-nous">Qui sommes-nous</a></li><li><a href="{{ route('home') }}#programmes">Programmes</a></li><li><a href="{{ route('home') }}#organisation">Gouvernance</a></li><li><a href="{{ route('home') }}#territoires">Territoires</a></li><li><a href="{{ route('home') }}#partenaires">Devenir partenaire</a></li></ul></div>
    <div><h4>Pratique</h4><ul><li><a href="{{ route('home') }}#evenement">JNUAF {{ config('jnuaf.edition') }}</a></li><li><a href="{{ route('home') }}#inscription">Inscription</a></li><li><a href="{{ route('home') }}#galerie">Galerie</a></li><li><a href="{{ route('home') }}#comite">Rejoindre le comité</a></li><li><a href="{{ route('home') }}#faq">FAQ</a></li><li><a href="{{ route('home') }}#confidentialite">Confidentialité</a></li><li><a href="{{ route('cookies') }}">Cookies</a></li><li><a href="{{ route('login') }}">Espace équipe</a></li></ul></div>
    <p class="fine"><span>© {{ config('jnuaf.edition') }} JNUAF · Journée du Numérique au Féminin</span><span>Pôle Tech &amp; Outils Numériques</span></p>
  </div>
</footer>

<script src="{{ asset('js/site.js') }}?v=9" defer></script>

<script src="{{ asset('js/call-confirm.js') }}?v=1" defer></script>

@include('partials.cookie-banner')


<script>/* jnuaf-s56 */

(function(){

  var b=document.getElementById('theme-toggle'); if(!b){return;}

  var set=function(m){document.documentElement.setAttribute('data-theme',m);try{localStorage.setItem('jnuaf-theme',m)}catch(e){} b.textContent=m==='dark'?'\u2600':'\u263E';};

  var cur=(function(){try{return localStorage.getItem('jnuaf-theme')||'auto'}catch(e){return 'auto'}})();

  var dark=cur==='dark'||(cur==='auto'&&window.matchMedia('(prefers-color-scheme: dark)').matches);

  document.documentElement.setAttribute('data-theme', dark?'dark':'light');

  b.textContent=dark?'\u2600':'\u263E';

  b.addEventListener('click',function(){var isDark=document.documentElement.getAttribute('data-theme')==='dark';var next=isDark?'light':'dark';cur=next;set(next);});

})();

</script>

<link rel="stylesheet" href="{{ asset('vendor/jnuaf-chatbot/chatbot.css') }}?v=s107">
<script src="{{ asset('vendor/jnuaf-chatbot/chatbot.js') }}?v=s107" data-base="{{ url('chatbot') }}" defer></script>
</body>
</html>
