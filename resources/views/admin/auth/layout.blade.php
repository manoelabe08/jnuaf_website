<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  @include('partials.favicons')
  <title>@yield('title') · Admin JNUAF</title>
  <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}?v=1">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=5">
</head>
<body>
  <div class="login">
    @yield('form')
  </div>
<script src="{{ asset('js/admin-ui.js') }}?v2" defer></script>
</body>
</html>
