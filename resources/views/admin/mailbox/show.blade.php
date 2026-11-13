
@extends('layouts.admin')



@section('title', 'Message — JNUAF')



@section('content')

@include('admin.mailbox.partials.styles')



@php

    $isSent = $folder === 'sent';

    $listRoute = $isSent ? 'admin.mailbox.sent' : 'admin.mailbox.inbox';

    $subject = (string) $mail['subject'];

    $replySubject = preg_match('/^re\s*:/i', $subject) ? $subject : 'Re : '.$subject;

@endphp



<div class="jmail">

    <a class="jm-back" href="{{ route($listRoute) }}">

        ← Retour {{ $isSent ? 'aux messages envoyés' : 'à la boîte de réception' }}

    </a>



    <article class="jm-card jm-read">

        <h1 class="jm-title jm-read-title">{{ $subject !== '' ? $subject : '(Sans objet)' }}</h1>



        <dl class="jm-meta">

            <dt>De</dt>

            <dd>{{ $mail['from_name'] !== '' ? $mail['from_name'].' <'.$mail['from'].'>' : $mail['from'] }}</dd>



            <dt>À</dt>

            <dd>

                @foreach($mail['to'] as $person)

                    <div>{{ $person['name'] !== '' ? $person['name'].' <'.$person['mail'].'>' : $person['mail'] }}</div>

                @endforeach

            </dd>



            @if(count($mail['cc']) > 0)

                <dt>Cc</dt>

                <dd>

                    @foreach($mail['cc'] as $person)

                        <div>{{ $person['name'] !== '' ? $person['name'].' <'.$person['mail'].'>' : $person['mail'] }}</div>

                    @endforeach

                </dd>

            @endif



            <dt>Date</dt>

            <dd>{{ $mail['date'] }}</dd>

        </dl>



        
@php

    $hasHtml = ! empty($mail['html']);

    $wantText = request()->query('view') === 'text';

    $allowImages = $folder === 'sent' || request()->boolean('images');

    $csp = 'default-src \'none\'; style-src \'unsafe-inline\'; font-src data:; img-src '.($allowImages ? 'https: data:' : 'data:');

    $doc = '<!doctype html><html lang="fr"><head><meta charset="utf-8">'

        .'<meta http-equiv="Content-Security-Policy" content="'.$csp.'">'

        .'<base target="_blank">'

        .'<style>body{margin:14px;font-family:Arial,Helvetica,sans-serif;color:#13281A}img{max-width:100%;height:auto}</style>'

        .'</head><body>'.($mail['html'] ?? '').'</body></html>';

@endphp



        
<div class="jm-translate">

            <button type="button" class="jm-compose jm-compose-on" id="jm-translate-btn" hidden>Traduire en fran&ccedil;ais</button>

            <span class="jm-hint" id="jm-translate-status" role="status"></span>

        </div>



        <div class="jm-body-wrap">


            @if($hasHtml)

                <div class="jm-viewtabs">

                    <a class="{{ $wantText ? '' : 'on' }}" href="{{ request()->fullUrlWithQuery(['view' => null]) }}">Mise en forme</a>

                    <a class="{{ $wantText ? 'on' : '' }}" href="{{ request()->fullUrlWithQuery(['view' => 'text']) }}">Texte brut</a>

                </div>

            @endif



            @if($hasHtml && ! $wantText)

                <iframe class="jm-frame"

                        sandbox="allow-popups allow-popups-to-escape-sandbox"

                        referrerpolicy="no-referrer"

                        title="Contenu du message"

                        srcdoc="{{ $doc }}"></iframe>



                @if(! $allowImages)

                    <p class="jm-hint">

                        Les images distantes sont bloquées par sécurité.

                        <a href="{{ request()->fullUrlWithQuery(['images' => 1]) }}">Afficher les images</a>

                    </p>

                @endif

            @elseif(trim($mail['body']) !== '')

                <div class="jm-body">{!! nl2br(e($mail['body'])) !!}</div>

            @else

                <div class="jm-body"><em>Ce message ne contient pas de texte.</em></div>

            @endif

        </div>



        
@if(count($mail['attachments']) > 0)

            <div class="jm-files">

                <p class="jm-files-title">{{ count($mail['attachments']) }} pièce(s) jointe(s)</p>



                @foreach($mail['attachments'] as $file)

                    <a class="jm-file" href="{{ route('admin.mailbox.attachment', ['folder' => $folder, 'uid' => $uid, 'index' => $file['index']]) }}">

                        <span>{{ $file['name'] }}</span>

                        @if($file['size'] > 0)

                            <small>{{ number_format($file['size'] / 1024, 0, ',', ' ') }} Ko</small>

                        @endif

                    </a>

                @endforeach

            </div>

        @endif



        @unless($isSent)

            <div class="jm-form-actions">

                <a class="jm-compose jm-compose-on"

                   href="{{ route('admin.mailbox.compose', ['to' => $mail['reply_to'], 'subject' => $replySubject]) }}">

                    Répondre

                </a>

            </div>

        @endunless

    
</article>



    <script src="{{ asset('js/mail-translate.js') }}?v=1" defer></script>


</div>

@endsection

