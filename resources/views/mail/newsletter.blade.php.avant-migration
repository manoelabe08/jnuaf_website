<x-mail-layout
    :web-url="$webUrl"
    reply-address="journeedunumeriqueaufeminin@gmail.com"
>
    <h1 style="margin:0 0 20px;color:#14592A;font-size:23px;line-height:1.35">
        {{ $campaign->subject }}
    </h1>

    <div style="line-height:1.7;overflow-wrap:anywhere">
        {!! \App\Support\NewsletterHtml::render($campaign->body) !!}
    </div>

    @if ($campaign->attachments->isNotEmpty())
        <div style="margin:22px 0;padding:14px 16px;background:#E3F1E5;border-radius:10px">
            <p style="margin:0 0 8px;font-weight:bold">
                Documents joints à ce message
            </p>

            <ul style="margin:0;padding-left:20px">
                @foreach ($campaign->attachments as $attachment)
                    <li>{{ $attachment->download_name }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p style="margin-top:24px">
        À bientôt,<br>
        Le Bureau de Pilotage de la JNUAF
    </p>

    <x-slot:footer>
        @if ($unsubscribeUrl)
            <p style="margin:0 0 10px">
                Vous recevez la newsletter de la JNUAF
                dans le cadre de votre abonnement.

                <a
                    href="{{ $unsubscribeUrl }}"
                    style="color:#14592A"
                >
                    Me désinscrire
                </a>

                ·

                <a
                    href="{{ route('home') }}#confidentialite"
                    style="color:#14592A"
                >
                    Confidentialité
                </a>
            </p>
        @endif
    </x-slot:footer>
</x-mail-layout>