<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Manajemen Voucher</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Kelola kode promo dan diskon untuk event Anda.</p>
        </div>
        <x-admin.button wire:click="openCreateModal" icon="plus" variant="primary">
            Tambah Voucher
        </x-admin.button>
    </div>

    <!-- Alert Session -->
    @if (session()->has('success'))
        <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl relative mb-6 flex items-center gap-3"
            role="alert">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span class="block sm:inline text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-admin.card title="Total Voucher" icon="ticket" iconColor="indigo">
            <div class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ $totalVouchers }}
            </div>
        </x-admin.card>
        <x-admin.card title="Voucher Aktif" icon="check-circle" iconColor="emerald">
            <div class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ $activeVouchers }}
            </div>
        </x-admin.card>
        <x-admin.card title="Voucher Terpakai (Berhasil)" icon="shopping-cart" iconColor="amber">
            <div class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ $totalUsedVouchers }}
            </div>
        </x-admin.card>
    </div>

    <!-- Filter & Table Section -->
    <x-admin.card>
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <x-admin.input wire:model.live.debounce.300ms="search" placeholder="Cari kode voucher..."
                    icon="search" />
            </div>
            <div class="w-full md:w-64">
                <select wire:model.live="event_uid"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                    <option value="">Semua Event</option>
                    @foreach($events as $event)
                        <option value="{{ $event->uid }}">{{ $event->event }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <x-admin.table :headers="['Kode', 'Event', 'Diskon', 'Min. Beli', 'Limit', 'Terpakai', 'Status', 'Aksi']">
            @forelse($vouchers as $voucher)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span
                            class="font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded">
                            {{ $voucher->code }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="text-sm text-slate-800 dark:text-slate-200 max-w-xs truncate" title="{{ $voucher->event->event ?? 'Global' }}">
                            {{ $voucher->event_uid ? Str::limit($voucher->event->event ?? 'N/A', 10) : 'Global' }}
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200">
                            @if($voucher->unit == 'persen' || $voucher->unit == '%')
                                {{ $voucher->nominal }}%
                                <span class="text-xs text-slate-500">(Maks. Rp
                                    {{ number_format($voucher->max_disc, 0, ',', '.') }})</span>
                            @else
                                Rp {{ number_format($voucher->nominal, 0, ',', '.') }}
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                        Rp {{ number_format($voucher->min_beli, 0, ',', '.') }}
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                        {{ $voucher->limit }}
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                        {{ $voucher->success_count }}
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <x-admin.toggle 
                            wire:click="toggleStatus({{ $voucher->id }})"
                            :active="$voucher->status === 'active'"
                            :label="$voucher->status === 'active' ? 'Aktif' : 'Nonaktif'"
                        />
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center gap-2">
                            <button wire:click="viewTransactions('{{ $voucher->code }}')"
                                class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-colors"
                                title="Lihat Transaksi">
                                <i data-lucide="list" class="w-4 h-4"></i>
                            </button>
                            <button wire:click="openEditModal({{ $voucher->id }})"
                                class="p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-xl transition-colors"
                                title="Edit">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            @if($voucher->cart_voucher_count == 0)
                                <button wire:click="confirmDelete({{ $voucher->id }})"
                                    class="p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors"
                                    title="Hapus">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            @else
                                <button class="p-2 text-slate-300 cursor-not-allowed" title="Sudah pernah digunakan">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col items-center gap-2">
                            <i data-lucide="search-x" class="w-10 h-10 text-slate-300 dark:text-slate-600"></i>
                            <p>Tidak ada voucher ditemukan.</p>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $vouchers->links() }}
            </x-slot>
        </x-admin.table>
    </x-admin.card>

    <!-- Create/Edit Modal -->
    <x-admin.modal name="voucher-modal" title="{{ $isEditMode ? 'Edit Voucher' : 'Tambah Voucher' }}" icon="ticket">
        <form wire:submit.prevent="save" class="space-y-4">
            <x-admin.input label="Kode Voucher" wire:model="code" placeholder="CONTOH: PROMO2024"
                error="{{ $errors->first('code') }}" />

            <div class="w-full">
                <label
                    class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
                    Event
                </label>
                <select wire:model="selected_event_uid"
                    class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('selected_event_uid') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-200 dark:border-slate-600' }} bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200">
                    <option value="">Pilih Event</option>
                    @foreach($events as $event)
                        <option value="{{ $event->uid }}">{{ $event->event }}</option>
                    @endforeach
                </select>
                @error('selected_event_uid') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="w-full">
                    <label
                        class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
                        Unit
                    </label>
                    <select wire:model.live="unit"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200">
                        <option value="persen">Persen (%)</option>
                        <option value="rupiah">Rupiah (Rp)</option>
                    </select>
                </div>
                <x-admin.input label="Nominal Diskon" type="number" wire:model.live="nominal" min="0"
                    placeholder="{{ $unit == 'persen' ? 'Contoh: 10' : 'Contoh: 50000' }}"
                    error="{{ $errors->first('nominal') }}" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-admin.input label="Min. Pembelian" type="number" wire:model.live="min_beli" min="0" placeholder="0"
                    error="{{ $errors->first('min_beli') }}" />
                @if($unit == 'rupiah')
                    <x-admin.input label="Maks. Diskon (Rp)" type="number" wire:model.live="max_disc" min="0"
                        placeholder="0" readonly
                        class="bg-slate-100 opacity-70 cursor-not-allowed"
                        error="{{ $errors->first('max_disc') }}" />
                @else
                    <x-admin.input label="Maks. Diskon (Rp)" type="number" wire:model.live="max_disc" min="0"
                        placeholder="0"
                        error="{{ $errors->first('max_disc') }}" />
                @endif
            </div>

            <x-admin.input label="Limit Penggunaan" type="number" wire:model.live="limit" min="1" placeholder="100"
                error="{{ $errors->first('limit') }}" />

            <div class="flex justify-end gap-3 pt-4">
                <x-admin.button type="button" variant="secondary" x-on:click="show = false">
                    Batal
                </x-admin.button>
                <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        {{ $isEditMode ? 'Simpan Perubahan' : 'Buat Voucher' }}
                    </span>
                    <span wire:loading.flex class="items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        Memproses...
                    </span>
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Delete Confirmation Modal -->
    <x-admin.modal name="delete-modal" title="Hapus Voucher" icon="trash-2">
        <div class="text-center p-4">
            <p class="text-slate-600 dark:text-slate-400 mb-6">Apakah Anda yakin ingin menghapus voucher ini? Tindakan
                ini tidak dapat dibatalkan.</p>
            <div class="flex justify-center gap-3">
                <x-admin.button variant="secondary" x-on:click="show = false">
                    Batal
                </x-admin.button>
                <x-admin.button variant="danger" wire:click="delete">
                    Ya, Hapus
                </x-admin.button>
            </div>
        </div>
    </x-admin.modal>

    <!-- Transaction List Modal -->
    <x-admin.modal name="transaction-modal" title="Transaksi Voucher: {{ $selectedVoucherCode }}" icon="list"
        maxWidth="xl">
        <div class="overflow-y-auto max-h-[70vh] -mx-6 px-6">
            <div
                class="text-xs text-slate-500 mb-4 bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-xl border border-emerald-100 dark:border-emerald-900/30 flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                <span>Menampilkan transaksi yang telah <strong>Berhasil (SUCCESS)</strong>.</span>
            </div>

            <table class="w-full text-sm text-left border-separate border-spacing-y-2">
                <thead class="sticky top-0 bg-white dark:bg-slate-800 z-10">
                    <tr class="text-slate-400 text-[10px] uppercase tracking-wider">
                        <th class="px-4 py-2 font-semibold">Invoice</th>
                        <th class="px-4 py-2 font-semibold">Customer</th>
                        <th class="px-4 py-2 font-semibold">Status</th>
                        <th class="px-4 py-2 font-semibold text-right">Potongan</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody x-data="{ expandedTrx: null }">
                    @forelse($transactions as $trx)
                        <tr @click="expandedTrx = (expandedTrx === '{{ $trx->uid }}' ? null : '{{ $trx->uid }}')"
                            class="group cursor-pointer bg-slate-50/50 dark:bg-slate-700/30 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all duration-200">
                            <td class="px-4 py-3 rounded-l-xl font-medium text-slate-700 dark:text-slate-300">
                                {{ $trx->invoice }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $trx->users->name ?? 'User' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $trx->users->email ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 rounded-full border border-emerald-200 dark:border-emerald-800">
                                    SUCCESS
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-rose-600 font-bold">
                                - Rp
                                {{ number_format($trx->hargaCarts->where('voucher', $selectedVoucherCode)->sum('disc'), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 rounded-r-xl text-slate-400">
                                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300"
                                    :class="expandedTrx === '{{ $trx->uid }}' ? 'rotate-180' : ''"></i>
                            </td>
                        </tr>

                        <!-- Expanded Details -->
                        <tr x-show="expandedTrx === '{{ $trx->uid }}'" x-collapse x-cloak>
                            <td colspan="4" class="px-4 pb-4 pt-0">
                                <div
                                    class="bg-white dark:bg-slate-800/50 rounded-2xl p-4 border border-slate-100 dark:border-slate-700 shadow-sm space-y-3">
                                    <h4
                                        class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                        <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                                        Detail Pesanan
                                    </h4>
                                    <div class="space-y-2">
                                        @foreach($trx->hargaCarts as $item)
                                            <div
                                                class="flex items-center justify-between text-xs py-2 border-b border-slate-50 dark:border-slate-700/50 last:border-0">
                                                <div class="flex flex-col">
                                                    <span
                                                        class="font-semibold text-slate-700 dark:text-slate-300">{{ $item->kategori_harga }}</span>
                                                    <span class="text-[10px] text-slate-500">{{ $item->quantity }}x @ Rp
                                                        {{ number_format($item->harga_ticket, 0, ',', '.') }}</span>
                                                </div>
                                                <div class="text-right">
                                                    <div class="font-medium text-slate-700 dark:text-slate-300">
                                                        Rp
                                                        {{ number_format($item->quantity * $item->harga_ticket, 0, ',', '.') }}
                                                    </div>
                                                    @if($item->voucher == $selectedVoucherCode)
                                                        <div class="text-[10px] text-rose-500 font-medium">Diskon: -Rp
                                                            {{ number_format($item->disc, 0, ',', '.') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div
                                        class="pt-2 flex justify-between items-center border-t border-slate-100 dark:border-slate-700">
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Total
                                            Transaksi</span>
                                        <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                            Rp
                                            {{ number_format($trx->hargaCarts->sum(fn($i) => $i->quantity * $i->harga_ticket) - $trx->hargaCarts->sum('disc'), 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center gap-2 opacity-50">
                                    <i data-lucide="shopping-cart" class="w-10 h-10"></i>
                                    <p class="text-sm font-medium">Belum ada transaksi sukses.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6 flex justify-end">
            <x-admin.button variant="secondary" x-on:click="show = false">
                Tutup
            </x-admin.button>
        </div>
    </x-admin.modal>
</div>