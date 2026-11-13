<x-mail-layout>
    <h1 style="margin:0 0 20px;color:#14592A;font-size:22px;line-height:1.35">
        Votre mot de passe temporaire expire bientôt
    </h1>

    <p style="margin:0 0 14px">Bonjour {{ $user->name }},</p>

    <p style="margin:0 0 14px">
        Le mot de passe temporaire de votre accès JNUAF.ORG expire le <b>{{ $expiresAt }}</b>.
        Connectez-vous avant cette date pour choisir votre propre mot de passe :
        <a href="{{ $loginUrl }}" style="color:#14592A">{{ preg_replace('#^https?://#', '', $loginUrl) }}</a>
    </p>

    <p style="margin:0 0 14px">
        Si ce délai est dépassé, vous pourrez toujours en définir un nouveau avec
        <a href="{{ $forgotUrl }}" style="color:#14592A">« Mot de passe oublié »</a>.
    </p>

    <p style="margin-top:24px">
        Cordialement,<br>
        L'équipe JNUAF.ORG
    </p>
</x-mail-layout>
