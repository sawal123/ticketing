<?php

/**
 * Static seed data for the marketing guide content foundation.
 *
 * Mirror of the previous Blade static view, structured for the
 * `marketing_guide_sections` + `marketing_guide_blocks` tables. Each
 * top-level key is a stable section key (unique per version), and the
 * inner `blocks` array is keyed by sort position.
 *
 * Updating this file and re-running the seeder refreshes content in place
 * via idempotent updateOrCreate().
 */

return array (
  'hero' => 
  array (
    'title' => 'Pengenalan',
    'slug' => 'pengenalan',
    'position' => 1,
    'blocks' => 
    array (
      1 => 
      array (
        'type' => 'text',
        'data' => 
        array (
          'badge' => 'Panduan Privat',
          'title' => 'Cara Kerja Gotik',
          'subtitle' => 'Kelola event, penjualan tiket, pembayaran hingga proses check-in dalam satu platform.',
          'show_recipient' => true,
          'show_expiry' => true,
          'cta' => 
          array (
            'label' => 'Mulai Sekarang',
            'href' => '#menjadi-penyelenggara',
            'icon' => 'arrow-right',
          ),
        ),
      ),
    ),
  ),
  'cara_kerja' => 
  array (
    'title' => 'Cara Kerja Gotik',
    'slug' => 'cara-kerja',
    'position' => 2,
    'blocks' => 
    array (
      1 => 
      array (
        'type' => 'text',
        'data' => 
        array (
          'intro' => 'Berikut adalah workflow lengkap dari awal event didaftarkan hingga dana dicairkan ke rekening Anda.',
        ),
      ),
      2 => 
      array (
        'type' => 'workflow',
        'data' => 
        array (
          'steps' => 
          array (
            0 => 
            array (
              'icon' => 'edit',
              'title' => 'Daftarkan Event',
              'description' => 'Mulai dengan mendaftarkan event baru di platform Gotik dan lengkapi informasi dasar.',
            ),
            1 => 
            array (
              'icon' => 'handshake',
              'title' => 'Kesepakatan & Aktivasi',
              'description' => 'Tim Gotik akan melakukan verifikasi dan aktivasi akun event Anda.',
            ),
            2 => 
            array (
              'icon' => 'ticket-alt',
              'title' => 'Setup Tiket & Pembayaran',
              'description' => 'Atur kategori tiket, harga, dan metode pembayaran yang ingin diaktifkan.',
            ),
            3 => 
            array (
              'icon' => 'rocket',
              'title' => 'Mulai Penjualan',
              'description' => 'Bagikan link event dan mulai menerima pembelian tiket dari pembeli.',
            ),
            4 => 
            array (
              'icon' => 'chart-line',
              'title' => 'Pantau Transaksi',
              'description' => 'Gunakan dashboard untuk memantau penjualan dan transaksi secara real-time.',
            ),
            5 => 
            array (
              'icon' => 'qrcode',
              'title' => 'Scan Tiket Saat Event',
              'description' => 'Gunakan fitur scanner untuk memverifikasi tiket dan check-in pengunjung.',
            ),
            6 => 
            array (
              'icon' => 'wallet',
              'title' => 'Penarikan Dana',
              'description' => 'Ajukan penarikan dana ke rekening bank Anda setelah event selesai.',
            ),
          ),
        ),
      ),
    ),
  ),
  'menjadi_penyelenggara' => 
  array (
    'title' => 'Menjadi Penyelenggara',
    'slug' => 'menjadi-penyelenggara',
    'position' => 3,
    'blocks' => 
    array (
      1 => 
      array (
        'type' => 'text',
        'data' => 
        array (
          'intro' => 'Proses sederhana untuk mulai mengelola event Anda bersama Gotik.',
        ),
      ),
      2 => 
      array (
        'type' => 'flow',
        'data' => 
        array (
          'boxes' => 
          array (
            0 => 
            array (
              'label' => 'Hubungi Gotik',
              'icon' => 'phone',
            ),
            1 => 
            array (
              'label' => 'Diskusi Event',
              'icon' => 'chat',
            ),
            2 => 
            array (
              'label' => 'Kesepakatan',
              'icon' => 'check',
            ),
            3 => 
            array (
              'label' => 'Akun Penyewa',
              'icon' => 'user',
            ),
            4 => 
            array (
              'label' => 'Aktivasi Event',
              'icon' => 'rocket',
            ),
            5 => 
            array (
              'label' => 'Mulai Penjualan',
              'icon' => 'money',
            ),
          ),
        ),
      ),
      3 => 
      array (
        'type' => 'cards',
        'data' => 
        array (
          'columns' => 3,
          'cards' => 
          array (
            0 => 
            array (
              'icon' => 'phone',
              'title' => 'Hubungi Tim',
              'body' => 'Hubungi tim Gotik untuk mendiskusikan kebutuhan dan detail event Anda.',
            ),
            1 => 
            array (
              'icon' => 'file-contract',
              'title' => 'Verifikasi',
              'body' => 'Tim kami akan melakukan verifikasi data dan kesepakatan syarat & ketentuan.',
            ),
            2 => 
            array (
              'icon' => 'lock-open',
              'title' => 'Aktivasi',
              'body' => 'Akun Anda aktif dan siap untuk mulai mengatur detail event dan tiket.',
            ),
          ),
        ),
      ),
    ),
  ),
  'setup_event' => 
  array (
    'title' => 'Setup Event',
    'slug' => 'setup-event',
    'position' => 4,
    'blocks' => 
    array (
      1 => 
      array (
        'type' => 'text',
        'data' => 
        array (
          'intro' => 'Lengkapi informasi event dengan mengisi detail penting di bawah ini.',
        ),
      ),
      2 => 
      array (
        'type' => 'placeholder',
        'data' => 
        array (
          'icon' => 'cog',
          'title' => 'Form Setup Event Gotik',
          'caption' => 'Nama Event • Deskripsi • Tanggal & Waktu • Lokasi • Kategori • Banner Event',
        ),
      ),
      3 => 
      array (
        'type' => 'cards',
        'data' => 
        array (
          'columns' => 2,
          'cards' => 
          array (
            0 => 
            array (
              'icon' => 'info-circle',
              'title' => 'Informasi Dasar',
              'body' => 'Masukkan nama event, deskripsi singkat, dan pilih kategori event yang sesuai.',
            ),
            1 => 
            array (
              'icon' => 'map-marker-alt',
              'title' => 'Lokasi & Waktu',
              'body' => 'Tentukan tanggal, waktu, dan lokasi penyelenggaraan event dengan detail.',
            ),
            2 => 
            array (
              'icon' => 'images',
              'title' => 'Media Visual',
              'body' => 'Upload banner atau poster event untuk menarik perhatian calon pembeli.',
            ),
            3 => 
            array (
              'icon' => 'align-left',
              'title' => 'Deskripsi Detail',
              'body' => 'Jelaskan detail lengkap event, fasilitas, speaker, dan informasi penting lainnya.',
            ),
          ),
        ),
      ),
    ),
  ),
  'tiket_harga' => 
  array (
    'title' => 'Tiket & Harga',
    'slug' => 'tiket-harga',
    'position' => 5,
    'blocks' => 
    array (
      1 => 
      array (
        'type' => 'text',
        'data' => 
        array (
          'intro' => 'Buat beberapa kategori tiket dengan harga dan kuota yang berbeda-beda.',
        ),
      ),
      2 => 
      array (
        'type' => 'tickets',
        'data' => 
        array (
          'tickets' => 
          array (
            0 => 
            array (
              'type' => 'Presale',
              'price' => 'Rp 150K',
              'quantity' => 'Kuota: 100 tiket',
              'description' => 'Diskon khusus untuk pembeli awal. Tersedia untuk 1 minggu pertama.',
            ),
            1 => 
            array (
              'type' => 'Regular',
              'price' => 'Rp 200K',
              'quantity' => 'Kuota: 500 tiket',
              'description' => 'Harga reguler untuk periode penjualan normal event.',
            ),
            2 => 
            array (
              'type' => 'VIP',
              'price' => 'Rp 500K',
              'quantity' => 'Kuota: 50 tiket',
              'description' => 'Akses premium dengan benefit eksklusif dan tempat duduk terbaik.',
            ),
          ),
          'tip' => 'Tip: Gunakan multiple tier pricing untuk maksimalkan revenue dan ciptakan sense of urgency dengan penawaran terbatas.',
        ),
      ),
    ),
  ),
  'cara_pembeli_beli' => 
  array (
    'title' => 'Cara Pembeli Membeli Tiket',
    'slug' => 'cara-pembeli-beli',
    'position' => 6,
    'blocks' => 
    array (
      1 => 
      array (
        'type' => 'text',
        'data' => 
        array (
          'intro' => 'Proses pembelian tiket yang mudah dan cepat untuk calon pengunjung event Anda.',
        ),
      ),
      2 => 
      array (
        'type' => 'flow',
        'data' => 
        array (
          'boxes' => 
          array (
            0 => 
            array (
              'label' => 'Pilih Event',
              'icon' => 'search',
            ),
            1 => 
            array (
              'label' => 'Pilih Tiket',
              'icon' => 'ticket',
            ),
            2 => 
            array (
              'label' => 'Isi Data',
              'icon' => 'edit',
            ),
            3 => 
            array (
              'label' => 'Verifikasi',
              'icon' => 'check',
            ),
            4 => 
            array (
              'label' => 'Pembayaran',
              'icon' => 'credit-card',
            ),
            5 => 
            array (
              'label' => 'E-Ticket',
              'icon' => 'envelope',
            ),
          ),
        ),
      ),
      3 => 
      array (
        'type' => 'cards',
        'data' => 
        array (
          'columns' => 2,
          'cards' => 
          array (
            0 => 
            array (
              'icon' => 'search',
              'title' => 'Cari Event',
              'body' => 'Pembeli dapat mencari event yang tersedia di platform atau mengakses link event langsung.',
            ),
            1 => 
            array (
              'icon' => 'check-circle',
              'title' => 'Verifikasi Data',
              'body' => 'Sistem akan memverifikasi data pembeli sebelum proses pembayaran dimulai.',
            ),
            2 => 
            array (
              'icon' => 'credit-card',
              'title' => 'Metode Pembayaran',
              'body' => 'Pembeli dapat memilih dari berbagai metode pembayaran yang tersedia di Gotik.',
            ),
            3 => 
            array (
              'icon' => 'check',
              'title' => 'Konfirmasi & E-Ticket',
              'body' => 'Setelah pembayaran berhasil, pembeli langsung menerima e-ticket via email.',
            ),
          ),
        ),
      ),
    ),
  ),
  'pembayaran' => 
  array (
    'title' => 'Pembayaran',
    'slug' => 'pembayaran',
    'position' => 7,
    'blocks' => 
    array (
      1 => 
      array (
        'type' => 'text',
        'data' => 
        array (
          'intro' => 'Platform Gotik mendukung berbagai metode pembayaran untuk kemudahan pelanggan Anda.',
        ),
      ),
      2 => 
      array (
        'type' => 'placeholder',
        'data' => 
        array (
          'icon' => 'credit-card',
          'title' => 'Metode Pembayaran Gotik',
          'caption' => 'E-Wallet • Transfer Bank • QRIS • Cicilan • Kartu Kredit',
        ),
      ),
      3 => 
      array (
        'type' => 'stats',
        'data' => 
        array (
          'stats' => 
          array (
            0 => 
            array (
              'value' => '2.5 Juta',
              'label' => 'Transaksi/Bulan',
            ),
            1 => 
            array (
              'value' => '99.9%',
              'label' => 'Uptime',
            ),
            2 => 
            array (
              'value' => '<2 Detik',
              'label' => 'Proses Pembayaran',
            ),
            3 => 
            array (
              'value' => '100%',
              'label' => 'Keamanan PCI DSS',
            ),
          ),
        ),
      ),
      4 => 
      array (
        'type' => 'cards',
        'data' => 
        array (
          'columns' => 2,
          'cards' => 
          array (
            0 => 
            array (
              'icon' => 'lock',
              'title' => 'Keamanan Transaksi',
              'body' => 'Semua transaksi dilindungi dengan enkripsi tingkat bank dan standar keamanan internasional.',
            ),
            1 => 
            array (
              'icon' => 'sync-alt',
              'title' => 'Realtime Settlement',
              'body' => 'Pembayaran dikonfirmasi secara realtime dan langsung diteruskan ke sistem event organizer.',
            ),
            2 => 
            array (
              'icon' => 'redo',
              'title' => 'Refund Otomatis',
              'body' => 'Sistem refund otomatis membantu mengembalikan dana pembeli bila event dibatalkan.',
            ),
            3 => 
            array (
              'icon' => 'headset',
              'title' => 'Support 24/7',
              'body' => 'Tim support Gotik siap membantu masalah pembayaran kapanpun dibutuhkan.',
            ),
          ),
        ),
      ),
    ),
  ),
  'dashboard_transaksi' => 
  array (
    'title' => 'Dashboard & Transaksi',
    'slug' => 'dashboard-transaksi',
    'position' => 8,
    'blocks' => 
    array (
      1 => 
      array (
        'type' => 'text',
        'data' => 
        array (
          'intro' => 'Pantau seluruh transaksi dan performa event Anda dalam satu dashboard terpusat.',
        ),
      ),
      2 => 
      array (
        'type' => 'stats',
        'data' => 
        array (
          'stats' => 
          array (
            0 => 
            array (
              'value' => '1,250',
              'label' => 'Total Transaksi',
            ),
            1 => 
            array (
              'value' => 'Rp 250M',
              'label' => 'Total Pendapatan',
            ),
            2 => 
            array (
              'value' => '98.5%',
              'label' => 'Tiket Terjual',
            ),
            3 => 
            array (
              'value' => '4.8★',
              'label' => 'Rating Event',
            ),
          ),
        ),
      ),
      3 => 
      array (
        'type' => 'cards',
        'data' => 
        array (
          'columns' => 2,
          'cards' => 
          array (
            0 => 
            array (
              'icon' => 'list',
              'title' => 'Histori Transaksi',
              'body' => 'Lihat semua transaksi pembelian tiket dengan detail lengkap (pembeli, jumlah, tanggal, status).',
            ),
            1 => 
            array (
              'icon' => 'download',
              'title' => 'Export & Laporan',
              'body' => 'Download laporan transaksi dalam format Excel atau PDF untuk keperluan administratif.',
            ),
            2 => 
            array (
              'icon' => 'eye',
              'title' => 'Monitoring Real-time',
              'body' => 'Monitor penjualan tiket dan pendapatan secara langsung dengan grafik dan statistik.',
            ),
            3 => 
            array (
              'icon' => 'cog',
              'title' => 'Pengaturan Event',
              'body' => 'Ubah detail event, harga tiket, dan pengaturan pembayaran kapan saja sesuai kebutuhan.',
            ),
          ),
        ),
      ),
    ),
  ),
  'qr_ticket' => 
  array (
    'title' => 'QR Ticket',
    'slug' => 'qr-ticket',
    'position' => 9,
    'blocks' => 
    array (
      1 => 
      array (
        'type' => 'text',
        'data' => 
        array (
          'intro' => 'Setiap pembelian tiket akan mendapatkan e-ticket dengan QR Code unik untuk check-in.',
        ),
      ),
      2 => 
      array (
        'type' => 'qr',
        'data' => 
        array (
          'event_name' => 'TechFest 2026',
          'event_date' => '25 September 2026 • Jakarta Convention Center',
          'icon' => 'qrcode',
          'ticket_holder' => 'John Doe',
          'ticket_type' => 'VIP',
          'ticket_number' => 'GOT-2026-001234',
          'entry_window' => '08:00 - 23:59',
          'footer' => 'Tunjukkan QR Code ini pada saat check-in. Satu tiket hanya untuk satu orang.',
        ),
      ),
      3 => 
      array (
        'type' => 'cards',
        'data' => 
        array (
          'columns' => 2,
          'cards' => 
          array (
            0 => 
            array (
              'icon' => 'mobile-alt',
              'title' => 'E-Ticket Digital',
              'body' => 'Pembeli menerima e-ticket dalam format digital yang dapat diakses lewat email atau aplikasi.',
            ),
            1 => 
            array (
              'icon' => 'shield-alt',
              'title' => 'Keamanan Ticket',
              'body' => 'QR Code unik dan terenkripsi mencegah pemalsuan atau duplikasi tiket event.',
            ),
          ),
        ),
      ),
    ),
  ),
  'scanner_checkin' => 
  array (
    'title' => 'Scanner & Check-in',
    'slug' => 'scanner-checkin',
    'position' => 10,
    'blocks' => 
    array (
      1 => 
      array (
        'type' => 'text',
        'data' => 
        array (
          'intro' => 'Proses check-in pengunjung yang cepat dan efisien menggunakan QR Code scanner.',
        ),
      ),
      2 => 
      array (
        'type' => 'flow',
        'data' => 
        array (
          'boxes' => 
          array (
            0 => 
            array (
              'label' => 'Buka Scanner',
              'icon' => 'qrcode',
            ),
            1 => 
            array (
              'label' => 'Scan QR',
              'icon' => 'camera',
            ),
            2 => 
            array (
              'label' => 'Verifikasi',
              'icon' => 'check',
            ),
            3 => 
            array (
              'label' => 'Check-in',
              'icon' => 'user-check',
            ),
          ),
        ),
      ),
      3 => 
      array (
        'type' => 'stats',
        'data' => 
        array (
          'stats' => 
          array (
            0 => 
            array (
              'value' => '<2 Detik',
              'label' => 'Waktu Scan',
            ),
            1 => 
            array (
              'value' => '100%',
              'label' => 'Akurasi Validasi',
            ),
            2 => 
            array (
              'value' => 'Offline',
              'label' => 'Mode Scanner',
            ),
            3 => 
            array (
              'value' => 'Multi',
              'label' => 'Device Support',
            ),
          ),
        ),
      ),
    ),
  ),
  'faq' => 
  array (
    'title' => 'FAQ',
    'slug' => 'faq',
    'position' => 11,
    'blocks' => 
    array (
      1 => 
      array (
        'type' => 'text',
        'data' => 
        array (
          'intro' => 'Pertanyaan yang sering diajukan tentang layanan Gotik untuk event organizer.',
        ),
      ),
      2 => 
      array (
        'type' => 'faq',
        'data' => 
        array (
          'items' => 
          array (
            0 => 
            array (
              'q' => 'Berapa lama proses aktivasi event?',
              'a' => 'Aktivasi event biasanya memakan waktu 1-2 hari kerja setelah semua dokumen dan kesepakatan lengkap.',
            ),
            1 => 
            array (
              'q' => 'Bagaimana sistem pembayaran dan pencairan dana?',
              'a' => 'Gotik menyediakan berbagai metode pembayaran. Pencairan dana ke rekening Anda dilakukan H+3 setelah event selesai.',
            ),
            2 => 
            array (
              'q' => 'Apakah ada biaya tambahan selain komisi?',
              'a' => 'Tidak ada biaya tersembunyi. Anda hanya membayar komisi sesuai kesepakatan awal di kontrak.',
            ),
            3 => 
            array (
              'q' => 'Bagaimana cara mendapatkan dukungan teknis?',
              'a' => 'Tim support Gotik tersedia 24/7 melalui email, telepon, dan live chat di dashboard.',
            ),
          ),
        ),
      ),
    ),
  ),
  'cta' => 
  array (
    'title' => 'Mulai Sekarang',
    'slug' => 'mulai',
    'position' => 12,
    'blocks' => 
    array (
      1 => 
      array (
        'type' => 'cta',
        'data' => 
        array (
          'heading' => 'Siap Mengelola Event Anda?',
          'body' => 'Hubungi tim Gotik hari ini dan dapatkan konsultasi gratis untuk kebutuhan event Anda.',
          'primary' => 
          array (
            'label' => 'Hubungi Tim Gotik',
            'href' => '#',
            'icon' => 'phone',
          ),
          'secondary' => 
          array (
            'label' => 'Pelajari Lebih Lanjut',
            'href' => '#cara-kerja',
            'icon' => 'arrow-right',
          ),
        ),
      ),
    ),
  ),
);
