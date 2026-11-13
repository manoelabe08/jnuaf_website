{!! "Bonjour".($first !== '' ? ' '.$first : '')."," !!}



{!! "Votre photo n'a malheureusement pas pu \u{ea}tre retenue." !!}

{!! "Motif : ".$reason !!}



{!! "Cette photo sert \u{e0} votre carte de membre : merci d'envoyer une photo d'identit\u{e9} pr\u{e9}sentable (portrait net, visage d\u{e9}gag\u{e9} et de face, fond clair, vous seul(e), sans filtre) avant le ".now()->addDays($days)->timezone('Europe/Paris')->format('d/m/Y')." :" !!}



{!! $url !!}



@include('mail.partials.footer-text')

