<x-mail-layout>
  <p style="margin-top:0">Bonjour {{ $registration->first_name }},</p>
  <p>Merci d'avoir participé @if ($registration->isEventOnly()) &agrave; <b>{{ $registration->eventTitle() }}</b> @else &agrave; la <b>JNUAF {{ $registration->edition }}</b> @endif à {{ $registration->territory->city }} ! Votre présence a contribué à faire de cette journée un moment fort pour le numérique au féminin.</p>
  <p>Votre <b>attestation de participation</b> est jointe à cet email au format PDF.</p>
  <p>Pour rester informée des prochains programmes (Tournée du Numérique, webinaires, mentorat) et de la prochaine édition, suivez #JNUAF.ORG.</p>
  <p style="margin-bottom:0">À très bientôt,<br>Le Bureau de Pilotage de la JNUAF</p>
</x-mail-layout>
