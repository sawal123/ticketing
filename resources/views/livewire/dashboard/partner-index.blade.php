<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Manajemen Partner</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Kelola link referral partner, potongan khusus pembeli, dan rekap komisi otomatis.</p>
        </div>
        <x-admin.button wire:click="openCreateModal" icon="plus" variant="primary">
            Tambah Partner
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

    <!-- Stats Section -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.card title="Total Partner" icon="users" iconColor="indigo">
            <div class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ $totalPartners }}
            </div>
        </x-admin.card>
        <x-admin.card title="Partner Aktif" icon="check-circle" iconColor="emerald">
            <div class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ $activePartners }}
            </div>
        </x-admin.card>
        <x-admin.card title="Kota Terjangkau" icon="map-pin" iconColor="amber">
            <div class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ $totalCities }}
            </div>
        </x-admin.card>
        <x-admin.card title="Total Komisi Partner" icon="wallet" iconColor="rose">
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                Rp {{ number_format($totalPartnerCommission, 0, ',', '.') }}
            </div>
        </x-admin.card>
    </div>

    <!-- Filter & Table Section -->
    <x-admin.card>
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <x-admin.input wire:model.live.debounce.300ms="search" placeholder="Cari nama, email, atau referensi..."
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

        <x-admin.table :headers="['Referensi & Link', 'Nama Partner', 'Event & Benefit', 'Penjualan & Komisi', 'Status', 'Aksi']">
            @forelse($partners as $partner)
                @php
                    $stats = $partnerStats[$partner->uid] ?? ['tx_count' => 0, 'ticket_qty' => 0, 'total_omzet' => 0, 'total_discount' => 0, 'total_commission' => 0];
                    $referralUrl = $partner->referralUrl();
                @endphp
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex flex-col gap-1.5" x-data="{ copied: false }">
                            <span
                                class="font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded text-xs inline-block w-fit">
                                {{ $partner->referensi }}
                            </span>
                            @if ($referralUrl && $partner->status === 'active')
                                <button type="button"
                                    x-on:click="navigator.clipboard.writeText(@js($referralUrl)); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline w-fit cursor-pointer">
                                    <i data-lucide="link" class="w-3 h-3"></i>
                                    <span x-text="copied ? 'Link Tersalin!' : 'Salin Link Referral'">Salin Link Referral</span>
                                </button>
                            @elseif (! $referralUrl)
                                <span class="text-[10px] text-slate-400">Pilih event untuk link langsung</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $partner->name }}</div>
                        <div class="text-xs text-slate-500">{{ $partner->email }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">{{ $partner->hp }} • {{ $partner->city }}</div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded inline-block mb-1.5">
                            {{ $partner->event->event ?? 'Global' }}
                        </div>
                        <div class="flex flex-wrap gap-1">
                            @if ($partner->hasActiveDiscount())
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                    Diskon {{ $partner->discount_type === 'persen' ? $partner->discount_value . '%' : 'Rp ' . number_format($partner->discount_value, 0, ',', '.') }}
                                </span>
                            @endif
                            @if ($partner->hasActiveCommission())
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    Komisi {{ $partner->commission_type === 'persen' ? $partner->commission_value . '%' : 'Rp ' . number_format($partner->commission_value, 0, ',', '.') . '/tiket' }}
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                            {{ number_format($stats['ticket_qty']) }} tiket ({{ number_format($stats['tx_count']) }} trx)
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Omzet: Rp {{ number_format($stats['total_omzet'], 0, ',', '.') }}
                        </div>
                        <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                            Komisi: Rp {{ number_format($stats['total_commission'], 0, ',', '.') }}
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <x-admin.toggle
                            wire:click="toggleStatus({{ $partner->id }})"
                            :active="$partner->status === 'active'"
                            :label="$partner->status === 'active' ? 'Aktif' : 'Nonaktif'"
                        />
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center gap-1.5">
                            <button wire:click="openTransactionsModal({{ $partner->id }})"
                                class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-colors"
                                title="Lihat Transaksi & Komisi">
                                <i data-lucide="receipt" class="w-4 h-4"></i>
                            </button>
                            <button wire:click="openEditModal({{ $partner->id }})"
                                class="p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-xl transition-colors"
                                title="Edit">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button wire:click="confirmDelete({{ $partner->id }})"
                                class="p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors"
                                title="Hapus">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col items-center gap-2">
                            <i data-lucide="search-x" class="w-10 h-10 text-slate-300 dark:text-slate-600"></i>
                            <p>Tidak ada partner ditemukan.</p>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $partners->links() }}
            </x-slot>
        </x-admin.table>
    </x-admin.card>

    <!-- Create/Edit Modal -->
    <x-admin.modal name="partner-modal" title="{{ $isEditMode ? 'Edit Partner' : 'Tambah Partner' }}" icon="users" maxWidth="xl">
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-admin.input label="Nama Partner" wire:model="name" placeholder="Masukan nama.."
                    error="{{ $errors->first('name') }}" />

                <x-admin.input label="Email" type="email" wire:model="email" placeholder="Masukan email.."
                    error="{{ $errors->first('email') }}" />
            </div>

            <div class="w-full">
                <label
                    class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
                    Event (Opsional — pilih event untuk link referral langsung)
                </label>
                <select wire:model="selected_event_uid"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200">
                    <option value="">Global / Semua Event</option>
                    @foreach($events as $event)
                        <option value="{{ $event->uid }}">{{ $event->event }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="w-full">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
                        Kota
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 border-r border-slate-200 pr-3 dark:border-slate-600">
                            <i data-lucide="building-2" class="w-4 h-4"></i>
                        </div>
                        <input
                            wire:model="city"
                            type="text"
                            placeholder="Choose City.."
                            class="w-full pl-12 pr-4 py-2.5 rounded-xl border {{ $errors->has('city') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-200 dark:border-slate-600' }} bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                        >
                    </div>
                    @error('city') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <x-admin.input label="Nomor HP" wire:model="hp" placeholder="Masukan nomor.."
                    error="{{ $errors->first('hp') }}" />
            </div>

            <x-admin.input label="Alamat" wire:model="alamat" placeholder="Masukan alamat.."
                error="{{ $errors->first('alamat') }}" />

            <!-- Potongan Khusus Pembeli (Referral) -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-800/60 p-4 space-y-3"
                x-data="{ discountType: @entangle('discount_type') }">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                        Potongan Khusus Link Partner (Opsional)
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Otomatis diterapkan untuk pembeli yang masuk melalui link referral partner ini.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1 uppercase tracking-wider">Jenis Potongan</label>
                        <select wire:model="discount_type" x-model="discountType"
                            class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm">
                            <option value="">Tanpa Potongan</option>
                            <option value="persen">Persen (%)</option>
                            <option value="rupiah">Rupiah (Rp)</option>
                        </select>
                    </div>
                    <div x-show="discountType !== ''" x-cloak>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1 uppercase tracking-wider"
                            x-text="discountType === 'persen' ? 'Potongan (%)' : 'Potongan (Rp)'">Nominal</label>
                        <input type="number" min="1" :max="discountType === 'persen' ? 99 : null" wire:model="discount_value"
                            class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm"
                            :placeholder="discountType === 'persen' ? '10' : '15000'">
                        @error('discount_value') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div x-show="discountType === 'persen'" x-cloak>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1 uppercase tracking-wider">Maks. Potongan (Rp)</label>
                        <input type="number" min="0" wire:model="discount_max"
                            class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm"
                            placeholder="Opsional (0 = tanpa batas)">
                    </div>
                </div>

                <label x-show="discountType !== ''" x-cloak class="flex items-center gap-2.5 cursor-pointer pt-1">
                    <input type="checkbox" wire:model="allow_promo_tickets"
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                        Potongan partner dapat digunakan untuk tiket harga promo
                    </span>
                </label>
            </div>

            <!-- Komisi Partner -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-emerald-50/40 dark:bg-emerald-900/10 p-4 space-y-3"
                x-data="{ commissionType: @entangle('commission_type') }">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                        Komisi Partner (Opsional)
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Dihitung otomatis untuk setiap transaksi sukses (lunas) melalui link partner ini.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1 uppercase tracking-wider">Jenis Komisi</label>
                        <select wire:model="commission_type" x-model="commissionType"
                            class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm">
                            <option value="">Tanpa Komisi</option>
                            <option value="rupiah">Rupiah per Tiket (Rp)</option>
                            <option value="persen">Persen dari Subtotal Bersih (%)</option>
                        </select>
                    </div>
                    <div x-show="commissionType !== ''" x-cloak>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1 uppercase tracking-wider"
                            x-text="commissionType === 'persen' ? 'Komisi (%)' : 'Komisi per Tiket (Rp)'">Nominal</label>
                        <input type="number" min="1" :max="commissionType === 'persen' ? 100 : null" wire:model="commission_value"
                            class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm"
                            :placeholder="commissionType === 'persen' ? '5' : '5000'">
                        @error('commission_value') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <x-admin.button type="button" variant="secondary" x-on:click="show = false">
                    Close
                </x-admin.button>
                <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        Simpan
                    </span>
                    <span wire:loading.flex class="items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        Memproses...
                    </span>
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Partner Transactions & Commission Modal -->
    <x-admin.modal name="partner-transactions-modal"
        title="Rekap Transaksi Partner: {{ $selectedPartnerName }}" icon="receipt" maxWidth="2xl">
        <div class="space-y-4">
            <div class="flex items-center justify-between text-xs bg-slate-50 dark:bg-slate-800/70 px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700">
                <span class="text-slate-500">Kode Referensi: <strong class="font-mono text-indigo-600 dark:text-indigo-400">{{ $selectedPartnerReferensi }}</strong></span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400">
                    Total Komisi: Rp {{ number_format(collect($partnerTransactions)->sum('partner_commission_amount'), 0, ',', '.') }}
                </span>
            </div>

            <div class="overflow-x-auto max-h-[60vh]">
                <table class="w-full text-xs text-left">
                    <thead class="text-[10px] uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="py-2 pr-3">Invoice</th>
                            <th class="py-2 pr-3">Pembeli</th>
                            <th class="py-2 pr-3 text-center">Tiket</th>
                            <th class="py-2 pr-3 text-right">Total</th>
                            <th class="py-2 text-right">Komisi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        @forelse ($partnerTransactions as $trx)
                            <tr>
                                <td class="py-2.5 pr-3">
                                    <div class="font-mono font-bold text-slate-700 dark:text-slate-200">{{ $trx['invoice'] }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $trx['paid_at'] }}</div>
                                </td>
                                <td class="py-2.5 pr-3">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $trx['buyer_name'] }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $trx['buyer_email'] }}</div>
                                </td>
                                <td class="py-2.5 pr-3 text-center font-bold">{{ $trx['qty'] }}</td>
                                <td class="py-2.5 pr-3 text-right">
                                    <div class="font-semibold">Rp {{ number_format($trx['gross_amount'], 0, ',', '.') }}</div>
                                    @if ($trx['partner_discount_amount'] > 0)
                                        <div class="text-[10px] text-amber-600">Diskon -Rp {{ number_format($trx['partner_discount_amount'], 0, ',', '.') }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                    Rp {{ number_format($trx['partner_commission_amount'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400">Belum ada transaksi sukses dari partner ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end pt-2">
                <x-admin.button type="button" variant="secondary" x-on:click="show = false">Tutup</x-admin.button>
            </div>
        </div>
    </x-admin.modal>

    <!-- Delete Confirmation Modal -->
    <x-admin.modal name="delete-modal" title="Hapus Partner" icon="trash-2">
        <div class="text-center p-4">
            <p class="text-slate-600 dark:text-slate-400 mb-6">Apakah Anda yakin ingin menghapus partner ini? Tindakan
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
</div>
