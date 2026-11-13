@extends('layouts.admin')
@section('title', 'Messages programmés')

@section('content')
<div class="top">
  <div><h1>Messages programmés</h1><p class="muted" style="margin:0">Envois différés depuis team@jnuaf.org (heure : {{ $timezone }}).</p></div>
  <a class="btn" href="{{ route('admin.mailbox.compose') }}">Nouveau message</a>
</div>

<div class="card tbl">
  <table>
    <thead><tr><th>Envoi prévu</th><th>Destinataires</th><th>Objet</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @forelse($mails as $m)
      @php($r = (array) $m->recipients)
      @php($list = array_merge($r['to'] ?? [], $r['cc'] ?? [], $r['bcc'] ?? []))
      <tr>
        <td>{{ $m->scheduled_at->timezone($timezone)->format('d/m/Y à H:i') }}</td>
        <td>{{ implode(', ', array_slice($list, 0, 3)) }}@if(count($list) > 3) <span class="muted">+ {{ count($list) - 3 }}</span>@endif</td>
        <td>{{ $m->subject }}</td>
        <td>
          {{ ['scheduled'=>'Programmé','sending'=>'En cours d’envoi','sent'=>'Envoyé','failed'=>'Échec','cancelled'=>'Annulé','uncertain'=>'À vérifier'][$m->status] ?? $m->status }}
          @if($m->sent_at)<br><span class="muted">{{ $m->sent_at->timezone($timezone)->format('d/m/Y H:i') }}</span>@endif
          @if($m->error)<br><span class="muted">{{ $m->error }}</span>@endif
        </td>
        <td>
          @if($m->status === 'scheduled')
          <form method="POST" action="{{ route('admin.mailbox.unschedule', $m) }}" data-confirm="Annuler cet envoi programmé ?">
            @csrf
            <button class="btn danger sm" type="submit">Annuler</button>
          </form>
          @endif
        </td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted">Aucun message programmé.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
