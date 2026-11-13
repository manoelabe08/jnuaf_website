
<x-mail-layout>

    <h1 style="margin:0 0 20px;color:#14592A;font-size:22px">Votre code de connexion</h1>

    <p style="margin:0 0 14px">Bonjour {{ $user->name }},</p>

    <p style="margin:0 0 14px">Saisissez ce code pour terminer votre connexion à JNUAF.ORG :</p>

    <p style="margin:18px 0;padding:16px;background:#E3F1E5;border-radius:10px;text-align:center;font-size:32px;letter-spacing:6px;font-weight:700;font-family:Consolas,Menlo,monospace">

        {{ substr($code, 0, 3) }} {{ substr($code, 3) }}

    </p>

    <p style="margin:0 0 14px;font-size:13px;color:#55685B">

        Ce code est valable {{ $minutes }} minutes et ne fonctionne qu'une fois.

        Tentative de connexion le {{ $when }}@if($ip) depuis l'adresse IP {{ $ip }}@endif.

    </p>

    <p style="margin:0 0 14px">

        <b>Si ce n'est pas vous</b>, ne communiquez ce code à personne : changez votre mot de passe et prévenez le Bureau de Pilotage.

    </p>

    <p style="margin-top:24px">Cordialement,<br>L'équipe JNUAF.ORG</p>

</x-mail-layout>

