<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'icon' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'icon' => null,
]); ?>
<?php foreach (array_filter(([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'icon' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
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
?>

<button type="<?php echo e($type); ?>" <?php echo e($attributes->merge(['class' => $classes])); ?>>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($icon): ?>
        <i data-lucide="<?php echo e($icon); ?>" class="w-4 h-4"></i>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php echo e($slot); ?>

</button>
<?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/components/admin/button.blade.php ENDPATH**/ ?>