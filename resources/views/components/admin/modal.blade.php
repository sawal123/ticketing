@props(['name', 'title', 'icon' => 'info', 'maxWidth' => 'md'])

@php
    $titleId = $name . '-title';
@endphp

<div
    x-data="{
        show: false,
        loading: false,
        loadError: '',
        displayTitle: @js($title),
        previousBodyOverflow: null,
        open(detail) {
            if (detail.name !== '{{ $name }}') return;
            this.loading = detail.loading === true;
            this.loadError = '';
            this.displayTitle = detail.title || @js($title);
            this.show = true;
        },
        close() {
            this.show = false;
            this.loading = false;
            this.loadError = '';
        },
        lockBackground(locked) {
            if (locked && this.previousBodyOverflow === null) {
                this.previousBodyOverflow = document.body.style.overflow;
                document.body.style.overflow = 'hidden';
            } else if (!locked && this.previousBodyOverflow !== null) {
                document.body.style.overflow = this.previousBodyOverflow;
                this.previousBodyOverflow = null;
            }
        },
    }"
    x-init="$watch('show', value => lockBackground(value))"
    x-on:open-modal.window="open($event.detail)"
    x-on:close-modal.window="if ($event.detail.name === '{{ $name }}') close()"
    x-on:mge-modal-loaded.window="if ($event.detail.name === '{{ $name }}') { loading = false; loadError = ''; displayTitle = $event.detail.title || displayTitle }"
    x-on:mge-modal-error.window="if ($event.detail.name === '{{ $name }}') { loading = false; loadError = $event.detail.message || 'Data editor tidak dapat dimuat. Silakan coba lagi.' }"
>
    <template x-teleport="body">
        <div 
            x-show="show"
            x-on:keydown.escape.window="close()"
            class="fixed inset-0 z-[100] flex items-start sm:items-center justify-center p-2 sm:p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300 overflow-hidden"
            style="display: none;"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <!-- Modal Backdrop -->
            <div class="absolute inset-0" x-on:click="close()"></div>
        
            <!-- Modal Card -->
            <div 
                class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-{{ $maxWidth }} p-6 relative z-10 border border-slate-200 dark:border-slate-700 overflow-y-auto overscroll-contain"
                style="max-height: calc(100dvh - 1rem);"
                role="dialog"
                aria-modal="true"
                aria-labelledby="{{ $titleId }}"
                x-bind:aria-busy="loading ? 'true' : 'false'"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            >
                <button x-on:click="close()" type="button" aria-label="Tutup dialog" title="Tutup dialog" class="absolute top-4 right-4 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
        
                <h3 id="{{ $titleId }}" class="text-xl font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                        <i data-lucide="{{ $icon }}" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <span x-text="displayTitle">{{ $title }}</span>
                </h3>
        
                <div class="modal-content text-slate-600 dark:text-slate-400">
                    <div x-show="loading" class="space-y-4" role="status" aria-live="polite">
                        <div class="flex items-center gap-3 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            <span class="w-5 h-5 rounded-full border-2 border-current border-t-transparent animate-spin"></span>
                            <span>Memuat data editor...</span>
                        </div>
                        <div class="space-y-3 animate-pulse" aria-hidden="true">
                            <div class="h-11 rounded-xl bg-slate-100 dark:bg-slate-700"></div>
                            <div class="h-11 rounded-xl bg-slate-100 dark:bg-slate-700"></div>
                            <div class="h-24 rounded-xl bg-slate-100 dark:bg-slate-700"></div>
                        </div>
                    </div>

                    <div x-show="!loading && loadError" class="space-y-4" role="alert">
                        <div class="rounded-xl bg-rose-50 dark:bg-rose-900/20 p-4 text-sm text-rose-700 dark:text-rose-300" x-text="loadError"></div>
                        <div class="flex justify-end">
                            <x-admin.button type="button" x-on:click="close()" variant="secondary">Tutup</x-admin.button>
                        </div>
                    </div>

                    <div x-show="!loading && !loadError">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
