@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'icon' => null,
])

@php
    $baseClasses = 'flex items-center justify-center gap-2 font-medium rounded-xl transition-all duration-200 btn-ripple shadow-md';
    
    $variants = [
        'primary' => 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-200 dark:shadow-indigo-900/30',
        'secondary' => 'border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 shadow-none',
        'success' => 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-200 dark:shadow-emerald-900/30',
        'danger' => 'bg-rose-600 hover:bg-rose-700 text-white shadow-rose-200 dark:shadow-rose-900/30',
        'ghost' => 'hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 shadow-none',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
    @endif
    {{ $slot }}
</button>
