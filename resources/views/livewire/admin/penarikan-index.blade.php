<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Manajemen Penarikan</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Daftar permintaan penarikan saldo oleh talent atau partner.</p>
    </div>

    <!-- Filters & Search -->
    <x-admin.card padding="p-4" class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-700 p-1 rounded-lg">
                    <button wire:click="$set('statusFilter', 'all')" 
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $statusFilter === 'all' ? 'bg-white dark:bg-slate-600 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Semua
                    </button>
                    <button wire:click="$set('statusFilter', 'pending')" 
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $statusFilter === 'pending' ? 'bg-white dark:bg-slate-600 shadow-sm text-amber-600' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Pending
                    </button>
                    <button wire:click="$set('statusFilter', 'success')" 
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $statusFilter === 'success' ? 'bg-white dark:bg-slate-600 shadow-sm text-emerald-600' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Success
                    </button>
                </div>
            </div>
            <div class="w-full md:w-1/3">
                <x-admin.input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari nama user atau catatan..." 
                    icon="search" 
                />
            </div>
        </div>
    </x-admin.card>

    <!-- Alert Message -->
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 rounded-xl text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            {{ session('message') }}
        </div>
    @endif

    <x-admin.table title="Permintaan Penarikan" :headers="['User', 'Jumlah', 'Catatan', 'Status', 'Tanggal', 'Aksi']" :count="$penarikans->total()">
        @forelse($penarikans as $item)
            <tr class="table-row-hover transition-colors">
                <td class="px-5 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-xs font-bold text-indigo-600 dark:text-indigo-400">
                            {{ substr($item->user->name ?? '?', 0, 1) }}
                        </div>
                        <div class="flex flex-col">
                            <span class="font-medium text-slate-800 dark:text-white">{{ $item->user->name ?? 'N/A' }}</span>
                            <span class="text-xs text-slate-500">{{ $item->user->email ?? '' }}</span>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4 whitespace-nowrap font-bold text-slate-800 dark:text-white">
                    Rp {{ number_format($item->amount, 0, ',', '.') }}
                </td>
                <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-400 max-w-xs truncate">
                    {{ $item->note ?? '-' }}
                </td>
                <td class="px-5 py-4 whitespace-nowrap">
                    @php
                        $statusNormalized = strtolower($item->status);
                        $statusClasses = [
                            'pending' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-700',
                            'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-700',
                            'failed' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-700',
                        ];
                        $statusClass = $statusClasses[$statusNormalized] ?? 'bg-slate-50 text-slate-600 border-slate-200';
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusClass }}">
                        {{ ucfirst($item->status) }}
                    </span>
                </td>
                <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                    {{ $item->created_at->format('d M Y, H:i') }}
                </td>
                <td class="px-5 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        @if($statusNormalized === 'pending')
                            <x-admin.button 
                                wire:click="approve('{{ $item->uid }}')" 
                                wire:confirm="Yakin ingin menyetujui penarikan ini?"
                                variant="secondary" 
                                size="sm" 
                                icon="check-circle"
                                class="text-emerald-600 dark:text-emerald-400"
                            >
                                Setujui
                            </x-admin.button>
                        @endif

                        @if($statusNormalized === 'success')
                            <a href="{{ url('/invoice/' . $item->uid) }}" target="_blank" class="no-underline">
                                <x-admin.button 
                                    variant="ghost" 
                                    size="sm" 
                                    icon="file-text"
                                    class="text-indigo-600 dark:text-indigo-400"
                                >
                                    Invoice
                                </x-admin.button>
                            </a>
                        @endif
                        
                        <x-admin.button variant="ghost" size="sm" icon="eye" title="Detail">
                        </x-admin.button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-5 py-12 text-center">
                    <div class="flex flex-col items-center justify-center text-slate-400">
                        <i data-lucide="inbox" class="w-12 h-12 mb-2 opacity-20"></i>
                        <p>Tidak ada data penarikan ditemukan.</p>
                    </div>
                </td>
            </tr>
        @endforelse

        <x-slot name="pagination">
            {{ $penarikans->links('components.admin.pagination') }}
        </x-slot>
    </x-admin.table>
</div>
