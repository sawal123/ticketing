@props([
    'name',
    'title' => 'Hapus Data',
    'message' => null,
    'description' => null,
])

@php
    $resolvedMessage = $message ?? $description ?? 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';
    $titleId = $name . '-title';
@endphp

<div
    x-data="{
        show: false,
        displayTitle: @js($title),
        displayMessage: @js($resolvedMessage),
        open(detail) {
            if (!detail || detail.name !== '{{ $name }}') return;
            this.displayTitle = detail.title || @js($title);
            this.displayMessage = detail.message || detail.description || @js($resolvedMessage);
            this.show = true;
        },
        close() {
            this.show = false;
        },
    }"
    x-on:open-modal.window="open($event.detail)"
    x-on:close-modal.window="if ($event.detail && $event.detail.name === '{{ $name }}') close()"
>
    <template x-teleport="body">
        <div
            x-show="show"
            x-on:keydown.escape.window="if (show) close()"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300"
            style="display: none;"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <!-- Modal Backdrop -->
            <div class="absolute inset-0" x-on:click="close()"></div>

            <!-- Modal Card -->
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="{{ $titleId }}"
                class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-sm p-8 relative z-10 border border-slate-200 dark:border-slate-700 text-center"
                x-transition:enter="transition ease-out duration-200 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            >
                <!-- Icon -->
                <div class="w-20 h-20 rounded-full bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center mx-auto mb-6 ring-8 ring-rose-50/50 dark:ring-rose-900/10">
                    <i data-lucide="trash-2" class="w-10 h-10 text-rose-600 dark:text-rose-400"></i>
                </div>

                <h3 id="{{ $titleId }}" class="text-xl font-extrabold text-slate-800 dark:text-white mb-2" x-text="displayTitle">
                    {{ $title }}
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed" x-text="displayMessage">
                    {{ $resolvedMessage }}
                </p>

                <div class="flex flex-col gap-3">
                    {{ $slot }}
                    <button type="button" x-on:click="close()" class="w-full py-3 px-4 rounded-2xl text-sm font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors">
                        Batal, Simpan Data
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
