<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Daftar Transaksi</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola dan pantau semua transaksi tiket masuk.</p>
    </div>

    <!-- Filters -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-2 bg-white dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700 w-fit">
            <button wire:click="$set('filter', 'all')" 
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $filter === 'all' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                Semua
            </button>
            <button wire:click="$set('filter', 'non-cash')" 
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $filter === 'non-cash' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                Online (Non-Cash)
            </button>
            <button wire:click="$set('filter', 'cash')" 
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $filter === 'cash' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                Tunai (Cash)
            </button>
        </div>

        <div class="w-full md:w-1/3">
            <x-admin.input 
                wire:model.live.debounce.300ms="search" 
                placeholder="Cari invoice atau nama user..." 
                icon="search" 
            />
        </div>
    </div>

    <x-admin.table 
        title="Data Transaksi" 
        :headers="['User', 'Event', 'Invoice', 'Payment', 'Tanggal', 'Aksi']"
        :count="$transactions->total()"
    >
        @foreach($transactions as $trx)
            <tr class="table-row-hover transition-colors">
                <td class="px-5 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600">
                            {{ substr($trx->users->name ?? '?', 0, 1) }}
                        </div>
                        <div class="flex flex-col">
                            <span class="font-medium text-slate-800 dark:text-white">{{ $trx->users->name ?? 'Guest' }}</span>
                            <span class="text-xs text-slate-500">{{ $trx->users->email ?? '' }}</span>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                    {{ Str::limit($trx->event->event ?? 'N/A', 20) }}
                </td>
                <td class="px-5 py-4 whitespace-nowrap">
                    <span class="text-xs font-mono font-bold bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-slate-700 dark:text-slate-300">
                        {{ $trx->invoice }}
                    </span>
                </td>
                <td class="px-5 py-4 whitespace-nowrap">
                    @php
                        $isCash = strtolower($trx->payment_type) === 'cash';
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $isCash ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-700' : 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-700' }}">
                        <i data-lucide="{{ $isCash ? 'banknote' : 'credit-card' }}" class="w-3 h-3"></i>
                        {{ strtoupper($trx->payment_type ?? 'N/A') }}
                    </span>
                </td>
                <td class="px-5 py-4 text-slate-600 dark:text-slate-400 whitespace-nowrap text-sm">
                    {{ $trx->created_at->format('d M Y, H:i') }}
                </td>
                <td class="px-5 py-4 text-center">
                    <x-admin.button variant="ghost" size="sm" icon="external-link">
                        Detail
                    </x-admin.button>
                </td>
            </tr>
        @endforeach

        <x-slot name="pagination">
            {{ $transactions->links('components.admin.pagination') }}
        </x-slot>
    </x-admin.table>
</div>
