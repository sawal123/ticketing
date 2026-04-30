<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard Admin' }} - AdminPanel Pro</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com" data-navigate-once></script>
    <script data-navigate-once>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>

    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest" data-navigate-track></script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js" data-navigate-track></script>

    <!-- Custom Font (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('admin/css/admin.css') }}" data-navigate-track>

    <!-- Custom JS -->
    <script src="{{ asset('admin/js/admin.js') }}" data-navigate-once defer></script>

    <!-- Flatpickr CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr" data-navigate-track></script>

    @livewireStyles
    @stack('styles')
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 h-screen overflow-hidden">

    <div class="flex h-full">
        <!-- SIDEBAR OVERLAY (MOBILE) -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden sidebar-transition opacity-0 pointer-events-none" aria-hidden="true"></div>

        <!-- SIDEBAR -->
        @include('layouts.partials.sidebar')

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- HEADER -->
            @include('layouts.partials.header')

            <!-- MAIN CONTENT -->
            <main class="flex-1 p-4 lg:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
    
    @livewireScripts
    @stack('scripts')
</body>
</html>
