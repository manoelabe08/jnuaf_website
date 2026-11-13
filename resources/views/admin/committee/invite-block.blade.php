@php($plan = session('invite_plan'))

<div class="card" style="margin-bottom:16px">

  <h2 style="margin-top:0">Inviter &agrave; candidater</h2>

  <p class="muted">Collez des adresses (une par ligne, 20 au maximum). Chaque personne re&ccedil;oit un message individuel envoy&eacute; depuis team@jnuaf.org, avec le lien du formulaire de candidature. Les adresses d&eacute;j&agrave; invit&eacute;es, d&eacute;j&agrave; candidates ou d&eacute;j&agrave; titulaires d&rsquo;un compte sont ignor&eacute;es.</p>

  @if (is_array($plan))

    @if (! empty($plan['todo']))

      <p><strong>{{ count($plan['todo']) }}</strong> invitation(s) pr&ecirc;te(s) :</p>

      <ul>@foreach ($plan['todo'] as $e)<li>{{ $e }}</li>@endforeach</ul>

    @else

      <p class="muted">Aucune adresse &agrave; inviter.</p>

    @endif

    @if (! empty($plan['skipped']))

      <p class="muted">Ignor&eacute;es :</p>

      <ul class="muted">@foreach ($plan['skipped'] as $e => $why)<li>{{ $e }} &mdash; {{ $why }}</li>@endforeach</ul>

    @endif

    <div style="display:flex;gap:8px;flex-wrap:wrap">

      @if (! empty($plan['todo']))

        <form method="POST" action="{{ route('admin.grades.invite.send') }}" data-confirm="Envoyer {{ count($plan['todo']) }} invitation(s) depuis team@jnuaf.org ?">

          @csrf

          <button class="btn" type="submit">Envoyer les invitations</button>

        </form>

      @endif

      <form method="POST" action="{{ route('admin.grades.invite.cancel') }}">

        @csrf

        <button class="btn ghost" type="submit">Annuler</button>

      </form>

    </div>

  @else

    <form method="POST" action="{{ route('admin.grades.invite.preview') }}">

      @csrf

      <textarea name="emails" rows="4" required maxlength="3000" style="width:100%" placeholder="prenom.nom@exemple.org"></textarea>

      <button class="btn" type="submit" style="margin-top:8px">Pr&eacute;parer la liste</button>

    </form>

  @endif

</div>

