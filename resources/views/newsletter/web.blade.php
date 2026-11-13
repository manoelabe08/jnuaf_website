@extends('newsletter.layout')
@section('content')
<article class="card">
    <p class="muted">Newsletter JNUAF</p>
    <h1>{{ $delivery->campaign->subject }}</h1>
    <div style="overflow-wrap:anywhere">{!! \App\Support\NewsletterHtml::render($delivery->campaign->body) !!}</div>
    @if($delivery->campaign->attachments->isNotEmpty())
    <h2>Documents joints</h2>
    <ul>
        @foreach($delivery->campaign->attachments as $attachment)
        <li><a href="{{ route('newsletter.file', [$token, $attachment]) }}">{{ $attachment->download_name }}</a></li>
        @endforeach
    </ul>
    @endif
    <p>À bientôt,<br>Le Bureau de Pilotage de la JNUAF</p>
</article>
<p class="muted">Vous ne souhaitez plus recevoir ces nouvelles ? <a href="{{ route('newsletter.unsubscribe', $token) }}">Me désinscrire</a>.</p>
@endsection
