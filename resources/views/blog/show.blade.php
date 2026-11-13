@extends('layouts.v2')

@section('title', $post->title)

@php

  $ogDesc = $post->excerpt ?: \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $post->content))), 180);

  $ogImg = $post->cover_path ? url('medias/'.$post->cover_path) : asset('images/logo.jpg');

  $shareUrl = route('blog.show', $post->slug);

@endphp

@section('head')

<link rel="canonical" href="{{ $shareUrl }}">

<meta name="description" content="{{ $ogDesc }}">

<meta property="og:type" content="article">

<meta property="og:site_name" content="JNUAF">

<meta property="og:title" content="{{ $post->title }}">

<meta property="og:description" content="{{ $ogDesc }}">

<meta property="og:url" content="{{ $shareUrl }}">

<meta property="og:image" content="{{ $ogImg }}">

<meta property="og:locale" content="fr_FR">

<meta property="article:published_time" content="{{ $post->published_at?->toIso8601String() }}">

<meta name="twitter:card" content="summary_large_image">

@endsection



@section('content')

<section><div class="wrap">

  <p class="eyebrow">{{ $post->published_at?->format('d/m/Y') }}</p>

  <h1>{{ $post->title }}</h1>
  @if ($post->cover_path)<img src="{{ url('medias/'.$post->cover_path) }}" alt="" style="max-width:100%;border-radius:12px;margin:16px 0">@endif
  @if ($post->category)<p class="eyebrow">{{ $post->category }}</p>@endif
  @if ($post->tags)<p class="fine">{{ $post->tags }}</p>@endif

  <style>.post-content{overflow-wrap:anywhere;line-height:1.7}.post-content .ql-align-center{text-align:center}.post-content .ql-align-right{text-align:right}.post-content .ql-align-justify{text-align:justify}.post-content::after{content:"";display:block;clear:both}.post-content img[style*=float]{margin:8px 16px}.post-content img,.post-content video,.post-content iframe{max-width:100%;height:auto;border-radius:8px;margin:12px 0}.post-content table{display:block;max-width:100%;overflow-x:auto}.post-content h2,.post-content h3{margin:24px 0 8px}.post-content blockquote{border-left:4px solid #ccc;margin:16px 0;padding:4px 16px}</style>
<div class="card post-content" style="margin-top:20px">{!! $post->content !!}</div>

  
<p class="fine" style="margin-top:16px">Partager :

  <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener">Facebook</a> &middot;

  <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener">LinkedIn</a> &middot;

  <a href="https://wa.me/?text={{ urlencode($post->title.' '.$shareUrl) }}" target="_blank" rel="noopener">WhatsApp</a> &middot;

  <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&amp;text={{ urlencode($post->title) }}" target="_blank" rel="noopener">X</a> &middot;

  <a href="mailto:?subject={{ rawurlencode($post->title) }}&amp;body={{ rawurlencode($shareUrl) }}">E-mail</a>

</p>


<p style="margin-top:20px"><a class="btn" href="{{ route('blog.index') }}">&larr; Toutes les actualit&eacute;s</a></p>

</div></section>

@endsection


<!-- jnuaf-s82 -->