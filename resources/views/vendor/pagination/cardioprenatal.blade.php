{{-- Paginação alinhada ao tema Cardioprenatal (sem Tailwind / sem -ml-px) --}}
@if ($paginator->hasPages())
    <nav class="cp-pagination" role="navigation" aria-label="Paginação">
        <p class="cp-pagination__summary">
            @if ($paginator->firstItem())
                Mostrando <strong>{{ $paginator->firstItem() }}</strong> a <strong>{{ $paginator->lastItem() }}</strong>
                de <strong>{{ $paginator->total() }}</strong> resultados
            @else
                {{ $paginator->count() }} resultado(s)
            @endif
        </p>

        <ul class="cp-pagination__list">
            @if ($paginator->onFirstPage())
                <li class="cp-pagination__item cp-pagination__item--disabled">
                    <span aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="cp-pagination__item">
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="cp-pagination__item cp-pagination__item--ellipsis">
                        <span>{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="cp-pagination__item cp-pagination__item--active" aria-current="page">
                                <span>{{ $page }}</span>
                            </li>
                        @else
                            <li class="cp-pagination__item">
                                <a href="{{ $url }}" aria-label="Ir para página {{ $page }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="cp-pagination__item">
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="cp-pagination__item cp-pagination__item--disabled">
                    <span aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
