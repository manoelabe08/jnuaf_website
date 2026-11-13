<x-mail-layout>
    @php
        $kind = $data['kind'] ?? 'generic_partner';
        $name = trim((string) ($data['name'] ?? ''));
        $contact = config('partner_portal.contact_email');
        $adminMail = in_array($kind, ['admin_document', 'admin_revoked', 'generic_admin'], true);
    @endphp

    <p style="margin-top:0">
    {{ (!$adminMail && $name !== '') ? 'Bonjour '.$name.',' : 'Bonjour,' }}
</p>

    @switch($kind)
        @case('invitation')
            <h1 style="font-size:22px;line-height:1.3;color:#14592A;margin:0 0 18px">Vos documents de partenariat</h1>
            <p>Votre espace documentaire JNUAF est disponible. Vous y retrouverez les documents que le comité d'organisation a mis à votre disposition.</p>
            <p>Pour vous connecter, ouvrez votre espace puis saisissez cette adresse email :</p>
            <p style="background:#E3F1E5;border-radius:10px;padding:12px 14px;word-break:break-word"><b>{{ $data['email'] }}</b></p>
            <p>Vous recevrez un code temporaire pour accéder à vos documents.</p>
            <p style="text-align:center;margin:26px 0">
                <a href="{{ $data['url'] }}" style="background:#F26B1D;color:#fff;text-decoration:none;font-weight:bold;padding:13px 22px;border-radius:999px;display:inline-block">Accéder à mes documents</a>
            </p>
            <p style="font-size:13px;color:#55685B">Ce lien est personnel. Pour protéger votre accès, ne le transférez pas.</p>
            @break

        @case('code')
            <h1 style="font-size:22px;line-height:1.3;color:#14592A;margin:0 0 18px">Votre code de connexion</h1>
            <p>Pour accéder à votre espace documentaire JNUAF, saisissez le code suivant sur la page de connexion :</p>
            <p style="background:#E3F1E5;border-radius:10px;padding:20px 12px;font-family:Consolas,monospace;font-size:32px;font-weight:bold;letter-spacing:7px;text-align:center;color:#14592A">{{ $data['code'] }}</p>
            <p>Ce code est valable <b>{{ $data['valid_minutes'] ?? 5 }} minutes à compter de son émission</b> et ne peut être utilisé qu'une seule fois.</p>
            <p style="font-size:13px;color:#55685B">Ne communiquez ce code à personne. Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer ce message et contacter le comité en cas de doute.</p>
            @break

        @case('partner_revoked')
            <h1 style="font-size:22px;line-height:1.3;color:#14592A;margin:0 0 18px">Renouveler votre accès</h1>
            <p>Votre précédent lien ne permet plus d'accéder à votre espace documentaire JNUAF.</p>
            <p>Pour obtenir un nouveau lien, veuillez contacter le comité d'organisation :</p>
            <p style="text-align:center;margin:26px 0">
                <a href="mailto:{{ $contact }}" style="background:#F26B1D;color:#fff;text-decoration:none;font-weight:bold;padding:13px 22px;border-radius:999px;display:inline-block">Contacter le comité</a>
            </p>
            <p style="font-size:13px;color:#55685B">Le comité pourra vous accompagner pour rétablir votre accès aux documents.</p>
            @break

        @case('admin_revoked')
            <h1 style="font-size:22px;line-height:1.3;color:#14592A;margin:0 0 18px">Un accès partenaire est à renouveler</h1>
            <p>Le lien de l'espace ci-dessous a été désactivé.</p>
            <div style="background:#E3F1E5;border-radius:10px;padding:14px 16px">
                <p style="margin:0 0 8px"><b>Partenaire :</b> {{ $data['name'] }}</p>
                <p style="margin:0 0 8px;word-break:break-word"><b>Email :</b> {{ $data['email'] }}</p>
                <p style="margin:0"><b>Motif :</b> {{ $data['reason'] }}</p>
            </div>
            <p>Consultez sa fiche pour vérifier la situation et, si nécessaire, lui transmettre un nouveau lien.</p>
            <p style="text-align:center;margin:26px 0">
                <a href="{{ $data['url'] }}" style="background:#F26B1D;color:#fff;text-decoration:none;font-weight:bold;padding:13px 22px;border-radius:999px;display:inline-block">Gérer cet accès</a>
            </p>
            @break

        @case('admin_document')
            <h1 style="font-size:22px;line-height:1.3;color:#14592A;margin:0 0 18px">Un partenaire a accédé à un document</h1>
            <p>Un premier accès a été enregistré pour le document ci-dessous.</p>
            <div style="background:#E3F1E5;border-radius:10px;padding:14px 16px">
                <p style="margin:0 0 8px"><b>Partenaire :</b> {{ $data['name'] }}</p>
                <p style="margin:0 0 8px;word-break:break-word"><b>Email :</b> {{ $data['email'] }}</p>
                <p style="margin:0 0 8px"><b>Document :</b> {{ $data['document'] }}</p>
                <p style="margin:0 0 8px"><b>Accès :</b> {{ $data['action'] }}</p>
                <p style="margin:0"><b>Date :</b> {{ $data['date'] }}</p>
            </div>
            <p style="text-align:center;margin:26px 0">
                <a href="{{ $data['url'] }}" style="background:#F26B1D;color:#fff;text-decoration:none;font-weight:bold;padding:13px 22px;border-radius:999px;display:inline-block">Voir le suivi du partenaire</a>
            </p>
            @break

        @case('generic_admin')
            <h1 style="font-size:22px;line-height:1.3;color:#14592A;margin:0 0 18px">Suivi d'un espace partenaire</h1>
            <p>Un événement concernant un espace partenaire a été enregistré. Retrouvez ses détails dans l'historique des espaces documentaires.</p>
            <p style="text-align:center;margin:26px 0">
                <a href="{{ $data['url'] }}" style="background:#F26B1D;color:#fff;text-decoration:none;font-weight:bold;padding:13px 22px;border-radius:999px;display:inline-block">Ouvrir le suivi des partenaires</a>
            </p>
            @break

        @default
            <h1 style="font-size:22px;line-height:1.3;color:#14592A;margin:0 0 18px">Votre espace documentaire JNUAF</h1>
            <p>Une mise à jour concerne votre accès documentaire. Si vous avez besoin d'aide, veuillez contacter le comité d'organisation.</p>
    @endswitch

    <p style="margin-top:24px;font-size:13px;color:#55685B">
        Besoin d'aide ? <a href="mailto:{{ $contact }}" style="color:#14592A;word-break:break-word">{{ $contact }}</a>
    </p>
    <p style="margin-bottom:0">À bientôt,<br>Le Bureau de Pilotage de la JNUAF</p>
</x-mail-layout>
