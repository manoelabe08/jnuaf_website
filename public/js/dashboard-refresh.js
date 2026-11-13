(function () {

  'use strict';

  var box = document.getElementById('dash');

  if (!box) { return; }

  setInterval(function () {

    if (document.hidden) { return; }

    fetch(window.location.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })

      .then(function (r) { return r.ok ? r.text() : null; })

      .then(function (t) {

        if (!t) { return; }

        var d = new DOMParser().parseFromString(t, 'text/html');

        var n = d.getElementById('dash');

        if (n) { box.innerHTML = n.innerHTML; }

      })

      .catch(function () {});

  }, 60000);

})();

