
(function () {

  'use strict';

  var tag = document.querySelector('script[data-mail-suggest]');

  if (!tag) return;

  var url = tag.getAttribute('data-url');

  ['jm-to', 'jm-cc', 'jm-bcc'].forEach(function (id) {

    var ta = document.getElementById(id);

    if (ta) attach(ta);

  });function attach(ta) {

    var wrap = document.createElement('div');

    wrap.className = 'jm-suggest-wrap';

    ta.parentNode.insertBefore(wrap, ta);

    wrap.appendChild(ta);

    var list = document.createElement('ul');

    list.className = 'jm-suggest';

    list.id = ta.id + '-suggest';

    list.setAttribute('role', 'listbox');

    list.hidden = true;

    wrap.appendChild(list);

    ta.setAttribute('autocomplete', 'off');

    ta.setAttribute('aria-autocomplete', 'list');

    ta.setAttribute('aria-controls', list.id);

    var items = [], active = -1, timer = null, ctrl = null, range = null;



    function close() {

      list.hidden = true;

      list.textContent = '';

      items = [];

      active = -1;

    }



    function tokenRange() {

      var v = ta.value, pos = ta.selectionStart, i = pos, j = pos;

      while (i > 0 && !/[,;\n]/.test(v.charAt(i - 1))) i--;

      while (j < v.length && !/[,;\n]/.test(v.charAt(j))) j++;

      var raw = v.slice(i, pos);

      var lead = raw.length - raw.replace(/^\s+/, '').length;

      return { start: i + lead, end: j, text: raw.trim() };

    }



    function mark(n) {

      active = n;

      Array.prototype.forEach.call(list.children, function (li, k) {

        var on = k === n;

        li.className = on ? 'on' : '';

        li.setAttribute('aria-selected', on ? 'true' : 'false');

        if (on) {

          ta.setAttribute('aria-activedescendant', li.id);

          li.scrollIntoView({ block: 'nearest' });

        }

      });

    }



    function pick(n) {

      var d = items[n];

      if (!d || !range) return;

      var v = ta.value;

      var head = v.slice(0, range.start);

      var after = v.slice(range.end).replace(/^\s*[,;]?\s*/, '');

      var ins = d.email + ', ';

      ta.value = head + ins + after;

      var caret = (head + ins).length;

      ta.setSelectionRange(caret, caret);

      close();

      ta.focus();

    }



    function render(data) {

      list.textContent = '';

      items = data;

      active = -1;

      if (!data.length) {

        list.hidden = true;

        return;

      }

      data.forEach(function (d, n) {

        var li = document.createElement('li');

        li.id = list.id + '-' + n;

        li.setAttribute('role', 'option');

        var a = document.createElement('span');

        a.className = 'jm-suggest-name';

        a.textContent = d.name || d.email;

        li.appendChild(a);

        if (d.name) {

          var b = document.createElement('span');

          b.className = 'jm-suggest-mail';

          b.textContent = d.email;

          li.appendChild(b);

        }

        li.addEventListener('mousedown', function (e) {

          e.preventDefault();

          pick(n);

        });

        list.appendChild(li);

      });

      list.hidden = false;

    }



    function search() {

      range = tokenRange();

      var q = range.text;

      if (q.length < 2) {

        close();

        return;

      }

      if (ctrl) ctrl.abort();

      ctrl = new AbortController();

      fetch(url + '?q=' + encodeURIComponent(q), {

        credentials: 'same-origin',

        headers: { Accept: 'application/json' },

        signal: ctrl.signal

      })

        .then(function (r) { return r.ok ? r.json() : []; })

        .then(function (d) {

          if (document.activeElement === ta) render(Array.isArray(d) ? d : []);

        })

        .catch(function () {});

    }



    ta.addEventListener('input', function () {

      clearTimeout(timer);

      timer = setTimeout(search, 150);

    });



    ta.addEventListener('keydown', function (e) {

      if (list.hidden || !items.length) return;

      if (e.key === 'ArrowDown') {

        e.preventDefault();

        mark((active + 1) % items.length);

      } else if (e.key === 'ArrowUp') {

        e.preventDefault();

        mark((active - 1 + items.length) % items.length);

      } else if (e.key === 'Enter' || e.key === 'Tab') {

        if (active < 0 && range && range.text.toLowerCase() === items[0].email) {

          close();

          return;

        }

        e.preventDefault();

        pick(active < 0 ? 0 : active);

      } else if (e.key === 'Escape') {

        close();

      }

    });



    ta.addEventListener('blur', function () {

      setTimeout(close, 150);

    });

  }

})();

