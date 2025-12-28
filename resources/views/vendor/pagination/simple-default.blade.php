@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <div class="join">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="join-item btn btn-disabled">@lang('pagination.previous')</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="join-item btn">@lang('pagination.previous')</a>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="join-item btn">@lang('pagination.next')</a>
            @else
                <span class="join-item btn btn-disabled">@lang('pagination.next')</span>
            @endif
        </div>
    </nav>
@endif
