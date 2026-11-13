@extends('layouts.admin')

@section('title', 'Articles')

@section('content')

<div class="page-head">

  <h1>Articles</h1>

  <a class="btn btn-green" href="{{ route('admin.posts.create') }}">Nouvel article</a>

</div>


<div class="card">

  <table>

    <thead><tr><th><input type="checkbox" id="check-all"></th><th>Image</th>
<th>Titre</th><th>Statut</th><th>Publi&eacute; le</th><th>Actions</th></tr></thead>

    <tbody>

    @forelse ($posts as $p)

      <tr>

        <td><input type="checkbox" name="ids[]" value="{{ $p->id }}" form="bulk-form" class="row-check"></td>

        <td>@if ($p->cover_path)<img src="{{ url('medias/'.$p->cover_path) }}" alt="" style="width:80px;height:50px;object-fit:cover;border-radius:6px">@endif</td>
<td>{{ $p->title }}</td>

        <td>{{ $p->status }}</td>

        <td>{{ $p->published_at ? $p->published_at->format('d/m/Y H:i') : '-' }}</td>

        <td>

          <a class="btn" href="{{ route('admin.posts.edit', $p) }}">Modifier</a>

          <a class="btn" href="{{ route('blog.show', $p->slug) }}" target="_blank" rel="noopener">Voir</a>

          @if ($p->status === 'draft')

            <form method="POST" action="{{ route('admin.posts.publish', $p) }}" style="display:inline">@csrf<button type="submit" class="btn btn-green">Publier</button></form>

          @else

            <form method="POST" action="{{ route('admin.posts.unpublish', $p) }}" style="display:inline">@csrf<button type="submit" class="btn">D&eacute;publier</button></form>

          @endif

          <form method="POST" action="{{ route('admin.posts.destroy', $p) }}" style="display:inline" onsubmit="return confirm('Supprimer cet article ? Cette action est d&eacute;finitive.')">@csrf<button class="btn" style="color:#b3261e">Supprimer</button></form>

        </td>

      </tr>

    @empty

      <tr><td colspan="6">Aucun article.</td></tr>

    @endforelse

    </tbody>

  </table>

</div>

<form id="bulk-form" method="POST" action="{{ route('admin.posts.bulk') }}">

  @csrf

  <div style="margin-top:12px;display:flex;gap:8px">

    <select name="action">

      <option value="">Choisir une action</option>

      <option value="publish">Publier</option>

      <option value="unpublish">D&eacute;publier</option>

    </select>

    <button class="btn btn-green" type="submit">Appliquer</button>

  </div>

</form>

{{ $posts->links() }}

<script>

document.getElementById("check-all").addEventListener("change", function(){

  var c = this.checked;

  document.querySelectorAll(".row-check").forEach(function(cb){ cb.checked = c; });

});

</script><!-- jnuaf-s94 -->
@endsection


<!-- jnuaf-s93 -->