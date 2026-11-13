<x-mail-layout>
  <p style="margin-top:0">Bonjour {{ $registration->first_name }},</p>
  <p>Merci pour votre inscription @if ($registration->isEventOnly()) &agrave; <b>{{ $registration->eventTitle() }}</b>. Toutes les places de cet &eacute;v&eacute;nement sont @else &agrave; la <b>JNUAF {{ $registration->edition }}</b>. Toutes les places du territoire <b>{{ $registration->territory->cityWithPreposition() }}</b> sont @endif actuellement prises : vous êtes inscrite sur <b>liste d'attente</b>.</p>
  <p>Dès qu'une place se libère, elle vous est attribuée automatiquement, dans l'ordre d'inscription : vous recevrez alors un email de confirmation avec votre badge d'entrée.</p>
  <p>Vous pouvez consulter votre inscription, la modifier ou vous retirer de la liste avec votre lien personnel :</p>
  <p style="text-align:center;margin:24px 0">
    <a href="{{ $manageUrl }}" style="background:#F26B1D;color:#fff;text-decoration:none;font-weight:bold;padding:13px 22px;border-radius:999px;display:inline-block">Gérer mon inscription</a>
  </p>
  <p style="font-size:13px;color:#55685B">Sans badge, l'entrée le jour J n'est pas garantie.</p>
  <p style="margin-bottom:0">À bientôt,<br>Le Bureau de Pilotage de la JNUAF</p>
</x-mail-layout>
