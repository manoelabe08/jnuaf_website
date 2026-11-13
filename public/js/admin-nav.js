
(function () {

  'use strict';

  var side = document.getElementById('admin-side');

  var btn = side ? side.querySelector('.menu-toggle') : null;

  if (!side || !btn) { return; }

  function setOpen(open) {

    side.classList.toggle('is-open', open);

    btn.setAttribute('aria-expanded', open ? 'true' : 'false');

  }

  btn.addEventListener('click', function () { setOpen(!side.classList.contains('is-open')); });

  document.addEventListener('keydown', function (e) {

    if (e.key === 'Escape' && side.classList.contains('is-open')) { setOpen(false); btn.focus(); }

  });

  var groups = side.querySelectorAll('details.grp');

  groups.forEach(function (g) {

    g.addEventListener('toggle', function () {

      if (g.open && window.matchMedia('(max-width: 760px)').matches) {

        groups.forEach(function (o) { if (o !== g) { o.open = false; } });

      }

    });

  });

})();

