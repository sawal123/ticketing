<aside id="sidebar" class="fixed lg:sticky top-0 left-0 z-50 w-64 h-full flex flex-col shadow-lg lg:shadow-none sidebar-transition -translate-x-full lg:translate-x-0 transition-colors duration-300 border-r border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300" aria-label="Sidebar navigasi">
    <!-- Logo / Brand -->
    <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-200 dark:border-slate-700 flex-shrink-0">
        <div class="w-9 h-9 bg-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
            <i data-lucide="zap" class="w-5 h-5 text-white"></i>
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-slate-900 dark:text-white font-bold text-lg leading-tight truncate">GOTIK Dashboard</h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs">Hybrid Engine v1.0</p>
        </div>
        <button onclick="closeSidebar()" class="lg:hidden p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors flex-shrink-0" aria-label="Tutup sidebar">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- Navigation Menu (scrollable) -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1" aria-label="Menu utama">
        <p class="px-3 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Menu Utama</p>
        
        <!-- a. Dashboard -->
        <a href="<?php echo e(route('dashboard.demo')); ?>" wire:navigate class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->routeIs('dashboard.demo') ? 'active' : ''); ?>">
            <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Dashboard</span>
        </a>

        <!-- b. Event -->
        <a href="<?php echo e(auth()->user()->role === 'admin' ? route('admin.event') : route('dashboard.demo.event')); ?>" wire:navigate class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->routeIs('admin.event') || request()->routeIs('dashboard.demo.event') ? 'active' : ''); ?>">
            <i data-lucide="calendar" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Event</span>
        </a>

        <!-- c. Voucher -->
        <a href="<?php echo e(url('/dashboard/voucher')); ?>" wire:navigate class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->is('*voucher*') ? 'active' : ''); ?>">
            <i data-lucide="ticket" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Voucher</span>
        </a>

        <!-- d. Partner -->
        <a href="<?php echo e(url('/dashboard/partner')); ?>" wire:navigate class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->is('*partner*') ? 'active' : ''); ?>">
            <i data-lucide="users-2" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Partner</span>
        </a>

        <p class="px-3 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-6 mb-2">Keuangan & Tim</p>

        <!-- e. Money -->
        <a href="<?php echo e(url('/dashboard/money')); ?>" wire:navigate class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->is('*money*') ? 'active' : ''); ?>">
            <i data-lucide="banknote" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Money</span>
        </a>

        <!-- f. Penarikan Saldo -->
        <a href="<?php echo e(auth()->user()->role === 'admin' ? route('admin.penarikan') : url('/dashboard/money')); ?>" wire:navigate class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->is('*penarikan*') ? 'active' : ''); ?>">
            <i data-lucide="wallet" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Penarikan Saldo</span>
        </a>

        <!-- g. Staff -->
        <a href="<?php echo e(url('/dashboard/staff')); ?>" wire:navigate class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->is('*staff*') ? 'active' : ''); ?>">
            <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Staff</span>
        </a>

        <p class="px-3 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-6 mb-2">Sistem</p>
        
        <!-- h. Pengaturan Akun -->
        <a href="<?php echo e(auth()->user()->role === 'admin' ? route('admin.setting') : url('/dashboard/profile')); ?>" wire:navigate class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 <?php echo e(request()->is('*setting*') || request()->is('*profile*') ? 'active' : ''); ?>">
            <i data-lucide="settings" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Pengaturan Akun</span>
        </a>

        <!-- i. Landing Page -->
        <a href="<?php echo e(route('admin.setting')); ?>" wire:navigate class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200">
            <i data-lucide="monitor" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-medium">Landing Page</span>
        </a>
    </nav>

    <!-- Sidebar Footer -->
    <div class="p-4 border-t border-slate-200 dark:border-slate-700 flex-shrink-0">
        <div class="flex items-center gap-3 px-2 py-2 rounded-lg bg-slate-100 dark:bg-slate-800">
            <div class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center text-white font-semibold text-sm flex-shrink-0 ring-2 ring-indigo-200 dark:ring-indigo-800">
                <?php echo e(substr(auth()->user()->name ?? 'Admin', 0, 1)); ?>

            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-slate-700 dark:text-slate-200 font-medium truncate"><?php echo e(auth()->user()->name ?? 'Administrator'); ?></p>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate"><?php echo e(auth()->user()->email ?? 'admin@example.com'); ?></p>
            </div>
        </div>
    </div>
</aside>
<?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/layouts/partials/sidebar.blade.php ENDPATH**/ ?>