
@php

$lots = $galleryLots ?? [];

$groups = $photos->filter(fn($p) => $p->url())->groupBy(function($p) {

    if ($p->path && str_starts_with($p->path, 'images/galerie/')) {

        return explode('/', $p->path)[2] ?? 'divers';

    }

    return 'televerse-' . $p->edition;

})->map(function($items, $folder) use ($lots) {

    $m = $lots[$folder] ?? [];

    $f = $items->first();

    return ['id' => $folder, 'title' => $m['title'] ?? ($f->title ?: $folder), 'edition' => $m['edition'] ?? $f->edition, 'cover' => $f->url(), 'count' => $items->count(), 'photos' => $items->values()];

})->sortByDesc('edition')->values();

@endphp

<section id="galerie"><div class="wrap">

<div class="sec-head"><p class="eyebrow">Galerie</p><h2>Retour sur <span class="o">nos éditions</span></h2><p class="lede">Ouvrez un dossier, puis touchez une photo pour l’agrandir.</p></div>

<div class="gal-folders" id="galFolders">

@foreach($groups as $g)

<button type="button" class="gal-folder" data-folder="{{ $g['id'] }}" aria-label="Ouvrir {{ $g['title'] }}, édition {{ $g['edition'] }}, {{ $g['count'] }} photos"><span class="gal-tab" aria-hidden="true"></span><span class="gal-cover"><img src="{{ $g['cover'] }}" alt="" loading="lazy"></span><span class="gal-meta"><b>{{ $g['title'] }}</b><small>Édition {{ $g['edition'] }}</small><span class="gal-count">{{ $g['count'] }} photos</span></span></button>

@endforeach

</div>

<div class="gal-photos" id="galPhotos" hidden>

<button type="button" class="btn btn-ghost" id="galBack">← Tous les dossiers</button>

<div id="galleryPhotos">

@foreach($groups as $g)

<div data-folder-panel="{{ $g['id'] }}" hidden><h3 class="gal-folder-title">{{ $g['title'] }} <small>Édition {{ $g['edition'] }} · {{ $g['count'] }} photos</small></h3><div class="gal-grid">

@foreach($g['photos'] as $p)

<button type="button" class="tile" data-lb-src="{{ $p->url() }}" data-lb-title="{{ $p->title }}" aria-label="Agrandir : {{ $p->title }}"><img src="{{ $p->url() }}" alt="{{ $p->title }}" loading="lazy"></button>

@endforeach

</div></div>

@endforeach

</div></div>

</div></section>

<div class="lightbox" id="lightbox" hidden role="dialog" aria-modal="true" aria-label="Photo agrandie"><div class="lb-top"><span id="lbCap"></span><button class="lb-btn" id="lbClose" type="button">Fermer ✕</button></div><figure><img id="lbImg" alt=""></figure><div class="lb-bar"><button class="lb-btn" id="lbPrev" type="button">← Précédente</button><button class="lb-btn" id="lbNext" type="button">Suivante →</button></div></div>
