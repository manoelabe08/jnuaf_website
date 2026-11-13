<x-mail-layout>
  <p style="margin-top:0">Bonjour {{ $user->name }},</p>
  <p>Une réinitialisation du mot de passe de votre compte de l'espace équipe JNUAF a été demandée.</p>
  <p style="text-align:center;margin:24px 0">
    <a href="{{ $url }}" style="background:#F26B1D;color:#fff;text-decoration:none;font-weight:bold;padding:13px 22px;border-radius:999px;display:inline-block">Choisir un nouveau mot de passe</a>
  </p>
  <p style="font-size:13px;color:#55685B">Ce lien est valable 3 minutes. Si vous n'êtes pas à l'origine de cette demande, ignorez cet email : votre mot de passe reste inchangé.</p>
</x-mail-layout>
