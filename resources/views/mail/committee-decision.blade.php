<x-mail-layout>
    <p style="margin-top:0">
        Bonjour {{ $application->full_name }},
    </p>

    @if ($decision === \App\Enums\ApplicationStatus::Accepted)
        <h2 style="color:#14592A">
            Votre candidature est retenue !
        </h2>

        <p>
            Nous avons le plaisir de vous informer que votre candidature
            au comité d'organisation de la JNUAF
            {{ config('jnuaf.edition') }} a été retenue.
        </p>

        <p>
            Pôle demandé : <b>{{ $application->pole }}</b><br>
            Pays : {{ $application->country }}
        </p>

        <p>
            Merci pour votre engagement et votre volonté de contribuer
            à cette aventure collective.
        </p>

        <p>
            Le Bureau de Pilotage vous contactera prochainement
            par email ou WhatsApp pour vous communiquer les prochaines
            étapes et les modalités de votre participation.
        </p>
    @elseif ($decision === \App\Enums\ApplicationStatus::Rejected)
        <h2 style="color:#14592A">
            Réponse à votre candidature
        </h2>

        <p>
            Merci pour votre candidature au comité d'organisation
            de la JNUAF {{ config('jnuaf.edition') }},
            pôle <b>{{ $application->pole }}</b>.
        </p>

        <p>
            Après examen de votre dossier, nous sommes au regret
            de ne pas retenir votre candidature pour ce recrutement.
        </p>

        <p>
            Nous vous remercions sincèrement pour votre intérêt,
            votre engagement et le temps consacré à votre candidature.
        </p>

        <p>
            Nous espérons vous retrouver à l'occasion des prochaines
            activités de la JNUAF.
        </p>
    @endif

    <p style="margin-bottom:0">
        Cordialement,<br>
        Le Bureau de Pilotage de la JNUAF
    </p>
</x-mail-layout>