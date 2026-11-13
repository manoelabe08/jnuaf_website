<x-mail-layout>
    @if($kind === 'confirm')
    <h1 style="margin:0 0 20px;color:#14592A;font-size:22px">Confirmez votre nouvelle adresse email</h1>
    <p style="margin:0 0 14px">Bonjour {{ $user->name }},</p>
    <p style="margin:0 0 14px">
        Une demande a été faite pour associer cette adresse (<b>{{ $newEmail }}</b>) à votre compte JNUAF.ORG,
        actuellement lié à <b>{{ $oldEmail }}</b>.
    </p>
    <p style="margin:0 0 14px">
        <a href="{{ $url }}" style="display:inline-block;padding:12px 20px;background:#14592A;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Confirmer cette adresse</a>
    </p>
    <p style="margin:0 0 14px;font-size:13px;color:#55685B">
        Ce lien est valable 60 minutes et ne fonctionne qu'une fois.
        @if($requiresApproval)
        Votre compte étant à accès complet, la modification devra ensuite être validée par le Bureau de Pilotage.
        @endif
        Si vous n'êtes pas à l'origine de cette demande, ignorez cet email : rien ne sera modifié.
    </p>

    @elseif($kind === 'requested')
    <h1 style="margin:0 0 20px;color:#14592A;font-size:22px">Demande de changement d'adresse email</h1>
    <p style="margin:0 0 14px">Bonjour {{ $user->name }},</p>
    <p style="margin:0 0 14px">
        Une demande de changement d'adresse email a été faite sur votre compte JNUAF.ORG, le {{ $when }},
        vers <b>{{ $newEmail }}</b>@if($ip) (adresse IP {{ $ip }})@endif.
    </p>
    <p style="margin:0 0 14px">
        Rien ne change tant que la nouvelle adresse n'est pas confirmée@if($requiresApproval) et que la demande n'est pas validée par le Bureau de Pilotage@endif.
        <b>Si ce n'est pas vous</b>, changez immédiatement votre mot de passe et prévenez le Bureau de Pilotage.
    </p>

    @elseif($kind === 'applied_old')
    <h1 style="margin:0 0 20px;color:#14592A;font-size:22px">Adresse email modifiée</h1>
    <p style="margin:0 0 14px">Bonjour {{ $user->name }},</p>
    <p style="margin:0 0 14px">
        L'adresse email de votre compte JNUAF.ORG a été remplacée par <b>{{ $newEmail }}</b> le {{ $when }}.
        Cette adresse ({{ $oldEmail }}) n'est plus associée au compte.
    </p>
    <p style="margin:0 0 14px">Si vous n'êtes pas à l'origine de ce changement, contactez immédiatement le Bureau de Pilotage.</p>

    @elseif($kind === 'applied_new')
    <h1 style="margin:0 0 20px;color:#14592A;font-size:22px">Votre adresse est maintenant active</h1>
    <p style="margin:0 0 14px">Bonjour {{ $user->name }},</p>
    <p style="margin:0 0 14px">
        Cette adresse (<b>{{ $newEmail }}</b>) est désormais l'adresse de connexion de votre compte JNUAF.ORG.
        Utilisez-la pour vous connecter avec votre mot de passe habituel.
    </p>

    @elseif($kind === 'refused')
    <h1 style="margin:0 0 20px;color:#14592A;font-size:22px">Changement d'adresse refusé</h1>
    <p style="margin:0 0 14px">Bonjour {{ $user->name }},</p>
    <p style="margin:0 0 14px">
        Votre demande de changement d'adresse email vers <b>{{ $newEmail }}</b> n'a pas été validée par le Bureau de Pilotage.
        Votre adresse actuelle ({{ $oldEmail }}) reste inchangée.
    </p>

    @elseif($kind === 'approval')
    <h1 style="margin:0 0 20px;color:#14592A;font-size:22px">Validation requise</h1>
    <p style="margin:0 0 14px">Un compte à accès complet demande à changer d'adresse email. La nouvelle adresse a été confirmée par son propriétaire.</p>
    <div style="margin:18px 0;padding:14px 16px;background:#E3F1E5;border-radius:10px">
        <p style="margin:0 0 4px"><b>Compte :</b> {{ $user->name }}</p>
        <p style="margin:0 0 4px"><b>Rôle :</b> {{ $user->role?->label() }}</p>
        <p style="margin:0 0 4px"><b>Adresse actuelle :</b> {{ $oldEmail }}</p>
        <p style="margin:0 0 4px"><b>Nouvelle adresse :</b> {{ $newEmail }}</p>
        <p style="margin:0 0 4px"><b>Demande faite le :</b> {{ $when }}</p>
        <p style="margin:0"><b>Adresse IP :</b> {{ $ip ?? '-' }}</p>
    </div>
    <p style="margin:0 0 14px">
        <a href="{{ $url }}" style="display:inline-block;padding:12px 20px;background:#14592A;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Examiner la demande</a>
    </p>
    <p style="margin:0;font-size:13px;color:#55685B">
        Lien à usage unique, valable 48 heures. Pour valider, vous devrez être connecté à un compte administrateur (avec double authentification). Sans décision, la demande expire et l'ancienne adresse reste active.
        Ne transférez pas cet email.
    </p>
    @endif

    <p style="margin-top:24px">Cordialement,<br>L'équipe JNUAF.ORG</p>
</x-mail-layout>
