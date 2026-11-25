<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    <link rel="shortcut icon" href="{{ asset('storage/icon/' . ($logo[0]->icon ?? '')) }}" type="image/x-icon">

    <!-- Sync Dark/Light Theme with Landing (localStorage: gotik_theme) before paint -->
    <script>
        (function () {
            function applyGotikAuthTheme(theme) {
                var resolved = theme === 'light' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', resolved);
                document.documentElement.classList.toggle('dark', resolved === 'dark');
            }

            window.resolveGotikTheme = function () {
                var saved = localStorage.getItem('gotik_theme');
                if (saved === 'light' || saved === 'dark') return saved;
                return window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            };

            window.toggleGotikTheme = function () {
                var current = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
                var next = current === 'light' ? 'dark' : 'light';
                localStorage.setItem('gotik_theme', next);
                applyGotikAuthTheme(next);
                window.dispatchEvent(new CustomEvent('gotik-theme-changed', { detail: { theme: next } }));
                return next;
            };

            applyGotikAuthTheme(window.resolveGotikTheme());

            document.addEventListener('livewire:navigated', function () {
                applyGotikAuthTheme(window.resolveGotikTheme());
            });

            window.addEventListener('storage', function (e) {
                if (e.key === 'gotik_theme') {
                    applyGotikAuthTheme(window.resolveGotikTheme());
                }
            });
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        indigo: {
                            50: '#f5f7ff',
                            100: '#ebf0fe',
                            200: '#ced9fd',
                            300: '#a1b6fb',
                            400: '#6d8bf7',
                            500: '#435ff0',
                            600: '#2c40e5',
                            700: '#2332cb',
                            800: '#212ba5',
                            900: '#202983',
                            950: '#13184d',
                        },
                    },
                },
            },
        }
    </script>
    @livewireStyles
    <style>
        [x-cloak] { display: none !important; }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="font-sans antialiased h-full bg-slate-50 text-slate-900 dark:bg-[#0b0a1a] dark:text-white transition-colors duration-200">
    <!-- Floating Theme Switcher (synced with landing) -->
    <div class="fixed top-4 right-4 z-50"
        x-data="{
            theme: document.documentElement.getAttribute('data-theme') || 'dark',
            toggle() {
                this.theme = window.toggleGotikTheme();
            }
        }"
        x-on:gotik-theme-changed.window="theme = $event.detail.theme">
        <button type="button"
            x-on:click="toggle()"
            :aria-label="theme === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
            :title="theme === 'dark' ? 'Mode Terang' : 'Mode Gelap'"
            class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/90 dark:bg-[#16152a]/90 border border-slate-200 dark:border-white/10 text-slate-700 dark:text-amber-300 shadow-sm hover:bg-slate-100 dark:hover:bg-[#1e1d35] transition-all duration-200">
            <!-- Sun (shown in dark mode) -->
            <svg x-show="theme === 'dark'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <circle cx="12" cy="12" r="4" />
                <path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41" />
            </svg>
            <!-- Moon (shown in light mode) -->
            <svg x-show="theme === 'light'" x-cloak class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
            </svg>
        </button>
    </div>

    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>

    @livewireScripts
    <script>
        (function () {
            const refreshLucide = () => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            };
            document.addEventListener('livewire:navigated', refreshLucide);
            document.addEventListener('DOMContentLoaded', refreshLucide);
            refreshLucide();
        })();
    </script>
</body>
</html>
