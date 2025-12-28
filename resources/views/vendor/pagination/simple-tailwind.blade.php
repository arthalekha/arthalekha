@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
        <div class="join">
            @if ($paginator->onFirstPage())
                <span class="join-item btn btn-disabled">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="join-item btn">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="join-item btn">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="join-item btn btn-disabled">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>
    </nav>
@endif
