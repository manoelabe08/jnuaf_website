
@extends('layouts.admin')



@section('title', 'Nouveau message — JNUAF')



@section('content')

@include('admin.mailbox.partials.styles')



@php

    $showCc = old('cc') !== null && old('cc') !== '' || old('bcc') !== null && old('bcc') !== '';

@endphp



<div class="jmail">

    <header class="jm-header">

        <div>

            <p class="jm-eyebrow">Espace administratif · JNUAF</p>

            <h1 class="jm-title">Nouveau message</h1>

            <p class="jm-subtitle">Envoyé depuis team@jnuaf.org. Les réponses reviennent dans la même boîte.</p>

        </div>

    </header>



    @if($errors->any())

        <div class="jm-error" role="alert">

            @foreach($errors->all() as $message)

                <div>{{ $message }}</div>

            @endforeach

        </div>

    @endif



    <form class="jm-card jm-form" method="POST"

          action="{{ route('admin.mailbox.send') }}"

          enctype="multipart/form-data"

          data-compose-send data-msg-schedule="Programmer l&rsquo;envoi de ce message ?" data-msg-dated="Une date de programmation est renseign&eacute;e. Envoyer maintenant &agrave; la place ?" data-msg-send="Envoyer ce message depuis team@jnuaf.org ?">

        @csrf



        <label class="jm-label" for="jm-from">De</label>

        <input class="jm-input" id="jm-from" type="text" value="JNUAF &lt;team@jnuaf.org&gt;" readonly>



        <label class="jm-label" for="jm-to">À</label>

        <textarea class="jm-input" id="jm-to" name="to" rows="2" required

                  placeholder="adresse1@exemple.fr, adresse2@exemple.fr">{{ old('to', $prefillTo) }}</textarea>

        <p class="jm-hint">Plusieurs adresses : séparez-les par une virgule, un point-virgule ou un retour à la ligne (20 maximum).</p>



        <details class="jm-details" @if($showCc) open @endif>

            <summary>Ajouter des destinataires en copie (Cc) ou en copie cachée (Cci)</summary>



            <label class="jm-label" for="jm-cc">Cc</label>

            <textarea class="jm-input" id="jm-cc" name="cc" rows="2">{{ old('cc') }}</textarea>



            <label class="jm-label" for="jm-bcc">Cci</label>

            <textarea class="jm-input" id="jm-bcc" name="bcc" rows="2">{{ old('bcc') }}</textarea>

        </details>



        <label class="jm-check">

            <input type="checkbox" name="individual" value="1" @checked(old('individual'))>

            <span>

                Envoi individuel : chaque destinataire (À, Cc et Cci) reçoit son propre message

                et ne voit pas les autres adresses.

            </span>

        </label>



        <label class="jm-label" for="jm-subject">Objet</label>

        <input class="jm-input" id="jm-subject" name="subject" type="text" required maxlength="150"

               value="{{ old('subject', $prefillSubject) }}">



        <label class="jm-label" for="jm-body">Message</label>

        <textarea class="jm-input jm-textarea" id="jm-body" name="body" rows="12" required>{{ old('body') }}</textarea>



        <label class="jm-label" for="jm-files">Pièces jointes</label>

        <input class="jm-input" id="jm-files" name="attachments[]" type="file" multiple

               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt">

        <p class="jm-hint">5 fichiers maximum, 10 Mo au total. PDF, images, Word, Excel, PowerPoint, CSV, TXT.</p>



        <div class="jm-form-actions">

            
<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-right:auto">

                <label style="display:flex;flex-direction:column;font-size:13px">Programmer l’envoi (facultatif) — {{ config('jnuaf_newsletter.timezone', 'Europe/Paris') }}

                    <input class="jm-input" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" min="{{ now()->timezone(config('jnuaf_newsletter.timezone', 'Europe/Paris'))->addMinutes(3)->format('Y-m-d\TH:i') }}">

                </label>

                <button class="jm-send" type="submit" formaction="{{ route('admin.mailbox.schedule') }}" data-schedule="1" style="background:#fff;color:#14592A;border:1px solid #14592A">Programmer</button>

                <a href="{{ route('admin.mailbox.scheduled') }}" style="font-size:13px">Messages programmés</a>

            </div>

            <button class="jm-send" type="submit">Envoyer</button>


            <a class="jm-refresh" href="{{ route('admin.mailbox.inbox') }}">Annuler</a>

        </div>

    </form>

</div>

<link rel="stylesheet" href="{{ asset('css/mail-suggest.css') }}?v=1">
<script src="{{ asset('js/mail-suggest.js') }}?v=1" data-mail-suggest data-url="{{ route('admin.mailbox.suggest') }}" defer></script>
@endsection

