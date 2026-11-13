(function () {

  'use strict';

  var form = document.getElementById('bulk-form');

  if (!form) { return; }

  var all = document.getElementById('bulk-all');

  var count = document.getElementById('bulk-count');
  var max = parseInt(form.getAttribute('data-bulk-max') || '100', 10);

  var buttons = form.querySelectorAll('button[type="submit"]');

  function items() { return Array.prototype.slice.call(document.querySelectorAll('.bulk-item')); }

  function selected() { return items().filter(function (c) { return c.checked; }).length; }

  function refresh() {

    var n = selected();

    var total = items().length;

    if (count) { count.textContent = String(n); }

    Array.prototype.forEach.call(buttons, function (b) { b.disabled = n === 0; });

    if (all) { all.checked = n > 0 && n === total; all.indeterminate = n > 0 && n < total; }

  }

  if (all) {

    all.addEventListener('change', function () {

      items().forEach(function (c) { c.checked = all.checked; });

      refresh();

    });

  }

  document.addEventListener('change', function (e) {

    if (e.target && e.target.classList && e.target.classList.contains('bulk-item')) { refresh(); }

  });

  form.addEventListener('submit', function (e) {

    var n = selected();

    var s = e.submitter;

    var a = s ? s.value : '';

    var custom = s && s.getAttribute ? s.getAttribute('data-bulk-confirm') : null;

    var msg;

    if (n === 0) { e.preventDefault(); return; }

    if (n > max) { window.alert('Maximum ' + max + ' \u00e9l\u00e9ments par action.'); e.preventDefault(); return; }

    if (custom) {

      msg = custom.replace('{n}', String(n));

    } else if (a === 'purge') {

      msg = 'Supprimer d\u00e9finitivement ' + n + ' \u00e9l\u00e9ment(s) ? Cette action est irr\u00e9versible.';

    } else {

      msg = 'Restaurer ' + n + ' \u00e9l\u00e9ment(s) ?';

    }

    if (!window.confirm(msg)) { e.preventDefault(); }

  });

  refresh();

})();
