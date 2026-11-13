(function () {

  var script = document.currentScript || document.querySelector('script[data-base]');

  var base = (script && script.getAttribute('data-base')) || '/chatbot';

  var tokenEl = document.querySelector('meta[name=csrf-token]');

  var token = tokenEl ? tokenEl.content : '';

  var code = '';

  try { code = sessionStorage.getItem('jc_code') || ''; } catch (e) {}

  var cfg = null, panel, log, input, toggle;



  function el(tag, cls, text) { var e = document.createElement(tag); if (cls) { e.className = cls; } if (text) { e.textContent = text; } return e; }

  function api(path, body) {

    var opt = body === undefined

      ? {headers: {'Accept': 'application/json'}}

      : {method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token}, body: JSON.stringify(body)};

    return fetch(base + path, opt).then(function (r) { return r.json().then(function (j) { return {status: r.status, data: j}; }, function () { return {status: r.status, data: {}}; }); });

  }

  function scroll() { log.scrollTop = log.scrollHeight; }

  function say(text, who) {

    var m = el('div', 'jc-msg ' + (who === 'me' ? 'jc-me' : 'jc-bot'));

    if (who === 'me') { m.textContent = text; }

    else {

      String(text).split(/(https?:\/\/[^\s]+)/g).forEach(function (part, i) {

        if (i % 2 === 1) {

          var tail = part.match(/[.,;:!?)]+$/);

          var url = tail ? part.slice(0, -tail[0].length) : part;

          var a = el('a', '', url); a.href = url; a.target = '_blank'; a.rel = 'noopener';

          m.appendChild(a);

          if (tail) { m.appendChild(document.createTextNode(tail[0])); }

        } else if (part) { m.appendChild(document.createTextNode(part)); }

      });

    }

    log.appendChild(m); scroll(); return m;

  }

  function chips(list) {

    if (!list || !list.length) { return; }

    var box = el('div', 'jc-chips');

    list.forEach(function (s) {

      var b = el('button', 'jc-chip', s.question || s.label); b.type = 'button';

      b.addEventListener('click', function () { box.remove(); send(s.question || s.label, s.id || s.faq_id || null); });

      box.appendChild(b);

    });

    log.appendChild(box); scroll();

  }

  function feedback(faqId) {

    var box = el('div', 'jc-fb');

    [['Utile', 1], ['Pas utile', 0]].forEach(function (x) {

      var b = el('button', '', x[0]); b.type = 'button';

      b.addEventListener('click', function () {

        api('/avis', {faq_id: faqId, helpful: x[1], code: code});

        box.textContent = 'Merci pour votre avis.';

      });

      box.appendChild(b);

    });

    log.appendChild(box); scroll();

  }

  function send(text, faqId) {

    text = (text || '').trim();

    if (!text) { return; }

    say(text, 'me');

    var wait = say('...', 'bot');

    api('/message', {message: text, code: code, faq_id: faqId || null, page: location.pathname}).then(function (res) {

      wait.remove();

      if (res.status === 429) { say('Trop de messages, merci de patienter un instant.'); return; }

      if (res.status !== 200) { say('Une erreur est survenue. Merci de r\u00e9essayer.'); return; }

      var d = res.data;

      if (d.code) { code = d.code; try { sessionStorage.setItem('jc_code', code); } catch (e) {} }

      say(d.text);

      if (d.type === 'faq' && d.faq_id) { feedback(d.faq_id); }

      if (d.type === 'none') { chips(d.suggestions); say('Vous pouvez aussi contacter l\u2019\u00e9quipe avec le lien en bas de la fen\u00eatre.'); }

    }).catch(function () { wait.remove(); say('Connexion impossible pour le moment.'); });

  }

  function handoffForm() {

    var form = el('form', 'jc-form'); form.noValidate = true;

    function field(label, name, type) { var l = el('label', '', label); l.setAttribute('for', 'jc-' + name); var i = el(type === 'textarea' ? 'textarea' : 'input'); i.id = 'jc-' + name; i.name = name; if (type !== 'textarea') { i.type = type; } form.appendChild(l); form.appendChild(i); return i; }

    form.appendChild(el('p', '', 'Laissez vos coordonn\u00e9es : l\u2019\u00e9quipe vous r\u00e9pondra.'));

    field('Pr\u00e9nom *', 'first_name', 'text');

    field('E-mail', 'email', 'email');

    field('T\u00e9l\u00e9phone', 'phone', 'tel');

    var lt = el('label', '', '\u00c9quipe *'); lt.setAttribute('for', 'jc-team');

    var sel = el('select'); sel.id = 'jc-team'; sel.name = 'team';

    (cfg.teams || []).forEach(function (t) { var o = el('option', '', t.label); o.value = t.slug; sel.appendChild(o); });

    form.appendChild(lt); form.appendChild(sel);

    field('Message (facultatif)', 'message', 'textarea');

    var cl = el('label', 'jc-consent'); var ck = el('input'); ck.type = 'checkbox'; ck.name = 'consent';

    cl.appendChild(ck); cl.appendChild(document.createTextNode(' J\u2019accepte que mes coordonn\u00e9es soient utilis\u00e9es pour me r\u00e9pondre et conserv\u00e9es 6 mois.'));

    form.appendChild(cl);

    var err = el('div', 'jc-err'); err.setAttribute('role', 'alert'); form.appendChild(err);

    var ok = el('button', 'jc-primary', 'Envoyer'); ok.type = 'submit';

    var back = el('button', 'jc-chip', 'Retour'); back.type = 'button'; back.style.marginLeft = '8px';

    form.appendChild(ok); form.appendChild(back);

    back.addEventListener('click', function () { form.remove(); log.style.display = ''; sendBar.style.display = ''; });

    form.addEventListener('submit', function (ev) {

      ev.preventDefault(); err.textContent = '';

      var f = form.elements;

      api('/contact', {code: code, first_name: f.first_name.value, email: f.email.value || null, phone: f.phone.value || null, team: f.team.value, message: f.message.value || null, consent: f.consent.checked}).then(function (res) {

        if (res.status === 200 && res.data.ok) {

          if (res.data.code) { code = res.data.code; try { sessionStorage.setItem('jc_code', code); } catch (e) {} }

          form.innerHTML = ''; form.appendChild(el('p', '', res.data.text));

          if (res.data.whatsapp) { var a = el('a', 'jc-primary', 'Ouvrir WhatsApp'); a.href = res.data.whatsapp; a.target = '_blank'; a.rel = 'noopener'; a.style.display = 'inline-block'; a.style.textDecoration = 'none'; form.appendChild(a); }

          return;

        }

        var msg = res.data.message || 'V\u00e9rifiez les champs.';

        if (res.data.errors) { var k = Object.keys(res.data.errors)[0]; msg = res.data.errors[k][0]; }

        err.textContent = msg;

      });

    });

    return form;}

  var sendBar;

  function build(c) {

    cfg = c;

    toggle = el('button'); toggle.id = 'jc-toggle'; toggle.type = 'button'; toggle.textContent = '\uD83D\uDCAC';

    toggle.setAttribute('aria-label', 'Ouvrir l\u2019assistant JNUAF'); toggle.setAttribute('aria-expanded', 'false');

    panel = el('div'); panel.id = 'jc-panel'; panel.setAttribute('role', 'dialog'); panel.setAttribute('aria-label', 'Assistant JNUAF');

    var head = el('div', 'jc-head'); head.appendChild(el('span', '', 'Assistant JNUAF'));

    var close = el('button', '', '\u00d7'); close.type = 'button'; close.setAttribute('aria-label', 'Fermer'); head.appendChild(close);

    log = el('div'); log.id = 'jc-log'; log.setAttribute('role', 'log'); log.setAttribute('aria-live', 'polite');

    sendBar = el('form', 'jc-send');

    input = el('input'); input.type = 'text'; input.maxLength = 500; input.placeholder = 'Votre question...'; input.setAttribute('aria-label', 'Votre question');

    var go = el('button', 'jc-primary', 'Envoyer'); go.type = 'submit';

    sendBar.appendChild(input); sendBar.appendChild(go);

    var foot = el('div', 'jc-foot'); foot.appendChild(el('span', '', c.privacy || ''));

    var hb = el('button', '', 'Parler \u00e0 l\u2019\u00e9quipe'); hb.type = 'button'; foot.appendChild(hb);

    panel.appendChild(head); panel.appendChild(log); panel.appendChild(sendBar); panel.appendChild(foot);

    document.body.appendChild(toggle); document.body.appendChild(panel);

    say(c.welcome);

    chips((c.options && c.options.length) ? c.options : c.suggestions);

    function open(v) { panel.classList.toggle('open', v); toggle.setAttribute('aria-expanded', v ? 'true' : 'false'); if (v) { input.focus(); } else { toggle.focus(); } }

    toggle.addEventListener('click', function () { open(!panel.classList.contains('open')); });

    close.addEventListener('click', function () { open(false); });

    panel.addEventListener('keydown', function (e) { if (e.key === 'Escape') { open(false); } });

    sendBar.addEventListener('submit', function (e) { e.preventDefault(); var v = input.value; input.value = ''; send(v, null); });

    hb.addEventListener('click', function () { log.style.display = 'none'; sendBar.style.display = 'none'; panel.insertBefore(handoffForm(), foot); });

  }

  api('/init').then(function (res) { if (res.status === 200 && !res.data.disabled) { build(res.data); } }).catch(function () {});

})();

