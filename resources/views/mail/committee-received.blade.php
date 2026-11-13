<x-mail-layout>
<p style="margin-top:0">Bonjour {{ $application->full_name }},</p>
<p>Merci pour votre candidature au comité d'organisation de la JNUAF, pôle <b>{{ $application->pole }}</b> ({{ $application->country }}).</p>
<p>Nous avons bien reçu votre CV et votre lettre de motivation. Le Bureau de Pilotage étudie chaque candidature et reviendra vers vous par email ou WhatsApp.</p>
<p style="font-size:13px;color:#55685B">Vos documents sont conservés pendant la durée du recrutement, puis supprimés {{ config('jnuaf.privacy.committee_retention_months') }} mois après la décision.</p>
<p style="margin-bottom:0">Merci de votre engagement,
Le Bureau de Pilotage de la JNUAF</p>
</x-mail-layout>