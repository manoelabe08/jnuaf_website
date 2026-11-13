
<x-mail-layout>

  <p style="margin-top:0;font-size:18px"><b>Nouvelle connexion à votre compte</b></p>

  <p>Bonjour {{ $name }},</p>

  <p>Votre compte JNUAF vient d'être utilisé depuis un appareil que nous ne connaissions pas encore.</p>

  <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:8px 0 18px">

    <tr>

      <td style="padding:7px 10px 7px 0;color:#55685B;font-size:13px;white-space:nowrap">Date</td>

      <td style="padding:7px 0;font-size:14px;border-bottom:1px solid #DCE6DD">{{ $at->timezone('Europe/Paris')->format('d/m/Y à H:i') }}</td>

    </tr>

    <tr>

      <td style="padding:7px 10px 7px 0;color:#55685B;font-size:13px;white-space:nowrap">Appareil</td>

      <td style="padding:7px 0;font-size:14px;border-bottom:1px solid #DCE6DD">{{ $device }}</td>

    </tr>

    <tr>

      <td style="padding:7px 10px 7px 0;color:#55685B;font-size:13px;white-space:nowrap">Adresse IP</td>

      <td style="padding:7px 0;font-size:14px;border-bottom:1px solid #DCE6DD">{{ $ip }}</td>

    </tr>

  </table>

  <p>Ce n'était pas vous ? <a href="{{ route('password.request') }}">Réinitialisez votre mot de passe maintenant</a>.</p>
  <p>Si c'est bien vous, vous n'avez rien à faire.</p>

  <p>Sinon, changez votre mot de passe sans attendre avec « Mot de passe oublié » sur la page de connexion, et prévenez l'équipe à <a href="mailto:team@jnuaf.org">team@jnuaf.org</a>.</p>

</x-mail-layout>

