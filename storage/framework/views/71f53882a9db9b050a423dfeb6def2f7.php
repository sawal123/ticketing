<aside id="sidebar"
    class="fixed lg:sticky top-0 left-0 z-50 w-64 h-full flex flex-col shadow-lg lg:shadow-none sidebar-transition -translate-x-full lg:translate-x-0 transition-colors duration-300 border-r border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300"
    aria-label="Sidebar navigasi">
    <!-- Logo / Brand -->
    <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-200 dark:border-slate-700 flex-shrink-0">
        <div class="w-9 h-9 bg-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
            <i data-lucide="zap" class="w-5 h-5 text-white"></i>
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-slate-900 dark:text-white font-bold text-lg leading-tight truncate">GOTIK Dashboard</h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs">Hybrid Engine v1.0</p>
        </div>
        <button onclick="closeSidebar()"
            class="lg:hidden p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors flex-shrink-0"
            aria-label="Tutup sidebar">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- Navigation Menu (scrollable) -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1" aria-label="Menu utama" x-data="{ 
        targetTab: '',
        openEventModal(tab) {
            this.targetTab = tab;
            $dispatch('open-modal', { name: 'select-event-modal' });
        }
    }">
        <p class="px-3 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Menu
            Utama</p>

        <!-- a. Dashboard -->
        <a href="<?php echo e(route('dashboard')); ?>" wire:navigate
            class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
            <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Dashboard</span>
        </a>

        <!-- b. Event -->
        <a href="<?php echo e(auth()->user()->role === 'admin' ? route('admin.event') : route('dashboard.event')); ?>"
            wire:navigate
            class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->routeIs('admin.event') || request()->routeIs('dashboard.event') ? 'active' : ''); ?>">
            <i data-lucide="calendar" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Event</span>
        </a>

        <!-- c. Voucher -->
        <a href="<?php echo e(route('dashboard.voucher')); ?>" wire:navigate
            class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->routeIs('dashboard.voucher') ? 'active' : ''); ?>">
            <i data-lucide="ticket" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Voucher</span>
        </a>

        <!-- d. Partner -->
        <a href="<?php echo e(route('dashboard.partner')); ?>" wire:navigate
            class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->routeIs('dashboard.partner') ? 'active' : ''); ?>">
            <i data-lucide="users" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Partner</span>
        </a>


        <!-- NEW: Ticket & Transaksi with Modal -->
        <div class="pt-2">
            <p class="px-3 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Operasional</p>
            
            <a href="#" @click.prevent="openEventModal('tiket')"
                class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200">
                <i data-lucide="shopping-bag" class="w-5 h-5 flex-shrink-0"></i>
                <span class="text-sm font-medium">Tiket Event</span>
            </a>

            <a href="#" @click.prevent="openEventModal('transaksi')"
                class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200">
                <i data-lucide="banknote" class="w-5 h-5 flex-shrink-0"></i>
                <span class="text-sm font-medium">Transaksi</span>
            </a>
        </div>

        <p class="px-3 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-6 mb-2">
            Keuangan & Tim</p>


        <!-- f. Penarikan Saldo -->
        <a href="<?php echo e(auth()->user()->role === 'admin' ? route('admin.penarikan') : route('dashboard.penarikan')); ?>"
            wire:navigate
            class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->routeIs('admin.penarikan') || request()->routeIs('dashboard.penarikan') ? 'active' : ''); ?>">
            <i data-lucide="wallet" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Penarikan Saldo</span>
        </a>

        <!-- g. Staff -->
        <a href="<?php echo e(route('dashboard.staff')); ?>" wire:navigate
            class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->routeIs('dashboard.staff') ? 'active' : ''); ?>">
            <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Staff</span>
        </a>

        <p class="px-3 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-6 mb-2">
            Sistem</p>

        <!-- h. Pengaturan Akun -->
        <a href="<?php echo e(auth()->user()->role === 'admin' ? route('admin.setting') : route('dashboard.settings')); ?>"
            wire:navigate
            class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->routeIs('dashboard.settings') || request()->is('*setting*') || request()->is('*profile*') ? 'active' : ''); ?>">
            <i data-lucide="settings" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Pengaturan Akun</span>
        </a>

        <!-- i. Landing Page -->
        <a href="<?php echo e(url('/')); ?>" wire:navigate
            class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200">
            <i data-lucide="monitor" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Landing Page</span>
        </a>


        <!-- MODAL PILIH EVENT -->
        <?php if (isset($component)) { $__componentOriginal883972b03e56cea0994a1aaccc5761f0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal883972b03e56cea0994a1aaccc5761f0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.modal','data' => ['name' => 'select-event-modal','title' => 'Pilih Event','icon' => 'shopping-cart']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'select-event-modal','title' => 'Pilih Event','icon' => 'shopping-cart']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="space-y-3">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Daftar Event Aktif</p>
                <div class="grid gap-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $activeEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <a :href="'<?php echo e(auth()->user()->role === 'admin' ? '/admin/event/' : '/dashboard/event/'); ?>' + '<?php echo e($event->uid); ?>' + '?activeTab=' + targetTab" 
                           wire:navigate
                           class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 hover:border-indigo-500 dark:hover:border-indigo-500 hover:bg-indigo-50/30 dark:hover:bg-indigo-900/20 transition-all duration-200 group">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0 ring-2 ring-slate-100 dark:ring-slate-800 group-hover:ring-indigo-100 dark:group-hover:ring-indigo-900/50 transition-all">
                                    <img src="<?php echo e(asset('storage/cover/' . $event->cover)); ?>" class="w-full h-full object-cover" alt="<?php echo e($event->event); ?>">
                                </div>
                                <div class="text-left">
                                    <h4 class="text-sm font-bold text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors"><?php echo e($event->event); ?></h4>
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Mulai Transaksi</p>
                                </div>
                            </div>
                            <i data-lucide="arrow-right" class="w-4 h-4 text-slate-300 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all"></i>
                        </a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <div class="text-center py-8">
                            <i data-lucide="alert-circle" class="w-12 h-12 text-slate-300 mx-auto mb-3"></i>
                            <p class="text-sm text-slate-500">Tidak ada event aktif saat ini.</p>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal883972b03e56cea0994a1aaccc5761f0)): ?>
<?php $attributes = $__attributesOriginal883972b03e56cea0994a1aaccc5761f0; ?>
<?php unset($__attributesOriginal883972b03e56cea0994a1aaccc5761f0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal883972b03e56cea0994a1aaccc5761f0)): ?>
<?php $component = $__componentOriginal883972b03e56cea0994a1aaccc5761f0; ?>
<?php unset($__componentOriginal883972b03e56cea0994a1aaccc5761f0); ?>
<?php endif; ?>
    </nav>

    <!-- Sidebar Footer -->
    <div class="p-4 border-t border-slate-200 dark:border-slate-700 flex-shrink-0">
        <div class="flex items-center gap-3 px-2 py-2 rounded-lg bg-slate-100 dark:bg-slate-800">
            <div
                class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center text-white font-semibold text-sm flex-shrink-0 ring-2 ring-indigo-200 dark:ring-indigo-800">
                <?php echo e(substr(auth()->user()->name ?? 'Admin', 0, 1)); ?>

            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-slate-700 dark:text-slate-200 font-medium truncate">
                    <?php echo e(auth()->user()->name ?? 'Administrator'); ?>

                </p>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate"><?php echo e(auth()->user()->email ??
                    'admin@example.com'); ?></p>
            </div>
        </div>
    </div>
</aside><?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/layouts/partials/sidebar.blade.php ENDPATH**/ ?>