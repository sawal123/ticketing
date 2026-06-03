<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Penarikan Saldo</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Kelola penarikan dana dari penjualan tiket online
                Anda.</p>
        </div>
        <div class="flex items-center gap-3">
            <x-admin.button wire:click="openCreateModal" icon="plus" variant="primary">
                Tarik Saldo
            </x-admin.button>
        </div>
    </div>

    <!-- Alert Session -->
    @if (session()->has('success'))
        <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl relative mb-6 flex items-center gap-3"
            role="alert">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span class="block sm:inline text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-rose-100 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl relative mb-6 flex items-center gap-3"
            role="alert">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <span class="block sm:inline text-sm font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Stats Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-admin.card title="Total Saldo (Non-Cash)" icon="banknote" iconColor="indigo">
            <div class="text-2xl font-bold text-slate-800 dark:text-white">
                Rp {{ number_format($totalSaldo, 0, ',', '.') }}
            </div>
            <p class="text-[10px] text-slate-500 mt-1">Total pendapatan dari penjualan online (termasuk kutipan pajak
                anda)</p>
        </x-admin.card>
        <x-admin.card title="Penarikan PENDING" icon="clock" iconColor="amber">
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                Rp {{ number_format($pendingWithdrawal, 0, ',', '.') }}
            </div>
            <p class="text-[10px] text-slate-500 mt-1">Menunggu persetujuan admin</p>
        </x-admin.card>
        <x-admin.card title="Penarikan SUCCESS" icon="check-circle" iconColor="emerald">
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                Rp {{ number_format($successWithdrawal, 0, ',', '.') }}
            </div>
            <p class="text-[10px] text-slate-500 mt-1">Total dana yang sudah dicairkan</p>
        </x-admin.card>
    </div>

    <!-- Filter & Table Section -->
    <x-admin.card>
        <div class="flex flex-col md:flex-row justify-between gap-4 mb-6">
            <div class="flex-1 max-w-md">
                <x-admin.input wire:model.live.debounce.300ms="search" placeholder="Cari catatan atau nominal..."
                    icon="search" />
            </div>
            {{-- <div class="flex items-center gap-2">
                <x-admin.button variant="secondary" size="sm" icon="file-text">
                    Export PDF
                </x-admin.button>
                <x-admin.button variant="secondary" size="sm" icon="file-spreadsheet">
                    Export Excel
                </x-admin.button>
            </div> --}}
        </div>

        <x-admin.table :headers="['Tanggal Pengajuan', 'Tanggal Disetujui', 'Nominal', 'Catatan', 'Status', 'Aksi']">
            @forelse($penarikans as $item)
                @php
                    $statusNormalized = strtolower($item->status);
                @endphp
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                        {{ $item->created_at->format('d M Y, H:i') }}
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                        {{ $item->approved_at?->format('d M Y, H:i') ?? '-' }}
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-sm font-bold text-slate-800 dark:text-slate-200">
                        Rp {{ number_format($item->amount, 0, ',', '.') }}
                    </td>
                    <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-400 max-w-xs truncate">
                        {{ $item->note ?: '-' }}
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        @if($statusNormalized === 'pending')
                            <span
                                class="px-2.5 py-1 text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 rounded-full border border-amber-200 dark:border-amber-800 uppercase">PENDING</span>
                        @elseif($statusNormalized === 'success')
                            <span
                                class="px-2.5 py-1 text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 rounded-full border border-emerald-200 dark:border-emerald-800 uppercase">SUCCESS</span>
                        @else
                            <span
                                class="px-2.5 py-1 text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 rounded-full border border-rose-200 dark:border-rose-800 uppercase">{{ $item->status }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center gap-2">
                            @if($statusNormalized === 'pending')
                                <button wire:click="openEditModal({{ $item->id }})"
                                    class="p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-xl transition-colors"
                                    title="Edit">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
                                <button wire:click="confirmDelete({{ $item->id }})"
                                    class="p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors"
                                    title="Batal">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            @endif

                            @if($statusNormalized === 'success')
                                <a href="{{ url('/invoice/' . $item->uid) }}" target="_blank"
                                    class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-colors flex items-center gap-1"
                                    title="Invoice">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                    <span class="text-[10px] font-bold">INVOICE</span>
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col items-center gap-2">
                            <i data-lucide="search-x" class="w-10 h-10 text-slate-300 dark:text-slate-600"></i>
                            <p>Belum ada riwayat penarikan.</p>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $penarikans->links() }}
            </x-slot>
        </x-admin.table>
    </x-admin.card>

    <!-- Create/Edit Modal -->
    <x-admin.modal name="penarikan-modal" title="{{ $isEditMode ? 'Edit Penarikan' : 'Ajukan Penarikan' }}"
        icon="wallet" maxWidth="md">
        <form wire:submit.prevent="save" class="space-y-4">
            <div
                class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-800 mb-4">
                <div class="text-xs text-indigo-600 dark:text-indigo-400 font-bold uppercase mb-1">Saldo Tersedia</div>
                <div class="text-xl font-bold text-slate-800 dark:text-white">
                    Rp
                    {{ number_format($totalSaldo - $successWithdrawal - $pendingWithdrawal + ($isEditMode ? $amount : 0), 0, ',', '.') }}
                </div>
            </div>

            <x-admin.input label="Nominal Penarikan" type="number" wire:model="amount" placeholder="Min. Rp 10.000"
                error="{{ $errors->first('amount') }}" />

            <x-admin.input label="Catatan (Opsional)" wire:model="note" placeholder="Contoh: Penarikan profit event X"
                error="{{ $errors->first('note') }}" />

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

    <!-- Delete Modal -->
    <x-admin.modal name="delete-modal" title="Batalkan Penarikan" icon="trash-2">
        <div class="text-center p-4">
            <p class="text-slate-600 dark:text-slate-400 mb-6">Apakah Anda yakin ingin membatalkan permintaan penarikan
                ini?</p>
            <div class="flex justify-center gap-3">
                <x-admin.button variant="secondary" x-on:click="show = false">
                    Batal
                </x-admin.button>
                <x-admin.button variant="danger" wire:click="delete">
                    Ya, Batalkan
                </x-admin.button>
            </div>
        </div>
    </x-admin.modal>
</div>
