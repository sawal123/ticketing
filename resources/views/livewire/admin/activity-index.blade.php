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
        <div class="flex flex-col gap-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="lg:col-span-2">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1 block">Cari Aktivitas</label>
                    <x-admin.input wire:model.live.debounce.300ms="search" placeholder="Cari aktivitas, IP, atau lokasi..." icon="search" />
                </div>
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1 block">Nama User</label>
                    <x-admin.input wire:model.live.debounce.300ms="filterUser" placeholder="Semua User" icon="user" />
                </div>
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1 block">Tingkat Dampak</label>
                    <select wire:model.live="filterImpact" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 transition-all outline-none">
                        <option value="all">Semua Dampak</option>
                        <option value="Normal">Normal</option>
                        <option value="Sensitif">Sensitif</option>
                        <option value="Berisiko Tinggi">Berisiko Tinggi</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1 block">Status Login</label>
                    <select wire:model.live="filterStatus" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 transition-all outline-none">
                        <option value="all">Semua Status</option>
                        <option value="Success">Success</option>
                        <option value="Failed">Failed</option>
                    </select>
                </div>
            </div>
        </div>

        <x-admin.table :headers="['Waktu', 'User', 'Aktivitas & Dampak', 'Status', 'IP & Lokasi']">
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
                                {{ substr($item->user->name ?? 'G', 0, 1) }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-800 dark:text-white">
                                    {{ $item->user->name ?? 'Guest' }}
                                </span>
                                <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">
                                    {{ $item->user->role ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex flex-col gap-1.5">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800">
                                    {{ $item->activity }}
                                </span>
                                
                                @php
                                    $impactColors = [
                                        'Normal' => 'bg-slate-100 text-slate-600 border-slate-200',
                                        'Sensitif' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'Berisiko Tinggi' => 'bg-rose-50 text-rose-700 border-rose-200 animate-pulse',
                                    ];
                                    $impactClass = $impactColors[$item->impact_level] ?? $impactColors['Normal'];
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider border {{ $impactClass }}">
                                    {{ $item->impact_level }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-400 max-w-xs line-clamp-1" title="{{ $item->description }}">
                                {{ $item->description }}
                            </p>
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        @if($item->login_status)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $item->login_status === 'Success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $item->login_status === 'Success' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                {{ $item->login_status }}
                            </span>
                        @else
                            <span class="text-slate-300">-</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-1.5 mb-1">
                                <i data-lucide="map-pin" class="w-3 h-3 text-slate-400"></i>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    {{ $item->location ?? 'Unknown' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="monitor" class="w-3 h-3 text-slate-400"></i>
                                <span class="text-[10px] font-mono text-slate-400" title="{{ $item->ip_address }} | {{ $item->user_agent }}">
                                    {{ $item->ip_address }}
                                </span>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-16 text-center">
                        <div class="flex flex-col items-center gap-3 text-slate-400">
                            <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800/50 rounded-full flex items-center justify-center">
                                <i data-lucide="history" class="w-8 h-8 opacity-20"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-600 dark:text-slate-400">Belum ada catatan aktivitas.</p>
                                <p class="text-xs">Log akan muncul di sini setelah sistem mendeteksi aktivitas baru.</p>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $activities->links('components.admin.pagination') }}
            </x-slot>
        </x-admin.table>
    </x-admin.card>
</div>
