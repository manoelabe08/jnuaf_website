@extends('layouts.v2')

@section('title', "Actualit\u{e9}s")

@section('content')

<section><div class="wrap">

  <div class="sec-head"><p class="eyebrow">Actualit&eacute;s</p><h2>Nos <span class="o">articles</span></h2></div>

  <div class="prog-cards">

    @forelse ($posts as $p)

      <article class="p-card">

        <h3><a href="{{ route('blog.show', $p->slug) }}">{{ $p->title }}</a></h3>

        <p class="muted-p">{{ $p->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($p->content), 150) }}</p>

        <p class="fine">{{ $p->published_at?->format('d/m/Y') }}</p>

      </article>

    @empty

      <p class="muted">Aucun article publi&eacute; pour le moment.</p>

    @endforelse

  </div>

  {{ $posts->links() }}

</div></section>

@endsection

