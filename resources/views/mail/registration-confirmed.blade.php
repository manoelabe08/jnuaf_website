<x-mail-layout>
  <p style="margin-top:0">Bonjour {{ $registration->first_name }},</p>
  @if ($promoted)
  <p><b>Bonne nouvelle : une place s'est libérée et vous a été attribuée.</b></p>
  @endif
  <p>Votre inscription @if ($registration->isEventOnly()) &agrave; <b>{{ $registration->eventTitle() }}</b> @else &agrave; la <b>JNUAF {{ $registration->edition }}</b> @endif en tant que <b>{{ $registration->profile->label() }}</b> sur le territoire <b>{{ $registration->territory->cityWithPreposition() }}</b> est confirmée.</p>
  <p>Votre badge est joint à cet email au format PDF. Présentez son QR code à l'entrée le jour J (sur téléphone ou imprimé).</p>
  <p style="background:#E3F1E5;border-radius:10px;padding:12px 14px;font-family:monospace">Identifiant de badge : {{ $registration->badge_code }}</p>
  <p>Pour consulter, modifier ou annuler votre inscription, ou supprimer vos données, utilisez votre lien personnel :</p>
  <p style="text-align:center;margin:24px 0">
    <a href="{{ $manageUrl }}" style="background:#F26B1D;color:#fff;text-decoration:none;font-weight:bold;padding:13px 22px;border-radius:999px;display:inline-block">Gérer mon inscription</a>
  </p>
  <p style="font-size:13px;color:#55685B">Ce lien est personnel : ne le transférez pas. Un rappel vous sera envoyé quelques jours avant l'événement.</p>
  <p style="margin-bottom:0">À très bientôt,<br>Le Bureau de Pilotage de la JNUAF</p>
</x-mail-layout>
