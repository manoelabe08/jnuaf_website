@extends('layouts.admin')

@section('title', 'Tableau de bord')



@section('content')

<div id="dash">

<div class="top">

  <div><h1>Bonjour {{ $user->name }}</h1><p class="muted" style="margin:0">&Eacute;dition {{ config('jnuaf.edition') }}</p></div>

  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">

    @if ($isAdmin)<select name="ville" data-autosubmit><option value="0">Toutes les villes</option>@foreach ($territories as $t)<option value="{{ $t->id }}" @selected($cityId === $t->id)>{{ $t->city }}</option>@endforeach</select>@endif

    <select name="jours" data-autosubmit>@foreach ([7 => '7 jours', 30 => '30 jours', 90 => '90 jours'] as $v => $l)<option value="{{ $v }}" @selected($days === $v)>{{ $l }}</option>@endforeach</select>

  </form>

</div>



<div class="grid g2" style="margin-bottom:16px">

  <div class="card" style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">

    <div style="text-align:center"><b style="font-size:2.4rem;color:var(--green);line-height:1">J-{{ max(0, $daysLeft) }}</b><br><span class="muted">avant l'&eacute;v&eacute;nement</span></div>

    <div style="flex:1;min-width:200px">

      @foreach ($milestones as $m)

        <div style="display:flex;justify-content:space-between;gap:10px;font-size:.9rem"><span>{{ $m['label'] }}</span><span class="muted">{{ $m['fmt'] }} &middot; J-{{ $m['left'] }}</span></div>

      @endforeach

    </div>

  </div>

  <div class="card" style="display:flex;gap:8px;flex-wrap:wrap;align-content:center">

    <a class="btn o" href="{{ route('admin.checkin.index') }}">Ouvrir le pointage</a>

    <a class="btn ghost" href="{{ route('admin.registrations.index') }}">Inscriptions</a>

    @if ($isAdmin)

      <a class="btn ghost" href="{{ route('admin.committee.index') }}">Candidatures</a>

      <a class="btn ghost" href="{{ route('admin.grades.index') }}">Grades du comit&eacute;</a>

      <a class="btn ghost" href="{{ route('admin.newsletter.index') }}">Newsletter</a>

      <a class="btn ghost" href="{{ route('admin.gallery.index') }}">Galerie</a>

      @if (\Illuminate\Support\Facades\Route::has('admin.events.create'))<a class="btn ghost" href="{{ route('admin.events.create') }}">Cr&eacute;er un &eacute;v&eacute;nement</a>@endif

      <a class="btn ghost" href="{{ route('admin.posts.create') }}">Nouvel article</a><!-- jnuaf-s73 -->

    @endif

  </div>

</div>



<div class="grid g4" style="margin-bottom:16px">

  <div class="card stat"><b>{{ $total }}</b><span>inscriptions confirm&eacute;es &middot; {{ $cancelled }} annulation(s)</span></div>

  <div class="card stat o"><b>{{ $present }}</b><span>pr&eacute;sentes ({{ $rate }} %)</span></div>

  <div class="card stat"><b>{{ $cur }}</b><span>nouvelles sur {{ $days }} jours @if ($delta !== null)<span class="pill {{ $delta >= 0 ? '' : 'r' }}">{{ $delta >= 0 ? '+' : '' }}{{ $delta }} %</span>@endif</span></div>

  <div class="card stat"><b>{{ $imageConsent }}</b><span>accords droit &agrave; l'image</span></div>

</div>



@if ($isAdmin)

<div class="card" style="margin-bottom:16px">

  <h2>&Agrave; traiter</h2>

  @forelse ($todo as $t)

    <a href="{{ $t['url'] }}" style="display:flex;gap:10px;align-items:center;padding:6px 0;text-decoration:none;color:inherit"><span class="pill o">{{ $t['n'] }}</span><span>{{ $t['text'] }}</span><span class="muted" style="margin-left:auto">&rarr;</span></a>

  @empty

    <p class="muted" style="margin:0">Rien &agrave; traiter pour le moment.</p>

  @endforelse

</div>

@endif



