@extends('partner-portal.layout')

@section('content')

@if ($share->status !== 'active')
    <h1>Renouveler mon accès</h1>

    <div class="card">
        <h2>Ce lien n’est plus utilisable</h2>

        <p>
            Pour retrouver vos documents, veuillez demander
            un nouveau lien au comité d’organisation.
        </p>

        <a
            class="btn"
            href="mailto:{{ config('partner_portal.contact_email') }}"
        >
            Contacter le comité
        </a>
    </div>
@else
    @php
        $isRequested = (bool) $requested;
        $isPending = (bool) ($pending ?? false);
    @endphp

    <h1>Accéder à mes documents</h1>

    <p class="muted">
        Identifiez-vous avec l’adresse email associée à votre invitation.
    </p>

    <div class="card">
        <h2>Votre adresse email</h2>

        <p>
            Recevez un code temporaire pour accéder
            à votre espace documentaire.
        </p>

        <form
            id="partner-email-form"
            method="POST"
            action="{{ route('partner-portal.request-code', $token) }}"
        >
            @csrf

            <label for="email">
                Adresse email
            </label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $loginEmail) }}"
                required
                autocomplete="email"
                maxlength="255"
                placeholder="vous@organisation.fr"
                aria-describedby="email-help"
            >

            <p id="email-help" class="muted">
                Utilisez l’adresse à laquelle votre invitation a été envoyée.
            </p>

            <button
                type="submit"
                id="send-code"
                @disabled($isPending)
            >
                {{ $isRequested ? 'Recevoir un nouveau code' : 'Recevoir mon code' }}
            </button>

            <p
                id="wait"
                class="muted"
                role="status"
                aria-live="polite"
            ></p>
        </form>
    </div>

    @if ($isRequested)
        <div class="card">
            <h2>Votre code de connexion</h2>

            <p>
                Saisissez les six chiffres reçus par email.
                Pensez à vérifier vos courriers indésirables.
            </p>

            <form
                id="partner-code-form"
                method="POST"
                action="{{ route('partner-portal.verify', $token) }}"
            >
                @csrf

                <label for="code">
                    Code reçu par email
                </label>

                <input
                    id="code"
                    class="code"
                    type="text"
                    name="code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    required
                    placeholder="000000"
                    aria-describedby="countdown"
                    @disabled($isPending)
                >

                <button
                    type="submit"
                    id="verify-code"
                    @disabled($isPending)
                >
                    Accéder à mes documents
                </button>

                <p
                    id="countdown"
                    role="status"
                    aria-live="polite"
                ></p>
            </form>

            <p class="muted">
                Votre code est personnel. Ne le communiquez à personne.
            </p>
        </div>
    @endif

    <noscript>
        <div class="alert">
            Activez JavaScript pour afficher le décompte et actualiser
            automatiquement l’état de votre demande.
        </div>
    </noscript>

    <script>
    (() => {
        const serverTime = Number(@json($serverTime)) * 1000;
        const startedAt = performance.now();

        const expiryValue = @json($expiry);
        const nextValue = @json($nextCode);

        const expiry = expiryValue === null
            ? null
            : Number(expiryValue);

        const nextCode = nextValue === null
            ? null
            : Number(nextValue);

        const requested = @json($isRequested);
        const pending = @json($isPending);

        const emailForm = document.getElementById(
            'partner-email-form'
        );

        const codeForm = document.getElementById(
            'partner-code-form'
        );

        const sendButton = document.getElementById('send-code');
        const verifyButton = document.getElementById('verify-code');
        const codeInput = document.getElementById('code');
        const waitMessage = document.getElementById('wait');
        const countdown = document.getElementById('countdown');

        if (!emailForm || !sendButton || !waitMessage) {
            return;
        }

        let refreshScheduled = false;
        let emailSubmitting = false;
        let codeSubmitting = false;

        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainder = seconds % 60;

            return String(minutes).padStart(2, '0')
                + ':'
                + String(remainder).padStart(2, '0');
        }

        function setCountdown(message) {
            if (countdown && countdown.textContent !== message) {
                countdown.textContent = message;
            }
        }

        function scheduleRefresh(delay) {
            if (refreshScheduled) {
                return;
            }

            refreshScheduled = true;

            window.setTimeout(() => {
                window.location.reload();
            }, delay);
        }

        function update() {
            if (emailSubmitting || codeSubmitting) {
                return;
            }

            if (pending) {
                sendButton.disabled = true;
                sendButton.textContent = 'Envoi en cours…';

                if (verifyButton) {
                    verifyButton.disabled = true;
                }

                if (codeInput) {
                    codeInput.disabled = true;
                }

                waitMessage.textContent =
                    'Votre demande est en cours de traitement.';

                setCountdown(
                    'Préparation de votre code de connexion…'
                );

                scheduleRefresh(3000);
                return;
            }

            sendButton.textContent = requested
                ? 'Recevoir un nouveau code'
                : 'Recevoir mon code';

            if (!requested) {
                sendButton.disabled = false;
                waitMessage.textContent = '';
                return;
            }

            const now = (
                serverTime + performance.now() - startedAt
            ) / 1000;

            const remaining = expiry === null
                ? 0
                : Math.max(0, Math.ceil(expiry - now));

            const waiting = nextCode === null
                ? 0
                : Math.max(0, Math.ceil(nextCode - now));

            const hasExpiredFinalCode =
                expiry !== null
                && nextCode === null
                && remaining === 0;

            const resendUnavailable =
                waiting > 0
                || (expiry !== null && nextCode === null);

            sendButton.disabled = resendUnavailable;

            if (verifyButton) {
                verifyButton.disabled = remaining === 0;
            }

            if (codeInput) {
                codeInput.disabled = remaining === 0;
            }

            if (remaining > 0) {
                setCountdown(
                    'Votre code expire dans '
                    + formatTime(remaining)
                );

                waitMessage.textContent = '';
            } else {
                setCountdown(
                    'Ce code n’est plus utilisable.'
                );

                waitMessage.textContent = waiting > 0
                    ? 'Veuillez patienter avant de demander un nouveau code.'
                    : '';
            }

            if (hasExpiredFinalCode) {
                scheduleRefresh(1000);
            }
        }

        emailForm.addEventListener('submit', () => {
            emailSubmitting = true;

            sendButton.disabled = true;
            sendButton.textContent = 'Envoi de votre demande…';
        });

        if (codeForm) {
            codeForm.addEventListener('submit', () => {
                codeSubmitting = true;

                if (verifyButton) {
                    verifyButton.disabled = true;
                    verifyButton.textContent = 'Vérification…';
                }
            });
        }

        update();
        window.setInterval(update, 1000);
    })();
    </script>
@endif

@endsection