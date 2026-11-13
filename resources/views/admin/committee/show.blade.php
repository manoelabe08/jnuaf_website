@extends('layouts.admin')
@section('title', $application->full_name)

@section('content')
@php($a = $application)
<div class="top">
  <div><a href="{{ route('admin.committee.index') }}">← Candidatures</a><h1>{{ $a->full_name }}</h1></div>
  <div style="display:flex;gap:8px">
    <a class="btn ghost" href="{{ route('admin.committee.download', [$a, 'cv']) }}" target="_blank">Ouvrir le CV</a>
    <a class="btn ghost" href="{{ route('admin.committee.download', [$a, 'lettre']) }}" target="_blank">Ouvrir la lettre</a>


    @if ($a->photo_path)<a class="btn ghost" href="{{ route('admin.committee.photo', $a).'?v='.substr(md5((string) $a->photo_path), 0, 8) }}" target="_blank">Ouvrir la photo</a>@endif

  </div>
</div>

<div class="grid g2">
  <div class="card">
    
<h2>Candidature</h2>

    @if ($a->photo_path)<p><img src="{{ route('admin.committee.photo', $a).'?v='.substr(md5((string) $a->photo_path), 0, 8) }}" alt="Photo de {{ $a->full_name }}" width="120" height="150" loading="lazy" style="object-fit:cover;border-radius:10px"></p>@elseif ($a->photo_rejected_at)<p class="muted">Photo refus&eacute;e le {{ \Illuminate\Support\Carbon::parse($a->photo_rejected_at)->format('d/m/Y') }} : {{ $a->photo_reject_reason }}. En attente d&#39;une nouvelle photo.</p>@else<p class="muted">Aucune photo re&ccedil;ue.</p>@endif

    <dl class="kv">
      <dt>Email</dt><dd><a href="mailto:{{ $a->email }}">{{ $a->email }}</a></dd>
      <dt>WhatsApp</dt><dd><a href="https://wa.me/{{ preg_replace('/\D/', '', $a->phone) }}" target="_blank" rel="noopener">{{ $a->phone }}</a></dd>
      <dt>Pays</dt><dd>{{ $a->country }}</dd>
      <dt>Pôle souhaité</dt><dd>{{ $a->pole }}</dd>
      <dt>Reçue le</dt><dd>{{ $a->created_at->format('d/m/Y H:i') }}</dd>
      <dt>Dernière revue</dt><dd>{{ $a->reviewed_at ? $a->reviewed_at->format('d/m/Y H:i').' par '.($a->reviewer?->name ?? '—') : '—' }}</dd>
    </dl>
  </div>
  <div class="card">
    <h2>Évaluation</h2>
    <form class="form" method="POST" action="{{ route('admin.committee.update', $a) }}">
      @csrf @method('PATCH')
      <label>Statut<select name="status">@foreach ($statuses as $v => $l)<option value="{{ $v }}" @selected($a->status->value === $v)>{{ $l }}</option>@endforeach</select></label>
      <label>Notes internes<textarea name="notes" maxlength="3000">{{ old('notes', $a->notes) }}</textarea></label>
      <button class="btn">Enregistrer</button>
    </form>
    
@if ($a->photo_path)

    <form class="form" method="POST" action="{{ route('admin.committee.photo.reject', $a) }}" data-confirm="Refuser cette photo et demander une nouvelle photo &agrave; la personne ?" style="margin-top:14px">

      @csrf

      <h3>Refuser la photo</h3>

      <label>Motif<select name="reason_code">

        <option value="blurry">Photo floue, trop sombre ou de mauvaise qualit&eacute;</option>

        <option value="face">Visage non visible ou partiellement cach&eacute;</option>

        <option value="group">Photo de groupe ou ne montrant pas la personne seule</option>

        <option value="presentable">Photo non pr&eacute;sentable (filtre, selfie, arri&egrave;re-plan charg&eacute;)</option>

        <option value="other">Autre motif</option>

      </select></label>

      <label>Pr&eacute;cision (facultatif)<input name="reason_note" maxlength="200"></label>

      <button class="btn danger" type="submit">Refuser la photo et la redemander</button>

    </form>

    @endif

    <form method="POST" action="{{ route('admin.committee.destroy', $a) }}" data-confirm="Supprimer la candidature et ses pièces jointes ?" style="margin-top:14px">
      @csrf @method('DELETE')
      <button class="btn danger">Supprimer la candidature et les pièces</button>
    </form>
  </div>
</div>
@php($effectivePole = $a->assigned_pole ?: $a->pole)

@php($prop = \Illuminate\Support\Facades\DB::table('committee_pole_proposals')->where('committee_application_id', $a->id)->latest('id')->first())

@php($poleList = array_values(array_filter((array) config('jnuaf.committee.poles'), fn ($p) => $p !== 'Peu importe')))

@php($propLabels = ['pending' => 'En attente de r&eacute;ponse', 'accepted' => 'Accept&eacute;e', 'declined' => 'Refus&eacute;e', 'expired' => 'Expir&eacute;e', 'resolved' => 'R&eacute;solue', 'cancelled' => 'Annul&eacute;e'])

<div class="card" style="margin-top:16px">

  <h2>Affectation du p&ocirc;le</h2>

  <dl class="kv">

    <dt>P&ocirc;le souhait&eacute;</dt><dd>{{ $a->pole }}</dd>

    <dt>P&ocirc;le attribu&eacute;</dt><dd>@if ($a->assigned_pole){{ $a->assigned_pole }}@else<span class="muted">Aucun (le p&ocirc;le souhait&eacute; s'applique)</span>@endif</dd>

    @if ($prop)<dt>Derni&egrave;re proposition</dt><dd>{{ $prop->from_pole }} &rarr; {{ $prop->to_pole }} <span class="pill g">{!! $propLabels[$prop->status] ?? e($prop->status) !!}</span></dd>@endif

  </dl>

  @if ($a->status->value !== 'rejected')

  <form class="form" method="POST" action="{{ route('admin.committee.pole.propose', $a) }}" data-confirm="Envoyer cette proposition de p&ocirc;le &agrave; la personne ?">

    @csrf

    <label>Proposer un autre p&ocirc;le (la personne recevra un e-mail pour accepter ou refuser)

      <select name="to_pole">@foreach ($poleList as $p)@if ($p !== $effectivePole)<option value="{{ $p }}">{{ $p }}</option>@endif @endforeach</select>

    </label>

    @error('to_pole')<p style="color:#B42318">{{ $message }}</p>@enderror

    <button class="btn" type="submit">Envoyer la proposition</button>

  </form>

  @endif

  @if ($prop && in_array($prop->status, ['declined', 'expired'], true))

  <form class="form" method="POST" action="{{ route('admin.committee.pole.resolve', $a) }}" style="margin-top:14px" data-confirm="Attribuer ce p&ocirc;le d&eacute;finitivement ?">

    @csrf

    <label>Trancher le conflit : attribuer directement un p&ocirc;le

      <select name="pole">@foreach ($poleList as $p)<option value="{{ $p }}" @selected($p === $effectivePole)>{{ $p }}</option>@endforeach</select>

    </label>

    <button class="btn ghost" type="submit">Attribuer ce p&ocirc;le</button>

  </form>

  @endif

</div>
@endsection