<div class="grid g2" style="margin-bottom:16px">

  <div class="card">

    <h2>Inscriptions cumul&eacute;es ({{ $days }} jours)</h2>

    <svg viewBox="0 0 600 150" width="100%" height="170" preserveAspectRatio="none" role="img" aria-label="Courbe des inscriptions cumul&eacute;es">

      <line x1="0" y1="142" x2="600" y2="142" stroke="#e1e8e2"></line>

      <line x1="0" y1="75" x2="600" y2="75" stroke="#e1e8e2" stroke-dasharray="3 3"></line>

      <polygon points="{{ $area }}" fill="rgba(20,89,42,.12)"></polygon>

      <polyline points="{{ $line }}" fill="none" stroke="#14592A" stroke-width="2.5" stroke-linejoin="round" vector-effect="non-scaling-stroke"></polyline>

    </svg>

    <div class="muted" style="display:flex;justify-content:space-between;font-size:.8rem"><span>{{ $fromLabel }}</span><span>total : {{ $max }}</span><span>{{ $toLabel }}</span></div>

  </div>

  <div class="card">

    <h2>Par profil</h2>

    <div style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">

      <svg viewBox="0 0 42 42" width="140" height="140" role="img" aria-label="R&eacute;partition par profil">

        <circle cx="21" cy="21" r="15.9155" fill="none" stroke="#e8eee9" stroke-width="6"></circle>

        @foreach ($segments as $s)

          <circle cx="21" cy="21" r="15.9155" fill="none" stroke="{{ $s['color'] }}" stroke-width="6" stroke-dasharray="{{ $s['dash'] }}" stroke-dashoffset="{{ $s['offset'] }}"></circle>

        @endforeach

        <text x="21" y="23" text-anchor="middle" font-size="7" font-weight="700" fill="#14592A">{{ $total }}</text>

      </svg>

      <div>

        @forelse ($segments as $s)

          <div style="display:flex;gap:8px;align-items:center;font-size:.9rem"><i style="width:10px;height:10px;border-radius:50%;background:{{ $s['color'] }};display:inline-block"></i>{{ $s['label'] }} <b>{{ $s['count'] }}</b></div>

        @empty

          <span class="muted">Aucune inscription.</span>

        @endforelse

      </div>

    </div>

  </div>

</div><div class="grid g2" style="margin-bottom:16px">

  <div class="card">

    <h2>Remplissage par ville</h2>

    @foreach ($cities as $c)

      <div style="margin-bottom:12px">

        <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap">

          <span><a href="{{ route('admin.registrations.index', ['territory' => $c['id']]) }}">{{ $c['city'] }}</a>

            @if ($c['pct'] !== null && $c['pct'] >= 100)<span class="pill r">Complet</span>@elseif ($c['pct'] !== null && $c['pct'] >= 90)<span class="pill o">Presque complet</span>@endif</span>

          <span><b>{{ $c['total'] }}</b>@if ($c['capacity']) <span class="muted">/ {{ $c['capacity'] }} places</span>@endif <span class="muted">&middot; {{ $c['present'] }} pr&eacute;sente(s)</span></span>

        </div>

        <div class="bar"><i style="width:{{ min(100, $c['pct'] ?? round($c['total'] / $maxCity * 100)) }}%@if ($c['pct'] !== null && $c['pct'] >= 90);background:var(--red)@endif"></i></div>

      </div>

    @endforeach

  </div>

  @if ($isAdmin)

  <div class="card">

    <h2>Candidatures au comit&eacute;</h2>

    @foreach ($funnel as $f)

      <div style="margin-bottom:10px">

        <div style="display:flex;justify-content:space-between"><span>{{ $f['label'] }}</span><b>{{ $f['n'] }}</b></div>

        <div class="bar"><i style="width:{{ round($f['n'] / $maxFunnel * 100) }}%;background:var(--orange)"></i></div>

      </div>

    @endforeach

    <div class="grid g2" style="margin-top:14px">

      <div><b>Par pays</b>@forelse ($byCountry as $name => $n)<div style="display:flex;justify-content:space-between;font-size:.9rem"><span>{{ $name }}</span><b>{{ $n }}</b></div>@empty<div class="muted">-</div>@endforelse</div>

      <div><b>Par p&ocirc;le</b>@forelse ($byPole as $name => $n)<div style="display:flex;justify-content:space-between;font-size:.9rem"><span>{{ $name }}</span><b>{{ $n }}</b></div>@empty<div class="muted">-</div>@endforelse</div>

    </div>

  </div>

  @endif

</div>



@if ($isAdmin && $campaign)

<div class="card" style="margin-bottom:16px">

  <div class="top" style="margin-bottom:8px"><h2 style="margin:0">Newsletter</h2><a href="{{ route('admin.newsletter.index') }}">Ouvrir &rarr;</a></div>

  <p style="margin:0 0 8px"><b>{{ $campaign['title'] }}</b> <span class="pill {{ $campaign['status'] === 'sending' ? 'o' : 'g' }}">{{ $campaign['status'] === 'sending' ? 'en cours d\'envoi' : $campaign['status'] }}</span></p>

  <div class="bar"><i style="width:{{ $campaign['pct'] }}%"></i></div>

  <div style="display:flex;gap:24px;flex-wrap:wrap;margin-top:10px">

    <div><b>{{ $campaign['sent'] }}</b><br><span class="muted">envoy&eacute;s ({{ $campaign['pct'] }} %)</span></div>

    <div><b>{{ $campaign['pending'] }}</b><br><span class="muted">en attente @if ($campaign['days'])&middot; environ {{ $campaign['days'] }} jour(s) au plafond de 900 par jour @endif</span></div>

    <div><b>{{ $campaign['failed'] }}</b><br><span class="muted">&eacute;checs ({{ $campaign['fail_pct'] }} %) @if ($campaign['fail_pct'] >= 5)<span class="pill r">&agrave; examiner</span>@endif</span></div>

  </div>

</div>

@endif

@if ($isAdmin && $cards)

