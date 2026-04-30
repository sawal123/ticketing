<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'iconColor' => 'indigo',
    'padding' => 'p-5',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'iconColor' => 'indigo',
    'padding' => 'p-5',
]); ?>
<?php foreach (array_filter(([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'iconColor' => 'indigo',
    'padding' => 'p-5',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $iconColors = [
        'indigo' => 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400',
        'emerald' => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400',
        'amber' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400',
        'rose' => 'bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400',
        'slate' => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400',
    ];
    $colorClass = $iconColors[$iconColor] ?? $iconColors['indigo'];
?>

<div <?php echo e($attributes->merge(['class' => 'bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm card-lift ' . $padding])); ?>>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($title || $icon): ?>
        <div class="flex items-center justify-between mb-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($icon): ?>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center <?php echo e($colorClass); ?>">
                    <i data-lucide="<?php echo e($icon); ?>" class="w-5 h-5"></i>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($title): ?>
                <h3 class="text-base font-semibold text-slate-800 dark:text-white"><?php echo e($title); ?></h3>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($headerAction)): ?>
                <?php echo e($headerAction); ?>

            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($subtitle): ?>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4"><?php echo e($subtitle); ?></p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php echo e($slot); ?>

</div>
<?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/components/admin/card.blade.php ENDPATH**/ ?>