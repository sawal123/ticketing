<div>
    <!-- Flatpickr Integration -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li><a href="{{ route('admin.event') }}" wire:navigate class="hover:text-indigo-600">Event</a></li>
                    <li><i data-lucide="chevron-right" class="w-3 h-3"></i></li>
                    <li class="text-slate-800 dark:text-white font-medium">Detail</li>
                </ol>
            </nav>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $event->event }}</h2>
        </div>
        <x-admin.button variant="secondary" icon="arrow-left" onclick="history.back()">
            Kembali
        </x-admin.button>
    </div>
    {{-- {{ url('/a') }} --}}
    <!-- Alert Message -->
    @if (session()->has('message'))
        <div
            class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 rounded-xl text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div
            class="mb-4 p-4 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-700 rounded-xl text-rose-700 dark:text-rose-400 flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Tab Navigation -->
    <div
        class="flex items-center gap-1 bg-white dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700 w-fit mb-6">
        <button wire:click="setTab('umum')"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'umum' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
            <i data-lucide="info" class="w-4 h-4"></i>
            Informasi & Talent
        </button>
        <button wire:click="setTab('tiket')"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'tiket' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
            <i data-lucide="ticket" class="w-4 h-4"></i>
            Manajemen Tiket
        </button>
        <button wire:click="setTab('transaksi')"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'transaksi' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
            <i data-lucide="shopping-cart" class="w-4 h-4"></i>
            Transaksi
        </button>
    </div>

    <div class="space-y-6">
        @if($activeTab === 'umum')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <x-admin.card title="Informasi Event">
                        <div class="space-y-4">
                            <div class="aspect-video rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700">
                                <img src="{{ asset('storage/cover/' . $event->cover) }}" alt="{{ $event->event }}"
                                    class="w-full h-full object-cover"
                                    onerror="this.src='https://placehold.co/800x450?text=No+Cover'">
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2">Deskripsi</h3>
                                <div
                                    class="text-slate-600 dark:text-slate-400 leading-relaxed prose dark:prose-invert max-w-none">
                                    @if($showFullDescription)
                                        {!! $event->deskripsi !!}
                                        <button wire:click="toggleDescription"
                                            class="text-indigo-600 hover:text-indigo-700 font-semibold text-sm mt-2 flex items-center gap-1">
                                            Sembunyikan <i data-lucide="chevron-up" class="w-4 h-4"></i>
                                        </button>
                                    @else
                                        {!! Str::limit(strip_tags($event->deskripsi), 300) !!}
                                        @if(strlen(strip_tags($event->deskripsi)) > 300)
                                            <button wire:click="toggleDescription"
                                                class="text-indigo-600 hover:text-indigo-700 font-semibold text-sm mt-2 flex items-center gap-1">
                                                Baca Selengkapnya <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </x-admin.card>

                    <x-admin.card title="Talent / Pengisi Acara">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            @forelse($event->talents as $talent)
                                <div class="flex flex-col items-center text-center group">
                                    <div
                                        class="w-20 h-20 rounded-full overflow-hidden mb-2 ring-2 ring-slate-100 dark:ring-slate-700 group-hover:ring-indigo-500 transition-all">
                                        <img src="{{ asset('storage/talent/' . $talent->gambar) }}" alt="{{ $talent->talent }}"
                                            class="w-full h-full object-cover"
                                            onerror="this.src='https://placehold.co/150x150?text=User'">
                                    </div>
                                    <span
                                        class="text-sm font-semibold text-slate-800 dark:text-white">{{ $talent->talent }}</span>
                                </div>
                            @empty
                                <p class="col-span-full text-center py-8 text-slate-400">Belum ada talent yang ditambahkan.</p>
                            @endforelse
                        </div>
                    </x-admin.card>
                </div>

                <div class="space-y-6">
                    <x-admin.card title="Detail Ringkas">
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="calendar" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Tanggal Pelaksanaan</p>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-white">
                                        {{ \Carbon\Carbon::parse($event->tanggal)->format('d F Y') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Lokasi</p>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ $event->alamat }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="info" class="w-4 h-4 text-amber-600 dark:text-amber-400"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Status</p>
                                    <span
                                        class="text-sm font-semibold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                        {{ ucfirst($event->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </x-admin.card>
                </div>
            </div>

        @elseif($activeTab === 'tiket')
            <x-admin.table title="Kategori Tiket & Harga" :headers="['Kategori', 'Stok', 'Terjual', 'Harga', 'Status', 'Aksi']" :count="$event->hargas->count()">
                @forelse($event->hargas as $harga)
                    <tr class="table-row-hover transition-colors">
                        <td class="px-5 py-4 font-bold text-slate-800 dark:text-white">
                            {{ $harga->kategori }}
                        </td>
                        <td class="px-5 py-4 text-slate-600 dark:text-slate-400">
                            {{ number_format($harga->qty) }}
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-col">
                                <span class="font-bold text-emerald-600">{{ number_format($harga->sold_count) }}</span>
                                <div class="w-20 h-1.5 bg-slate-100 rounded-full mt-1 overflow-hidden">
                                    <div class="h-full bg-emerald-500"
                                        style="width: {{ min(100, ($harga->sold_count / max(1, $harga->qty)) * 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span class="text-lg font-extrabold text-indigo-600 dark:text-indigo-400">
                                Rp {{ number_format($harga->harga, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <!-- Toggle Status -->
                            <button wire:click="toggleTicketStatus({{ $harga->id }})"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none {{ $harga->status === 'active' ? 'bg-emerald-500' : 'bg-slate-300' }}">
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $harga->status === 'active' ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <x-admin.button wire:click="editTicket({{ $harga->id }})" variant="ghost" size="sm"
                                    icon="pencil" class="text-indigo-600" title="Edit Tiket" />
                                <x-admin.button wire:click="confirmDeleteTicket({{ $harga->id }})" variant="ghost" size="sm"
                                    icon="trash-2" class="text-rose-600" title="Hapus Tiket" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-400">Belum ada kategori tiket.</td>
                    </tr>
                @endforelse
            </x-admin.table>

        @elseif($activeTab === 'transaksi')
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
                <div
                    class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                        <i data-lucide="ticket" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tiket Terjual</p>
                        <p class="text-2xl font-extrabold text-slate-800 dark:text-white">
                            {{ number_format($metrics['total_tickets']) }}</p>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                        <i data-lucide="banknote" class="w-6 h-6 text-emerald-600 dark:text-emerald-400"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Tiket Rp</p>
                        <p class="text-2xl font-extrabold text-slate-800 dark:text-white">Rp
                            {{ number_format($metrics['total_revenue'], 0, ',', '.') }}</p>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                        <i data-lucide="shopping-cart" class="w-6 h-6 text-amber-600 dark:text-amber-400"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Transaksi</p>
                        <p class="text-2xl font-extrabold text-slate-800 dark:text-white">
                            {{ number_format($metrics['total_transactions']) }}</p>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center">
                        <i data-lucide="pie-chart" class="w-6 h-6 text-rose-600 dark:text-rose-400"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pajak</p>
                        <p class="text-2xl font-extrabold text-slate-800 dark:text-white">Rp
                            {{ number_format($metrics['total_pajak'], 0, ',', '.') }}</p>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-sky-50 dark:bg-sky-900/30 flex items-center justify-center">
                        <i data-lucide="wifi" class="w-6 h-6 text-sky-600 dark:text-sky-400"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Int. Fee</p>
                        <p class="text-2xl font-extrabold text-slate-800 dark:text-white">Rp
                            {{ number_format($metrics['total_internet_fee'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Filters UI -->
                <x-admin.card padding="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                        <div>
                            <label
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Metode
                                Bayar</label>
                            <select wire:model.live="filterPayment"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                                <option value="all">Semua Metode</option>
                                <option value="cash">Tunai (Cash)</option>
                                <option value="non-cash">Non-Tunai</option>
                            </select>
                        </div>
                        <div wire:ignore>
                            <label
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Rentang
                                Tanggal</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                                </div>
                                <input type="text" x-data x-init="flatpickr($el, {
                                            mode: 'range',
                                            dateFormat: 'Y-m-d',
                                            onChange: function(selectedDates, dateStr) {
                                                $wire.set('filterRange', dateStr);
                                            }
                                        })" placeholder="Pilih rentang tanggal..."
                                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Cari
                                    Transaksi</label>
                                <x-admin.input wire:model.live.debounce.300ms="searchTransaction"
                                    placeholder="Invoice / Nama..." icon="search" />
                            </div>
                            <x-admin.button variant="ghost" icon="rotate-ccw" wire:click="resetFilters" class="mb-0.5"
                                title="Reset Filter"></x-admin.button>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.table title="Daftar Transaksi Terfilter" :headers="['User', 'Invoice', 'Payment', 'Tanggal', 'Aksi']" :count="$transactions->total()">
                    @forelse($transactions as $trx)
                        <tr class="table-row-hover transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-xs font-bold text-indigo-600">
                                        {{ substr($trx->users->name ?? '?', 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span
                                            class="font-medium text-slate-800 dark:text-white">{{ $trx->users->name ?? 'Guest' }}</span>
                                        <span class="text-xs text-slate-500">{{ $trx->users->email ?? '' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="text-xs font-mono font-bold bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-slate-700 dark:text-slate-300">
                                    {{ $trx->invoice }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-700">
                                    {{ strtoupper($trx->payment_type ?? 'N/A') }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-400">
                                {{ $trx->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <x-admin.button
                                        x-on:click="$dispatch('open-modal', {name: 'transaction-detail-modal'}); $wire.showTransactionDetail('{{ $trx->uid }}')"
                                        variant="ghost" size="sm" icon="eye" class="text-indigo-600" title="Lihat Detail" />
                                    <x-admin.button wire:click="confirmResendEmail('{{ $trx->uid }}')"
                                        variant="ghost" size="sm" icon="mail" class="text-emerald-600"
                                        title="Kirim Ulang Email" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i data-lucide="search-x" class="w-12 h-12 mb-2 opacity-20"></i>
                                    <p>Tidak ada transaksi yang sesuai dengan filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    <x-slot name="pagination">
                        {{ $transactions->links('components.admin.pagination') }}
                    </x-slot>
                </x-admin.table>
            </div>
        @endif
    </div>

    <!-- Edit Ticket Modal -->
    <x-admin.modal name="edit-ticket-modal" title="Edit Kategori Tiket" icon="pencil">
        <form wire:submit="updateTicket" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nama
                    Kategori</label>
                <x-admin.input wire:model="editingHarga.kategori" placeholder="Contoh: VIP, Reguler..." required />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Stok
                        (Qty)</label>
                    <x-admin.input type="number" wire:model="editingHarga.qty" required />
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Harga
                        (Rp)</label>
                    <x-admin.input type="number" wire:model="editingHarga.harga" required />
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                <select wire:model="editingHarga.status"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <x-admin.button type="button" x-on:click="show = false" variant="ghost">Batal</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="save">Simpan Perubahan</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Delete Confirmation Modal -->
    <x-admin.modal-delete name="delete-ticket-modal" title="Hapus Kategori Tiket?"
        message="Data tiket akan dihapus permanen dari sistem. Pastikan Anda sudah memeriksa laporan penjualan sebelum melanjutkan.">
        <x-admin.button wire:click="deleteTicket" variant="primary"
            class="w-full !bg-rose-600 hover:!bg-rose-700 !py-3 shadow-lg shadow-rose-200" icon="trash-2">
            Ya, Hapus Sekarang
        </x-admin.button>
    </x-admin.modal-delete>

    <!-- Forbidden Delete Modal -->
    <x-admin.modal name="forbidden-delete-modal" title="Tidak Dapat Dihapus" icon="alert-triangle">
        <div class="text-center py-4">
            <div
                class="w-16 h-16 bg-amber-50 dark:bg-amber-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="alert-circle" class="w-8 h-8 text-amber-600 dark:text-amber-400"></i>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-6">
                Kategori tiket ini tidak dapat dihapus karena sudah memiliki riwayat **transaksi yang berhasil**.
                Menghapus data ini akan merusak validitas laporan keuangan Anda.
            </p>
            <p
                class="text-xs text-slate-400 bg-slate-50 dark:bg-slate-700/50 p-3 rounded-xl border border-slate-100 dark:border-slate-700">
                Saran: Jika tiket sudah tidak ingin dijual, silakan ubah status menjadi **Nonaktif** melalui tombol
                toggle di tabel.
            </p>
            <div class="mt-8">
                <x-admin.button x-on:click="show = false" variant="secondary" class="w-full">
                    Mengerti, Tutup
                </x-admin.button>
            </div>
        </div>
    </x-admin.modal>

    <!-- Transaction Detail Modal -->
    <x-admin.modal name="transaction-detail-modal" title="Detail Transaksi" icon="file-text">
        <div wire:loading wire:target="showTransactionDetail" class="w-full py-12">
            <div class="flex flex-col items-center justify-center space-y-4">
                <div class="w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-sm text-slate-500 font-medium animate-pulse">Mengambil data transaksi...</p>
            </div>
        </div>

        <div wire:loading.remove wire:target="showTransactionDetail">
            @if($selectedTransaction)
                <div class="space-y-6">
                    <!-- Transaction Info -->
                    <div class="flex justify-between items-start border-b border-slate-100 dark:border-slate-700 pb-4">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Invoice</p>
                            <p class="text-lg font-mono font-bold text-slate-800 dark:text-white">
                                {{ $selectedTransaction->invoice }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status</p>
                            <span
                                class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100">
                                {{ $selectedTransaction->status }}
                            </span>
                        </div>
                    </div>

                    <!-- User Info -->
                    <div class="flex items-center gap-4 bg-slate-50 dark:bg-slate-700/50 p-4 rounded-2xl">
                        <div
                            class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                            {{ substr($selectedTransaction->users->name ?? '?', 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-white">
                                {{ $selectedTransaction->users->name ?? 'Guest' }}</p>
                            <p class="text-xs text-slate-500">{{ $selectedTransaction->users->email ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Tickets Purchased -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Item Tiket</h4>
                        <div class="space-y-2">
                            @foreach($selectedTransaction->hargaCarts as $item)
                                <div
                                    class="flex justify-between items-center p-3 rounded-xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800">
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-white">
                                            {{ $item->masterHarga->kategori ?? 'Kategori Dihapus' }}</p>
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
                                {{ number_format($selectedTransaction->hargaCarts->sum(fn($i) => $i->quantity * $i->harga_ticket), 0, ',', '.') }}</span>
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
                                {{ number_format($selectedTransaction->internet_fee ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">
                                Pajak / Fee
                                @if(isset($selectedTransaction->pajak_persen) && $selectedTransaction->pajak_persen > 0)
                                    ({{ $selectedTransaction->pajak_persen }}%)
                                @endif
                            </span>
                            <span class="font-bold text-rose-600">Rp
                                {{ number_format($selectedTransaction->pajak ?? 0, 0, ',', '.') }}</span>
                        </div>

                        <div class="flex justify-between text-sm border-t border-slate-100 dark:border-slate-700 pt-2 mt-2">
                            <span class="text-slate-500">Metode Pembayaran</span>
                            <span
                                class="font-bold text-slate-800 dark:text-white uppercase">{{ $selectedTransaction->payment_type }}</span>
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
                        <x-admin.button x-on:click="show = false" variant="secondary" icon="x-circle" class="w-full !py-3">
                            Tutup
                        </x-admin.button>
                        <a href="{{ url('/invoice/' . $selectedTransaction->uid) }}" target="_blank" class="block w-full">
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

    <!-- Resend Email Confirmation Modal -->
    <x-admin.modal name="resend-email-modal" title="Kirim Ulang Barcode?" icon="mail">
        <div class="text-center py-4">
            <div class="w-16 h-16 bg-emerald-50 dark:bg-emerald-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="mail" class="w-8 h-8 text-emerald-600 dark:text-emerald-400"></i>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-6">
                Apakah Anda yakin ingin mengirim ulang barcode tiket ke email pembeli? Pastikan koneksi internet stabil agar proses pengiriman lancar.
            </p>
            <div class="mt-8 flex gap-3">
                <x-admin.button x-on:click="show = false" variant="secondary" class="w-full">
                    Batal
                </x-admin.button>
                <x-admin.button 
                    wire:click="resendEmail" 
                    variant="primary" 
                    class="w-full !bg-emerald-600 hover:!bg-emerald-700 shadow-lg shadow-emerald-200"
                    icon="send"
                >
                    Ya, Kirim Sekarang
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

        // Fallback for first load and navigation
        document.addEventListener('livewire:navigated', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</div>