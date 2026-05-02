<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['active' => false, 'label' => null]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['active' => false, 'label' => null]); ?>
<?php foreach (array_filter((['active' => false, 'label' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="flex items-center gap-3">
    <button <?php echo e($attributes); ?>

        type="button"
        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none <?php echo e($active ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700'); ?>"
        role="switch" 
        aria-checked="<?php echo e($active ? 'true' : 'false'); ?>"
    >
        <span
            class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out <?php echo e($active ? 'translate-x-5' : 'translate-x-0'); ?>">
            <span
                class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity <?php echo e($active ? 'opacity-0 ease-out duration-100' : 'opacity-100 ease-in duration-200'); ?>"
                aria-hidden="true">
                <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 12 12">
                    <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <span
                class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity <?php echo e($active ? 'opacity-100 ease-in duration-200' : 'opacity-0 ease-out duration-100'); ?>"
                aria-hidden="true">
                <svg class="h-3 w-3 text-emerald-600" fill="currentColor" viewBox="0 0 12 12">
                    <path
                        d="M3.707 5.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414L5 7.586 3.707 5.293z" />
                </svg>
            </span>
        </span>
    </button>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($label): ?>
        <span class="text-xs font-medium text-slate-500">
            <?php echo e($label); ?>

        </span>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/components/admin/toggle.blade.php ENDPATH**/ ?>