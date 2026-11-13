
(function () {

  'use strict';

  document.addEventListener('submit', function (e) {

    var f = e.target;

    if (!f || !f.getAttribute) { return; }

    var msg = f.getAttribute('data-confirm');

    if (msg && !window.confirm(msg)) { e.preventDefault(); return; }

    var bm = f.getAttribute('data-confirm-button');

    if (bm && e.submitter && e.submitter.name === f.getAttribute('data-confirm-button-name') && e.submitter.value === f.getAttribute('data-confirm-button-value') && !window.confirm(bm)) { e.preventDefault(); return; }

    if (f.hasAttribute('data-compose-send')) {

      var s = e.submitter, d = f.elements['scheduled_at'], m;

      if (s && s.dataset && s.dataset.schedule) { m = f.getAttribute('data-msg-schedule'); }

      else if (d && d.value) { m = f.getAttribute('data-msg-dated'); }

      else { m = f.getAttribute('data-msg-send'); }

      if (!window.confirm(m)) { e.preventDefault(); }

    }

  }, true);

  document.addEventListener('change', function (e) {

    var t = e.target;

    if (t && t.hasAttribute && t.hasAttribute('data-autosubmit') && t.form) { t.form.submit(); }

  });

  ['paste', 'drop'].forEach(function (name) {

    document.addEventListener(name, function (e) {

      var t = e.target;

      if (t && t.hasAttribute && t.hasAttribute('data-nopaste')) { e.preventDefault(); }

    }, true);

  });

})();

