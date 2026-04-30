<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'label' => null,
    'type' => 'text',
    'placeholder' => '',
    'icon' => null,
    'error' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'label' => null,
    'type' => 'text',
    'placeholder' => '',
    'icon' => null,
    'error' => null,
]); ?>
<?php foreach (array_filter(([
    'label' => null,
    'type' => 'text',
    'placeholder' => '',
    'icon' => null,
    'error' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="w-full">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($label): ?>
        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
            <?php echo e($label); ?>

        </label>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="relative">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($icon): ?>
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i data-lucide="<?php echo e($icon); ?>" class="w-4 h-4 text-slate-400"></i>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <input 
            type="<?php echo e($type); ?>" 
            placeholder="<?php echo e($placeholder); ?>" 
            <?php echo e($attributes->merge([
                'class' => 'w-full ' . ($icon ? 'pl-10' : 'px-4') . ' pr-4 py-2.5 rounded-xl border ' . 
                ($error ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-200 dark:border-slate-600') . 
                ' bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:ring-2 ' . 
                ($error ? 'focus:ring-rose-500' : 'focus:ring-indigo-500') . 
                ' focus:border-transparent transition-all duration-200'
            ])); ?>

        >
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($error): ?>
        <p class="mt-1 text-xs text-rose-500"><?php echo e($error); ?></p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/components/admin/input.blade.php ENDPATH**/ ?>