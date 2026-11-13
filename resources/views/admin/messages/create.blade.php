@extends('layouts.admin')

@section('title', 'Messages au comité')

@section('content')

<div class="top">
    <div>
        <h1>Messages aux candidatures du comité</h1>

        <p class="muted" style="margin-bottom:0">
            Rédigez un message et vérifiez son rendu avant de l’envoyer.
        </p>
    </div>
</div>

<div class="card" style="margin-bottom:18px">
    <h2>À propos de cette messagerie</h2>

    <p>
        Cette page concerne uniquement les candidatures au comité,
        selon le groupe sélectionné.
    </p>

    <p class="muted" style="margin-bottom:0">
        La version actuelle traite au maximum 40 adresses distinctes
        par envoi. Elle ne constitue pas encore le module newsletter.
    </p>
</div>

<div class="card">
    <form
        class="form"
        method="POST"
        action="{{ route('admin.messages.send') }}"
        data-confirm-button="Envoyer ce message au groupe s&eacute;lectionn&eacute; ? La version actuelle traite au maximum 40 adresses." data-confirm-button-name="test" data-confirm-button-value="0"
    >
        @csrf

        <label for="message-group">
            Destinataires

            <select id="message-group" name="group" required>
                <option value="">Choisir un groupe</option>

                @foreach ($groups as $value => $label)
                    <option
                        value="{{ $value }}"
                        @selected(old('group') === $value)
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </label>

        <label for="message-subject">
            Objet de l’email

            <input
                id="message-subject"
                type="text"
                name="subject"
                value="{{ old('subject') }}"
                maxlength="150"
                required
                placeholder="Objet du message"
            >
        </label>

        <label for="message-body">
            Contenu du message

            <textarea
                id="message-body"
                name="body"
                rows="12"
                maxlength="5000"
                required
                placeholder="Rédigez votre message ici…"
            >{{ old('body') }}</textarea>
        </label>

        <p class="muted" style="margin:0">
            Le test sera envoyé uniquement à votre adresse :
            {{ auth()->user()->email }}.
            Le groupe doit néanmoins être sélectionné pour valider
            le formulaire.
        </p>

        <div style="display:flex;gap:12px;flex-wrap:wrap">
            <button
                type="submit"
                class="btn ghost"
                name="test"
                value="1"
            >
                M’envoyer un test
            </button>

            <button
                type="submit"
                class="btn"
                name="test"
                value="0"
            >
                Envoyer au groupe sélectionné
            </button>
        </div>
    </form>
</div>

@endsection