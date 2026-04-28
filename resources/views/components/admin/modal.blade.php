@props(['name', 'title', 'icon' => 'info'])

<div 
    x-data="{ show: false }" 
    x-show="show"
    x-on:open-modal.window="if ($event.detail.name === '{{ $name }}') show = true"
    x-on:close-modal.window="if ($event.detail.name === '{{ $name }}') show = false"
    x-on:keydown.escape.window="show = false"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-all duration-300"
    style="display: none;"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <!-- Modal Backdrop -->
    <div class="absolute inset-0" x-on:click="show = false"></div>

    <!-- Modal Card -->
    <div 
        class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-md p-6 relative z-10 border border-slate-200 dark:border-slate-700"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
    >
        <button x-on:click="show = false" class="absolute top-4 right-4 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 transition-colors">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                <i data-lucide="{{ $icon }}" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            </div>
            {{ $title }}
        </h3>

        <div class="modal-content text-slate-600 dark:text-slate-400">
            {{ $slot }}
        </div>
    </div>
</div>
