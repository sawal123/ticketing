<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Manajemen Penarikan</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Daftar permintaan penarikan saldo oleh talent atau
            partner.</p>
    </div>

    <!-- Filters & Search -->
    <x-admin.card padding="p-4" class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-700 p-1 rounded-lg">
                    <button wire:click="setStatusFilter('all')"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $statusFilter === 'all' ? 'bg-white dark:bg-slate-600 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Semua
                    </button>
                    <button wire:click="setStatusFilter('pending')"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $statusFilter === 'pending' ? 'bg-white dark:bg-slate-600 shadow-sm text-amber-600' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Pending
                    </button>
                    <button wire:click="setStatusFilter('processing')"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $statusFilter === 'processing' ? 'bg-white dark:bg-slate-600 shadow-sm text-sky-600' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Processing
                    </button>
                    <button wire:click="setStatusFilter('success')"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $statusFilter === 'success' ? 'bg-white dark:bg-slate-600 shadow-sm text-emerald-600' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Success
                    </button>
                </div>
            </div>
            <div class="w-full md:w-1/3">
                <x-admin.input wire:model.live.debounce.300ms="search" placeholder="Cari nama user atau catatan..."
                    icon="search" />
            </div>
        </div>
    </x-admin.card>

    <!-- Alert Message -->
    @if (session()->has('message'))
        <div
            class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 rounded-xl text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            {{ session('message') }}
        </div>
    @endif

    <x-admin.table title="Permintaan Penarikan" :headers="['User', 'Jumlah', 'Catatan', 'Status', 'Tanggal Pengajuan', 'Tanggal Disetujui', 'Aksi']" :count="$penarikans->total()">
        @forelse($penarikans as $item)
            <tr class="table-row-hover transition-colors">
                <td class="px-5 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-xs font-bold text-indigo-600 dark:text-indigo-400">
                            {{ substr($item->user->name ?? '?', 0, 1) }}
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="font-medium text-slate-800 dark:text-white">{{ $item->user->name ?? 'N/A' }}</span>
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
                            'pending' =>
                                'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-700',
                            'processing' =>
                                'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/30 dark:text-sky-400 dark:border-sky-700',
                            'success' =>
                                'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-700',
                            'failed' =>
                                'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-700',
                        ];
                        $statusClass =
                            $statusClasses[$statusNormalized] ?? 'bg-slate-50 text-slate-600 border-slate-200';
                    @endphp
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusClass }}">
                        {{ ucfirst($item->status) }}
                    </span>
                </td>
                <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                    {{ $item->created_at->format('d M Y, H:i') }}
                </td>
                <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                    {{ $item->approved_at?->format('d M Y, H:i') ?? '-' }}
                </td>
                <td class="px-5 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        @if ($statusNormalized === 'pending')
                            <x-admin.button wire:click="process('{{ $item->uid }}')"
                                wire:confirm="Mulai proses permintaan penarikan ini?" variant="secondary" size="sm"
                                icon="play-circle" class="text-sky-600 dark:text-sky-400">
                                Proses
                            </x-admin.button>
                        @endif

                        @if ($statusNormalized === 'processing')
                            <x-admin.button wire:click="complete('{{ $item->uid }}')"
                                wire:confirm="Tandai permintaan penarikan ini selesai?" variant="secondary"
                                size="sm" icon="check-circle" class="text-emerald-600 dark:text-emerald-400">
                                Selesaikan
                            </x-admin.button>
                        @endif

                        @if ($statusNormalized === 'success')
                            <a href="{{ url('/invoice/' . $item->uid) }}" target="_blank" class="no-underline">
                                <x-admin.button variant="ghost" size="sm" icon="file-text"
                                    class="text-indigo-600 dark:text-indigo-400">
                                    Invoice
                                </x-admin.button>
                            </a>
                        @endif

                        <x-admin.button wire:click="openTransferProofModal('{{ $item->uid }}')" variant="ghost"
                            size="sm" icon="edit-3" title="Edit Bukti Transfer">
                        </x-admin.button>

                        <x-admin.button wire:click="openDetail('{{ $item->uid }}')" variant="ghost" size="sm"
                            icon="eye" title="Detail">
                        </x-admin.button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-5 py-12 text-center">
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

    <x-admin.modal name="penarikan-detail-modal" title="Detail Penarikan" icon="eye" maxWidth="lg">
        @if ($selectedPenarikan)
            @php
                $display = fn($value) => filled($value) ? $value : '-';
            @endphp
            <div class="space-y-6">
                <div>
                    <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Penyewa</h4>
                    <dl class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Nama</dt>
                            <dd class="mt-1 font-semibold text-slate-800 dark:text-white">
                                {{ $display($selectedPenarikan->user->name ?? null) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Email</dt>
                            <dd class="mt-1 font-semibold text-slate-800 dark:text-white">
                                {{ $display($selectedPenarikan->user->email ?? null) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Nomor HP</dt>
                            <dd class="mt-1 font-semibold text-slate-800 dark:text-white">
                                {{ $display($selectedPenarikan->user->nomor ?? null) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="border-t border-slate-200 pt-5 dark:border-slate-700">
                    <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Penarikan</h4>
                    <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">UID</dt>
                            <dd class="mt-1 font-mono text-sm font-semibold text-slate-800 dark:text-white">
                                {{ $display($selectedPenarikan->uid) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Jumlah</dt>
                            <dd class="mt-1 font-semibold text-slate-800 dark:text-white">Rp
                                {{ number_format((int) $selectedPenarikan->amount, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Saldo Saat Pengajuan / Kwitansi</dt>
                            <dd class="mt-1 font-semibold text-slate-800 dark:text-white">Rp
                                {{ number_format((int) $selectedPenarikan->kwitansi, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Status</dt>
                            <dd class="mt-1 font-semibold text-slate-800 dark:text-white">
                                {{ $display($selectedPenarikan->status) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Tanggal Pengajuan</dt>
                            <dd class="mt-1 font-semibold text-slate-800 dark:text-white">
                                {{ $selectedPenarikan->created_at?->format('d M Y, H:i') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Tanggal Disetujui</dt>
                            <dd class="mt-1 font-semibold text-slate-800 dark:text-white">
                                {{ $selectedPenarikan->approved_at?->format('d M Y, H:i') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Tanggal Diproses</dt>
                            <dd class="mt-1 font-semibold text-slate-800 dark:text-white">
                                {{ $selectedPenarikan->processing_at?->format('d M Y, H:i') ?? '-' }}</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-xs font-semibold text-slate-400">Catatan</dt>
                            <dd class="mt-1 font-semibold text-slate-800 dark:text-white">
                                {{ $display($selectedPenarikan->note) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="border-t border-slate-200 pt-5 dark:border-slate-700">
                    <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Rekening Tujuan</h4>
                    <dl class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Nama Bank</dt>
                            <dd class="mt-1 font-semibold text-slate-800 dark:text-white">
                                {{ $display($selectedBank['bank_name'] ?? null) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Nomor Rekening</dt>
                            <dd class="mt-1 font-semibold text-slate-800 dark:text-white">
                                {{ $display($selectedBank['bank_account_number'] ?? null) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Nama Pemilik</dt>
                            <dd class="mt-1 font-semibold text-slate-800 dark:text-white">
                                {{ $display($selectedBank['bank_account_name'] ?? null) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="border-t border-slate-200 pt-5 dark:border-slate-700">
                    <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Bukti Transfer</h4>
                    @if (filled($selectedPenarikan->transfer_proof))
                        <div class="space-y-4">
                            <div
                                class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40">
                                <img src="{{ route('penarikan.transfer-proof.show', $selectedPenarikan->uid) }}"
                                    alt="Bukti transfer penarikan {{ $selectedPenarikan->uid }}"
                                    class="max-h-96 w-full object-contain">
                            </div>
                            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-semibold text-slate-400">Tanggal Upload</dt>
                                    <dd class="mt-1 font-semibold text-slate-800 dark:text-white">
                                        {{ $selectedPenarikan->transfer_proof_uploaded_at?->format('d M Y, H:i') ?? '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-slate-400">Diunggah Oleh</dt>
                                    <dd class="mt-1 font-semibold text-slate-800 dark:text-white">
                                        {{ $display($selectedPenarikan->transferProofUploader?->name) }}
                                    </dd>
                                </div>
                            </dl>
                            <div>
                                <a href="{{ route('penarikan.transfer-proof.show', $selectedPenarikan->uid) }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                    <i data-lucide="external-link" class="h-4 w-4"></i>
                                    Lihat ukuran penuh
                                </a>
                            </div>
                        </div>
                    @else
                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Bukti transfer belum
                            tersedia.</p>
                    @endif
                </div>
            </div>
        @endif
    </x-admin.modal>

    <x-admin.modal name="transfer-proof-modal" title="Edit Bukti Transfer" icon="image" maxWidth="lg">
        @if ($editingTransferProofPenarikan)
            <form wire:submit.prevent="saveTransferProof" class="space-y-5" enctype="multipart/form-data">
                <div
                    class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Penyewa</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">
                                {{ $editingTransferProofPenarikan->user->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Status</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">
                                {{ $editingTransferProofPenarikan->status ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nominal</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">Rp
                                {{ number_format((int) $editingTransferProofPenarikan->amount, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                @if (filled($editingTransferProofPenarikan->transfer_proof))
                    <div class="space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Bukti Saat Ini</p>
                        <div
                            class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40">
                            <img src="{{ route('penarikan.transfer-proof.show', $editingTransferProofPenarikan->uid) }}"
                                alt="Bukti transfer penarikan {{ $editingTransferProofPenarikan->uid }}"
                                class="max-h-96 w-full object-contain">
                        </div>
                    </div>
                @endif

                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                        Upload Bukti Transfer
                    </label>
                    <input type="file" wire:model="transferProof"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-600 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:file:bg-indigo-900/30 dark:file:text-indigo-300">
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Format aman: JPG, JPEG, PNG, WEBP.
                        Maksimal 2 MB.</p>
                    @error('transferProof')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <x-admin.button type="button" variant="secondary" x-on:click="show = false">
                        Close
                    </x-admin.button>
                    <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveTransferProof,transferProof">
                            Simpan Bukti
                        </span>
                        <span wire:loading.flex wire:target="saveTransferProof,transferProof"
                            class="items-center gap-2">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                            Memproses...
                        </span>
                    </x-admin.button>
                </div>
            </form>
        @endif
    </x-admin.modal>
</div>
