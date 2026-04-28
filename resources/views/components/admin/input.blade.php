@props([
    'label' => null,
    'type' => 'text',
    'placeholder' => '',
    'icon' => null,
    'error' => null,
])

<div class="w-full">
    @if($label)
        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        @if($icon)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i data-lucide="{{ $icon }}" class="w-4 h-4 text-slate-400"></i>
            </div>
        @endif

        <input 
            type="{{ $type }}" 
            placeholder="{{ $placeholder }}" 
            {{ $attributes->merge([
                'class' => 'w-full ' . ($icon ? 'pl-10' : 'px-4') . ' pr-4 py-2.5 rounded-xl border ' . 
                ($error ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-200 dark:border-slate-600') . 
                ' bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:ring-2 ' . 
                ($error ? 'focus:ring-rose-500' : 'focus:ring-indigo-500') . 
                ' focus:border-transparent transition-all duration-200'
            ]) }}
        >
    </div>

    @if($error)
        <p class="mt-1 text-xs text-rose-500">{{ $error }}</p>
    @endif
</div>
