@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'iconColor' => 'indigo',
    'padding' => 'p-5',
])

@php
    $iconColors = [
        'indigo' => 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400',
        'emerald' => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400',
        'amber' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400',
        'rose' => 'bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400',
        'slate' => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400',
    ];
    $colorClass = $iconColors[$iconColor] ?? $iconColors['indigo'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm card-lift ' . $padding]) }}>
    @if($title || $icon)
        <div class="flex items-center justify-between mb-3">
            @if($icon)
                <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $colorClass }}">
                    <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
                </div>
            @endif
            
            @if($title)
                <h3 class="text-base font-semibold text-slate-800 dark:text-white">{{ $title }}</h3>
            @endif

            @if(isset($headerAction))
                {{ $headerAction }}
            @endif
        </div>
    @endif

    @if($subtitle)
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">{{ $subtitle }}</p>
    @endif

    {{ $slot }}
</div>
