@extends('newsletter.layout')
@section('content')
<div class="card">
    <h1>Votre abonnement newsletter</h1>
    @if(session('newsletter_status'))<p class="alert" role="status">{{ session('newsletter_status') }}</p>@endif
    @if($delivery->subscriber->status === 'unsubscribed')
        <p>Vous êtes désinscrit(e) de la newsletter JNUAF.</p>
        <p>Cette désinscription ne supprime pas votre compte ni votre inscription à un événement. Les messages nécessaires à vos démarches restent distincts.</p>
    @else
        <p>Confirmez votre choix pour ne plus recevoir les newsletters JNUAF à l'adresse associée à ce lien.</p>
        <form method="POST" action="{{ route('newsletter.unsubscribe.confirm', $token) }}">
            @csrf
            <button type="submit">Confirmer ma désinscription</button>
        </form>
        <p><a href="{{ route('newsletter.web', $token) }}">Revenir à la newsletter</a></p>
    @endif
</div>
@endsection
