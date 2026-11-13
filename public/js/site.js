(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  /* ---------- Menu mobile ---------- */
  const header = $('.nav'), burger = $('.burger');
  if (header && burger) {
    const setOpen = open => {
      header.classList.toggle('open', open);
      burger.setAttribute('aria-expanded', open);
      burger.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
    };
    burger.addEventListener('click', () => setOpen(!header.classList.contains('open')));
    $$('#mainNav a').forEach(a => a.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && header.classList.contains('open')) { setOpen(false); burger.focus(); } });
    document.addEventListener('click', e => { if (!header.contains(e.target)) setOpen(false); });
    matchMedia('(min-width: 981px)').addEventListener('change', e => { if (e.matches) setOpen(false); });
  }

  /* ---------- Programme par territoire (onglets accessibles) ---------- */
  const tabs = $$('.prog-tabs [role="tab"]');
  function selectTab(tab) {
    tabs.forEach(t => {
      const on = t === tab;
      t.setAttribute('aria-selected', on);
      t.tabIndex = on ? 0 : -1;
      const panel = document.getElementById(t.getAttribute('aria-controls'));
      if (panel) panel.hidden = !on;
    });
  }
  tabs.forEach((t, i) => {
    t.addEventListener('click', () => selectTab(t));
    t.addEventListener('keydown', e => {
      const d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
      if (!d) return;
      const next = tabs[(i + d + tabs.length) % tabs.length];
      selectTab(next); next.focus();
    });
  });
  if (tabs[0]) selectTab(tabs.find(t => t.getAttribute('aria-selected') === 'true') || tabs[0]);

  
/* ---------- Galerie : dossiers ---------- */

const folders = $('#galFolders');

const photoView = $('#galPhotos');

const photoGrid = $('#galleryPhotos');

if (folders && photoView && photoGrid) {

  const showFolders = () => {

    photoView.hidden = true;

    folders.hidden = false;

    const first = folders.querySelector('button');

    if (first) first.focus();

  };

  const openFolder = (id) => {

    folders.hidden = true;

    photoView.hidden = false;

    $$('[data-folder-panel]', photoGrid).forEach(p => {

      p.hidden = p.dataset.folderPanel !== id;

    });

    const back = $('#galBack');

    if (back) back.focus();

  };

  $$('[data-folder]', folders).forEach(b => {

    b.addEventListener('click', () => openFolder(b.dataset.folder));

  });

  const back = $('#galBack');

  if (back) back.addEventListener('click', showFolders);

}



/* ---------- Visionneuse ---------- */

const lb = $('#lightbox');

if (lb) {

  const img = $('#lbImg'), cap = $('#lbCap'), prev = $('#lbPrev'), next = $('#lbNext');

  let set = [], idx = 0, lastFocus = null;

  const show = () => {

    const el = set[idx];

    img.src = el.dataset.lbSrc;

    img.alt = el.dataset.lbTitle;

    cap.textContent = el.dataset.lbTitle + ' · ' + (idx + 1) + '/' + set.length;

    prev.hidden = next.hidden = set.length < 2;

  };

  const open = (list, i) => {

    set = list; idx = i;

    lastFocus = document.activeElement;

    show();

    lb.hidden = false;

    $('#lbClose').focus();

  };

  const close = () => {

    lb.hidden = true;

    if (lastFocus) lastFocus.focus();

  };

  document.addEventListener('click', e => {

    const t = e.target.closest('[data-lb-src]');

    if (!t) return;

    const panel = t.closest('[data-folder-panel]');

    const list = panel ? $$('[data-lb-src]', panel) : [t];

    open(list, list.indexOf(t));

  });

  $('#lbClose').onclick = close;

  prev.onclick = () => { idx = (idx - 1 + set.length) % set.length; show(); };

  next.onclick = () => { idx = (idx + 1 + set.length) % set.length; show(); };

  lb.addEventListener('click', e => {

    if (e.target === lb || e.target.tagName === 'FIGURE') close();

  });

  document.addEventListener('keydown', e => {

    if (lb.hidden) return;

    if (e.key === 'Escape') close();

    if (e.key === 'ArrowLeft') prev.click();

    if (e.key === 'ArrowRight') next.click();

  });

}


