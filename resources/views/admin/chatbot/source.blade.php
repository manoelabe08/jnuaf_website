@extends('layouts.admin')



@section('title', 'Source du chatbot')



@section('content')



<div class="page-head">

  <h1>{{ $source->title }}</h1>

  <a class="btn" href="{{ route('admin.chatbot.sources') }}">Retour aux sources</a>

</div>






<div class="card" style="margin-bottom:12px">

  <p>Type : @if ($source->type === 'pdf')PDF @elseif ($source->type === 'url')Lien @else Article @endif &middot; {{ $source->passage_count }} passage(s) &middot; {{ $source->char_count }} caract&egrave;res &middot; Statut : @if ($source->active)<strong>activ&eacute;e</strong> @else <strong>d&eacute;sactiv&eacute;e</strong> @endif</p>

  @if ($source->url)<p>Adresse : <a href="{{ $source->url }}" target="_blank" rel="noopener">{{ $source->url }}</a></p>@endif

  @if ($source->error)<p class="flash">{{ $source->error }}</p>@endif

  @if ($source->passage_count > 0)

  <form method="POST" action="{{ route('admin.chatbot.sources.toggle', $source->id) }}" style="display:inline">@csrf<button type="submit" class="btn @if (! $source->active) btn-green @endif">@if ($source->active)D&eacute;sactiver cette source @else Activer cette source @endif</button></form>

  @endif

  <form method="POST" action="{{ route('admin.chatbot.sources.refresh', $source->id) }}" style="display:inline">@csrf<button type="submit" class="btn">Actualiser le contenu</button></form>

</div>



<h2>Extraits lus par le chatbot</h2>

@forelse ($passages as $p)

  <div class="card" style="margin-bottom:8px"><small>Passage {{ $p->position + 1 }}@if ($p->page) &middot; page {{ $p->page }}@endif</small><p style="white-space:pre-wrap">{{ \Illuminate\Support\Str::limit($p->body, 700) }}</p></div>

@empty

  <div class="card"><p>Aucun passage extrait.</p></div>

@endforelse



@endsection

