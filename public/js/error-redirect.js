
(function () {

  'use strict';

  var box = document.querySelector('[data-redirect]');

  if (!box) { return; }

  var url = box.getAttribute('data-redirect');

  var seconds = parseInt(box.getAttribute('data-seconds') || '10', 10);

  var out = document.getElementById('countdown');

  var line = document.getElementById('redirect-line');

  var stay = document.getElementById('stay');

  var timer = null;

  if (line) { line.hidden = false; }

  if (stay) { stay.hidden = false; }

  function tick() {

    seconds -= 1;

    if (seconds <= 0) { clearInterval(timer); window.location.assign(url); return; }

    if (out) { out.textContent = String(seconds); }

  }

  timer = setInterval(tick, 1000);

  if (stay) {

    stay.addEventListener('click', function () {

      clearInterval(timer);

      if (line) { line.textContent = 'Redirection annul\u00e9e.'; }

      stay.hidden = true;

    });

  }

})();

