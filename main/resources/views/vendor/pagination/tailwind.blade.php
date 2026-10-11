@if($paginator->hasPages())
<nav class="pagination-toolbar" aria-label="Pagination navigation">
    <p>Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }}</p>
    <div class="pagination-links">
        @if($paginator->onFirstPage())<span class="pagination-disabled" aria-disabled="true"><x-action-icon name="left"/> Previous</span>@else<a class="button button-secondary button-small" href="{{ $paginator->previousPageUrl() }}" rel="prev"><x-action-icon name="left"/> Previous</a>@endif
        @foreach($elements as $element)
            @if(is_string($element))<span class="pagination-gap" aria-hidden="true">{{ $element }}</span>@endif
            @if(is_array($element))
                @foreach($element as $page => $url)
                    @if($page === $paginator->currentPage())<span class="pagination-current" role="link" aria-current="page" aria-disabled="true" aria-label="Page {{ $page }}">{{ $page }}</span>@else<a class="button button-quiet button-small pagination-number" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>@endif
                @endforeach
            @endif
        @endforeach
        @if($paginator->hasMorePages())<a class="button button-secondary button-small" href="{{ $paginator->nextPageUrl() }}" rel="next">Next <x-action-icon name="right"/></a>@else<span class="pagination-disabled" aria-disabled="true">Next <x-action-icon name="right"/></span>@endif
    </div>
</nav>
@endif
