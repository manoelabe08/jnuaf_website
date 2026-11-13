
@extends('layouts.admin')



@section('title', 'Discussions')



@section('content')

<style>

@font-face {

  font-family: 'Noto Color Emoji';

  src: url('{{ asset('fonts/NotoColorEmoji-Regular.ttf') }}') format('truetype');

  font-display: swap;

}

</style>


<style>

  .chat-force, .chat-force * { color: #13281A !important; }

  .chat-force .chat-main { background: #fff !important; }

  .chat-force .chat-side { background: #fff !important; }

  .chat-force .chat-room { color: #13281A !important; }

  .chat-force .chat-room.on { color: #14592A !important; }

  .chat-force .chat-badge { color: #fff !important; }

  .chat-force .msg .meta { color: #55685B !important; }

  .chat-force .msg .meta b { color: #14592A !important; }

  .chat-force .msg .actions { margin-top: 4px; display: flex; gap: 6px; justify-content: flex-end; opacity: 0; transition: opacity .15s; }
  .msg:hover .actions { opacity: 1; }
  @media (hover: none), (max-width: 800px) { .msg .actions { opacity: 1; } }
  .msg .actions button { background: #fff; border: 1px solid #DCE6DD; border-radius: 8px; padding: 2px 8px; font-size: 13px; cursor: pointer; }
  .msg .actions button:hover { background: #F1F6F1; }
  .msg .reactions { margin-top: 4px; display: flex; gap: 6px; flex-wrap: wrap; }
  .msg .reactions button, .msg .actions button[data-react-emoji] { font-family: 'Noto Color Emoji', 'Segoe UI Emoji', 'Apple Color Emoji', sans-serif; }
  .msg .reactions button { background: #F1F6F1; border: 1px solid #DCE6DD; border-radius: 10px; padding: 2px 8px; font-size: 13px; cursor: pointer; }
  .msg .reactions button.on { background: #E3F1E5; border-color: #14592A; font-weight: bold; }
  .msg .bubble { color: #13281A !important; background: #F1F6F1 !important; }

  .chat-force .msg.mine .bubble { color: #13281A !important; background: #E3F1E5 !important; }

  .chat-force .bubble a { color: #14592A !important; font-weight: bold; text-decoration: underline; }

  .chat-force .chat-head strong { color: #14592A !important; }

  .chat-force .btn.ghost { color: #14592A !important; background: #fff !important; border: 1px solid #14592A !important; }

  .chat-force .btn.o { color: #fff !important; background: #F26B1D !important; border: 1px solid #F26B1D !important; }

  .chat-force textarea { color: #13281A !important; background: #fff !important; border: 1px solid #DCE6DD !important; }

  .chat-force select { color: #13281A !important; background: #fff !important; border: 1px solid #DCE6DD !important; }


</style>

<style>

  .chat { display: flex; gap: 16px; align-items: stretch; margin-top: 14px; }

  .chat-side { width: 260px; flex: none; background: #fff; border: 1px solid #DCE6DD; border-radius: 12px; padding: 12px; }

  .chat-main { flex: 1; min-width: 0; background: #fff; border: 1px solid #DCE6DD; border-radius: 12px; display: flex; flex-direction: column; }

  .chat-h { margin: 12px 4px 6px; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; color: #55685B; }

  .chat-h:first-child { margin-top: 0; }

  .chat-room { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 8px; color: #13281A; text-decoration: none; font-size: 14px; }

  .chat-room:hover { background: #F1F6F1; }

  .chat-room.on { background: #E3F1E5; font-weight: bold; }

  .chat-badge { background: #F26B1D; color: #fff; border-radius: 10px; padding: 1px 8px; font-size: 12px; font-weight: bold; }

  .chat-badge[hidden] { display: none; }

  .chat-new { margin-top: 10px; display: flex; gap: 6px; }

  .chat-new select { flex: 1; min-width: 0; }

  .chat-head { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-bottom: 1px solid #DCE6DD; }

  .chat-log { height: 80vh; max-height: 560px; overflow-y: auto; padding: 14px 16px; }

  .msg { margin-bottom: 12px; max-width: 80%; }

  .msg.mine { margin-left: auto; text-align: right; }

  .msg .meta { font-size: 12px; color: #55685B; margin-bottom: 2px; }

  .msg .bubble { display: inline-block; text-align: left; background: #F1F6F1; border-radius: 12px; padding: 8px 12px; white-space: pre-wrap; overflow-wrap: anywhere; }

  .msg.mine .bubble { background: #E3F1E5; }

  .chat-form { display: flex; gap: 8px; padding: 12px 16px; border-top: 1px solid #DCE6DD; }

  .chat-form textarea { flex: 1; resize: vertical; }

  @media (max-width: 800px) { .chat { flex-direction: column; } .chat-side { width: auto; } }

</style>



<div class="top">

  <div>

    <h1>Discussions</h1>

    <p class="muted" style="margin:0">Salon g√©n√©ral, salons par territoire et messages priv√©s. Les messages sont chiffr√©s sur le serveur.</p>

  </div>

</div>



<div class="chat chat-force">

  <aside class="chat-side">

    <p class="chat-h">Salons</p>

    @foreach ($rooms->where('type', '!=', 'direct') as $r)

      <a class="chat-room @if ($r->id === $current->id) on @endif" href="{{ route('admin.chat.show', $r) }}">

        <span>{{ $titles[$r->id] }}</span>

        <span class="chat-badge" data-badge="{{ $r->id }}" @if (empty($unread[$r->id])) hidden @endif>{{ $unread[$r->id] ?? 0 }}</span>

      </a>

    @endforeach



    <p class="chat-h">Messages priv√©s</p>

    @foreach ($rooms->where('type', 'direct') as $r)

      <a class="chat-room @if ($r->id === $current->id) on @endif" href="{{ route('admin.chat.show', $r) }}">

        <span>{{ $titles[$r->id] }}</span>

        <span class="chat-badge" data-badge="{{ $r->id }}" @if (empty($unread[$r->id])) hidden @endif>{{ $unread[$r->id] ?? 0 }}</span>

      </a>

    @endforeach



    <form class="chat-new" method="POST" action="{{ route('admin.chat.direct') }}">

      @csrf

      <select name="user_id" required aria-label="Nouveau message priv√©">

        <option value="">Nouveau message priv√©‚Ä¶</option>

        @foreach ($users as $other)

          <option value="{{ $other->id }}">{{ $other->name }}</option>

        @endforeach

      </select>

      <button class="btn ghost" type="submit">Ouvrir</button>

    </form>

  </aside>



  <section class="chat-main">

    <header class="chat-head">

      <strong>{{ $titles[$current->id] }}</strong>

      @if ($current->type === 'direct')

        <form method="POST" action="{{ route('admin.chat.call', $current) }}" style="margin:0" data-confirm="Envoyer un lien d‚Äôappel vid√©o √† votre interlocuteur ?">

          @csrf

          <button class="btn ghost" type="submit">Appeler</button>

        </form>

      @endif

    </header>



    <div id="chat-log" class="chat-log" aria-live="polite"

         data-last="{{ $lastId }}"

         data-current="{{ $current->id }}"
         data-current-type="{{ $current->type }}"

         data-poll="{{ route('admin.chat.poll', $current) }}"

         data-unread="{{ route('admin.chat.unread') }}"
         data-emojis='{{ json_encode(App\Services\ChatService::EMOJIS) }}'>

      @foreach ($messages as $m)

        <div class="msg @if ((int) $m->user_id === $meId) mine @endif" data-id="{{ $m->id }}">

          <div class="meta"><b>{{ $m->user?->name ?? 'Compte supprim√©' }}</b> ¬∑ <time class="t" datetime="{{ $m->created_at->toIso8601String() }}"></time></div>

          
<div class="bubble" data-body="{{ $m->body }}">@if ($m->kind === 'call' && str_starts_with($m->body, 'https://meet.jit.si/jnuaf-'))<a href="{{ $m->body }}" target="_blank" rel="noopener noreferrer">Appel vid√©o ‚Äî rejoindre</a>
@else{{ $m->body }}@endif @if ($m->attachment_path)<div style="margin-top:6px">@if (str_starts_with((string) $m->attachment_mime, 'image/'))<a href="{{ route('admin.chat.attachment', $m) }}" target="_blank" rel="noopener noreferrer"><img src="{{ route('admin.chat.attachment', $m) }}" alt="{{ $m->attachment_name }}" loading="lazy" style="display:block;max-width:260px;max-height:260px;border-radius:10px"></a>@else<a href="{{ route('admin.chat.attachment', $m) }}" target="_blank" rel="noopener noreferrer">PDF ‚Äî {{ $m->attachment_name }} ({{ number_format(($m->attachment_size ?? 0) / 1024, 0, ',', ' ') }} Ko)</a>@endif</div>@endif</div>


          <div class="reactions" data-reactions="{{ $m->id }}" data-for="{{ $m->id }}"></div>

          <div class="actions" data-actions="{{ $m->id }}">

            
@foreach (App\Services\ChatService::EMOJIS as $emoji)

              
<button type="button" data-react-emoji="{{ $emoji }}" title="{{ $emoji }}">

              <img src="{{ '/me/public/images/emoji/'.(['Ì†ΩÌ±ç'=>'1f44d','‚ù§Ô∏è'=>'2764','Ì†ΩÌ∏Ç'=>'1f602','Ì†ΩÌ∏Æ'=>'1f62e','Ì†ºÌæâ'=>'1f389'][$emoji] ?? '').'.svg' }}" alt="" width="18" height="18" style="vertical-align:middle">

            </button>


            @endforeach


            @if ((int) $m->user_id === $meId || (auth()->user()->isAdmin() && $current->type === 'territory'))

              <button type="button" data-delete="{{ route('admin.chat.messages.destroy', $m) }}">Supprimer</button>

            @endif

          </div>


        </div>

      @endforeach

    </div>



    <form id="chat-form" class="chat-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.chat.send', $current) }}">

      @csrf

      <textarea id="chat-body" name="body" rows="2" maxlength="2000" placeholder="√âcrire un message‚Ä¶ (Entr√©e pour envoyer, Maj+Entr√©e pour un retour √† la ligne)" aria-label="Message"></textarea>

      
<input type="file" id="chat-file" name="attachment" accept="image/jpeg,image/png,image/webp,application/pdf" style="display:none">

      <label for="chat-file" class="btn" id="chat-attach" title="Joindre une photo ou un PDF" aria-label="Joindre un fichier" style="cursor:pointer;display:inline-flex;align-items:center"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg></label>

      <span id="chat-file-name" style="font-size:12px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span>

      <button class="btn o" type="submit" id="chat-send">Envoyer</button>


    </form>

  </section>

</div>



<script>

(function () {

  var log = document.getElementById('chat-log');

  var form = document.getElementById('chat-form');

  var body = document.getElementById('chat-body');

  var btn = document.getElementById('chat-send');

  if (!log || !form) { return; }



  var token = form.querySelector('input[name=_token]').value;

  var last = parseInt(log.dataset.last || '0', 10);

  var current = log.dataset.current;
  var base = log.dataset.poll.split('/admin/')[0];

  var baseTitle = document.title;

  var callPrefix = 'https://meet.jit.si/jnuaf-';



  function fmt(iso) {

    var d = new Date(iso);

    return isNaN(d) ? '' : d.toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });

  }



  function nearBottom() {

    return log.scrollHeight - log.scrollTop - log.clientHeight < 80;

  }



  document.querySelectorAll('time.t').forEach(function (t) { t.textContent = fmt(t.getAttribute('datetime')); });



  
  
var emojis = JSON.parse(log.dataset.emojis || '[]');

  var emojiFiles = { 'Ì†ΩÌ±ç': '1f44d', '‚ù§Ô∏è': '2764', 'Ì†ΩÌ∏Ç': '1f602', 'Ì†ΩÌ∏Æ': '1f62e', 'Ì†ºÌæâ': '1f389' };

  function emojiImg(emoji) {

    var file = emojiFiles[emoji];

    return file ? '<img src="/me/public/images/emoji/' + file + '.svg" alt="" width="18" height="18" style="vertical-align:middle">' : emoji;

  }


  var meId = parseInt(document.body.dataset.meId || '0', 10);

  var isAdmin = document.body.dataset.isAdmin === '1';

  var currentType = log.dataset.currentType || 'global';



  function renderReactions(box, data) {

    box.innerHTML = '';

    Object.keys(data || {}).forEach(function (emoji) {

      var people = data[emoji] || [];

      var b = document.createElement('button');

      b.type = 'button';

      b.className = people.some(function (p) { return p.id === meId; }) ? 'on' : '';

      b.innerHTML = emojiImg(emoji) + ' ' + people.length;

      b.title = people.map(function (p) { return p.name; }).join(', ');

      b.addEventListener('click', function () { react(box.dataset.for, emoji); });

      box.appendChild(b);

    });

  }



  async function react(id, emoji) {

    try {

      var r = await fetch(base + '/admin/discussions/messages/' + id + '/reactions', {

        method: 'POST',

        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },

        credentials: 'same-origin',

        body: new URLSearchParams({ _token: token, emoji: emoji })

      });

      if (!r.ok) { return; }

      var d = await r.json();

      var box = log.querySelector('[data-reactions="' + id + '"]');

      if (box) { renderReactions(box, d.reactions); }

    } catch (e) {}

  }



  async function removeMessage(id, url) {

    if (!confirm('Supprimer ce message ?')) { return; }

    try {

      var r = await fetch(base + url, {

        method: 'DELETE',

        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },

        credentials: 'same-origin'

      });

      if (r.ok) {

        var wrap = log.querySelector('[data-id="' + id + '"]');

        if (wrap) {

          var bubble = wrap.querySelector('.bubble');

          if (bubble) { bubble.textContent = 'Message supprim√©'; }

          var rbox = wrap.querySelector('.reactions');

          if (rbox) { rbox.innerHTML = ''; }

          var act = wrap.querySelector('.actions');

          if (act) { act.style.display = 'none'; }

        }

      }

    } catch (e) {}

  }



  function decorate(wrap) {

    var id = wrap.dataset.id;

    var act = wrap.querySelector('[data-actions="' + id + '"]');

    var rbox = wrap.querySelector('[data-reactions="' + id + '"]');

    if (!act || !rbox) { return; }

    
act.querySelectorAll('[data-react-emoji]').forEach(function (b) {

      b.addEventListener('click', function () { react(id, b.dataset.reactEmoji); });

    });

    var del = act.querySelector('[data-delete]');

    if (del) {

      del.addEventListener('click', function () { removeMessage(id, del.dataset.delete); });

    }

  }



  document.querySelectorAll('.msg').forEach(decorate);



  
function renderAttachment(att) {

    var box = document.createElement('div');

    box.style.marginTop = '6px';

    var a = document.createElement('a');

    a.href = att.url;

    a.target = '_blank';

    a.rel = 'noopener noreferrer';

    if (att.image) {

      var img = document.createElement('img');

      img.src = att.url;

      img.alt = att.name || 'Photo';

      img.loading = 'lazy';

      img.style.cssText = 'display:block;max-width:260px;max-height:260px;border-radius:10px';

      a.appendChild(img);

    } else {

      var kb = Math.max(1, Math.round((att.size || 0) / 1024));

      a.textContent = 'PDF ‚Äî ' + (att.name || 'document') + ' (' + kb + ' Ko)';

    }

    box.appendChild(a);

    return box;

  }



  function add(m) {


    if (log.querySelector('[data-id="' + m.id + '"]')) { return; }

    var wrap = document.createElement('div');

    wrap.className = 'msg' + (m.mine ? ' mine' : '');

    wrap.dataset.id = m.id;



    var meta = document.createElement('div');

    meta.className = 'meta';

    var who = document.createElement('b');

    who.textContent = m.author;

    var time = document.createElement('time');

    time.setAttribute('datetime', m.at || '');

    time.textContent = fmt(m.at);

    meta.appendChild(who);

    meta.appendChild(document.createTextNode(' ¬∑ '));

    meta.appendChild(time);



    var bubble = document.createElement('div');

    bubble.className = 'bubble';

    if (m.kind === 'call' && m.body.indexOf(callPrefix) === 0) {

      var a = document.createElement('a');

      a.href = m.body;

      a.target = '_blank';

      a.rel = 'noopener noreferrer';

      a.textContent = 'Appel vid√©o ‚Äî rejoindre';

      bubble.appendChild(a);

    
} else {

      if (m.body) { bubble.textContent = m.body; }

      if (m.attachment) { bubble.appendChild(renderAttachment(m.attachment)); }

    }




    wrap.appendChild(meta);

    wrap.appendChild(bubble);


    var rbox = document.createElement('div');

    rbox.className = 'reactions';

    rbox.dataset.reactions = m.id;

    rbox.dataset.for = m.id;

    wrap.appendChild(rbox);

    var act = document.createElement('div');

    act.className = 'actions';

    act.dataset.actions = m.id;

    
emojis.forEach(function (emoji) {

      var eb = document.createElement('button');

      eb.type = 'button';

      eb.dataset.reactEmoji = emoji;

      eb.innerHTML = emojiImg(emoji);

      act.appendChild(eb);

    });


    if (m.mine || (isAdmin && currentType === 'territory')) {

      var db = document.createElement('button');

      db.type = 'button';

      db.dataset.delete = '/admin/discussions/messages/' + m.id;

      db.textContent = 'Supprimer';

      act.appendChild(db);

    }

    wrap.appendChild(act);

    decorate(wrap);


    log.appendChild(wrap);

    last = Math.max(last, m.id);

  }



  async function poll() {

    if (document.hidden) { return; }

    try {

      var r = await fetch(log.dataset.poll + '?after=' + last, {

        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },

        credentials: 'same-origin'

      });

      if (!r.ok) { return; }

      var d = await r.json();

      if (d.messages.length) {

        var stick = nearBottom();

        d.messages.forEach(add);

        if (stick) { log.scrollTop = log.scrollHeight; }

      }

    } catch (e) {}

  }



  async function unread() {

    if (document.hidden) { return; }

    try {

      var r = await fetch(log.dataset.unread, {

        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },

        credentials: 'same-origin'

      });

      if (!r.ok) { return; }

      var d = await r.json();

      var total = 0;

      Object.keys(d.counts).forEach(function (id) {

        if (id === current) { return; }

        var n = d.counts[id];

        var badge = document.querySelector('[data-badge="' + id + '"]');

        if (!badge) {

          if (n > 0 && body.value === '') { location.reload(); }

          return;

        }

        badge.textContent = n;

        badge.hidden = n === 0;

        total += n;

      });

      document.title = total > 0 ? '(' + total + ') ' + baseTitle : baseTitle;

    } catch (e) {}

  }



  var fileInput = document.getElementById('chat-file');
  var fileLabel = document.getElementById('chat-file-name');
  var attachBtn = document.getElementById('chat-attach');
  function clearFile() {
    if (fileInput) { fileInput.value = ''; }
    if (fileLabel) { fileLabel.textContent = ''; }
  }
  if (attachBtn && fileInput) {
    
    fileInput.addEventListener('change', function () {
      var f = fileInput.files[0];
      if (!f) { clearFile(); return; }
      if (f.size > 10 * 1024 * 1024) { alert('Fichier trop lourd (10 Mo maximum).'); clearFile(); return; }
      fileLabel.textContent = f.name;
    });
  }

  form.addEventListener('submit', async function (e) {

    e.preventDefault();

    var text = body.value.trim();
    var chosen = fileInput ? fileInput.files[0] : null;
    if (!text && !chosen) { return; }
    var fd = new FormData();
    fd.append('_token', token);
    fd.append('body', text);
    if (chosen) { fd.append('attachment', chosen); }

    btn.disabled = true;

    try {

      var r = await fetch(form.action, {

        method: 'POST',

        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },

        credentials: 'same-origin',

        body: fd

      });

      if (r.ok) {

        var d = await r.json();

        add(d.message);
        clearFile();

        body.value = '';

        log.scrollTop = log.scrollHeight;

      } else {

        alert(r.status === 422 || r.status === 413 ? 'Fichier refus√© : JPG, PNG, WebP ou PDF, 10 Mo maximum.' : 'Message non envoy√©. Rechargez la page et r√©essayez.');

      }

    } catch (err) {

      alert('Message non envoy√©. V√©rifiez votre connexion.');

    }

    btn.disabled = false;

    body.focus();

  });



  body.addEventListener('keydown', function (e) {

    if (e.key === 'Enter' && !e.shiftKey) {

      e.preventDefault();

      form.requestSubmit();

    }

  });



 
document.addEventListener('visibilitychange', function () {
    if (!document.hidden) { poll(); unread(); }
  });

  setInterval(poll, 4000);
  setInterval(unread, 10000);
  log.scrollTop = log.scrollHeight;
})();
</script>
@endsection
