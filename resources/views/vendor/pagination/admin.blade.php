
@if ($paginator->hasPages())

<nav class="pager" role="navigation" aria-label="Pagination" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-top:16px">

    <div style="display:flex;gap:8px;align-items:center">

        @if ($paginator->onFirstPage())

            <span class="btn ghost sm" aria-disabled="true" style="opacity:.45;pointer-events:none">&lsaquo; Pr&eacute;c&eacute;dent</span>

        @else

            <a class="btn ghost sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo; Pr&eacute;c&eacute;dent</a>

        @endif

        @if ($paginator->hasMorePages())

            <a class="btn ghost sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Suivant &rsaquo;</a>

        @else

            <span class="btn ghost sm" aria-disabled="true" style="opacity:.45;pointer-events:none">Suivant &rsaquo;</span>

        @endif

    </div>

    <span class="muted">

        @if (method_exists($paginator, 'lastPage'))

            Page {{ $paginator->currentPage() }} sur {{ $paginator->lastPage() }} &middot; {{ $paginator->total() }} &eacute;l&eacute;ment(s)

        @else

            Page {{ $paginator->currentPage() }}

        @endif

    </span>

</nav>

@endif

