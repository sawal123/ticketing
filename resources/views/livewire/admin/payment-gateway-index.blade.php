<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Payment Gateway</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola metode pembayaran dan biaya layanan.</p>
        </div>
        <x-admin.button variant="primary" icon="plus" wire:click="openCreateModal">
            Tambah Payment
        </x-admin.button>
    </div>

    <!-- Alert Message -->
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 rounded-xl text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-700 rounded-xl text-rose-700 dark:text-rose-400 flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            {{ session('error') }}
        </div>
    @endif

    <div class="space-y-4">
        <!-- Search & Filters -->
        <x-admin.card padding="p-4">
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <x-admin.input 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Cari nama payment atau kategori..." 
                        icon="search" 
                    />
                </div>
            </div>
        </x-admin.card>

        <!-- Table -->
        <x-admin.table title="Daftar Payment Gateway" :headers="['Icon', 'Payment', 'Kategori', 'Biaya', 'Status', 'Aksi']" :count="$gateways->total()">
            @forelse($gateways as $gateway)
                <tr class="table-row-hover transition-colors">
                    <td class="px-5 py-4">
                        <div class="w-12 h-8 rounded bg-slate-50 dark:bg-slate-700 overflow-hidden flex items-center justify-center border border-slate-100 dark:border-slate-600">
                            @if($gateway->icon)
                                <img src="{{ asset('storage/' . $gateway->icon) }}" alt="{{ $gateway->payment }}" class="max-w-full max-h-full object-contain">
                            @else
                                <i data-lucide="image" class="w-4 h-4 text-slate-300"></i>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-4 font-bold text-slate-800 dark:text-white">
                        {{ $gateway->payment }}
                    </td>
                    <td class="px-5 py-4">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-600">
                            {{ strtoupper($gateway->category) }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <span class="font-semibold text-indigo-600 dark:text-indigo-400">
                            @if($gateway->biaya_type === 'rupiah')
                                Rp {{ number_format($gateway->biaya, 0, ',', '.') }}
                            @else
                                {{ $gateway->biaya }}%
                            @endif
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <!-- Toggle Status -->
                        <button 
                            wire:click="toggleStatus({{ $gateway->id }})"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none {{ $gateway->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"
                        >
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $gateway->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <x-admin.button 
                                wire:click="edit({{ $gateway->id }})" 
                                variant="ghost" 
                                size="sm" 
                                icon="pencil"
                                class="text-indigo-600"
                                title="Edit"
                            />
                            <x-admin.button 
                                wire:click="confirmDelete({{ $gateway->id }})" 
                                variant="ghost" 
                                size="sm" 
                                icon="trash-2"
                                class="text-rose-600"
                                title="Hapus"
                            />
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                        <div class="flex flex-col items-center justify-center">
                            <i data-lucide="info" class="w-12 h-12 mb-2 opacity-20"></i>
                            <p>Belum ada payment gateway yang ditambahkan.</p>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $gateways->links('components.admin.pagination') }}
            </x-slot>
        </x-admin.table>
    </div>

    <!-- Create/Edit Modal -->
    <x-admin.modal name="payment-gateway-modal" title="{{ $editingId ? 'Edit Payment Gateway' : 'Tambah Payment Gateway' }}" icon="{{ $editingId ? 'pencil' : 'plus' }}">
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-full">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Payment</label>
                    <x-admin.input wire:model="payment" placeholder="Contoh: Midtrans, QRIS, Bank Transfer..." required />
                    @error('payment') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Kategori</label>
                    <select wire:model="category" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                        <option value="">Pilih Kategori</option>
                        <option value="ewallet">E-Wallet</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="va">Virtual Account</option>
                        <option value="qris">QRIS</option>
                        <option value="cash">Tunai (Offline)</option>
                    </select>
                    @error('category') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                    <select wire:model="is_active" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Biaya Layanan</label>
                    <x-admin.input type="number" step="any" wire:model="biaya" required />
                    @error('biaya') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Tipe Biaya</label>
                    <select wire:model="biaya_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                        <option value="rupiah">Rupiah (Rp)</option>
                        <option value="persen">Persentase (%)</option>
                    </select>
                    @error('biaya_type') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                <div class="col-span-full">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Icon Payment</label>
                    <div class="mt-2 flex items-center gap-4">
                        <div class="w-20 h-12 rounded bg-slate-100 dark:bg-slate-800 overflow-hidden flex items-center justify-center border border-dashed border-slate-300 dark:border-slate-600">
                            @if($icon)
                                <img src="{{ $icon->temporaryUrl() }}" class="max-w-full max-h-full object-contain">
                            @elseif($currentIcon)
                                <img src="{{ asset('storage/' . $currentIcon) }}" class="max-w-full max-h-full object-contain">
                            @else
                                <i data-lucide="image" class="w-6 h-6 text-slate-300"></i>
                            @endif
                        </div>
                        <div class="flex-1">
                            <input type="file" wire:model="icon" class="hidden" id="icon-upload" accept="image/*">
                            <label for="icon-upload" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer transition-all">
                                <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                                Pilih Gambar
                            </label>
                            <p class="text-[10px] text-slate-500 mt-1">PNG, JPG, SVG max 2MB</p>
                        </div>
                    </div>
                    @error('icon') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <x-admin.button type="button" x-on:click="show = false" variant="ghost">Batal</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="save">Simpan Payment</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Delete Confirmation Modal -->
    <x-admin.modal-delete 
        name="delete-gateway-modal" 
        title="Hapus Payment Gateway?" 
        message="Metode pembayaran ini akan dihapus permanen. Pastikan tidak ada transaksi aktif yang bergantung pada metode ini."
    >
        <x-admin.button 
            wire:click="delete" 
            variant="primary" 
            class="w-full !bg-rose-600 hover:!bg-rose-700 !py-3 shadow-lg shadow-rose-200"
            icon="trash-2"
        >
            Ya, Hapus Sekarang
        </x-admin.button>
    </x-admin.modal-delete>

    <!-- Cannot Delete Warning Modal -->
    <x-admin.modal name="cannot-delete-gateway-modal" title="Tidak Dapat Dihapus" icon="alert-triangle">
        <div class="text-center py-4">
            <div class="w-16 h-16 bg-amber-50 dark:bg-amber-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="alert-circle" class="w-8 h-8 text-amber-600 dark:text-amber-400"></i>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-6">
                Payment Gateway ini **tidak dapat dihapus** karena sudah pernah digunakan dalam transaksi. Menghapus data ini akan menyebabkan error pada riwayat pesanan pembeli.
            </p>
            <p class="text-xs text-slate-400 bg-slate-50 dark:bg-slate-700/50 p-3 rounded-xl border border-slate-100 dark:border-slate-700">
                Saran: Jika Anda tidak ingin menggunakan metode ini lagi, silakan ubah status menjadi **Nonaktif** melalui tombol toggle di tabel.
            </p>
            <div class="mt-8">
                <x-admin.button x-on:click="show = false" variant="secondary" class="w-full">
                    Mengerti, Tutup
                </x-admin.button>
            </div>
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

