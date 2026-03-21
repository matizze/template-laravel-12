@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="flex items-center justify-between pt-2">
        <p class="text-sm text-gray-400">
            Mostrando
            <span class="font-medium text-gray-400">{{ $paginator->firstItem() }}</span>
            até
            <span class="font-medium text-gray-400">{{ $paginator->lastItem() }}</span>
            de
            <span class="font-medium text-gray-400">{{ $paginator->total() }}</span>
            resultados
        </p>

        <div class="inline-flex rounded-lg overflow-hidden border border-gray-200">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="px-3 py-2 text-sm text-gray-300 bg-gray-100 cursor-not-allowed">
                    <x-icon name="lucide-chevron-left" class="size-4" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-2 text-sm text-gray-400 bg-gray-100 hover:bg-gray-200/30">
                    <x-icon name="lucide-chevron-left" class="size-4" />
                </a>
            @endif

            {{-- Pages --}}
            @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="px-4 py-2 text-sm font-medium bg-gray-200/40 border-x border-gray-200/40">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="px-4 py-2 text-sm text-gray-400 bg-gray-100 border-x border-gray-200 hover:bg-gray-200/30">{{ $page }}</a>
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-2 text-sm text-gray-400 bg-gray-100 hover:bg-gray-200/40">
                    <x-icon name="lucide-chevron-right" class="size-4" />
                </a>
            @else
                <span class="px-3 py-2 text-sm text-gray-300 bg-gray-100 cursor-not-allowed">
                    <x-icon name="lucide-chevron-right" class="size-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
