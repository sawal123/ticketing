<?php

/**
 * Static seed data for the marketing guide content foundation.
 *
 * 100% mirror of resources/views/marketing-guide/index.blade.php — section
 * keys/slugs/positions/blocks correspond to the existing static view. The
 * rendering layer can later swap to this database-backed source without
 * changing the public page.
 *
 * Top-level key is a stable section key (unique per version). The `blocks`
 * array is keyed by sort position. `nav_group` reflects the original
 * sidebar grouping used in the static view.
 *
 * Re-running MarketingGuideContentSeeder is idempotent (updateOrCreate).
 */

return [
    'pengenalan' => [
        'title' => 'Pengenalan',
        'slug' => 'pengenalan',
        'nav_group' => 'MENGENAL GOTIK',
        'position' => 1,
        'blocks' => [
            1 => [
                'type' => 'text',
                'data' => [
                    'badge' => 'Panduan Privat',
                    'title' => 'Cara Kerja Gotik',
                    'subtitle' => 'Kelola event, penjualan tiket, pembayaran hingga proses check-in dalam satu platform.',
                    'show_recipient' => true,
                    'show_expiry' => true,
                    'cta' => [
                        'label' => 'Mulai Sekarang',
                        'href' => '#menjadi-penyelenggara',
                        'icon' => 'arrow-right',
                    ],
                ],
            ],
        ],
    ],
    'cara_kerja' => [
        'title' => 'Cara Kerja Gotik',
        'slug' => 'cara-kerja',
        'nav_group' => 'MENGENAL GOTIK',
        'position' => 2,
        'blocks' => [
            1 => [
                'type' => 'text',
                'data' => [
                    'intro' => 'Berikut adalah workflow lengkap dari awal event didaftarkan hingga dana dicairkan ke rekening Anda.',
                ],
            ],
            2 => [
                'type' => 'workflow',
                'data' => [
                    'steps' => [
                        ['icon' => 'edit', 'title' => 'Daftarkan Event', 'description' => 'Mulai dengan mendaftarkan event baru di platform Gotik dan lengkapi informasi dasar.'],
                        ['icon' => 'handshake', 'title' => 'Kesepakatan & Aktivasi', 'description' => 'Tim Gotik akan melakukan verifikasi dan aktivasi akun event Anda.'],
                        ['icon' => 'ticket-alt', 'title' => 'Setup Tiket & Pembayaran', 'description' => 'Atur kategori tiket, harga, dan metode pembayaran yang ingin diaktifkan.'],
                        ['icon' => 'rocket', 'title' => 'Mulai Penjualan', 'description' => 'Bagikan link event dan mulai menerima pembelian tiket dari pembeli.'],
                        ['icon' => 'chart-line', 'title' => 'Pantau Transaksi', 'description' => 'Gunakan dashboard untuk memantau penjualan dan transaksi secara real-time.'],
                        ['icon' => 'qrcode', 'title' => 'Scan Tiket Saat Event', 'description' => 'Gunakan fitur scanner untuk memverifikasi tiket dan check-in pengunjung.'],
                        ['icon' => 'wallet', 'title' => 'Penarikan Dana', 'description' => 'Ajukan penarikan dana ke rekening bank Anda setelah event selesai.'],
                    ],
                ],
            ],
        ],
    ],
    'menjadi_penyelenggara' => [
        'title' => 'Menjadi Penyelenggara',
        'slug' => 'menjadi-penyelenggara',
        'nav_group' => 'MEMULAI EVENT',
        'position' => 3,
        'blocks' => [
            1 => [
                'type' => 'text',
                'data' => [
                    'intro' => 'Proses sederhana untuk mulai mengelola event Anda bersama Gotik.',
                ],
            ],
            2 => [
                'type' => 'flow',
                'data' => [
                    'boxes' => [
                        ['label' => 'Hubungi Gotik', 'icon' => 'phone'],
                        ['label' => 'Diskusi Event', 'icon' => 'comment'],
                        ['label' => 'Kesepakatan', 'icon' => 'check'],
                        ['label' => 'Akun Penyewa', 'icon' => 'user'],
                        ['label' => 'Aktivasi Event', 'icon' => 'rocket'],
                        ['label' => 'Mulai Penjualan', 'icon' => 'money-bill'],
                    ],
                ],
            ],
            3 => [
                'type' => 'cards',
                'data' => [
                    'columns' => 3,
                    'cards' => [
                        ['icon' => 'phone', 'title' => 'Hubungi Tim', 'body' => 'Hubungi tim Gotik untuk mendiskusikan kebutuhan dan detail event Anda.'],
                        ['icon' => 'file-contract', 'title' => 'Verifikasi', 'body' => 'Tim kami akan melakukan verifikasi data dan kesepakatan syarat & ketentuan.'],
                        ['icon' => 'lock-open', 'title' => 'Aktivasi', 'body' => 'Akun Anda aktif dan siap untuk mulai mengatur detail event dan tiket.'],
                    ],
                ],
            ],
        ],
    ],
    'setup_event' => [
        'title' => 'Setup Event',
        'slug' => 'setup-event',
        'nav_group' => 'MEMULAI EVENT',
        'position' => 4,
        'blocks' => [
            1 => [
                'type' => 'text',
                'data' => [
                    'intro' => 'Lengkapi informasi event dengan mengisi detail penting di bawah ini.',
                ],
            ],
            2 => [
                'type' => 'placeholder',
                'data' => [
                    'icon' => 'cog',
                    'title' => 'Form Setup Event Gotik',
                    'caption' => 'Nama Event • Deskripsi • Tanggal & Waktu • Lokasi • Kategori • Banner Event',
                ],
            ],
            3 => [
                'type' => 'cards',
                'data' => [
                    'columns' => 2,
                    'cards' => [
                        ['icon' => 'info-circle', 'title' => 'Informasi Dasar', 'body' => 'Masukkan nama event, deskripsi singkat, dan pilih kategori event yang sesuai.'],
                        ['icon' => 'map-marker-alt', 'title' => 'Lokasi & Waktu', 'body' => 'Tentukan tanggal, waktu, dan lokasi penyelenggaraan event dengan detail.'],
                        ['icon' => 'images', 'title' => 'Media Visual', 'body' => 'Upload banner atau poster event untuk menarik perhatian calon pembeli.'],
                        ['icon' => 'align-left', 'title' => 'Deskripsi Detail', 'body' => 'Jelaskan detail lengkap event, fasilitas, speaker, dan informasi penting lainnya.'],
                    ],
                ],
            ],
        ],
    ],
    'tiket_harga' => [
        'title' => 'Tiket & Harga',
        'slug' => 'tiket-harga',
        'nav_group' => 'MEMULAI EVENT',
        'position' => 5,
        'blocks' => [
            1 => [
                'type' => 'text',
                'data' => [
                    'intro' => 'Buat beberapa kategori tiket dengan harga dan kuota yang berbeda-beda.',
                ],
            ],
            2 => [
                'type' => 'tickets',
                'data' => [
                    'tickets' => [
                        ['type' => 'Presale', 'price' => 'Rp 150K', 'quantity' => 'Kuota: 100 tiket', 'description' => 'Diskon khusus untuk pembeli awal. Tersedia untuk 1 minggu pertama.'],
                        ['type' => 'Regular', 'price' => 'Rp 200K', 'quantity' => 'Kuota: 500 tiket', 'description' => 'Harga reguler untuk periode penjualan normal event.'],
                        ['type' => 'VIP', 'price' => 'Rp 500K', 'quantity' => 'Kuota: 50 tiket', 'description' => 'Akses premium dengan benefit eksklusif dan tempat duduk terbaik.'],
                    ],
                    'tip' => 'Tip: Gunakan multiple tier pricing untuk maksimalkan revenue dan ciptakan sense of urgency dengan penawaran terbatas.',
                ],
            ],
        ],
    ],
    'cara_pembeli_beli' => [
        'title' => 'Cara Pembeli Membeli Tiket',
        'slug' => 'cara-pembeli-beli',
        'nav_group' => 'PENJUALAN',
        'position' => 6,
        'blocks' => [
            1 => [
                'type' => 'text',
                'data' => [
                    'intro' => 'Proses pembelian tiket yang mudah dan cepat untuk calon pengunjung event Anda.',
                ],
            ],
            2 => [
                'type' => 'flow',
                'data' => [
                    'boxes' => [
                        ['label' => 'Pilih Event', 'icon' => 'search'],
                        ['label' => 'Pilih Tiket', 'icon' => 'ticket-alt'],
                        ['label' => 'Isi Data', 'icon' => 'edit'],
                        ['label' => 'Verifikasi', 'icon' => 'check-circle'],
                        ['label' => 'Pembayaran', 'icon' => 'credit-card'],
                        ['label' => 'E-Ticket', 'icon' => 'envelope'],
                    ],
                ],
            ],
            3 => [
                'type' => 'cards',
                'data' => [
                    'columns' => 2,
                    'cards' => [
                        ['icon' => 'search', 'title' => 'Cari Event', 'body' => 'Pembeli dapat mencari event yang tersedia di platform atau mengakses link event langsung.'],
                        ['icon' => 'check-circle', 'title' => 'Verifikasi Data', 'body' => 'Sistem akan memverifikasi data pembeli sebelum proses pembayaran dimulai.'],
                        ['icon' => 'credit-card', 'title' => 'Metode Pembayaran', 'body' => 'Pembeli dapat memilih dari berbagai metode pembayaran yang tersedia di Gotik.'],
                        ['icon' => 'check', 'title' => 'Konfirmasi & E-Ticket', 'body' => 'Setelah pembayaran berhasil, pembeli langsung menerima e-ticket via email.'],
                    ],
                ],
            ],
        ],
    ],
    'pembayaran' => [
        'title' => 'Pembayaran',
        'slug' => 'pembayaran',
        'nav_group' => 'PENJUALAN',
        'position' => 7,
        'blocks' => [
            1 => [
                'type' => 'text',
                'data' => [
                    'intro' => 'Platform Gotik mendukung berbagai metode pembayaran untuk kemudahan pelanggan Anda.',
                ],
            ],
            2 => [
                'type' => 'placeholder',
                'data' => [
                    'icon' => 'credit-card',
                    'title' => 'Metode Pembayaran Gotik',
                    'caption' => 'E-Wallet • Transfer Bank • QRIS • Cicilan • Kartu Kredit',
                ],
            ],
            3 => [
                'type' => 'stats',
                'data' => [
                    'stats' => [
                        ['value' => '2.5 Juta', 'label' => 'Transaksi/Bulan'],
                        ['value' => '99.9%', 'label' => 'Uptime'],
                        ['value' => '<2 Detik', 'label' => 'Proses Pembayaran'],
                        ['value' => '100%', 'label' => 'Keamanan PCI DSS'],
                    ],
                ],
            ],
            4 => [
                'type' => 'cards',
                'data' => [
                    'columns' => 2,
                    'cards' => [
                        ['icon' => 'lock', 'title' => 'Keamanan Transaksi', 'body' => 'Semua transaksi dilindungi dengan enkripsi tingkat bank dan standar keamanan internasional.'],
                        ['icon' => 'redo', 'title' => 'Instant Settlement', 'body' => 'Pembayaran yang berhasil langsung terdaftar di sistem dan dapat ditarik kapan saja.'],
                    ],
                ],
            ],
        ],
    ],
    'dashboard_transaksi' => [
        'title' => 'Dashboard & Transaksi',
        'slug' => 'dashboard-transaksi',
        'nav_group' => 'PENJUALAN',
        'position' => 8,
        'blocks' => [
            1 => [
                'type' => 'text',
                'data' => [
                    'intro' => 'Monitor semua aktivitas dan transaksi event Anda melalui dashboard yang intuitif.',
                ],
            ],
            2 => [
                'type' => 'placeholder',
                'data' => [
                    'icon' => 'chart-bar',
                    'title' => 'Screenshot Dashboard Gotik',
                    'caption' => 'Real-time Analytics • Sales Chart • Transaction History • Performance Metrics',
                ],
            ],
            3 => [
                'type' => 'stats',
                'data' => [
                    'stats' => [
                        ['value' => '1,250', 'label' => 'Total Transaksi'],
                        ['value' => 'Rp 250M', 'label' => 'Total Pendapatan'],
                        ['value' => '98.5%', 'label' => 'Tiket Terjual'],
                        ['value' => '4.8★', 'label' => 'Rating Event'],
                    ],
                ],
            ],
            4 => [
                'type' => 'cards',
                'data' => [
                    'columns' => 2,
                    'cards' => [
                        ['icon' => 'list', 'title' => 'Histori Transaksi', 'body' => 'Lihat semua transaksi pembelian tiket dengan detail lengkap (pembeli, jumlah, tanggal, status).'],
                        ['icon' => 'download', 'title' => 'Export & Laporan', 'body' => 'Download laporan transaksi dalam format Excel atau PDF untuk keperluan administratif.'],
                        ['icon' => 'eye', 'title' => 'Monitoring Real-time', 'body' => 'Monitor penjualan tiket dan pendapatan secara langsung dengan grafik dan statistik.'],
                        ['icon' => 'cog', 'title' => 'Pengaturan Event', 'body' => 'Ubah detail event, harga tiket, dan pengaturan pembayaran kapan saja sesuai kebutuhan.'],
                    ],
                ],
            ],
        ],
    ],
    'qr_ticket' => [
        'title' => 'QR Ticket',
        'slug' => 'qr-ticket',
        'nav_group' => 'HARI-H EVENT',
        'position' => 9,
        'blocks' => [
            1 => [
                'type' => 'text',
                'data' => [
                    'intro' => 'Setiap pembelian tiket akan mendapatkan e-ticket dengan QR Code unik untuk check-in.',
                ],
            ],
            2 => [
                'type' => 'qr',
                'data' => [
                    'event_name' => 'TechFest 2026',
                    'event_date' => '25 September 2026 • Jakarta Convention Center',
                    'icon' => 'qrcode',
                    'ticket_holder' => 'John Doe',
                    'ticket_type' => 'VIP',
                    'ticket_number' => 'GOT-2026-001234',
                    'entry_window' => '08:00 - 23:59',
                    'footer' => 'Tunjukkan QR Code ini pada saat check-in. Satu tiket hanya untuk satu orang.',
                ],
            ],
            3 => [
                'type' => 'cards',
                'data' => [
                    'columns' => 2,
                    'cards' => [
                        ['icon' => 'mobile-alt', 'title' => 'E-Ticket Digital', 'body' => 'Pembeli menerima e-ticket dalam format digital yang dapat diakses lewat email atau aplikasi.'],
                        ['icon' => 'shield-alt', 'title' => 'Keamanan Ticket', 'body' => 'QR Code unik dan terenkripsi mencegah pemalsuan atau duplikasi tiket event.'],
                    ],
                ],
            ],
        ],
    ],
    'scanner_checkin' => [
        'title' => 'Scanner & Check-in',
        'slug' => 'scanner-checkin',
        'nav_group' => 'HARI-H EVENT',
        'position' => 10,
        'blocks' => [
            1 => [
                'type' => 'text',
                'data' => [
                    'intro' => 'Proses check-in pengunjung yang cepat dan efisien menggunakan aplikasi scanner Gotik.',
                ],
            ],
            2 => [
                'type' => 'flow',
                'data' => [
                    'boxes' => [
                        ['label' => 'Pengunjung Datang', 'icon' => 'users'],
                        ['label' => 'QR Dipindai', 'icon' => 'mobile-alt'],
                        ['label' => 'Verifikasi Tiket', 'icon' => 'check'],
                        ['label' => 'Check-in Berhasil', 'icon' => 'check-circle'],
                    ],
                ],
            ],
            3 => [
                'type' => 'placeholder',
                'data' => [
                    'icon' => 'mobile-alt',
                    'title' => 'Screenshot Scanner App Gotik',
                    'caption' => 'Camera QR Scanner • Real-time Status • Ticket Validation • Check-in Counter',
                ],
            ],
            4 => [
                'type' => 'cards',
                'data' => [
                    'columns' => 2,
                    'cards' => [
                        ['icon' => 'camera', 'title' => 'Scanner Real-time', 'body' => 'Gunakan kamera smartphone untuk memindai QR Code tiket dengan cepat dan akurat.'],
                        ['icon' => 'wifi', 'title' => 'Offline Mode', 'body' => 'Fitur scanner dapat bekerja tanpa koneksi internet dengan sinkronisasi otomatis saat online.'],
                        ['icon' => 'chart-pie', 'title' => 'Statistik Live', 'body' => 'Lihat statistik check-in real-time untuk mengetahui berapa banyak pengunjung yang sudah masuk.'],
                        ['icon' => 'users', 'title' => 'Multi-checker', 'body' => 'Buat multiple scanner untuk mempercepat proses check-in di berbagai pintu masuk event.'],
                    ],
                ],
            ],
        ],
    ],
    'laporan' => [
        'title' => 'Laporan',
        'slug' => 'laporan',
        'nav_group' => 'KEUANGAN',
        'position' => 11,
        'blocks' => [
            1 => [
                'type' => 'text',
                'data' => [
                    'intro' => 'Akses laporan komprehensif tentang penjualan, transaksi, dan check-in event Anda.',
                ],
            ],
            2 => [
                'type' => 'cards',
                'data' => [
                    'columns' => 2,
                    'cards' => [
                        ['icon' => 'ticket-alt', 'title' => 'Laporan Penjualan Tiket', 'body' => 'Detail penjualan per kategori tiket, termasuk jumlah terjual, revenue, dan performa penjualan.'],
                        ['icon' => 'exchange-alt', 'title' => 'Laporan Transaksi', 'body' => 'Rincian lengkap setiap transaksi pembayaran, metode pembayaran, dan status pembayaran.'],
                        ['icon' => 'sign-in-alt', 'title' => 'Laporan Check-in', 'body' => 'Statistik check-in pengunjung, jumlah pengunjung yang hadir, dan perbandingan dengan kapasitas.'],
                        ['icon' => 'download', 'title' => 'Export Laporan', 'body' => 'Download laporan dalam format Excel, PDF, atau CSV untuk kebutuhan administrasi dan audit.'],
                    ],
                ],
            ],
            3 => [
                'type' => 'placeholder',
                'data' => [
                    'icon' => 'file-pdf',
                    'title' => 'Contoh Laporan Event Gotik',
                    'caption' => 'Comprehensive Report • Sales Analytics • Transaction Details • Attendance Summary',
                ],
            ],
        ],
    ],
    'penarikan_dana' => [
        'title' => 'Penarikan Dana',
        'slug' => 'penarikan-dana',
        'nav_group' => 'KEUANGAN',
        'position' => 12,
        'blocks' => [
            1 => [
                'type' => 'text',
                'data' => [
                    'intro' => 'Proses pencairan dana yang mudah dan aman ke rekening bank Anda.',
                ],
            ],
            2 => [
                'type' => 'flow',
                'data' => [
                    'boxes' => [
                        ['label' => 'Penjualan', 'icon' => 'money-bill'],
                        ['label' => 'Rekap Transaksi', 'icon' => 'chart-line'],
                        ['label' => 'Ajukan Penarikan', 'icon' => 'paper-plane'],
                        ['label' => 'Verifikasi', 'icon' => 'check'],
                        ['label' => 'Dana Dikirim', 'icon' => 'university'],
                    ],
                ],
            ],
            3 => [
                'type' => 'cards',
                'data' => [
                    'columns' => 2,
                    'cards' => [
                        ['icon' => 'calculator', 'title' => 'Perhitungan Otomatis', 'body' => 'Sistem secara otomatis menghitung pendapatan Anda setelah dikurangi biaya platform dan pajak.'],
                        ['icon' => 'clock', 'title' => 'Proses Cepat', 'body' => 'Dana Anda akan ditransfer dalam waktu 1-3 hari kerja setelah persetujuan.'],
                        ['icon' => 'hand-holding-usd', 'title' => 'Biaya Transparan', 'body' => 'Tidak ada biaya tersembunyi. Semua biaya ditampilkan dengan jelas sebelum penarikan dana.'],
                        ['icon' => 'shield-alt', 'title' => 'Keamanan Dana', 'body' => 'Dana Anda disimpan dalam escrow account terpisah dan sepenuhnya aman.'],
                    ],
                ],
            ],
        ],
    ],
    'faq' => [
        'title' => 'Pertanyaan yang Sering Diajukan',
        'slug' => 'faq',
        'nav_group' => 'LAINNYA',
        'position' => 13,
        'blocks' => [
            1 => [
                'type' => 'text',
                'data' => [
                    'intro' => 'Temukan jawaban untuk pertanyaan umum tentang platform Gotik.',
                    'intro_spacing' => '40px',
                ],
            ],
            2 => [
                'type' => 'faq',
                'data' => [
                    'items' => [
                        ['question' => 'Apakah penyelenggara harus memiliki website?', 'answer' => 'Tidak perlu. Platform Gotik menyediakan halaman event lengkap yang dapat digunakan untuk menjual tiket. Anda hanya perlu membagikan link event kepada calon pembeli, baik melalui media sosial, email, atau channel lainnya.'],
                        ['question' => 'Apakah Gotik menyediakan halaman penjualan tiket?', 'answer' => 'Ya, Gotik menyediakan halaman penjualan tiket yang fully customizable untuk setiap event. Halaman tersebut responsif, modern, dan dioptimalkan untuk konversi tinggi. Anda dapat mengatur desain, deskripsi, harga, dan kategori tiket sesuai kebutuhan.'],
                        ['question' => 'Apakah tiket memiliki QR Code?', 'answer' => 'Ya, setiap tiket yang dibeli akan memiliki QR Code unik yang dikirimkan melalui email ke pembeli. QR Code ini digunakan untuk verifikasi tiket saat check-in event.'],
                        ['question' => 'Bagaimana proses check-in di event?', 'answer' => 'Pengunjung datang ke event dan menunjukkan QR Code dari e-ticket mereka. Petugas menggunakan aplikasi scanner Gotik untuk memindai QR Code, sistem akan memverifikasi tiket, dan pengunjung dapat langsung masuk ke event.'],
                        ['question' => 'Bagaimana pencairan dana dilakukan?', 'answer' => 'Anda dapat mengajukan penarikan dana melalui dashboard setelah event selesai. Dana akan dihitung otomatis setelah dikurangi biaya platform. Proses verifikasi memakan waktu 1-2 hari, dan dana akan ditransfer ke rekening bank Anda dalam 1-3 hari kerja.'],
                        ['question' => 'Berapa biaya platform Gotik?', 'answer' => 'Biaya platform Gotik bervariasi tergantung pada paket dan volume transaksi Anda. Hubungi tim Gotik untuk mendapatkan penawaran khusus dan skema biaya yang sesuai dengan kebutuhan event Anda.'],
                    ],
                ],
            ],
        ],
    ],
    'cta_hubungi' => [
        'title' => 'Hubungi Gotik',
        'slug' => 'hubungi',
        'nav_group' => 'LAINNYA',
        'position' => 14,
        'blocks' => [
            1 => [
                'type' => 'cta',
                'data' => [
                    'title' => 'Siap Menjalankan Event Bersama Gotik?',
                    'subtitle' => 'Hubungi tim Gotik sekarang untuk memulai perjalanan event Anda dan memaksimalkan penjualan tiket.',
                    'cta' => [
                        'label' => 'Hubungi Tim Gotik',
                        'href' => 'mailto:hello@gotik.io',
                        'icon' => 'envelope',
                        'variant' => 'cta',
                    ],
                ],
            ],
        ],
    ],
];