@extends('layouts.admin')

@section('title', 'Audit des droits')



@section('content')

<div class="top"><div><h1>Audit des droits</h1><p class="muted" style="margin:0">Vue en lecture seule des comptes, de leurs droits et des points &agrave; surveiller.</p></div></div>



<div class="card" style="display:flex;gap:28px;flex-wrap:wrap;padding:14px 18px">

  <div><b style="font-size:1.6rem">{{ $stats['total'] }}</b><br><span class="muted">comptes actifs</span></div>

  <div><b style="font-size:1.6rem">{{ $stats['admins'] }}</b><br><span class="muted">administrateurs</span></div>

  <div><b style="font-size:1.6rem">{{ $stats['admins_sans_mfa'] }}</b><br><span class="muted">admins sans second facteur</span></div>

  <div><b style="font-size:1.6rem">{{ $stats['alertes'] }}</b><br><span class="muted">comptes avec alerte</span></div>

</div>



<div class="card tbl">

  <table class="stack">

    <thead><tr><th>Compte</th><th>R&ocirc;le</th><th>Ville</th><th>Derni&egrave;re connexion</th><th>Second facteur</th><th>Alertes</th></tr></thead>

    <tbody>

    @forelse ($rows as $r)

      <tr>

        <td class="lead"><b>{{ $r['name'] }}</b>@if ($r['is_super']) <span class="pill g">Super admin</span>@endif<br><span class="muted">{{ $r['email'] }}</span></td>

        <td data-label="R&ocirc;le">{{ $r['role'] }}</td>

        <td data-label="Ville">{{ $r['city'] ?? 'Toutes' }}</td>

        <td data-label="Derni&egrave;re connexion">{{ $r['last_login'] ?? 'Jamais' }}</td>

        <td data-label="Second facteur">{{ $r['mfa'] ? "Activ\u{e9}" : 'Non' }}</td>

        <td data-label="Alertes">@forelse ($r['flags'] as $f)<span class="pill o" style="margin:0 4px 4px 0">{{ $f }}</span>@empty<span class="muted">Aucune</span>@endforelse</td>

      </tr>

    @empty

      <tr><td colspan="6" class="muted">Aucun compte.</td></tr>

    @endforelse

    </tbody>

  </table>

</div>

@endsection

