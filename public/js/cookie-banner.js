
(function () {

  var NAME = 'jnuaf_cookie_notice';

  var banner = document.getElementById('cookie-banner');

  if (!banner) { return; }



  function alreadySeen() {

    return document.cookie.split('; ').some(function (c) { return c.indexOf(NAME + '=') === 0; });

  }



  function remember() {

    var secure = location.protocol === 'https:' ? '; Secure' : '';

    document.cookie = NAME + '=1; max-age=15552000; path=/; SameSite=Lax' + secure;

  }



  if (!alreadySeen()) { banner.hidden = false; }



  var ok = document.getElementById('cookie-banner-ok');

  if (ok) {

    ok.addEventListener('click', function () {

      remember();

      banner.hidden = true;

    });

  }

})();

