
@extends('layouts.admin')



@section('title', 'Messagerie — JNUAF')



@section('content')

@include('admin.mailbox.partials.styles')



@php

    $isSent = ($folder ?? 'inbox') === 'sent';

    $baseRoute = $isSent ? 'admin.mailbox.sent' : 'admin.mailbox.inbox';

    $isFiltered = $params['q'] !== ''

        || $params['filter'] !== 'all'

        || $params['period'] > 0

        || $params['sort'] !== 'date_desc';

@endphp



<div class="jmail">

    <header class="jm-header">

        <div>

            <p class="jm-eyebrow">Espace administratif · JNUAF</p>

            <h1 class="jm-title">Messagerie</h1>

            <p class="jm-subtitle">Les échanges de votre équipe, au même endroit.</p>

        </div>

        <div class="jm-account">

            <span class="jm-dot" aria-hidden="true"></span>

            team@jnuaf.org

        </div>

    </header>






    @if($error)

        <div class="jm-error" role="alert">{{ $error }}</div>

    @endif



    <nav class="jm-tabs" aria-label="Dossiers">

        <a class="jm-tab {{ $isSent ? '' : 'jm-tab-on' }}" href="{{ route('admin.mailbox.inbox') }}">Boîte de réception</a>

        <a class="jm-tab {{ $isSent ? 'jm-tab-on' : '' }}" href="{{ route('admin.mailbox.sent') }}">Envoyés</a>

    </nav>



    <form class="jm-filters" method="GET" action="{{ route($baseRoute) }}" role="search">

        <input class="jm-input jm-search" type="search" name="q" maxlength="100"

               value="{{ $params['q'] }}" aria-label="Rechercher"

               placeholder="Rechercher (expéditeur, objet, contenu)">



        <select class="jm-input jm-select" name="filter" aria-label="Filtrer">

            <option value="all" @selected($params['filter'] === 'all')>Tous les messages</option>

            <option value="unread" @selected($params['filter'] === 'unread')>Non lus</option>

            <option value="attachments" @selected($params['filter'] === 'attachments')>Avec pièce jointe</option>

        </select>



        <select class="jm-input jm-select" name="period" aria-label="Période">

            <option value="0" @selected($params['period'] === 0)>Toute période</option>

            <option value="7" @selected($params['period'] === 7)>7 derniers jours</option>

            <option value="30" @selected($params['period'] === 30)>30 derniers jours</option>

            <option value="90" @selected($params['period'] === 90)>90 derniers jours</option>

        </select>



        <select class="jm-input jm-select" name="sort" aria-label="Trier">

            <option value="date_desc" @selected($params['sort'] === 'date_desc')>Plus récents d'abord</option>

            <option value="date_asc" @selected($params['sort'] === 'date_asc')>Plus anciens d'abord</option>

            <option value="sender" @selected($params['sort'] === 'sender')>{{ $isSent ? 'Destinataire (A-Z)' : 'Expéditeur (A-Z)' }}</option>

            <option value="subject" @selected($params['sort'] === 'subject')>Objet (A-Z)</option>

        </select>



        <button class="jm-send" type="submit">Appliquer</button>



        @if($isFiltered)

            <a class="jm-refresh" href="{{ route($baseRoute) }}">Réinitialiser</a>

        @endif

    </form>



    <section class="jm-card" aria-labelledby="jm-inbox-title">

        <div class="jm-toolbar">

            <div>

                <div class="jm-heading-row">

                    <h2 class="jm-heading" id="jm-inbox-title">{{ $isSent ? 'Messages envoyés' : 'Boîte de réception' }}</h2>

                    <span class="jm-count">{{ $total }}</span>

                </div>

                <p class="jm-caption">

                    {{ $total }} message(s) trouvé(s).

                    @if($capped)

                        Seuls les 100 plus récents sont analysés : affinez la recherche pour remonter plus loin.

                    @endif

                </p>

            </div>

            <div class="jm-actions">

                <a class="jm-refresh" href="{{ route($baseRoute, array_filter($params)) }}">Actualiser</a>

                <a class="jm-compose jm-compose-on" href="{{ route('admin.mailbox.compose') }}">+ Nouveau message</a>

            </div>

        </div>



        @forelse($messages as $message)

            @php

                $who = $isSent

                    ? ($message['to_name'] ?: ($message['to'] ?: 'Destinataire inconnu'))

                    : ($message['from_name'] ?: ($message['from'] ?: 'Expéditeur inconnu'));

                $address = $isSent ? $message['to'] : $message['from'];

                $initial = mb_strtoupper(mb_substr($who, 0, 1));

                $subject = (string) $message['subject'];

                $replySubject = preg_match('/^re\s*:/i', $subject) ? $subject : 'Re : '.$subject;

                $unread = ! $isSent && ! $message['seen'];

                $extra = $message['to_count'] - 1;

            @endphp

            <article class="jm-message {{ $unread ? 'jm-unread' : '' }}">

                <div class="jm-avatar" aria-hidden="true">{{ $initial }}</div>

                <div>

                    <div class="jm-message-top">

                        <h3 class="jm-sender">

                            {{ $isSent ? 'À : ' : '' }}{{ $who }}

                            @if($isSent && $extra > 0)

                                <span class="jm-more">+{{ $extra }}</span>

                            @endif

                            @if($unread)

                                <span class="jm-new">Non lu</span>

                            @endif

                        </h3>

                        <span class="jm-date">{{ $message['date'] }}</span>

                    </div>

                    <p class="jm-email">{{ $address }}</p>

                    <p class="jm-subject"><a class="jm-open" href="{{ route('admin.mailbox.show', ['folder' => $isSent ? 'sent' : 'inbox', 'uid' => $message['uid']]) }}">{{ $subject !== '' ? $subject : '(Sans objet)' }}</a></p>



                    @unless($isSent)

                        <a class="jm-reply" href="{{ route('admin.mailbox.compose', ['to' => $message['from'], 'subject' => $replySubject]) }}">Répondre</a>

                    @endunless



                    @if($message['attachments'] > 0)

                        <span class="jm-attachment">{{ $message['attachments'] }} pièce(s) jointe(s)</span>

                    @endif

                </div>

            </article>

        @empty

            <div class="jm-empty">

                {{ $isFiltered ? 'Aucun message ne correspond à cette recherche.' : 'Aucun message à afficher dans ce dossier.' }}

            </div>

        @endforelse



        @if($pages > 1)

            <nav class="jm-pager" aria-label="Pagination">

                @if($page > 1)

                    <a class="jm-refresh" href="{{ route($baseRoute, array_merge(array_filter($params), ['page' => $page - 1])) }}">← Précédent</a>

                @endif



                <span class="jm-caption">Page {{ $page }} sur {{ $pages }}</span>



                @if($page < $pages)

                    <a class="jm-refresh" href="{{ route($baseRoute, array_merge(array_filter($params), ['page' => $page + 1])) }}">Suivant →</a>

                @endif

            </nav>

        @endif



        <footer class="jm-footer">

            {{ count($messages) }} message(s) affiché(s) · Compte team@jnuaf.org

        </footer>

    </section>

</div>

@endsection

