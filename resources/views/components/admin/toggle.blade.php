@props(['active' => false, 'label' => null])

<div class="flex items-center gap-3">
    <button {{ $attributes }}
        type="button"
        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $active ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}"
        role="switch" 
        aria-checked="{{ $active ? 'true' : 'false' }}"
    >
        <span
            class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $active ? 'translate-x-5' : 'translate-x-0' }}">
            <span
                class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity {{ $active ? 'opacity-0 ease-out duration-100' : 'opacity-100 ease-in duration-200' }}"
                aria-hidden="true">
                <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 12 12">
                    <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <span
                class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity {{ $active ? 'opacity-100 ease-in duration-200' : 'opacity-0 ease-out duration-100' }}"
                aria-hidden="true">
                <svg class="h-3 w-3 text-emerald-600" fill="currentColor" viewBox="0 0 12 12">
                    <path
                        d="M3.707 5.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414L5 7.586 3.707 5.293z" />
                </svg>
            </span>
        </span>
    </button>
    @if($label)
        <span class="text-xs font-medium text-slate-500">
            {{ $label }}
        </span>
    @endif
</div>
