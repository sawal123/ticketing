<div
    x-data="{
        trx: null,
        formatRp(val) {
            return 'Rp ' + (Number(val) || 0).toLocaleString('id-ID');
        },
        openDetail(detail) {
            if (!detail) return;
            if (typeof detail === 'string') {
                const bytes = Uint8Array.from(atob(detail), c => c.charCodeAt(0));
                this.trx = JSON.parse(new TextDecoder().decode(bytes));
            } else {
                this.trx = detail;
            }
            $dispatch('open-modal', { name: 'transaction-detail-modal' });
        },
    }"
    x-on:open-dashboard-trx-detail.window="openDetail($event.detail)"
>
    <x-admin.modal name="transaction-detail-modal" title="Detail Transaksi" icon="file-text" :viewportScroll="true">
        <!-- Instant Alpine.js Detail View (0ms, no server roundtrip) -->
        <div x-show="trx !== null" style="display: none;" class="space-y-6">
            <div class="flex justify-between items-start border-b border-slate-100 dark:border-slate-700 pb-4">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Invoice</p>
                    <p class="text-lg font-mono font-bold text-slate-800 dark:text-white" x-text="trx?.invoice || '-'"></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status</p>
                    <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100"
                        x-text="trx?.status || 'SUCCESS'"></span>
                </div>
            </div>

            <div class="flex items-center gap-4 bg-slate-50 dark:bg-slate-700/50 p-4 rounded-2xl">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold"
                    x-text="(trx?.buyerName || '?').substring(0, 1)"></div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pembeli</p>
                    <p class="text-sm font-bold text-slate-800 dark:text-white break-words" x-text="trx?.buyerName || '-'"></p>
                    <p class="text-xs text-slate-500 break-all" x-text="trx?.buyerEmail || '-'"></p>
                    <p x-show="trx?.operatorLabel" style="display: none;" class="mt-2 text-[10px] text-slate-400"
                        x-text="'Dibuat oleh: ' + (trx?.operatorLabel || '')"></p>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Item Tiket</h4>
                <div class="space-y-2">
                    <template x-for="(item, idx) in (trx?.items || [])" :key="idx">
                        <div class="flex justify-between items-center p-3 rounded-xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800">
                            <div>
                                <p class="text-sm font-bold text-slate-800 dark:text-white" x-text="item.name"></p>
                                <p class="text-xs text-slate-500" x-text="item.qty + ' x ' + formatRp(item.price)"></p>
                            </div>
                            <div class="text-right font-bold text-indigo-600" x-text="formatRp(item.total)"></div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-700 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Subtotal Tiket</span>
                    <span class="font-bold text-slate-800 dark:text-white" x-text="formatRp(trx?.subtotal)"></span>
                </div>
                <div x-show="(trx?.discount || 0) > 0" style="display: none;" class="flex justify-between text-sm">
                    <span class="text-slate-500 flex items-center gap-1">
                        Diskon
                        <span x-show="trx?.voucherCode" style="display: none;"
                            class="text-[10px] bg-emerald-50 text-emerald-600 px-1.5 py-0.5 rounded uppercase font-bold"
                            x-text="trx?.voucherCode"></span>
                    </span>
                    <span class="font-bold text-emerald-600" x-text="'-' + formatRp(trx?.discount)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Internet Fee</span>
                    <span class="font-bold text-slate-800 dark:text-white" x-text="formatRp(trx?.internetFee)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">
                        Pajak / Fee
                        <span x-show="(trx?.pajakPersen || 0) > 0" style="display: none;"
                            x-text="'(' + trx?.pajakPersen + '%)'"></span>
                    </span>
                    <span class="font-bold text-rose-600" x-text="formatRp(trx?.pajak)"></span>
                </div>
                <div class="flex justify-between text-sm border-t border-slate-100 dark:border-slate-700 pt-2 mt-2">
                    <span class="text-slate-500">Metode Pembayaran</span>
                    <span class="font-bold text-slate-800 dark:text-white uppercase" x-text="trx?.paymentType || 'N/A'"></span>
                </div>
                <div class="flex justify-between text-lg font-extrabold pt-2">
                    <span class="text-slate-800 dark:text-white">Total Bayar</span>
                    <span class="text-indigo-600" x-text="formatRp(trx?.totalBayar)"></span>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3">
                <x-admin.button type="button" x-on:click="close()" variant="secondary" icon="x-circle" class="w-full !py-3">
                    Tutup
                </x-admin.button>
                <a x-bind:href="trx?.invoiceUrl || '#'" target="_blank" class="block w-full">
                    <x-admin.button type="button" variant="primary" icon="download"
                        class="w-full !py-3 shadow-lg shadow-indigo-200">
                        Download
                    </x-admin.button>
                </a>
            </div>
        </div>

        <!-- Server-rendered fallback (when opened programmatically via Livewire showTransactionDetail) -->
        <div x-show="trx === null">
            @if ($selectedTransaction)
                @php
                    $isCash = strtolower((string) $selectedTransaction->payment_type) === 'cash';
                    $buyer = $isCash
                        ? $selectedTransaction->cashBuyer
                        : $selectedTransaction->users;
                    $buyerName = $isCash
                        ? ($buyer->name ?? 'Data Pembeli Tidak Ditemukan')
                        : ($selectedTransaction->buyerName() ?: ($buyer->name ?? 'Guest'));
                    $buyerEmail = $isCash
                        ? ($buyer->email ?? '-')
                        : ($selectedTransaction->buyerEmail() ?: ($buyer->email ?? 'N/A'));
                    $operator = $selectedTransaction->users;
                @endphp
                <div class="space-y-6">
                    <div class="flex justify-between items-start border-b border-slate-100 dark:border-slate-700 pb-4">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Invoice</p>
                            <p class="text-lg font-mono font-bold text-slate-800 dark:text-white">{{ $selectedTransaction->invoice }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status</p>
                            <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100">{{ $selectedTransaction->status }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 bg-slate-50 dark:bg-slate-700/50 p-4 rounded-2xl">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">{{ substr($buyerName, 0, 1) }}</div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pembeli</p>
                            <p class="text-sm font-bold text-slate-800 dark:text-white">{{ $buyerName }}</p>
                            <p class="text-xs text-slate-500">{{ $buyerEmail }}</p>
                            @if ($selectedTransaction->payment_type === 'cash' && $operator)
                                <p class="mt-2 text-[10px] text-slate-400">Dibuat oleh: {{ $operator->name }} ({{ $operator->email }})</p>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Item Tiket</h4>
                        <div class="space-y-2">
                            @foreach ($selectedTransaction->hargaCarts as $item)
                                <div class="flex justify-between items-center p-3 rounded-xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800">
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-white">{{ $item->masterHarga->kategori ?? 'Kategori Dihapus' }}</p>
                                        <p class="text-xs text-slate-500">{{ $item->quantity }} x Rp {{ number_format($item->harga_ticket, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="text-right font-bold text-indigo-600">Rp {{ number_format($item->quantity * $item->harga_ticket, 0, ',', '.') }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 dark:border-slate-700 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Subtotal Tiket</span>
                            <span class="font-bold text-slate-800 dark:text-white">Rp {{ number_format($selectedTransaction->hargaCarts->sum(fn($i) => $i->quantity * $i->harga_ticket), 0, ',', '.') }}</span>
                        </div>
                        @if ($discount > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500 flex items-center gap-1">Diskon @if ($voucherCode) <span class="text-[10px] bg-emerald-50 text-emerald-600 px-1.5 py-0.5 rounded uppercase font-bold">{{ $voucherCode }}</span> @endif</span>
                                <span class="font-bold text-emerald-600">-Rp {{ number_format($discount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Internet Fee</span>
                            <span class="font-bold text-slate-800 dark:text-white">Rp {{ number_format($selectedTransaction->internet_fee ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Pajak / Fee @if (isset($selectedTransaction->pajak_persen) && $selectedTransaction->pajak_persen > 0) ({{ $selectedTransaction->pajak_persen }}%) @endif</span>
                            <span class="font-bold text-rose-600">Rp {{ number_format($selectedTransaction->pajak ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm border-t border-slate-100 dark:border-slate-700 pt-2 mt-2">
                            <span class="text-slate-500">Metode Pembayaran</span>
                            <span class="font-bold text-slate-800 dark:text-white uppercase">{{ $selectedTransaction->payment_type }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-extrabold pt-2">
                            <span class="text-slate-800 dark:text-white">Total Bayar</span>
                            @php
                                $totalBayar = $selectedTransaction->hargaCarts->sum(fn($i) => $i->quantity * $i->harga_ticket)
                                    - $discount
                                    + ($selectedTransaction->pajak ?? 0)
                                    + ($selectedTransaction->internet_fee ?? 0);
                            @endphp
                            <span class="text-indigo-600">Rp {{ number_format($totalBayar, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-2 gap-3">
                        <x-admin.button type="button" x-on:click="close()" variant="secondary" icon="x-circle" class="w-full !py-3">Tutup</x-admin.button>
                        <a href="{{ url('/invoice/' . $selectedTransaction->uid) }}" target="_blank" class="block w-full">
                            <x-admin.button type="button" variant="primary" icon="download" class="w-full !py-3 shadow-lg shadow-indigo-200">Download</x-admin.button>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </x-admin.modal>
</div>
