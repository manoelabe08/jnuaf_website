<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover"
    >
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="referrer" content="no-referrer">

    @include('partials.favicons')

    <title>Espace partenaires · JNUAF</title>

    <link
        rel="stylesheet"
        href="{{ asset('fonts/fonts.css') }}?v=1"
    >
    <link
        rel="stylesheet"
        href="{{ asset('css/site.css') }}?v=10"
    >

    <style>
        body.partner-page {
            margin: 0;
            min-height: 100vh;
            background: #F7FAF6;
            color: #13281A;
        }

        .partner-page *,
        .partner-page *::before,
        .partner-page *::after {
            box-sizing: border-box;
        }

        .partner-header {
            background: #FFFFFF;
            border-bottom: 1px solid #DCE6DD;
        }

        .partner-header-inner {
            width: min(1120px, calc(100% - 40px));
            margin: 0 auto;
            min-height: 96px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .partner-brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            color: #14592A;
            text-decoration: none;
        }

        .partner-brand-mark {
            font-size: 32px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -1px;
            white-space: nowrap;
        }

        .partner-brand-mark span {
            color: #F26B1D;
        }

        .partner-brand-description {
            padding-left: 14px;
            border-left: 1px solid #DCE6DD;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.4;
            color: #55685B;
        }

        .partner-home-link {
            color: #14592A;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }

        .partner-home-link:hover {
            text-decoration: underline;
        }

        .partner-banner {
            background: #14592A;
            border-bottom: 5px solid #F26B1D;
            color: #FFFFFF;
        }

        .partner-banner-inner {
            width: min(960px, calc(100% - 40px));
            margin: 0 auto;
            padding: 30px 0;
        }

        .partner-eyebrow {
            margin: 0 0 8px;
            color: #F9C49E;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .partner-banner-title {
            margin: 0;
            color: #FFFFFF;
            font-size: clamp(24px, 4vw, 34px);
            font-weight: 750;
            line-height: 1.2;
        }

        .partner-banner-description {
            max-width: 650px;
            margin: 12px 0 0;
            color: #E3F1E5;
            font-size: 15px;
            line-height: 1.6;
        }

        main.partner-main {
            width: min(960px, calc(100% - 40px));
            margin: 0 auto;
            padding: 36px 0 48px;
        }

        .partner-main h1 {
            margin: 0 0 18px;
            color: #14592A;
            font-size: clamp(25px, 4vw, 34px);
            line-height: 1.25;
        }

        .partner-main h2 {
            margin: 0 0 14px;
            color: #14592A;
            font-size: 21px;
            line-height: 1.35;
        }

        .partner-main p {
            line-height: 1.7;
        }

        .partner-main .card {
            margin: 20px 0;
            padding: 28px;
            background: #FFFFFF;
            border: 1px solid #DCE6DD;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(20, 89, 42, 0.045);
        }

        .partner-main .muted {
            color: #55685B;
            font-size: 14px;
        }

        .partner-main label {
            display: block;
            margin: 18px 0 8px;
            color: #13281A;
            font-size: 14px;
            font-weight: 700;
        }

        .partner-main input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]) {
            display: block;
            width: 100%;
            max-width: 100%;
            min-height: 50px;
            padding: 12px 15px;
            background: #FFFFFF;
            border: 1px solid #B7C9BA;
            border-radius: 11px;
            color: #13281A;
            font: inherit;
        }

        .partner-main input:focus {
            outline: 3px solid rgba(242, 107, 29, 0.2);
            outline-offset: 2px;
            border-color: #F26B1D;
        }

        .partner-main .btn,
        .partner-main button,
        .partner-dialog button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            margin-top: 16px;
            padding: 12px 21px;
            background: #F26B1D;
            border: 1px solid #F26B1D;
            border-radius: 999px;
            color: #FFFFFF;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.3;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .partner-main .btn:hover,
        .partner-main button:hover,
        .partner-dialog button:hover {
            background: #D95610;
            border-color: #D95610;
            color: #FFFFFF;
        }

        .partner-main button:disabled,
        .partner-dialog button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .partner-page a:focus-visible,
        .partner-page button:focus-visible {
            outline: 3px solid #F26B1D;
            outline-offset: 4px;
        }

        .partner-main .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .partner-main .actions .btn + .btn {
            background: #FFFFFF;
            border-color: #14592A;
            color: #14592A;
        }

        .partner-main .actions .btn + .btn:hover {
            background: #E3F1E5;
        }

        .partner-main .code {
            max-width: 340px !important;
            background: #F7FAF6 !important;
            color: #14592A !important;
            font-family: Consolas, monospace !important;
            font-size: 28px !important;
            font-weight: 700 !important;
            letter-spacing: 7px;
            text-align: center;
        }

        .partner-main .alert {
            margin: 0 0 22px;
            padding: 16px 19px;
            background: #E3F1E5;
            border: 1px solid #C7DFC9;
            border-radius: 12px;
            color: #14592A;
            line-height: 1.6;
        }

        .partner-main .alert.error {
            background: #FFF3ED;
            border-color: #F1C4AB;
            color: #843A18;
        }

        .partner-main .alert p {
            margin: 4px 0;
        }

        .partner-main #countdown {
            display: inline-block;
            margin-top: 18px;
            padding: 10px 14px;
            background: #E3F1E5;
            border-radius: 10px;
            color: #14592A;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .partner-main #wait {
            margin-bottom: 0;
            color: #55685B;
            font-size: 13px;
            line-height: 1.6;
        }

        footer.partner-footer {
            margin: 0;
            padding: 26px 20px;
            background: #FFFFFF;
            border-top: 1px solid #DCE6DD;
            color: #55685B;
            font-size: 13px;
            line-height: 1.7;
        }

        .partner-footer-inner {
            width: min(960px, 100%);
            margin: 0 auto;
        }

        .partner-footer p {
            margin: 6px 0;
        }

        .partner-footer a {
            color: #14592A;
            overflow-wrap: anywhere;
        }

        dialog.partner-dialog {
            width: min(520px, calc(100% - 32px));
            max-height: calc(100vh - 48px);
            padding: 28px;
            overflow-y: auto;
            background: #FFFFFF;
            border: 1px solid #DCE6DD;
            border-radius: 18px;
            color: #13281A;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.22);
        }

        .partner-dialog::backdrop {
            background: rgba(10, 30, 18, 0.65);
        }

        .partner-dialog h2 {
            margin: 0 0 16px;
            color: #14592A;
            font-size: 23px;
            line-height: 1.3;
        }

        .partner-dialog p {
            line-height: 1.7;
        }

        .partner-dialog-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
        }

        .partner-dialog-actions form {
            margin: 0;
        }

        .partner-dialog .partner-cancel {
            background: #FFFFFF;
            border-color: #14592A;
            color: #14592A;
        }

        .partner-dialog .partner-cancel:hover {
            background: #E3F1E5;
            border-color: #14592A;
            color: #14592A;
        }

        @media (max-width: 600px) {
            .partner-header-inner {
                width: calc(100% - 32px);
                min-height: 80px;
                gap: 16px;
            }

            .partner-brand-mark {
                font-size: 27px;
            }

            .partner-brand-description {
                display: none;
            }

            .partner-home-link {
                font-size: 12px;
            }

            .partner-banner-inner,
            main.partner-main {
                width: calc(100% - 32px);
            }

            .partner-banner-inner {
                padding: 24px 0;
            }

            main.partner-main {
                padding: 26px 0 34px;
            }

            .partner-main .card {
                padding: 21px;
                border-radius: 14px;
            }

            .partner-main .actions {
                flex-direction: column;
                gap: 0;
            }

            .partner-main .actions .btn {
                width: 100%;
            }

            .partner-dialog {
                padding: 23px;
            }

            .partner-dialog-actions {
                flex-direction: column;
            }

            .partner-dialog-actions button,
            .partner-dialog-actions form {
                width: 100%;
            }

            .partner-dialog-actions button {
                margin-top: 0;
            }
        }
    </style>
