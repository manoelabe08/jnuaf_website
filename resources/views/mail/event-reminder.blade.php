<x-mail-layout>
  <p style="margin-top:0">Bonjour {{ $registration->first_name }},</p>
  <p>@if ($registration->isEventOnly()) <b>{{ $registration->eventTitle() }}</b> @else La <b>JNUAF {{ $registration->edition }}</b> @endif a lieu le <b>{{ $registration->eventDate()->translatedFormat('l d F Y') }}</b>. Nous avons hâte de vous accueillir à {{ $registration->territory->city }} !</p>
  <p>Votre badge est de nouveau joint à cet email. À l'entrée, présentez simplement son QR code : un scan suffit.</p>
  <p style="background:#E3F1E5;border-radius:10px;padding:12px 14px;font-family:monospace">Identifiant de badge : {{ $registration->badge_code }}</p>
  @if ($registration->accessibility_needs)
    <p>Vous nous avez signalé un besoin spécifique ({{ $registration->accessibility_needs }}) : votre coordination territoriale en est informée.</p>
  @endif
  <p>Un empêchement ? Utilisez le lien personnel reçu lors de votre inscription pour libérer votre place.</p>
  <p style="margin-bottom:0">À très vite,<br>Le Bureau de Pilotage de la JNUAF</p>
</x-mail-layout>
