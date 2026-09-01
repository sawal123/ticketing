@props([
    'context',
    'label' => 'Panduan',
])

@php
    $contexts = [
        'dashboard' => [
            'title' => 'Panduan Dashboard',
            'items' => [
                'Melihat ringkasan event, transaksi, tiket terjual, dan total omset.',
                'Membuka akses cepat ke detail event serta transaksi online atau cash.',
                'Memantau informasi operasional utama dari event yang sedang berjalan.',
            ],
        ],
        'event-index' => [
            'title' => 'Panduan Manajemen Event',
            'items' => [
                'Membuat event baru dan membuka detail event yang sudah terdaftar.',
                'Melihat status persetujuan, MOU, dan transaksi dari setiap event.',
                'Mengubah data event atau menghapus event yang masih menunggu persetujuan.',
                'Mengaktifkan atau menutup event yang sudah disetujui.',
            ],
        ],
        'event-form' => [
            'title' => 'Panduan Pengaturan Event',
            'items' => [
                'Melengkapi informasi event, penyelenggara, jadwal, dan lokasi event.',
                'Menyiapkan rekening pencairan serta dokumen pendukung yang diminta sistem.',
                'Mengisi pajak, cover, dan deskripsi sebelum event diajukan atau diperbarui.',
            ],
        ],
        'event-detail' => [
            'title' => 'Panduan Detail Event',
            'items' => [
                'Meninjau informasi utama event, talent, jadwal, lokasi, dan status event.',
                'Membuka halaman edit jika ada data event yang perlu diperbarui.',
                'Memantau kesiapan event sebelum lanjut ke tiket, MOU, atau transaksi.',
            ],
        ],
        'event-ticket' => [
            'title' => 'Panduan Manajemen Tiket',
            'items' => [
                'Membuat kategori tiket sesuai kebutuhan event.',
                'Menentukan harga dan kuota setiap kategori tiket.',
                'Mengatur status aktif atau nonaktif tiket.',
                'Memantau jumlah tiket yang sudah terjual pada tiap kategori.',
            ],
        ],
        'event-mou' => [
            'title' => 'Panduan MOU',
            'items' => [
                'Melihat status dokumen MOU atau addendum yang terkait dengan event ini.',
                'Mengunduh file unsigned atau signed jika tombolnya tersedia.',
                'Mengunggah dokumen bertanda tangan saat alurnya sudah meminta upload.',
                'Memantau proses review atau verifikasi admin sesuai status yang tampil.',
            ],
        ],
        'event-transaction' => [
            'title' => 'Panduan Transaksi Event',
            'items' => [
                'Melihat transaksi event yang berhasil sesuai filter yang sedang aktif.',
                'Menyaring transaksi berdasarkan metode bayar, tanggal, dan pencarian invoice atau pembeli.',
                'Membuka detail transaksi untuk melihat status pembayaran dan rincian tiket.',
                'Mengunduh laporan Excel atau PDF sesuai filter transaksi yang dipilih.',
            ],
        ],
        'withdrawal' => [
            'title' => 'Panduan Penarikan',
            'items' => [
                'Melihat saldo online dan ringkasan status penarikan.',
                'Mengajukan penarikan baru sesuai alur yang tersedia pada halaman ini.',
                'Mengubah atau membatalkan penarikan yang masih berstatus pending.',
                'Membuka invoice dan bukti transfer jika dokumennya sudah tersedia.',
            ],
        ],
    ];

    $help = $contexts[$context] ?? null;
    $articleAnchors = ['dashboard' => 'memulai-dashboard', 'event-index' => 'memulai-event', 'event-form' => 'memulai-event', 'event-detail' => 'operasional-event', 'event-ticket' => 'penjualan-tiket', 'event-mou' => 'dokumen-mou', 'event-transaction' => 'operasional-transaksi', 'withdrawal' => 'keuangan-penarikan'];
    $modalName = 'contextual-help-' . $context;
@endphp

@if ($help)
    <div class="flex items-center">
        <button
            type="button"
            x-on:click="$dispatch('open-modal', { name: '{{ $modalName }}' })"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/90 px-3 py-2 text-xs font-bold text-slate-600 shadow-sm transition hover:border-indigo-300 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800/90 dark:text-slate-300 dark:hover:border-indigo-500 dark:hover:text-indigo-300"
            aria-label="Buka {{ strtolower($help['title']) }}"
            aria-haspopup="dialog"
            title="Buka {{ strtolower($help['title']) }}">
            <span aria-hidden="true"
                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-[11px] font-black text-slate-600 dark:bg-slate-700 dark:text-slate-200">?</span>
            <span>{{ $label }}</span>
        </button>
    </div>

    <x-admin.modal :name="$modalName" :title="$help['title']" icon="info" maxWidth="lg">
        <div class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
                Di halaman ini Anda dapat:
            </div>

            <ul class="space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                @foreach ($help['items'] as $item)
                    <li class="flex items-start gap-3">
                        <span aria-hidden="true" class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-indigo-500"></span>
                        <span>{{ $item }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="flex justify-end pt-2">
                @if (isset($articleAnchors[$context]) && in_array(auth()->user()?->role, ['penyewa', 'staff'], true))
                    <a href="{{ route('dashboard.help') . '#' . $articleAnchors[$context] }}" class="mr-auto text-sm font-semibold text-indigo-600 hover:text-indigo-700">Lihat panduan lengkap</a>
                @endif
                <x-admin.button type="button" variant="secondary" x-on:click="show = false" title="Tutup panduan">
                    Tutup
                </x-admin.button>
            </div>
        </div>
    </x-admin.modal>
@endif
