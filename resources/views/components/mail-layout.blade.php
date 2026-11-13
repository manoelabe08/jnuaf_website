@props([
    'webUrl' => null,
    'replyAddress' => null,
])

@php
    $logoName = null;
    $logoWidth = 220;

    if (is_file(public_path('images/mail-logo.png'))) {
        $logoName = 'mail-logo.png';
    } elseif (is_file(public_path('images/logo.jpg'))) {
        $logoName = 'logo.jpg';
        $logoWidth = 110;
    }

    $contactEmail = config('jnuaf.contact_email')
        ?: config('partner_portal.contact_email');

    $hasReplyAddress = trim((string) $replyAddress) !== '';
@endphp

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >
    <meta name="color-scheme" content="light">
</head>

<body style="margin:0;background:#F7FAF6;font-family:Arial,Helvetica,sans-serif;color:#13281A">
    <table
        role="presentation"
        width="100%"
        cellpadding="0"
        cellspacing="0"
        style="background:#F7FAF6;padding:24px 12px"
    >
        <tr>
            <td align="center">

                @if ($webUrl)
                    <table
                        role="presentation"
                        width="100%"
                        cellpadding="0"
                        cellspacing="0"
                        style="max-width:560px"
                    >
                        <tr>
                            <td style="padding:0 4px 12px;font-size:12px;color:#55685B;text-align:center">
                                Cet email ne s’affiche pas correctement ?

                                <a
                                    href="{{ $webUrl }}"
                                    style="color:#14592A"
                                >
                                    Lire dans votre navigateur
                                </a>
                            </td>
                        </tr>
                    </table>
                @endif

                <table
                    role="presentation"
                    width="100%"
                    cellpadding="0"
                    cellspacing="0"
                    style="max-width:560px;background:#FFFFFF;border-radius:14px;overflow:hidden;border:1px solid #DCE6DD"
                >
                    <tr>
                        <td
                            align="center"
                            style="background:#FFFFFF;padding:22px 28px;text-align:center"
                        >
                            @if ($logoName)
                                <img
                                    src="{{ asset('images/'.$logoName) }}"
                                    alt="JNUAF — Journée du Numérique au Féminin"
                                    width="{{ $logoWidth }}"
                                    style="display:block;margin:0 auto;border:0;height:auto;max-width:{{ $logoWidth }}px"
                                >
                            @else
                                <div style="font-size:24px;font-weight:bold;letter-spacing:1px;color:#14592A;text-align:center">
                                    <span style="color:#F26B1D">JN</span>UAF
                                </div>

                                <div style="font-size:12px;color:#55685B;text-align:center">
                                    Journée du Numérique au Féminin
                                </div>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="height:5px;background:#F26B1D"></td>
                    </tr>

                    <tr>
                        <td style="padding:28px;font-size:15px;line-height:1.6">
                            {{ $slot }}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 28px;border-top:1px solid #DCE6DD;font-size:12px;line-height:1.6;color:#55685B">

                            <p style="margin:0 0 12px">
                                À la JNUAF, nous respectons le temps
                                de chacune et de chacun.
                                Sauf échéance expressément indiquée,
                                aucune action immédiate n’est attendue
                                en dehors de vos horaires habituels.
                            </p>

                            @if ($hasReplyAddress)
                                <p style="margin:0 0 12px">
                                    Pour contacter l’équipe JNUAF,
                                    vous pouvez répondre à ce message.
                                    Votre réponse sera adressée à :

                                    <a
                                        href="mailto:{{ $replyAddress }}"
                                        style="color:#14592A;overflow-wrap:anywhere"
                                    >
                                        {{ $replyAddress }}
                                    </a>
                                </p>
                            @else
                                <p style="margin:0 0 12px">
                                    Ce message est envoyé automatiquement
                                    depuis une adresse qui ne reçoit
                                    pas de réponses.

                                    @if ($contactEmail)
                                        Pour nous écrire :

                                        <a
                                            href="mailto:{{ $contactEmail }}"
                                            style="color:#14592A;overflow-wrap:anywhere"
                                        >
                                            {{ $contactEmail }}
                                        </a>.
                                    @endif
                                </p>
                            @endif

                            @if (isset($footer) && trim((string) $footer) !== '')
                                <div style="margin:0 0 12px">
                                    {{ $footer }}
                                </div>
                            @endif

                            <p style="margin:0">
                                @foreach (config('jnuaf.contacts', []) as $contact)
                                    {{ $contact['label'] }} :
                                    {{ $contact['phone'] }}

                                    @if (! $loop->last)
                                        ·
                                    @endif
                                @endforeach
                            </p>


                            <p style="margin:10px 0 0">

                                <strong style="color:#14592A">Suivez-nous</strong><br>

                                @foreach (config('jnuaf_social.links', []) as $social)

                                    <a href="{{ $social['url'] }}" style="color:#14592A;text-decoration:underline">{{ $social['label'] }}</a>@if (! $loop->last) &middot; @endif

                                @endforeach

                            </p>


                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>