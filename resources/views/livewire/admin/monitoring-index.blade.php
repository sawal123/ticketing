<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Monitoring Data</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Pantau perubahan data yang dilakukan user, penyewa, dan staff.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-admin.card title="Total Perubahan" icon="activity" iconColor="indigo">
            <div class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($totalChanges) }}</div>
            <p class="text-[10px] text-slate-500 mt-1">Aktivitas perubahan data tercatat</p>
        </x-admin.card>
        <x-admin.card title="Hari Ini" icon="calendar" iconColor="emerald">
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($todayChanges) }}</div>
            <p class="text-[10px] text-slate-500 mt-1">Perubahan data hari ini</p>
        </x-admin.card>
        <x-admin.card title="Berisiko Tinggi" icon="alert-triangle" iconColor="rose">
            <div class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ number_format($highRiskChanges) }}</div>
            <p class="text-[10px] text-slate-500 mt-1">Biasanya aktivitas hapus data</p>
        </x-admin.card>
    </div>

    <x-admin.card>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1 block">Cari</label>
                <x-admin.input wire:model.live.debounce.300ms="search" placeholder="Nama, email, aktivitas, IP..." icon="search" />
            </div>
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1 block">Role</label>
                <select wire:model.live="filterRole" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="all">Semua Role</option>
                    <option value="user">User</option>
                    <option value="penyewa">Penyewa</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1 block">Aksi</label>
                <select wire:model.live="filterAction" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="all">Semua Aksi</option>
                    <option value="create">Tambah/Simpan</option>
                    <option value="update">Ubah</option>
                    <option value="delete">Hapus</option>
                </select>
            </div>
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1 block">Dampak</label>
                <select wire:model.live="filterImpact" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="all">Semua Dampak</option>
                    <option value="Normal">Normal</option>
                    <option value="Sensitif">Sensitif</option>
                    <option value="Berisiko Tinggi">Berisiko Tinggi</option>
                </select>
            </div>
        </div>

        <x-admin.table :headers="['Waktu', 'Pelaku', 'Perubahan Data', 'Dampak', 'Lokasi']">
            @forelse($logs as $log)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $log->created_at->translatedFormat('d M Y') }}</span>
                            <span class="text-[10px] text-slate-400">{{ $log->created_at->format('H:i:s') }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-xs uppercase">
                                {{ substr($log->user->name ?? 'U', 0, 1) }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-800 dark:text-white">{{ $log->user->name ?? 'Unknown' }}</span>
                                <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">{{ $log->user->role ?? 'N/A' }} - {{ $log->user->email ?? '-' }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ $log->activity }}</span>
                            <p class="text-xs text-slate-600 dark:text-slate-400 max-w-xl" title="{{ $log->description }}">{{ $log->description }}</p>
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        @php
                            $impactClass = match($log->impact_level) {
                                'Berisiko Tinggi' => 'bg-rose-50 text-rose-700 border-rose-200',
                                'Sensitif' => 'bg-amber-50 text-amber-700 border-amber-200',
                                default => 'bg-slate-100 text-slate-600 border-slate-200',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $impactClass }}">
                            {{ $log->impact_level }}
                        </span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $log->location ?? 'Unknown' }}</span>
                            <span class="text-[10px] font-mono text-slate-400">{{ $log->ip_address }}</span>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-16 text-center text-slate-400">
                        <i data-lucide="shield-check" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                        <p class="text-sm font-bold">Belum ada monitoring perubahan data.</p>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $logs->links('components.admin.pagination') }}
            </x-slot>
        </x-admin.table>
    </x-admin.card>
</div>
