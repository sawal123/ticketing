<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Manajemen Event</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Daftar semua event yang terdaftar di platform.</p>
    </div>

    <!-- Filter & Search -->
    <x-admin.card padding="p-4" class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="w-full md:w-1/3">
                <x-admin.input wire:model.live.debounce.300ms="search" placeholder="Cari nama event..." icon="search" />
            </div>
            <div class="flex items-center gap-2">
                <x-admin.button variant="secondary" icon="filter" size="sm">
                    Filter
                </x-admin.button>
            </div>
        </div>
    </x-admin.card>

    <x-admin.table title="Daftar Event" :headers="['Gambar', 'Nama Event', 'Total Transaksi', 'Total Omset', 'Status', 'Aksi']" :count="$events->total()">
        @foreach($events as $event)
            <tr class="table-row-hover transition-colors">
                <td class="px-5 py-4 whitespace-nowrap">
                    <img src="{{ asset('storage/cover/' . $event->cover) }}" alt="{{ $event->event }}"
                        class="w-16 h-10 object-cover rounded-lg shadow-sm border border-slate-200 dark:border-slate-700"
                        onerror="this.src='https://placehold.co/400x250?text=No+Image'">
                </td>
                <td class="px-5 py-4">
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-800 dark:text-white">{{ $event->event }}</span>
                        <span
                            class="text-xs text-slate-500 dark:text-slate-400">{{ \Carbon\Carbon::parse($event->tanggal)->format('d M Y') }}</span>
                    </div>
                </td>
                <td class="px-5 py-4 font-semibold text-slate-700 dark:text-slate-300">
                    {{ number_format($event->total_transaksi) }} Transaksi
                </td>
                <td class="px-5 py-4">
                    <span class="text-lg font-extrabold text-indigo-600 dark:text-indigo-400">
                        Rp {{ number_format($event->total_omset, 0, ',', '.') }}
                    </span>
                </td>
                <td class="px-5 py-4">
                    @php
                        $statusClasses = [
                            'active' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-700',
                            'pending' => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-700',
                            'close' => 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-700',
                        ];
                        $statusClass = $statusClasses[strtolower($event->status)] ?? 'bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-600';
                    @endphp
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusClass }}">
                        {{ ucfirst($event->status) }}
                    </span>
                </td>
                <td class="px-5 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.event.detail', $event->uid) }}" wire:navigate title="Informasi Umum">
                            <x-admin.button variant="ghost" size="sm" icon="eye"
                                class="text-slate-600 dark:text-slate-400" />
                        </a>
                        <a href="{{ route('admin.event.detail', $event->uid) }}?activeTab=tiket" wire:navigate
                            title="Manajemen Tiket">
                            <x-admin.button variant="ghost" size="sm" icon="ticket"
                                class="text-emerald-600 dark:text-emerald-400" />
                        </a>
                        <a href="{{ route('admin.event.detail', $event->uid) }}?activeTab=transaksi" wire:navigate
                            title="Daftar Transaksi">
                            <x-admin.button variant="ghost" size="sm" icon="credit-card"
                                class="text-indigo-600 dark:text-indigo-400" />
                        </a>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot name="pagination">
            {{ $events->links('components.admin.pagination') }}
        </x-slot>
    </x-admin.table>
</div>