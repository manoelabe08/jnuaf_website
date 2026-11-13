
<x-mail-layout>

    <h1 style="margin:0 0 20px;color:#14592A;font-size:23px;line-height:1.35">

        {{ $isReset ? 'Votre mot de passe a été réinitialisé' : 'Votre accès administrateur professionnel' }}

    </h1>



    <p style="margin:0 0 14px">Bonjour {{ $user->name }},</p>



    @if($isReset)

    <p style="margin:0 0 14px">

        Le Bureau de Pilotage a réinitialisé le mot de passe de votre compte administrateur JNUAF.ORG.

        Un nouveau mot de passe temporaire a été généré automatiquement par le système : vous seul le recevez.

    </p>

    @else

    <p style="margin:0 0 14px">

        Un compte <b>administrateur professionnel</b> de la plateforme JNUAF.ORG vient d'être créé à votre nom

        par le Bureau de Pilotage. Il vous donne accès à des données confidentielles de l'organisation.

        Le mot de passe temporaire a été généré automatiquement par le système : personne d'autre que vous ne le connaît.

    </p>

    @endif



    <div style="margin:18px 0;padding:14px 16px;background:#E3F1E5;border-radius:10px">

        <p style="margin:0 0 4px"><b>Email :</b> {{ $user->email }}</p>

        <p style="margin:0 0 4px"><b>Mot de passe temporaire :</b> <span style="font-family:Consolas,Menlo,monospace;font-size:16px">{{ $password }}</span></p>

        <p style="margin:0 0 4px"><b>Rôle :</b> {{ $roleLabel }}</p>

        @if($territoryName)

        <p style="margin:0"><b>Territoire :</b> {{ $territoryName }}</p>

        @endif

    </div>



    <p style="margin:0 0 14px">

        Connectez-vous ici :

        <a href="{{ $loginUrl }}" style="color:#14592A">{{ preg_replace('#^https?://#', '', $loginUrl) }}</a>

    </p>



    <p style="margin:0 0 14px">

        @if($expiresAt)<b>Ce mot de passe temporaire est valable jusqu'au {{ $expiresAt }}.</b> Passé ce délai, utilisez « Mot de passe oublié » sur la page de connexion pour en définir un nouveau.<br><br>@endif<b>Dès votre première connexion, vous devrez choisir un nouveau mot de passe</b> (10 caractères minimum,

        avec lettres et chiffres). Le mot de passe temporaire ci-dessus ne sera plus utilisable ensuite.

    </p>



    <p style="margin:0 0 14px">

        Cet accès est strictement personnel et professionnel : ne le partagez avec personne et ne transférez pas

        cet email. La création et les modifications de ce compte sont enregistrées.

    </p>



    <p style="margin-top:24px">

        Cordialement,<br>

        L'équipe JNUAF.ORG

    </p>

</x-mail-layout>

