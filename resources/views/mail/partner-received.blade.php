<x-mail-layout>
  <p style="margin-top:0">Bonjour {{ $inquiry->contact_name }},</p>
  <p>Merci de l'intérêt que {{ $inquiry->organization }} porte à <b>#JNUAF.ORG</b> et à notre action pour la place des femmes dans le numérique.</p>
  <p>Votre proposition a bien été transmise à l'équipe Partenariats &amp; Sponsoring :</p>
  <ul>@foreach ($inquiry->typeLabels() as $label)<li>{{ $label }}</li>@endforeach</ul>
  <p>Nous reviendrons vers vous rapidement pour en discuter et vous présenter notre dossier de partenariat.</p>
  <p style="margin-bottom:0">Bien cordialement,<br>L'équipe Partenariats &amp; Sponsoring de #JNUAF.ORG</p>
</x-mail-layout>
