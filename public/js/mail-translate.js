(function () {

  'use strict';

  var btn = document.getElementById('jm-translate-btn');

  var statusEl = document.getElementById('jm-translate-status');

  if (!btn || !statusEl) { return; }

  if (!('Translator' in self)) {

    statusEl.textContent = 'Traduction indisponible : utilisez Chrome ou Edge sur ordinateur.';

    return;

  }

  btn.hidden = false;



  var frame = document.querySelector('.jm-frame');

  var plain = document.querySelector('.jm-body');

  var MAX_CHARS = 30000;

  var state = { done: false, showing: 'original', origSrc: null, frSrc: null, origHtml: null, frHtml: null };



  function setStatus(t) { statusEl.textContent = t; }

  function langName(code) {

    try { return new Intl.DisplayNames(['fr'], { type: 'language' }).of(code); } catch (e) { return code; }

  }



  function collectFrame() {

    var doc = new DOMParser().parseFromString(frame.getAttribute('srcdoc') || '', 'text/html');

    var walker = doc.createTreeWalker(doc.body, NodeFilter.SHOW_TEXT);

    var nodes = [];

    var n;

    while ((n = walker.nextNode())) {

      var tag = n.parentNode ? n.parentNode.nodeName : '';

      if (tag === 'STYLE' || tag === 'SCRIPT') { continue; }

      if (n.nodeValue.trim() === '') { continue; }

      nodes.push(n);

    }

    return { doc: doc, nodes: nodes };

  }



  async function translateAll(translator, texts) {

    var cache = new Map();

    var out = [];

    var used = 0;

    for (var i = 0; i < texts.length; i++) {

      var raw = texts[i];

      var core = raw.trim();

      if (core === '' || used + core.length > MAX_CHARS) { out.push(raw); continue; }

      var lead = raw.slice(0, raw.indexOf(core));

      var trail = raw.slice(lead.length + core.length);

      var res = cache.get(core);

      if (res === undefined) {

        res = await translator.translate(core);

        cache.set(core, res);

        used += core.length;

      }

      out.push(lead + res + trail);

      setStatus('Traduction en cours : ' + (i + 1) + '/' + texts.length);

    }

    return out;

  }



  async function run() {

    var parts = null;

    var texts;

    if (frame) {

      parts = collectFrame();

      texts = parts.nodes.map(function (n) { return n.nodeValue; });

    } else if (plain) {

      texts = (plain.textContent || '').split(/\r?\n/);

    } else {

      setStatus('Rien \u00e0 traduire.');

      return;

    }

    var sample = texts.join(' ').replace(/\s+/g, ' ').slice(0, 1500);

    if (sample.trim() === '') { setStatus('Rien \u00e0 traduire.'); return; }



    var source = 'en';

    if ('LanguageDetector' in self) {

      try {

        var detector = await LanguageDetector.create();

        var found = await detector.detect(sample);

        if (found && found.length) {

          var top = found[0];

          if (top.detectedLanguage === 'fr' && top.confidence > 0.6) {

            setStatus('Ce message est d\u00e9j\u00e0 en fran\u00e7ais.');

            return;

          }

          if (top.detectedLanguage && top.detectedLanguage !== 'und' && top.confidence > 0.4) {

            source = top.detectedLanguage;

          }

        }

      } catch (e) { /* d\u00e9tection facultative */ }

    }



    var avail = await Translator.availability({ sourceLanguage: source, targetLanguage: 'fr' });

    if (avail === 'unavailable') {

      setStatus('Langue non prise en charge par le navigateur : ' + langName(source) + '.');

      return;

    }

    var translator = await Translator.create({

      sourceLanguage: source,

      targetLanguage: 'fr',

      monitor: function (m) {

        m.addEventListener('downloadprogress', function (e) {

          setStatus('T\u00e9l\u00e9chargement du mod\u00e8le de langue : ' + Math.round(e.loaded * 100) + ' %');

        });

      }

    });

    setStatus('Traduction en cours\u2026');

    var out = await translateAll(translator, texts);



    if (frame) {

      state.origSrc = frame.getAttribute('srcdoc');

      parts.nodes.forEach(function (n, i) { n.nodeValue = out[i]; });

      state.frSrc = '<!doctype html>' + parts.doc.documentElement.outerHTML;

      frame.setAttribute('srcdoc', state.frSrc);

    } else {

      state.origHtml = plain.innerHTML;

      plain.textContent = '';

      out.forEach(function (line, i) {

        if (i > 0) { plain.appendChild(document.createElement('br')); }

        plain.appendChild(document.createTextNode(line));

      });

      state.frHtml = plain.innerHTML;

    }

    state.done = true;

    state.showing = 'fr';

    btn.textContent = 'Afficher l\u2019original';

    setStatus('Traduit de ' + langName(source) + ' vers le fran\u00e7ais par votre navigateur. Rien n\u2019est envoy\u00e9 \u00e0 un serveur.');

  }



  function show(which) {

    if (frame) {

      frame.setAttribute('srcdoc', which === 'fr' ? state.frSrc : state.origSrc);

    } else if (plain) {

      plain.innerHTML = which === 'fr' ? state.frHtml : state.origHtml;

    }

  }



  btn.addEventListener('click', async function () {

    if (state.done) {

      state.showing = state.showing === 'fr' ? 'original' : 'fr';

      show(state.showing);

      btn.textContent = state.showing === 'fr' ? 'Afficher l\u2019original' : 'Traduire en fran\u00e7ais';

      return;

    }

    btn.disabled = true;

    try {

      await run();

    } catch (e) {

      setStatus('La traduction a \u00e9chou\u00e9 : ' + (e && e.message ? e.message : 'erreur inconnue'));

    }

    btn.disabled = false;

  });

})();

