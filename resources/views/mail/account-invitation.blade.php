<x-mail-layout>

    <h1 style="margin:0 0 20px;color:#14592A;font-size:23px;line-height:1.35">

        @if($isReset)

        Nouvelle invitation pour votre compte

        @else

        Votre acc&egrave;s administrateur professionnel

        @endif

    </h1>

    <p style="margin:0 0 14px">Bonjour {{ $user->name }},</p>

    @if($isReset)

    <p style="margin:0 0 14px">

        Le Bureau de Pilotage a r&eacute;initialis&eacute; l'acc&egrave;s &agrave; votre compte JNUAF.ORG.

        Aucun mot de passe n'est envoy&eacute; par email : d&eacute;finissez le v&ocirc;tre avec le lien ci-dessous.

    </p>

    @else

    <p style="margin:0 0 14px">

        Un compte <b>administrateur professionnel</b> de la plateforme JNUAF.ORG vient d'&ecirc;tre cr&eacute;&eacute; &agrave; votre nom

        par le Bureau de Pilotage. Il vous donne acc&egrave;s &agrave; des donn&eacute;es confidentielles de l'organisation.

        Aucun mot de passe n'est envoy&eacute; par email : vous d&eacute;finissez le v&ocirc;tre avec le lien ci-dessous.

    </p>

    @endif

    <div style="margin:18px 0;padding:14px 16px;background:#E3F1E5;border-radius:10px">

        <p style="margin:0 0 4px"><b>Email :</b> {{ $user->email }}</p>

        <p style="margin:0 0 4px"><b>R&ocirc;le :</b> {{ $roleLabel }}</p>

        @if($territoryName)

        <p style="margin:0"><b>Territoire :</b> {{ $territoryName }}</p>

        @endif

    </div>

    <p style="margin:18px 0">

        <a href="{{ $inviteUrl }}" style="display:inline-block;padding:12px 22px;background:#14592A;color:#ffffff;border-radius:8px;text-decoration:none;font-weight:bold">D&eacute;finir mon mot de passe</a>

    </p>

    <p style="margin:0 0 14px">

        Ce lien est personnel, &agrave; usage unique et valable {{ $validHours }} heures.

        S'il a expir&eacute;, demandez une nouvelle invitation au Bureau de Pilotage.

    </p>

    <p style="margin:0 0 14px;font-size:13px">

        Si le bouton ne s'affiche pas, copiez ce lien dans votre navigateur :<br>

        <span style="word-break:break-all">{{ $inviteUrl }}</span>

    </p>

    <p style="margin:0 0 14px">

        Apr&egrave;s avoir d&eacute;fini votre mot de passe, connectez-vous ici :

        <a href="{{ $loginUrl }}" style="color:#14592A">{{ preg_replace('#^https?://#', '', $loginUrl) }}</a>

    </p>

    <p style="margin:0 0 14px">

        Cet acc&egrave;s est strictement personnel et professionnel : ne le partagez avec personne et ne transf&eacute;rez pas cet email.

        La cr&eacute;ation et les modifications de ce compte sont enregistr&eacute;es.

    </p>

    <p style="margin-top:24px">

        Cordialement,<br>

        L'&eacute;quipe JNUAF.ORG

    </p>

</x-mail-layout>

