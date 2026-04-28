@props(['name', 'title' => 'Hapus Data', 'message' => 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.'])

<div 
    x-data="{ show: false }" 
    x-show="show"
    x-on:open-modal.window="if ($event.detail.name === '{{ $name }}') show = true"
    x-on:close-modal.window="if ($event.detail.name === '{{ $name }}') show = false"
    x-on:keydown.escape.window="show = false"
    class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300"
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
        class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-sm p-8 relative z-10 border border-slate-200 dark:border-slate-700 text-center"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
    >
        <!-- Icon -->
        <div class="w-20 h-20 rounded-full bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center mx-auto mb-6 ring-8 ring-rose-50/50 dark:ring-rose-900/10">
            <i data-lucide="trash-2" class="w-10 h-10 text-rose-600 dark:text-rose-400"></i>
        </div>

        <h3 class="text-xl font-extrabold text-slate-800 dark:text-white mb-2">
            {{ $title }}
        </h3>
        
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">
            {{ $message }}
        </p>

        <div class="flex flex-col gap-3">
            {{ $slot }}
            <button type="button" x-on:click="show = false" class="w-full py-3 px-4 rounded-2xl text-sm font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors">
                Batal, Simpan Data
            </button>
        </div>
    </div>
</div>
