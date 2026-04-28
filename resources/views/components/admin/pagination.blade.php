@if ($paginator->hasPages())
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-5 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Menampilkan 
            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $paginator->firstItem() }}</span> 
            sampai 
            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $paginator->lastItem() }}</span> 
            dari 
            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $paginator->total() }}</span> 
            data
        </p>
        
        <nav class="flex items-center gap-1" aria-label="Pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <button class="p-2 rounded-lg text-slate-300 dark:text-slate-600 cursor-not-allowed" disabled>
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </button>
            @else
                <button wire:click="previousPage" wire:loading.attr="disabled" class="p-2 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 transition-all duration-200">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </button>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="px-3 py-1 text-slate-400">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <button class="px-3 py-1 text-sm font-bold bg-indigo-600 text-white rounded-lg shadow-sm">
                                {{ $page }}
                            </button>
                        @else
                            <button wire:click="gotoPage({{ $page }})" class="px-3 py-1 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-all">
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <button wire:click="nextPage" wire:loading.attr="disabled" class="p-2 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 transition-all duration-200">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </button>
            @else
                <button class="p-2 rounded-lg text-slate-300 dark:text-slate-600 cursor-not-allowed" disabled>
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </button>
            @endif
        </nav>
    </div>
@endif
