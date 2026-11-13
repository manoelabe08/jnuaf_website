@php
    $replyAddress = 'journeedunumeriqueaufeminin@gmail.com';

    $lines = [
        'JNUAF — Newsletter',
        '',
        $campaign->subject,
        '',
        \App\Support\NewsletterHtml::toText($campaign->body),
        '',
    ];

    if ($webUrl) {
        $lines[] = 'Lire dans le navigateur : '.$webUrl;
        $lines[] = '';
    }

    if ($campaign->attachments->isNotEmpty()) {
        $lines[] = 'Documents joints à ce message :';

        foreach ($campaign->attachments as $attachment) {
            $lines[] = '- '.$attachment->download_name;
        }

        $lines[] = '';
    }

    $lines[] = 'À bientôt,';
    $lines[] = 'Le Bureau de Pilotage de la JNUAF';
    $lines[] = '';

    $lines[] = 'À la JNUAF, nous respectons le temps de chacune et de chacun. '
        .'Sauf échéance expressément indiquée, aucune action immédiate '
        .'n’est attendue en dehors de vos horaires habituels.';

    $lines[] = '';

    $lines[] = 'Pour contacter l’équipe JNUAF, vous pouvez répondre '
        .'à ce message. Votre réponse sera adressée à : '
        .$replyAddress;

    $lines[] = '';

    if ($unsubscribeUrl) {
        $lines[] = 'Me désinscrire : '.$unsubscribeUrl;
    }

    $lines[] = 'Confidentialité : '.route('home').'#confidentialite';

    echo implode(PHP_EOL, $lines).PHP_EOL;
@endphp

@include('mail.partials.footer-text')
