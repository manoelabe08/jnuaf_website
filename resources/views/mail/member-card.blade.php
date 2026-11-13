
<x-mail-layout reply-address="team@jnuaf.org">

    <h1 style="margin:0 0 20px;color:#14592A;font-size:23px;line-height:1.35">

        Votre carte de membre JNUAF

    </h1>



    <p style="margin:0 0 14px">Bonjour {{ $card->full_name }},</p>



    <p style="margin:0 0 14px">

        Vous trouverez en pièce jointe votre carte de membre JNUAF, au format PDF.

        Elle comporte un QR code qui permet de vérifier sa validité.

    </p>



    <div style="margin:18px 0;padding:14px 16px;background:#E3F1E5;border-radius:10px">

        <p style="margin:0 0 4px"><b>Numéro de membre :</b> {{ $card->number }}</p>

        <p style="margin:0 0 4px"><b>Catégorie :</b> {{ $categoryLabel }}</p>

        <p style="margin:0"><b>Valable jusqu'au :</b> {{ $card->valid_until->format('d/m/Y') }}</p>

    </div>



    <p style="margin:0 0 14px">

        Pour toute question sur l'utilisation de cette carte, merci de consulter

        <a href="{{ $infoUrl }}" style="color:#14592A">{{ preg_replace('#^https?://#', '', $infoUrl) }}</a>.

    </p>



    <p style="margin-top:24px">

        Cordialement,<br>

        Le Bureau de Pilotage de la JNUAF

    </p>

</x-mail-layout>

