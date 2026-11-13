
Bonjour {{ $card->full_name }},



Vous trouverez en pièce jointe votre carte de membre JNUAF, au format PDF.



Numéro de membre : {{ $card->number }}

Catégorie : {{ $categoryLabel }}

Valable jusqu'au : {{ $card->valid_until->format('d/m/Y') }}



Pour toute question sur l'utilisation de cette carte, merci de consulter {{ $infoUrl }}



Cordialement,

Le Bureau de Pilotage de la JNUAF

@include('mail.partials.footer-text')
