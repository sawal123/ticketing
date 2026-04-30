<div>
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Daftar Transaksi</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola dan pantau semua transaksi tiket masuk.</p>
        </div>
        <x-admin.button wire:click="resetFilters" variant="ghost" icon="rotate-ccw">
            Reset Filter
        </x-admin.button>
    </div>

    <!-- Filters Section -->
    <x-admin.card padding="p-4" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="md:col-span-1">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Cari Transaksi</label>
                <x-admin.input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Invoice, Nama, Email..." 
                    icon="search" 
                />
            </div>

            <!-- Status -->
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status Pembayaran</label>
                <select wire:model.live="status" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                    <option value="all">Semua Status</option>
                    <option value="SUCCESS">Success</option>
                    <option value="PENDING">Pending</option>
                    <option value="UNPAID">Unpaid</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
            </div>

            <!-- Date -->
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal Transaksi</label>
                <x-admin.input type="date" wire:model.live="date" />
            </div>

            <!-- Payment Type -->
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Metode Bayar</label>
                <select wire:model.live="paymentType" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                    <option value="all">Semua Metode</option>
                    <option value="cash">Tunai (Cash)</option>
                    <option value="non-cash">Online (Non-Cash)</option>
                </select>
            </div>
        </div>
    </x-admin.card>

    <x-admin.table 
        title="Data Transaksi" 
        :headers="['User', 'Event', 'Invoice', 'Status', 'Payment', 'Tanggal', 'Aksi']"
        :count="$transactions->total()"
    >
        @foreach($transactions as $trx)
            <tr class="table-row-hover transition-colors">
                <td class="px-5 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-500">
                            {{ substr($trx->users->name ?? 'G', 0, 1) }}
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-800 dark:text-white">{{ $trx->users->name ?? 'Guest/Cash' }}</span>
                            <span class="text-[10px] text-slate-500">{{ $trx->users->email ?? '-' }}</span>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4 text-slate-600 dark:text-slate-400 whitespace-nowrap text-sm">
                    {{ Str::limit($trx->event->event ?? 'N/A', 25) }}
                </td>
                <td class="px-5 py-4 whitespace-nowrap">
                    <span class="text-xs font-mono font-bold bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300">
                        {{ $trx->invoice }}
                    </span>
                </td>
                <td class="px-5 py-4 whitespace-nowrap">
                    @php
                        $statusStyles = match($trx->status) {
                            'SUCCESS' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',
                            'PENDING' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
                            'UNPAID' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800',
                            'CANCELLED' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800/50 dark:text-slate-500 dark:border-slate-700',
                            default => 'bg-slate-50 text-slate-600 border-slate-200',
                        };
                    @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider border {{ $statusStyles }}">
                        {{ $trx->status }}
                    </span>
                </td>
                <td class="px-5 py-4 whitespace-nowrap">
                    @php
                        $isCash = strtolower($trx->payment_type) === 'cash';
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[10px] font-bold border {{ $isCash ? 'bg-slate-50 text-amber-600 border-amber-100' : 'bg-slate-50 text-indigo-600 border-indigo-100' }}">
                        <i data-lucide="{{ $isCash ? 'banknote' : 'credit-card' }}" class="w-3 h-3"></i>
                        {{ strtoupper($trx->payment_type ?? 'N/A') }}
                    </span>
                </td>
                <td class="px-5 py-4 text-slate-600 dark:text-slate-400 whitespace-nowrap text-xs font-medium">
                    {{ $trx->created_at->format('d M Y, H:i') }}
                </td>
                <td class="px-5 py-4 text-center">
                    <x-admin.button wire:click="openDetail({{ $trx->id }})" variant="ghost" size="sm" icon="eye" class="text-indigo-600">
                        Detail
                    </x-admin.button>
                </td>
            </tr>
        @endforeach

        <x-slot name="pagination">
            {{ $transactions->links('components.admin.pagination') }}
        </x-slot>
    </x-admin.table>

    <x-admin.modal name="trx-detail-modal" title="Detail Transaksi" icon="file-text">
        <div wire:loading wire:target="openDetail" class="w-full py-12">
            <div class="flex flex-col items-center justify-center space-y-4">
                <div class="w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-sm text-slate-500 font-medium animate-pulse">Mengambil data transaksi...</p>
            </div>
        </div>

        <div wire:loading.remove wire:target="openDetail">
            @if($selectedTrx)
                <div class="space-y-6 max-h-[75vh] overflow-y-auto px-1 custom-scrollbar">
                    <!-- Transaction Info -->
                    <div class="flex justify-between items-start border-b border-slate-100 dark:border-slate-700 pb-4">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Invoice</p>
                            <p class="text-lg font-mono font-bold text-slate-800 dark:text-white">
                                {{ $selectedTrx->invoice }}</p>
                            <p class="text-[10px] text-slate-500 mt-1">{{ $selectedTrx->created_at->format('d F Y, H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status</p>
                            @php
                                $statusStyles = match($selectedTrx->status) {
                                    'SUCCESS' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'PENDING' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    'UNPAID' => 'bg-rose-50 text-rose-700 border-rose-100',
                                    default => 'bg-slate-50 text-slate-700 border-slate-100',
                                };
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $statusStyles }}">
                                {{ $selectedTrx->status }}
                            </span>
                        </div>
                    </div>

                    <!-- User Info -->
                    <div class="flex items-center gap-4 bg-slate-50 dark:bg-slate-700/50 p-4 rounded-2xl">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                            {{ substr($selectedTrx->users->name ?? '?', 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-white">
                                {{ $selectedTrx->users->name ?? 'Guest/Cash' }}</p>
                            <p class="text-xs text-slate-500">{{ $selectedTrx->users->email ?? 'N/A' }} @if($selectedTrx->users->nomor) | {{ $selectedTrx->users->nomor }} @endif</p>
                        </div>
                    </div>

                    <!-- Event Info -->
                    <div class="p-3 bg-indigo-50/50 dark:bg-indigo-900/10 rounded-xl border border-indigo-100/50 flex items-center gap-3">
                        <i data-lucide="calendar" class="w-4 h-4 text-indigo-600"></i>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Event Terkait</span>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $selectedTrx->event->event ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <!-- Tickets Purchased -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Item Tiket</h4>
                        <div class="space-y-2">
                            @foreach($selectedTrx->hargaCarts as $item)
                                <div class="flex justify-between items-center p-3 rounded-xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800">
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-white">
                                            {{ $item->kategori_harga }}</p>
                                        <p class="text-xs text-slate-500">{{ $item->quantity }} x Rp
                                            {{ number_format($item->harga_ticket, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="text-right font-bold text-indigo-600">
                                        Rp {{ number_format($item->quantity * $item->harga_ticket, 0, ',', '.') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-700 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Subtotal Tiket</span>
                            <span class="font-bold text-slate-800 dark:text-white">Rp
                                {{ number_format($selectedTrx->hargaCarts->sum(fn($i) => $i->quantity * $i->harga_ticket), 0, ',', '.') }}</span>
                        </div>
                        @if($discount > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500 flex items-center gap-1">Diskon @if($voucherCode) <span
                                    class="text-[10px] bg-emerald-50 text-emerald-600 px-1.5 py-0.5 rounded uppercase font-bold">{{ $voucherCode }}</span>
                                @endif</span>
                                <span class="font-bold text-emerald-600">-Rp {{ number_format($discount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Internet Fee</span>
                            <span class="font-bold text-slate-800 dark:text-white">Rp
                                {{ number_format($selectedTrx->internet_fee ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">
                                Pajak / Fee
                                @if(isset($selectedTrx->pajak_persen) && $selectedTrx->pajak_persen > 0)
                                    ({{ $selectedTrx->pajak_persen }}%)
                                @endif
                            </span>
                            <span class="font-bold text-rose-600">Rp
                                {{ number_format($selectedTrx->pajak ?? 0, 0, ',', '.') }}</span>
                        </div>

                        <div class="flex justify-between text-sm border-t border-slate-100 dark:border-slate-700 pt-2 mt-2">
                            <span class="text-slate-500 font-medium">Metode Pembayaran</span>
                            <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-white uppercase">
                                <i data-lucide="{{ strtolower($selectedTrx->payment_type) === 'cash' ? 'banknote' : 'credit-card' }}" class="w-4 h-4 text-slate-400"></i>
                                {{ $selectedTrx->payment_type }}
                            </div>
                        </div>
                        <div class="flex justify-between text-lg font-extrabold pt-2">
                            <span class="text-slate-800 dark:text-white">Total Bayar</span>
                            @php
                                $totalBayar = $selectedTrx->hargaCarts->sum(fn($i) => $i->quantity * $i->harga_ticket)
                                    - $discount
                                    + ($selectedTrx->pajak ?? 0)
                                    + ($selectedTrx->internet_fee ?? 0);
                            @endphp
                            <span class="text-indigo-600">Rp {{ number_format($totalBayar, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-2 gap-3">
                        <x-admin.button x-on:click="show = false" variant="secondary" icon="x-circle" class="w-full !py-3">
                            Tutup
                        </x-admin.button>
                        <a href="{{ url('/invoice/' . $selectedTrx->uid) }}" target="_blank" class="block w-full">
                            <x-admin.button variant="primary" icon="download"
                                class="w-full !py-3 shadow-lg shadow-indigo-200">
                                Download
                            </x-admin.button>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </x-admin.modal>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', (el, component) => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        });
    </script>
</div>