<div class="card" style="margin-bottom:16px">

  <div class="top" style="margin-bottom:8px"><h2 style="margin:0">Cartes de membre</h2><a href="{{ route('admin.cards.index') }}">Tout voir &rarr;</a></div>

  <div class="grid g4">

    <div class="stat"><b>{{ $cards['valid'] }}</b><span>valides</span></div>

    <div class="stat"><b>{{ $cards['sent'] }}</b><span>envoy&eacute;es</span></div>

    <div class="stat o"><b>{{ $cards['unsent'] }}</b><span>&agrave; envoyer</span></div>

    <div class="stat"><b>{{ $cards['revoked'] }}</b><span>r&eacute;voqu&eacute;es</span></div>

  </div>

</div>

@endif



<div class="grid g2" style="margin-bottom:16px">

  <div class="card">

    <div class="top" style="margin-bottom:8px"><h2 style="margin:0">Derni&egrave;res inscriptions</h2><a href="{{ route('admin.registrations.index') }}">Tout voir &rarr;</a></div>

    <div class="tbl"><table class="stack">

      <thead><tr><th>Nom</th><th>Profil</th><th>Ville</th><th>Date</th></tr></thead>

      <tbody>

      @forelse ($latest as $r)

        <tr><td class="lead"><a href="{{ route('admin.registrations.show', $r) }}"><b>{{ $r->fullName() }}</b></a></td><td data-label="Profil"><span class="pill">{{ $r->profile->label() }}</span></td><td data-label="Ville">{{ $r->territory?->city }}</td><td data-label="Date">{{ $r->created_at->format('d/m/Y H:i') }}</td></tr>

      @empty

        <tr><td colspan="4" class="muted">Aucune inscription pour le moment.</td></tr>

      @endforelse

      </tbody>

    </table></div>

  </div>

  @if ($isAdmin)

  <div class="card">

    <div class="top" style="margin-bottom:8px"><h2 style="margin:0">Derni&egrave;res candidatures</h2><a href="{{ route('admin.committee.index') }}">Tout voir &rarr;</a></div>

    <div class="tbl"><table class="stack">

      <thead><tr><th>Candidat&middot;e</th><th>P&ocirc;le</th><th>Statut</th><th>Date</th></tr></thead>

      <tbody>

      @forelse ($latestApps as $a)

        <tr><td class="lead"><a href="{{ route('admin.committee.show', $a) }}"><b>{{ $a->full_name }}</b></a></td><td data-label="P&ocirc;le">{{ $a->pole }}</td><td data-label="Statut"><span class="pill g">{{ $a->status->label() }}</span></td><td data-label="Date">{{ $a->created_at->format('d/m/Y') }}</td></tr>

      @empty

        <tr><td colspan="4" class="muted">Aucune candidature.</td></tr>

      @endforelse

      </tbody>

    </table></div>

  </div>

  @endif

</div>



@if ($isSuper)

<div class="grid g2" style="margin-bottom:16px">

  <div class="card">

    <div class="top" style="margin-bottom:8px"><h2 style="margin:0">Derni&egrave;res actions sensibles</h2><a href="{{ route('admin.sensitive.index') }}">Tout voir &rarr;</a></div>

    @php($statusLabels = ['pending' => 'En attente', 'approved' => 'Approuv&eacute;e', 'rejected' => 'Refus&eacute;e', 'consumed' => 'Ex&eacute;cut&eacute;e', 'expired' => 'Expir&eacute;e'])

    @forelse ($recent as $r)

      <div style="display:flex;justify-content:space-between;gap:10px;padding:4px 0;font-size:.9rem"><span>{{ $r['label'] }} <span class="muted">&middot; {{ $r['who'] }}</span></span><span><span class="pill g">{!! $statusLabels[$r['status']] ?? e($r['status']) !!}</span> <span class="muted">{{ $r['at'] }}</span></span></div>

    @empty

      <p class="muted" style="margin:0">Aucune action sensible r&eacute;cente.</p>

    @endforelse

  </div>

  @if ($health)

  <div class="card">

    <h2>Sant&eacute; technique</h2>

    <div style="display:flex;gap:28px;flex-wrap:wrap">

      <div>Sauvegarde<br>@if ($health['backup_age_h'] === null)<span class="pill r">introuvable</span>@else<span class="pill {{ $health['backup_age_h'] > 36 ? 'r' : '' }}">il y a {{ $health['backup_age_h'] }} h</span>@endif</div>

      <div>Planificateur<br>@if (! $health['cron_known'])<span class="pill g">en attente du premier signal</span>@elseif ($health['cron_ok'])<span class="pill">actif</span>@else<span class="pill r">arr&ecirc;t&eacute;</span>@endif</div>

      <div>Erreurs r&eacute;centes (journal)<br><span class="pill {{ ($health['errors'] ?? 0) > 0 ? 'o' : '' }}">{{ $health['errors'] ?? 'n/d' }}</span></div>

    </div>

  </div>

  @endif

</div>

@endif



<p class="muted" style="text-align:right;font-size:.85rem"><a href="{{ route('admin.dashboard.old') }}">Ancienne version du tableau de bord</a></p>

</div>

<script src="{{ asset('js/dashboard-refresh.js') }}?v=1" defer></script>

@endsection

