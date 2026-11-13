@extends('layouts.admin')



@section('title', 'Chatbot')



@section('content')



<div class="page-head">

  <h1>Chatbot</h1>
  <a class="btn" href="{{ route('admin.chatbot.sources') }}">Sources : PDF, liens, articles</a>

  <form method="POST" action="{{ route('admin.chatbot.toggle') }}" style="display:inline">@csrf

    @if ($stats['enabled'])<button type="submit" class="btn">D&eacute;sactiver le chatbot</button>@else<button type="submit" class="btn btn-green">Activer le chatbot</button>@endif

  </form>

</div>




@if ($errors->any())<div class="flash">{{ $errors->first() }}</div>@endif



<p>&Eacute;tat : @if ($stats['enabled'])<strong>actif</strong> (visible par les visiteuses)@else<strong>d&eacute;sactiv&eacute;</strong> (la bulle est masqu&eacute;e)@endif &middot; {{ $stats['faqs'] }} questions dans la base de connaissances.</p>



<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px">

  <div class="card" style="flex:1 1 150px"><div style="font-size:28px;font-weight:bold">{{ $stats['conv7'] }}</div><div>Conversations (7 jours)</div></div>

  <div class="card" style="flex:1 1 150px"><div style="font-size:28px;font-weight:bold">{{ $stats['conv30'] }}</div><div>Conversations (30 jours)</div></div>

  <div class="card" style="flex:1 1 150px"><div style="font-size:28px;font-weight:bold">{{ $stats['questions30'] }}</div><div>Questions pos&eacute;es (30 jours)</div></div>

  <div class="card" style="flex:1 1 150px"><div style="font-size:28px;font-weight:bold">{{ $stats['rate'] === null ? '-' : $stats['rate'].' %' }}</div><div>Taux de r&eacute;ponse</div></div>

  <div class="card" style="flex:1 1 150px"><div style="font-size:28px;font-weight:bold">{{ $stats['helpful'] === null ? '-' : $stats['helpful'].' %' }}</div><div>R&eacute;ponses jug&eacute;es utiles</div></div>

  <div class="card" style="flex:1 1 150px"><div style="font-size:28px;font-weight:bold">{{ $stats['open'] }}</div><div>Questions sans r&eacute;ponse</div></div>

  <div class="card" style="flex:1 1 150px"><div style="font-size:28px;font-weight:bold">{{ $stats['learned'] }}</div><div>R&eacute;ponses apprises</div></div>

  <div class="card" style="flex:1 1 150px"><div style="font-size:28px;font-weight:bold">{{ $stats['waiting'] }}</div><div>Demandes en attente</div></div>

</div>



<h2>Demandes en attente d'une &eacute;quipe</h2>

<div class="card">

  <table>

    <thead><tr><th>R&eacute;f.</th><th>Pr&eacute;nom</th><th>&Eacute;quipe</th><th>E-mail</th><th>T&eacute;l&eacute;phone</th><th>Depuis</th><th>Action</th></tr></thead>

    <tbody>

    @forelse ($requests as $r)

      <tr>

        <td>{{ $r->code }}</td>

        <td>{{ $r->first_name }}</td>

        <td>{{ $teams[$r->team] ?? $r->team }}</td>

        <td>{{ $r->email ?: '-' }}</td>

        <td>{{ $r->phone ?: '-' }}</td>

        <td>{{ $r->waiting_since ? \Illuminate\Support\Carbon::parse($r->waiting_since)->format('d/m/Y H:i') : '-' }}</td>

        <td><form method="POST" action="{{ route('admin.chatbot.close', $r->id) }}">@csrf<button type="submit" class="btn">Marquer comme trait&eacute;e</button></form></td>

      </tr>

    @empty

      <tr><td colspan="7">Aucune demande en attente.</td></tr>

    @endforelse

    </tbody>

  </table>

</div>



<h2>Questions sans r&eacute;ponse : apprendre au chatbot</h2>

@forelse ($unanswered as $u)

  <div class="card" style="margin-bottom:12px">

    <p><strong>{{ $u->sample ?: $u->normalized }}</strong> <small>({{ $u->occurrences }} fois, derni&egrave;re le {{ $u->last_seen_at ? \Illuminate\Support\Carbon::parse($u->last_seen_at)->format('d/m/Y H:i') : '-' }})</small></p>

    <form method="POST" action="{{ route('admin.chatbot.answer', $u->id) }}">

      @csrf

      <label>Question &agrave; enregistrer<input type="text" name="question" value="{{ $u->sample }}" required maxlength="255"></label>

      <label>R&eacute;ponse<textarea name="answer" rows="3" required maxlength="2000"></textarea></label>

      <label>Mots-cl&eacute;s (facultatif)<input type="text" name="keywords" maxlength="500"></label>

      <label>Cat&eacute;gorie (facultatif)<input type="text" name="category" maxlength="60"></label>

      <button class="btn btn-green" type="submit">R&eacute;pondre et apprendre</button>

    </form>

    <form method="POST" action="{{ route('admin.chatbot.ignore', $u->id) }}" style="margin-top:6px">@csrf<button class="btn" type="submit">Ignorer</button></form>

  </div>

@empty

  <div class="card"><p>Aucune question en attente.</p></div>

@endforelse



<h2>Questions les plus consult&eacute;es</h2>

<div class="card">

  <table>

    <thead><tr><th>Question</th><th>Consultations</th><th>Utile</th><th>Pas utile</th></tr></thead>

    <tbody>

    @foreach ($top as $t)

      <tr><td>{{ $t->question }}</td><td>{{ $t->hits }}</td><td>{{ $t->helpful_yes }}</td><td>{{ $t->helpful_no }}</td></tr>

    @endforeach

    </tbody>

  </table>

</div>



@endsection

