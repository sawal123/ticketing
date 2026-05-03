<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Aktivitas & Log Login</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Pantau aktivitas pengguna dan riwayat login di platform.</p>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-admin.card title="Total Aktivitas" icon="activity" iconColor="indigo">
            <div class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ \App\Models\ActivityLog::count() }}
            </div>
            <p class="text-[10px] text-slate-500 mt-1">Total log tersimpan di database</p>
        </x-admin.card>
        <x-admin.card title="Aktivitas Hari Ini" icon="calendar" iconColor="emerald">
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                {{ \App\Models\ActivityLog::whereDate('created_at', now())->count() }}
            </div>
            <p class="text-[10px] text-slate-500 mt-1">Log aktivitas dalam 24 jam terakhir</p>
        </x-admin.card>
        <x-admin.card title="Pengguna Aktif" icon="users" iconColor="amber">
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                {{ \App\Models\ActivityLog::where('created_at', '>=', now()->subMinutes(15))->distinct('user_uid')->count() }}
            </div>
            <p class="text-[10px] text-slate-500 mt-1">Pengguna yang berinteraksi dalam 15 menit terakhir</p>
        </x-admin.card>
    </div>

    <!-- Filter & Table Section -->
    <x-admin.card>
        <div class="flex flex-col md:flex-row justify-between gap-4 mb-6">
            <div class="flex-1 max-w-md">
                <x-admin.input wire:model.live.debounce.300ms="search" placeholder="Cari aktivitas atau IP..." icon="search" />
            </div>
            <div class="w-full md:w-64">
                <x-admin.input wire:model.live.debounce.300ms="filterUser" placeholder="Filter nama user..." icon="user" />
            </div>
        </div>

        <x-admin.table :headers="['Waktu', 'User', 'Aktivitas', 'IP & Browser']">
            @forelse($activities as $item)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                {{ $item->created_at->translatedFormat('d M Y') }}
                            </span>
                            <span class="text-[10px] text-slate-400">
                                {{ $item->created_at->format('H:i:s') }}
                            </span>
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 font-bold text-xs uppercase">
                                {{ substr($item->user->name ?? 'Guest', 0, 1) }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-800 dark:text-white">
                                    {{ $item->user->name ?? 'Guest' }}
                                </span>
                                <span class="text-[10px] text-slate-500">
                                    {{ $item->user->role ?? '-' }}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex flex-col gap-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ str_contains($item->activity, 'Login') ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700' }} w-fit">
                                {{ $item->activity }}
                            </span>
                            <p class="text-xs text-slate-600 dark:text-slate-400 max-w-xs line-clamp-2">
                                {{ $item->description }}
                            </p>
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                            <span class="text-xs font-mono text-slate-500">{{ $item->ip_address }}</span>
                            <span class="text-[10px] text-slate-400 truncate max-w-[150px]" title="{{ $item->user_agent }}">
                                {{ Str::limit($item->user_agent, 30) }}
                            </span>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-5 py-10 text-center">
                        <div class="flex flex-col items-center gap-2 text-slate-400">
                            <i data-lucide="history" class="w-10 h-10 opacity-20"></i>
                            <p class="text-sm">Belum ada catatan aktivitas.</p>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $activities->links() }}
            </x-slot>
        </x-admin.table>
    </x-admin.card>
</div>