</head>

<body class="partner-page">

<header class="partner-header">
    <div class="partner-header-inner">
        <a
            class="partner-brand"
            href="{{ route('home') }}"
            aria-label="JNUAF, accueil"
        >
            <span class="partner-brand-mark">
                <span>JN</span>UAF
            </span>

            <span class="partner-brand-description">
                Journée du Numérique<br>
                au Féminin
            </span>
        </a>

        <a class="partner-home-link" href="{{ route('home') }}">
            Retour au site JNUAF ↗
        </a>
    </div>
</header>

<div class="partner-banner">
    <div class="partner-banner-inner">
        <p class="partner-eyebrow">Partenariats JNUAF</p>

        <p class="partner-banner-title">
            Votre espace documentaire
        </p>

        <p class="partner-banner-description">
            Retrouvez les documents mis à votre disposition
            par le comité d’organisation.
        </p>
    </div>
</div>

<main class="partner-main" id="partner-main">
    @if (session('status'))
        <div class="alert" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @yield('content')
</main>

<footer class="partner-footer">
    <div class="partner-footer-inner">
        <p>
            <strong>Une question concernant vos documents ?</strong>
        </p>

        <p>
            Contactez le comité :
            <a href="mailto:{{ config('partner_portal.contact_email') }}">
                {{ config('partner_portal.contact_email') }}
            </a>
        </p>

        <p>
            Les accès à cet espace sont enregistrés à des fins
            de sécurité et de suivi du partenariat.
        </p>

        <p>
            <a href="{{ route('v2.legal') }}">
                Confidentialité
            </a>
            ·
            © {{ config('jnuaf.edition') }} JNUAF
        </p>
    </div>
