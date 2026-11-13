<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex,nofollow">
  @include('partials.favicons')
  <title>@yield('title') · Admin JNUAF</title>
  <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}?v=1">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=6">
  <link rel="stylesheet" href="{{ asset('css/admin-nav.css') }}?v=1">
</head>
<body>
@php($u = auth()->user())
<div class="shell">
  
<?php

  $pending = $u->isSuperAdmin()

      ? \App\Models\SensitiveAction::where('status', 'pending')->where('expires_at', '>', now())->count()

      : 0;

  $navGroups = [

    ['label' => "\u{c9}v\u{e9}nements", 'items' => [

      ['admin.events.index', 'admin.events.*', "\u{c9}v\u{e9}nements", true],

      ['admin.territories.index', 'admin.territories.*', 'Territoires', true],

      ['admin.program.index', 'admin.program.*', 'Programme', true],

      ['admin.speakers.index', 'admin.speakers.*', 'Intervenantes', true],

      ['admin.registrations.index', 'admin.registrations.*', 'Inscriptions', false],

      ['admin.checkin.index', 'admin.checkin.*', 'Pointage', false],

    ]],

    ['label' => "Communaut\u{e9}", 'items' => [

      ['admin.committee.index', 'admin.committee.*', "Candidatures comit\u{e9}", true],
      ['admin.grades.index', 'admin.grades.*', "Grades du comit\u{e9}", true],

      ['admin.cards.index', 'admin.cards.*', 'Cartes de membre', true],

      ['admin.partners.index', 'admin.partners.*', 'Demandes partenaires', true],

      ['admin.partner-portal.index', 'admin.partner-portal.*', 'Documents partenaires', true],

      ['admin.sponsors.index', 'admin.sponsors.*', 'Bandeau partenaires', true],

    ]],

    ['label' => 'Communication', 'items' => [

      ['admin.newsletter.index', 'admin.newsletter.*', 'Newsletter', true],

      ['admin.mailbox.inbox', 'admin.mailbox.*', 'Messagerie', true],

      ['admin.chat.show', 'admin.chat.*', 'Discussions', false],

      ['admin.posts.index', 'admin.posts.*', 'Articles du blog', true],
      ['admin.pages.index', 'admin.pages.*', 'Pages publiques', true],
      ['admin.gallery.index', 'admin.gallery.*', 'Galerie', true],
      ['admin.chatbot.index', 'admin.chatbot.*', 'Chatbot', true],

    ]],

    ['label' => 'Administration', 'items' => [

      ['admin.sensitive.index', 'admin.sensitive.*', 'Validations', true, true],
      ['admin.trash.index', 'admin.trash.*', 'Corbeille', true, false],

      ['admin.users.index', 'admin.users.*', "Comptes & exports", true],

      ['admin.security.index', 'admin.security.*', "Journal de s\u{e9}curit\u{e9}", true],

      ...(auth()->user()?->isSuperAdmin() ? [['admin.audit.rights', 'admin.audit.*', 'Audit des droits', true]] : []),
      ['admin.settings.edit', 'admin.settings.*', "Param\u{e8}tres", true],

    ]],

  ];

  $navGroups = collect($navGroups)->map(function ($g) use ($u) {

      $g['items'] = array_values(array_filter($g['items'], fn ($i) => $u->isAdmin() || ! $i[3]));

      return $g;

  })->filter(fn ($g) => count($g['items']) > 0)->values()->all();

?>

<aside class="side" id="admin-side">

  <div class="side-top">

    <a class="brand" href="{{ route('admin.dashboard') }}" aria-label="JNUAF, tableau de bord"><img class="brand-logo" src="{{ asset('images/logo.jpg') }}" alt="JNUAF" width="200" height="200"></a>

    <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="admin-nav">Menu</button>

  </div>

  <nav class="side-nav" id="admin-nav" aria-label="Navigation principale">

    <a class="nav" href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>Tableau de bord</a>

    @foreach ($navGroups as $g)

      <?php $open = collect($g['items'])->contains(fn ($i) => request()->routeIs($i[1])); ?>

      <details class="grp" @if ($open) open @endif>

        <summary>{!! $g['label'] !!}</summary>

        @foreach ($g['items'] as $i)

          <a class="nav" href="{{ route($i[0]) }}" @if (request()->routeIs($i[1])) aria-current="page" @endif>{!! $i[2] !!}@if (! empty($i[4]) && $pending > 0)<span class="badge-count">{{ $pending }}</span>@endif</a>

        @endforeach

      </details>

    @endforeach

    <a class="nav" href="{{ route('admin.profile') }}" @if (request()->routeIs('admin.profile*')) aria-current="page" @endif>Mon profil</a>

    <a class="nav" href="{{ route('home') }}" target="_blank" rel="noopener">Voir le site &#8599;</a>

  </nav>

  <div class="who">
      
<a href="{{ route('admin.profile') }}" style="display:flex;align-items:center;gap:8px;color:inherit;text-decoration:none">@if($u->avatar_path)<img src="{{ $u->avatarUrl() }}" alt="" width="36" height="36" style="border-radius:50%;object-fit:cover">@else<span style="width:36px;height:36px;border-radius:50%;background:#E3F1E5;color:#14592A;display:inline-flex;align-items:center;justify-content:center;font-weight:700">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>@endif<span>{{ $u->name }}</span></a>
<br>
      <span style="opacity:.75">{{ $u->role->label() }}@if ($u->territory) · {{ $u->territory->city }}@endif</span><br>
      <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Se déconnecter</button></form>
    </div>

</aside>

  <main class="main">
    @if (session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())
      <div class="errors" role="alert"><strong>Certaines informations sont invalides :</strong><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
  </main>
</div>
@stack('scripts')
<script src="{{ asset('js/admin-ui.js') }}?v2" defer></script>
<script src="{{ asset('js/admin-nav.js') }}?v=1" defer></script>
</body>
</html>