/* ---------- Biographies des intervenantes (fenêtres <dialog>) ---------- */
  $$('[data-dialog]').forEach(btn => btn.addEventListener('click', () => {
    const dlg = document.getElementById(btn.dataset.dialog);
    if (!dlg) return;
    if (typeof dlg.showModal === 'function') dlg.showModal(); else dlg.setAttribute('open', '');
    document.documentElement.style.overflow = 'hidden';
    const closeBtn = $('[data-close]', dlg);
    if (closeBtn) closeBtn.focus();
  }));
  $$('.spk-dialog').forEach(dlg => {
    const close = () => { if (typeof dlg.close === 'function') dlg.close(); else dlg.removeAttribute('open'); };
    $$('[data-close]', dlg).forEach(b => b.addEventListener('click', close));
    // Clic sur le fond sombre (en dehors du contenu) : fermeture.
    dlg.addEventListener('click', e => { if (e.target === dlg) close(); });
    // Échap ferme nativement ; on rétablit le défilement de la page à chaque fermeture.
    dlg.addEventListener('close', () => { document.documentElement.style.overflow = ''; });
  });

  /* ---------- Copie des numéros ---------- */
  $$('[data-copy]').forEach(b => b.addEventListener('click', () => {
    const el = document.getElementById(b.dataset.copy), txt = el.textContent.replace(/\s/g, '');
    const done = () => { b.textContent = 'Numéro copié'; setTimeout(() => (b.textContent = 'Copier le numéro'), 1800); };
    const fallback = () => { const r = document.createRange(); r.selectNodeContents(el); const s = getSelection(); s.removeAllRanges(); s.addRange(r); b.textContent = 'Numéro sélectionné'; };
    try { navigator.clipboard.writeText(txt).then(done, fallback); } catch (e) { fallback(); }
  }));

  /* ---------- Formulaire multi-profils ---------- */
  const form = $('#regForm');
  function showBranch(radio) {
    if (!form || !radio) return;
    $$('.branch', form).forEach(b => { b.hidden = b.dataset.branch !== radio.dataset.branch; });
    const star = $('[data-only="intervenante"]', form);
    if (star) star.hidden = radio.value !== 'intervenante';
  }
  if (form) {
    form.addEventListener('change', e => { if (e.target.name === 'profile') showBranch(e.target); });
    showBranch($('input[name="profile"]:checked', form));
  }
  $$('[data-profile]').forEach(a => a.addEventListener('click', () => {
    const r = document.getElementById('pr-' + a.dataset.profile);
    if (r) { r.checked = true; showBranch(r); }
  }));

  /* ---------- Validation côté client (le serveur reste la référence) ---------- */
  const MSG = {
    valueMissing: 'Ce champ est obligatoire.',
    typeMismatch: 'Adresse email invalide, ex. nom@domaine.ci',
    patternMismatch: "Numéro invalide, indiquez l'indicatif, ex. +225…",
    tooShort: 'Ce champ est trop court.',
  };
  function setErr(el, msg) {
    const f = el.closest('.field');
    if (!f) return;
    f.classList.toggle('invalid', !!msg);
    const err = $('.err', f);
    if (err) err.textContent = msg || '';
  }
  function check(el) {
    if (el.closest('[hidden]') || el.type === 'checkbox') return true;
    let msg = '';
    if (el.type === 'file' && el.files[0]) {
      const fl = el.files[0], maxKb = +el.dataset.maxKb || 0;
      if (fl.type !== 'application/pdf' && !/\.pdf$/i.test(fl.name) && !/^image\//.test(fl.type) && !/\.(jpe?g|png|webp)$/i.test(fl.name)) msg = 'Le fichier doit être au format PDF.';
      else if (maxKb && fl.size > maxKb * 1024) msg = 'Le fichier dépasse ' + Math.round(maxKb / 1024) + ' Mo.';
    } else if (!el.validity.valid) {
      const key = Object.keys(MSG).find(k => el.validity[k]);
      msg = el.type === 'file' && el.validity.valueMissing ? (/image/.test(el.accept) ? 'Ajoutez une photo.' : 'Ajoutez un fichier PDF.') : (MSG[key] || el.validationMessage);
    }
    setErr(el, msg);
    return !msg;
  }
  $$('form[data-validate]').forEach(f => {
    f.noValidate = true;
    const fields = () => $$('input:not([type=radio]):not([type=hidden]):not([name=website]), select, textarea', f);
    fields().forEach(el => el.addEventListener('blur', () => check(el)));
    f.addEventListener('submit', e => {
      const bad = fields().filter(el => !check(el));
      const consent = $('input[type=checkbox][required]', f);
      if (consent && !consent.checked) bad.push(consent);
      if (bad.length) {
        e.preventDefault();
        bad[0].focus();
        if (consent && bad.includes(consent)) consent.closest('label').style.color = 'var(--orange)';
        return;
      }
      const btn = $('button[type=submit]', f);
      if (btn) { btn.disabled = true; btn.textContent = 'Envoi en cours…'; }
    });
  });
})();
