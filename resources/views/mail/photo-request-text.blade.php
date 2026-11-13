{!! "Bonjour".($first !== '' ? ' '.$first : '')."," !!}



{!! "Pour pr\u{e9}parer votre carte de membre JNUAF et votre profil, il nous manque votre photo. Merci de l'envoyer avant le ".now()->addDays($days)->timezone('Europe/Paris')->format('d/m/Y')." depuis votre lien personnel :" !!}



{!! $url !!}



{!! "Le lien est personnel et utilisable une seule fois." !!}

{!! "Conseils : portrait net, visage d\u{e9}gag\u{e9} et de face, fond clair. Votre photo sert uniquement \u{e0} votre carte de membre et \u{e0} votre profil." !!}



@include('mail.partials.footer-text')

