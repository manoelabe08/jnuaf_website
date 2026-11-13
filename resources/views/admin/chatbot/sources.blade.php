@extends('layouts.admin')



@section('title', 'Sources du chatbot')



@section('content')



<div class="page-head">

  <h1>Sources de connaissances</h1>

  <a class="btn" href="{{ route('admin.chatbot.index') }}">Retour au suivi</a>

</div>




@if ($errors->any())<div class="flash">{{ $errors->first() }}</div>@endif



<p>Le chatbot cherche d'abord dans la FAQ, puis dans les sources <strong>activ&eacute;es</strong>. Un PDF ou un lien import&eacute; reste d&eacute;sactiv&eacute; tant que vous ne l'avez pas valid&eacute;. N'activez jamais un document interne ou confidentiel : ses extraits pourraient &ecirc;tre montr&eacute;s aux visiteuses.</p>



<div class="card" style="margin-bottom:12px">

  <h2>Ajouter un PDF</h2>

  <form method="POST" action="{{ route('admin.chatbot.sources.pdf') }}" enctype="multipart/form-data">

    @csrf

    <label>Fichier PDF (20 Mo maximum, texte s&eacute;lectionnable)<input type="file" name="pdf" accept="application/pdf" required></label>

    <label>Titre affich&eacute; comme source (facultatif)<input type="text" name="title" maxlength="255"></label>

    <button class="btn btn-green" type="submit">Importer le PDF</button>

  </form>

</div>



<div class="card" style="margin-bottom:12px">

  <h2>Ajouter un lien</h2>

  <form method="POST" action="{{ route('admin.chatbot.sources.url') }}">

    @csrf

    <label>Adresse de la page (https://...)<input type="url" name="url" maxlength="500" required></label>

    <label>Titre (facultatif)<input type="text" name="title" maxlength="255"></label>

    <button class="btn btn-green" type="submit">Importer la page</button>

  </form>

</div>



<div class="card" style="margin-bottom:12px">

  <h2>Articles du blog</h2>

  <p>{{ $postCount }} article(s) publi&eacute;(s). L'import cr&eacute;e ou met &agrave; jour une source par article, activ&eacute;e automatiquement car le contenu est d&eacute;j&agrave; public.</p>

  <form method="POST" action="{{ route('admin.chatbot.sources.blog') }}">@csrf<button class="btn btn-green" type="submit">Importer / actualiser les articles</button></form>

</div>



<h2>Sources enregistr&eacute;es</h2>

<div class="card">

  <table>

    <thead><tr><th>Titre</th><th>Type</th><th>Passages</th><th>Statut</th><th>Mise &agrave; jour</th><th>Erreur</th><th>Actions</th></tr></thead>

    <tbody>

    @forelse ($sources as $s)

      <tr>

        <td>{{ $s->title }}</td>

        <td>@if ($s->type === 'pdf')PDF @elseif ($s->type === 'url')Lien @else Article @endif</td>

        <td>{{ $s->passage_count }}</td>

        <td>@if ($s->active)Activ&eacute;e @else D&eacute;sactiv&eacute;e @endif</td>

        <td>{{ \Illuminate\Support\Carbon::parse($s->updated_at)->format('d/m/Y H:i') }}</td>

        <td>{{ $s->error ?: '-' }}</td>

        <td>

          <a class="btn" href="{{ route('admin.chatbot.sources.show', $s->id) }}">Voir</a>

          @if ($s->passage_count > 0)

          <form method="POST" action="{{ route('admin.chatbot.sources.toggle', $s->id) }}" style="display:inline">@csrf<button type="submit" class="btn">@if ($s->active)D&eacute;sactiver @else Activer @endif</button></form>

          @endif

        </td>

      </tr>

    @empty

      <tr><td colspan="7">Aucune source pour le moment.</td></tr>

    @endforelse

    </tbody>

  </table>

</div>



@endsection