</footer>

<dialog
    class="partner-dialog"
    id="partner-logout-dialog"
    aria-labelledby="partner-logout-title"
    aria-describedby="partner-logout-description"
>
    <h2 id="partner-logout-title">
        Quitter votre espace documentaire ?
    </h2>

    <div id="partner-logout-description">
        <p>
            Vous êtes sur le point de vous déconnecter.
            Vos documents ne seront plus accessibles depuis cette session.
        </p>

        @if (isset($share) && $share->code_count >= 3)
            <p>
                Les trois codes disponibles pour ce lien ont déjà été
                émis. Pour vous reconnecter après la déconnexion,
                vous devrez demander un nouveau lien au comité
                d’organisation.
            </p>
        @else
            <p>
                Pour revenir, vous devrez demander un nouveau code.
                Un délai d’attente peut s’appliquer avant de pouvoir
                le recevoir.
            </p>
        @endif
    </div>

    <div class="partner-dialog-actions">
        <form method="dialog">
            <button
                type="submit"
                class="partner-cancel"
                autofocus
            >
                Revenir à mes documents
            </button>
        </form>

        <button type="button" id="partner-confirm-logout">
            Se déconnecter
        </button>
    </div>
</dialog>

<script>
(() => {
    const main = document.getElementById('partner-main');
    const dialog = document.getElementById('partner-logout-dialog');
    const confirmButton = document.getElementById(
        'partner-confirm-logout'
    );

    if (!main || !dialog || !confirmButton) {
        return;
    }

    let pendingForm = null;

    main.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.method.toUpperCase() !== 'POST') {
            return;
        }

        const action = new URL(form.action, window.location.href);

        if (!action.pathname.endsWith('/deconnexion')) {
            return;
        }

        if (form.dataset.logoutConfirmed === '1') {
            return;
        }

        if (typeof dialog.showModal !== 'function') {
            const confirmed = window.confirm(
                'Voulez-vous vous déconnecter ? '
                + 'Une nouvelle identification sera nécessaire '
                + 'pour revenir.'
            );

            if (!confirmed) {
                event.preventDefault();
            }

            return;
        }

        event.preventDefault();

        pendingForm = form;
        confirmButton.disabled = false;
        dialog.showModal();
    });

    confirmButton.addEventListener('click', () => {
        if (!pendingForm) {
            return;
        }

        const form = pendingForm;

        form.dataset.logoutConfirmed = '1';
        confirmButton.disabled = true;

        dialog.close();
        form.requestSubmit();
    });

    dialog.addEventListener('close', () => {
        pendingForm = null;
    });
})();
</script>

@stack('scripts')

</body>
</html>