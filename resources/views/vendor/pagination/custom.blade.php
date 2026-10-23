@if ($paginator->hasPages())
<div class="pagination-wrapper">

    <span class="pagination-info">
        Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
        de {{ $paginator->total() }} alumnos
    </span>

    <div class="pagination-controls">

        {{-- Primera página --}}
        @if ($paginator->onFirstPage())
            <button class="page-btn" disabled><i class="ri-arrow-left-double-line"></i></button>
        @else
            <a href="{{ $paginator->url(1) }}&{{ http_build_query(request()->except('page')) }}" class="page-btn"><i class="ri-arrow-left-double-line"></i></a>
        @endif

        {{-- Anterior --}}
        @if ($paginator->onFirstPage())
            <button class="page-btn" disabled><i class="ri-arrow-left-s-line"></i></button>
        @else
            <a href="{{ $paginator->previousPageUrl() }}&{{ http_build_query(request()->except('page')) }}" class="page-btn"><i class="ri-arrow-left-s-line"></i></a>
        @endif

        {{-- Números de página --}}
        @php
            $current = $paginator->currentPage();
            $last    = $paginator->lastPage();
            $window  = 2; // páginas a cada lado del actual
        @endphp

        @if ($current > $window + 2)
            <a href="{{ $paginator->url(1) }}&{{ http_build_query(request()->except('page')) }}" class="page-btn">1</a>
            @if ($current > $window + 3)
                <span class="page-ellipsis">…</span>
            @endif
        @endif

        @for ($i = max(1, $current - $window); $i <= min($last, $current + $window); $i++)
            @if ($i === $current)
                <button class="page-btn active" disabled>{{ $i }}</button>
            @else
                <a href="{{ $paginator->url($i) }}&{{ http_build_query(request()->except('page')) }}" class="page-btn">{{ $i }}</a>
            @endif
        @endfor

        @if ($current < $last - $window - 1)
            @if ($current < $last - $window - 2)
                <span class="page-ellipsis">…</span>
            @endif
            <a href="{{ $paginator->url($last) }}&{{ http_build_query(request()->except('page')) }}" class="page-btn">{{ $last }}</a>
        @endif

        {{-- Siguiente --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}&{{ http_build_query(request()->except('page')) }}" class="page-btn"><i class="ri-arrow-right-s-line"></i></a>
        @else
            <button class="page-btn" disabled><i class="ri-arrow-right-s-line"></i></button>
        @endif

        {{-- Última página --}}
        @if ($paginator->currentPage() === $paginator->lastPage())
            <button class="page-btn" disabled><i class="ri-arrow-right-double-line"></i></button>
        @else
            <a href="{{ $paginator->url($last) }}&{{ http_build_query(request()->except('page')) }}" class="page-btn"><i class="ri-arrow-right-double-line"></i></a>
        @endif

    </div>
</div>
@endif