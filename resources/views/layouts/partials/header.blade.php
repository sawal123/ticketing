<header class="sticky top-0 z-30 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 shadow-sm flex-shrink-0">
    <div class="flex items-center justify-between px-4 lg:px-6 py-3">
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <button onclick="openSidebar()" class="lg:hidden p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition-colors flex-shrink-0" aria-label="Buka sidebar">
                <i data-lucide="menu" class="w-5 h-5"></i>
            </button>
            <div class="relative max-w-md w-full hidden sm:block">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                </div>
                <input type="text" placeholder="Cari apa saja..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200" aria-label="Pencarian">
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <button class="relative p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition-all duration-200" aria-label="Notifikasi">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-white dark:ring-slate-800"></span>
            </button>
            <button id="themeToggle" onclick="toggleTheme()" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition-all duration-200" aria-label="Toggle tema">
                <i data-lucide="sun" id="iconSun" class="w-5 h-5 hidden"></i>
                <i data-lucide="moon" id="iconMoon" class="w-5 h-5"></i>
            </button>

            <!-- Profile Avatar with Dropdown -->
            <div class="relative ml-1">
                <button id="profileBtn" onclick="toggleProfileDropdown()" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-all duration-200" aria-label="Profil pengguna">
                    <div class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center text-white font-semibold text-sm flex-shrink-0 ring-2 ring-indigo-200 dark:ring-indigo-800">
                        {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                    </div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200 hidden md:inline">{{ auth()->user()->name ?? 'Admin' }}</span>
                    <i data-lucide="chevron-down" id="chevronProfile" class="w-4 h-4 text-slate-400 hidden md:block transition-transform duration-200"></i>
                </button>
                <!-- Dropdown Menu -->
                <div id="profileDropdown" class="dropdown-profile absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg py-1 z-50">
                    <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        <i data-lucide="user" class="w-4 h-4"></i> Profil Saya
                    </a>
                    <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        <i data-lucide="settings" class="w-4 h-4"></i> Pengaturan
                    </a>
                    <hr class="border-slate-200 dark:border-slate-700 my-1">
                    <a href="{{ url('/logout') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Keluar
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
