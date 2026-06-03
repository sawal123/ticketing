@props([
    'label' => null,
    'type' => 'text',
    'placeholder' => '',
    'icon' => null,
    'error' => null,
    'revealable' => false,
])

<div class="w-full">
    @if($label)
        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
            {{ $label }}
        </label>
    @endif

    <div class="relative" @if($revealable && $type === 'password') x-data="{ visible: false }" @endif>
        @if($icon)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i data-lucide="{{ $icon }}" class="w-4 h-4 text-slate-400"></i>
            </div>
        @endif

        <input 
            type="{{ $type }}" 
            @if($revealable && $type === 'password') x-bind:type="visible ? 'text' : 'password'" @endif
            placeholder="{{ $placeholder }}" 
            {{ $attributes->merge([
                'class' => 'w-full ' . ($icon ? 'pl-10' : 'px-4') . ($revealable && $type === 'password' ? ' pr-11' : ' pr-4') . ' py-2.5 rounded-xl border ' .
                ($error ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-200 dark:border-slate-600') . 
                ' bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:ring-2 ' . 
                ($error ? 'focus:ring-rose-500' : 'focus:ring-indigo-500') . 
                ' focus:border-transparent transition-all duration-200'
            ]) }}
        >

        @if($revealable && $type === 'password')
            <button type="button" x-on:click="visible = !visible"
                class="absolute inset-y-0 right-0 px-3 flex items-center justify-center text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                x-bind:title="visible ? 'Sembunyikan password' : 'Lihat password'">
                <svg x-show="!visible" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                <svg x-show="visible" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="24"
                    height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                    <path d="m15 18-.722-3.25" />
                    <path d="M2 8a10.645 10.645 0 0 0 20 0" />
                    <path d="m20 15-1.726-2.05" />
                    <path d="m4 15 1.726-2.05" />
                    <path d="m9 18 .722-3.25" />
                </svg>
            </button>
        @endif
    </div>

    @if($error)
        <p class="mt-1 text-xs text-rose-500">{{ $error }}</p>
    @endif
</div>
