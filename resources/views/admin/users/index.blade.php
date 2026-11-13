@extends('layouts.admin')
@section('title', 'Comptes & exports')

@section('content')
<div class="top"><div><h1>Comptes de l'équipe</h1><p class="muted" style="margin:0">Une coordination ne voit et ne pointe que les inscriptions de son territoire.</p></div></div>

<div class="card tbl" style="margin-bottom:16px">
  <table class="stack">
    <thead><tr><th>Nom</th><th>Rôle</th><th>Territoire</th><th>Dernière connexion</th><th></th></tr></thead>
    <tbody>
    @foreach ($users as $user)
      <tr>
        <td class="lead"><b>{{ $user->name }}</b>@if ($user->suspended_at) <span class="pill r">Suspendu</span>@endif<br><span class="muted">{{ $user->email }}</span>
          <details class="edit"><summary>Modifier</summary>
            <form class="form" method="POST" action="{{ route('admin.users.update', $user) }}">
              @csrf @method('PUT')
              @include('admin.users.fields', ['user' => $user])
              
<button class="btn sm">Enregistrer</button>

            </form>

            @unless ($user->is(auth()->user()))

            <form class="inline" method="POST" action="{{ route('admin.users.reset-password', $user) }}" data-confirm="Générer un nouveau mot de passe et l’envoyer par email ? La personne devra le changer à sa prochaine connexion." style="margin-top:10px">

              @csrf

              <button class="btn sm" type="submit">Réinitialiser le mot de passe</button>

            </form>

            @endunless

          </details>
        </td>
        <td data-label="Rôle"><span class="pill {{ $user->isAdmin() ? 'o' : '' }}">{{ $user->role->label() }}</span></td>
        <td data-label="Territoire">{{ $user->territory?->city ?? '—' }}</td>
        <td data-label="Connexion">{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}@if($user->must_change_password)<br><span class="muted">Mot de passe temporaire en cours</span>@endif</td>
        <td>@if ($user->suspended_at && auth()->user()->isSuperAdmin())<form class="inline" method="POST" action="{{ route('admin.users.reactivate', $user) }}">@csrf<button class="btn sm o" type="submit">R&eacute;activer</button></form> @endif @unless ($user->is(auth()->user()) || $user->isSuperAdmin())<form class="inline" method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Supprimer ce compte ? Sans validation du super admin, le compte sera seulement suspendu en attendant sa d&eacute;cision.">@csrf @method('DELETE')<button class="btn danger sm">Supprimer</button></form>@endunless</td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>

<div class="grid g2">
  <div class="card">
    <h2>Créer un compte</h2>
    <p class="muted">Le mot de passe est généré automatiquement par le système et envoyé uniquement à l'adresse email du compte.</p>
    <form class="form" method="POST" action="{{ route('admin.users.store') }}">
      @csrf
      @include('admin.users.fields', ['user' => null])
      <button class="btn">Créer</button>
    </form>
  </div>
  <div class="card tbl">
    <h2>Journal des exports</h2>
    <table>
      <thead><tr><th>Date</th><th>Par</th><th>Lignes</th><th>Filtres</th></tr></thead>
      <tbody>
      @forelse ($exports as $e)
        <tr><td>{{ $e->created_at->format('d/m/Y H:i') }}</td><td>{{ $e->user?->name ?? 'Compte supprimé' }}</td><td>{{ $e->row_count }}</td>
          <td class="muted">{{ collect($e->filters)->map(fn ($v, $k) => "$k=$v")->implode(', ') ?: 'aucun' }}</td></tr>
      @empty
        <tr><td colspan="4" class="muted">Aucun export.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
