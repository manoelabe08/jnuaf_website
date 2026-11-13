<x-mail-layout reply-address="team@jnuaf.org">

    <h1 style="margin:0 0 16px;color:#14592A;font-size:22px;line-height:1.35">Bonjour{{ $first !== '' ? ' '.$first : '' }},</h1>

    <p style="margin:0 0 12px">Pour pr&eacute;parer votre <strong>carte de membre JNUAF</strong> et votre profil, il nous manque votre photo. Merci de l&#39;envoyer <strong>avant le {{ now()->addDays($days)->timezone('Europe/Paris')->format('d/m/Y') }}</strong> depuis votre lien personnel.</p>

    <p style="text-align:center;margin:24px 0"><a href="{{ $url }}" style="background:#14592A;color:#ffffff;text-decoration:none;padding:13px 22px;border-radius:10px;font-weight:bold;display:inline-block">Envoyer ma photo</a></p>

    <p style="margin:0 0 8px;font-size:13px;color:#55685B">Le lien est personnel et utilisable une seule fois. S&#39;il ne fonctionne pas, copiez cette adresse dans votre navigateur :<br><a href="{{ $url }}" style="color:#14592A;overflow-wrap:anywhere">{{ $url }}</a></p>

    <p style="margin:12px 0 0;font-size:13px;color:#55685B">Conseils : portrait net, visage d&eacute;gag&eacute; et de face, fond clair, format vertical si possible. Votre photo sert uniquement &agrave; votre carte de membre et &agrave; votre profil ; vous pouvez en demander la suppression en r&eacute;pondant &agrave; ce message.</p>

</x-mail-layout>

