@extends('layouts.admin')

@section('title', 'Validations')



@section('content')

@php

  $labels = ['pending' => 'En attente', 'approved' => 'Approuv&eacute;e', 'rejected' => 'Refus&eacute;e', 'consumed' => 'Ex&eacute;cut&eacute;e', 'expired' => 'Expir&eacute;e'];

  $pills = ['pending' => 'o', 'approved' => 'g', 'rejected' => 'r'];

@endphp

<div class="top">

  <div><h1>Validations</h1><p class="muted" style="margin:0">@if ($isSuper) Actions critiques en attente du Bureau de Pilotage. @else Vos demandes d'actions critiques. @endif</p></div>

</div>

@if ($errors->has('code'))

  <div class="card" style="padding:12px 18px;color:#B42318">{{ $errors->first('code') }}</div>

@endif

@if ($isSuper)

<form id="bulk-form" method="POST" action="{{ route('admin.sensitive.bulk') }}" class="card" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:10px 14px">

  @csrf

  <span class="muted"><strong id="bulk-count">0</strong> demande(s) s&eacute;lectionn&eacute;e(s)</span>

  <input name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="Code (pour approuver)" maxlength="12" style="width:170px">

  <button class="btn sm o" type="submit" name="op" value="approve" data-bulk-confirm="Approuver {n} demande(s) ?" disabled>Approuver la s&eacute;lection</button>

  <button class="btn sm ghost" type="submit" name="op" value="reject" data-bulk-confirm="Refuser {n} demande(s) ?" disabled>Refuser la s&eacute;lection</button>

</form>

@endif

<div class="card tbl">

  <table class="stack">

    <thead><tr><th>@if ($isSuper)<input type="checkbox" id="bulk-all" aria-label="Tout s&eacute;lectionner">@endif</th><th>Date</th><th>Demandeur</th><th>Action</th><th>Cible</th><th>Statut</th><th></th></tr></thead>

    <tbody>

    @forelse ($actions as $a)

      <tr>

        <td data-label="">@if ($isSuper && in_array($a->status, ['pending', 'approved'], true))<input type="checkbox" class="bulk-item" name="ids[]" value="{{ $a->id }}" form="bulk-form" aria-label="S&eacute;lectionner">@endif</td>
        <td data-label="Date">{{ $a->created_at->format('d/m/Y H:i') }}</td>

        <td data-label="Demandeur">{{ $a->user?->name }}<br><span class="muted">{{ $a->user?->email }}</span></td>

        
<td class="lead">{{ $svc->label($a->action_key) }}@if ($a->payload)<br><span class="muted">{{ (json_decode((string) $a->payload, true)['name'] ?? '') }} &middot; {{ (json_decode((string) $a->payload, true)['email'] ?? '') }}</span>@endif</td>


        <td data-label="Cible"><code>{{ \Illuminate\Support\Str::limit((string) parse_url($a->target_url, PHP_URL_PATH), 60) }}</code></td>

        <td data-label="Statut"><span class="pill {{ $pills[$a->status] ?? '' }}">{!! $labels[$a->status] ?? e($a->status) !!}</span>@if ($a->decider)<br><span class="muted">par {{ $a->decider->name }}</span>@endif</td>

        <td>

          @if ($isSuper && $a->status === 'pending')

            <form method="POST" action="{{ route('admin.sensitive.approve', $a) }}" style="display:flex;gap:6px;align-items:center">@csrf

              <input name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="Code" maxlength="12" required style="width:90px">

              <button class="btn sm o" type="submit">Approuver</button>

            </form>

          @endif

          @if ($isSuper && in_array($a->status, ['pending', 'approved'], true))

            <form method="POST" action="{{ route('admin.sensitive.reject', $a) }}" style="margin-top:6px">@csrf<button class="btn sm ghost" type="submit">Refuser</button></form>

          @endif

        </td>

      </tr>

    @empty

      <tr><td colspan="7" class="muted">Aucune demande.</td></tr>

    @endforelse

    </tbody>

  </table>

  {{ $actions->links('admin.partials.pager') }}

</div>

<script src="{{ asset('js/admin-bulk.js') }}?v=2" defer></script>
@endsection

