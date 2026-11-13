<x-mail-layout reply-address="team@jnuaf.org">

    <h1 style="margin:0 0 16px;color:#14592A;font-size:22px;line-height:1.35">Bonjour{{ $first !== '' ? ' '.$first : '' }},</h1>

    <p style="margin:0 0 12px">Votre photo n&#39;a malheureusement pas pu &ecirc;tre retenue.</p>

    <div style="margin:16px 0;padding:12px 16px;background:#FDECEA;border-radius:10px;color:#B42318"><strong>Motif :</strong> {{ $reason }}</div>

    <p style="margin:0 0 12px">Cette photo sert &agrave; votre <strong>carte de membre</strong> : merci d&#39;envoyer une <strong>photo d&#39;identit&eacute; pr&eacute;sentable</strong> (portrait net, visage d&eacute;gag&eacute; et de face, fond clair, vous seul(e), sans filtre) <strong>avant le {{ now()->addDays($days)->timezone('Europe/Paris')->format('d/m/Y') }}</strong>.</p>

    <p style="text-align:center;margin:24px 0"><a href="{{ $url }}" style="background:#14592A;color:#ffffff;text-decoration:none;padding:13px 22px;border-radius:10px;font-weight:bold;display:inline-block">Envoyer une nouvelle photo</a></p>

    <p style="margin:0;font-size:13px;color:#55685B">Le lien est personnel et utilisable une seule fois. S&#39;il ne fonctionne pas, copiez cette adresse dans votre navigateur :<br><a href="{{ $url }}" style="color:#14592A;overflow-wrap:anywhere">{{ $url }}</a></p>

</x-mail-layout>

