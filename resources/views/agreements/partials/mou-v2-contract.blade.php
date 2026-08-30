@php
    $agreement = is_array($payload['agreement'] ?? null) ? $payload['agreement'] : [];
    $event = is_array($payload['event'] ?? null) ? $payload['event'] : [];
    $platformParty = is_array($payload['platform_party'] ?? null) ? $payload['platform_party'] : [];
    $organizer = is_array($payload['organizer'] ?? null) ? $payload['organizer'] : [];

    $display = static fn ($value, $fallback = '-') => filled($value) ? $value : $fallback;
    $eventName = $display($event['event_name'] ?? $event['name'] ?? null);
    $eventStart = $display($event['start'] ?? $event['date'] ?? null);
    $eventEnd = $display($event['end'] ?? null);
    $eventVenue = $display($event['venue_name'] ?? null);
    $eventVenueAddress = $display($event['venue_address'] ?? $event['legacy_address'] ?? null);
    $eventLocation = collect([
        $event['venue_city'] ?? null,
        $event['venue_province'] ?? null,
    ])->filter(fn ($value) => filled($value))->implode(', ');
    $eventLocation = $display($eventLocation);
    $documentNumber = $display($agreement['document_number'] ?? null);
    $templateVersion = $display($agreement['template_version'] ?? null);
    $agreementUid = $display($agreement['uid'] ?? null);

    $platformRows = [
        'Nama Badan Usaha' => $platformParty['company_name'] ?? null,
        'Legalitas / NIB' => $platformParty['legal_id'] ?? null,
        'Alamat' => $platformParty['address'] ?? null,
        'Nama Perwakilan' => $platformParty['representative_name'] ?? null,
        'Jabatan' => $platformParty['representative_position'] ?? null,
        'Email' => $platformParty['email'] ?? null,
        'Telepon' => $platformParty['phone'] ?? null,
        'Website' => $platformParty['website'] ?? null,
    ];

    $organizerRows = [
        'Nama Penyelenggara' => $organizer['organizer_name'] ?? null,
        'Penanggung Jawab' => $organizer['responsible_name'] ?? null,
        'Jabatan' => $organizer['responsible_position'] ?? null,
        'Telepon' => $organizer['phone'] ?? null,
        'Email' => $organizer['email'] ?? null,
        'Alamat' => $organizer['address'] ?? null,
    ];

    $articles = [
        1 => [
            'title' => 'Definisi',
            'clauses' => [
                'Platform adalah sistem penjualan, distribusi, validasi, dan pengelolaan tiket event yang dioperasikan oleh PIHAK PERTAMA dengan nama layanan Gotik.',
                'Event adalah kegiatan sebagaimana tercantum dalam dokumen ini beserta penyelenggaraan, akses masuk, dan aktivitas turunannya yang dikelola oleh PIHAK KEDUA.',
                'Penyelenggara adalah PIHAK KEDUA yang bertanggung jawab atas perizinan, pelaksanaan, materi promosi, dan seluruh keputusan operasional Event.',
                'Pembeli adalah setiap pihak yang memperoleh tiket melalui Platform sesuai syarat transaksi yang berlaku.',
                'Tiket adalah bukti hak akses ke Event yang diterbitkan dan/atau divalidasi melalui Platform.',
                'Payment Gateway adalah kanal atau metode pembayaran yang tersedia pada sistem untuk mendukung pemrosesan transaksi pembelian tiket oleh Pembeli.',
                'Rekonsiliasi adalah proses pencocokan, pemeriksaan, dan penetapan data transaksi penjualan tiket berdasarkan catatan pada Platform dan/atau kanal pembayaran terkait.',
                'Refund adalah pengembalian dana kepada Pembeli atas transaksi tiket sesuai kebijakan Event, hasil koordinasi PARA PIHAK, dan ketentuan kanal pembayaran yang relevan.',
                'Gate System adalah sistem validasi dan pencatatan akses masuk Event yang digunakan untuk memeriksa keabsahan tiket pada saat pelaksanaan Event.',
            ],
        ],
        2 => [
            'title' => 'Maksud, Tujuan dan Ruang Lingkup',
            'clauses' => [
                'Perjanjian ini dibuat untuk mengatur kerja sama penjualan dan pengelolaan tiket Event melalui Platform Gotik secara tertib, terdokumentasi, dan dapat dipertanggungjawabkan.',
                'PIHAK PERTAMA menyediakan sarana teknologi untuk listing, penjualan tiket, pemantauan transaksi, dan validasi tiket sesuai konfigurasi yang disepakati PARA PIHAK.',
                'PIHAK KEDUA menggunakan layanan tersebut untuk kepentingan penyelenggaraan Event dengan tetap memegang tanggung jawab atas substansi Event dan pemenuhan kewajiban hukumnya.',
            ],
        ],
        3 => [
            'title' => 'Pendaftaran, Verifikasi dan Listing Event',
            'clauses' => [
                'PIHAK KEDUA wajib menyampaikan data Event, identitas penyelenggara, rekening penerimaan dana, dan dokumen pendukung secara benar, lengkap, serta mutakhir.',
                'PIHAK PERTAMA berwenang melakukan pemeriksaan administratif dan/atau verifikasi terhadap data dan dokumen yang disampaikan PIHAK KEDUA sebelum Event ditayangkan pada Platform.',
                'Apabila terdapat data yang tidak lengkap, tidak konsisten, atau memerlukan klarifikasi lanjutan, PIHAK PERTAMA berhak menunda aktivasi listing sampai persyaratan dipenuhi.',
                'Tindakan verifikasi oleh PIHAK PERTAMA tidak mengalihkan tanggung jawab substantif atas isi data dan legalitas Event dari PIHAK KEDUA.',
            ],
        ],
        4 => [
            'title' => 'Penjualan dan Pengelolaan Tiket',
            'clauses' => [
                'PIHAK PERTAMA memfasilitasi penjualan tiket Event melalui Platform sesuai data Event yang didaftarkan dan disetujui untuk ditampilkan.',
                'PIHAK KEDUA bertanggung jawab penuh atas keakuratan informasi tiket, kebijakan akses, syarat penggunaan, dan materi yang dimasukkan ke dalam sistem.',
                'Perubahan konfigurasi tiket pada sistem hanya berlaku sebagai data operasional layanan dan tidak otomatis menjadi bagian dari isi kontraktual dokumen ini kecuali disepakati lain secara tertulis.',
                'PIHAK PERTAMA dapat menolak, membatasi, atau menangguhkan konfigurasi tertentu apabila secara teknis, administratif, atau kepatuhan dinilai berisiko terhadap pelaksanaan Event atau kepentingan Pembeli.',
            ],
        ],
        5 => [
            'title' => 'Biaya, Pembayaran dan Pajak',
            'clauses' => [
                'PIHAK PERTAMA dapat menyediakan kanal pembayaran yang tersedia pada Platform untuk memproses transaksi pembelian tiket oleh Pembeli.',
                'Biaya tambahan kepada Pembeli, apabila berlaku, wajib diinformasikan secara transparan kepada Pembeli sebelum transaksi dan/atau pembayaran diselesaikan.',
                'Biaya yang timbul dari penggunaan kanal pembayaran tertentu, apabila berlaku, diinformasikan kepada Pembeli sebelum transaksi sesuai metode pembayaran yang dipilih.',
                'Pajak, pungutan, atau biaya lain yang relevan dilaksanakan sesuai karakter transaksi dan mengikuti ketentuan yang berlaku.',
            ],
        ],
        6 => [
            'title' => 'Rekonsiliasi dan Pencairan Dana',
            'clauses' => [
                'PIHAK PERTAMA melakukan pencatatan transaksi dan rekonsiliasi penjualan tiket berdasarkan data transaksi yang tercatat pada Platform dan kanal pembayaran terkait.',
                'Pencairan dana dilakukan ke rekening terverifikasi yang didaftarkan oleh PIHAK KEDUA setelah memperhitungkan komponen biaya, refund, chargeback, koreksi, atau penyesuaian lain yang sah apabila berlaku.',
                'PARA PIHAK memahami bahwa rincian teknis pencairan dapat mengikuti ketentuan komersial, kebutuhan verifikasi, dan hasil rekonsiliasi transaksi.',
            ],
        ],
        7 => [
            'title' => 'Pembatalan, Penjadwalan Ulang dan Refund',
            'clauses' => [
                'PIHAK KEDUA bertanggung jawab atas keputusan pembatalan, penundaan, penjadwalan ulang, perubahan substansial Event, atau kondisi lain yang mempengaruhi hak Pembeli.',
                'PIHAK KEDUA wajib memberikan informasi yang jelas dan tepat waktu kepada PIHAK PERTAMA agar komunikasi kepada Pembeli dapat dilakukan secara memadai melalui kanal yang tersedia.',
                'Pelaksanaan refund, pengalihan tiket, atau opsi penanganan lain dilakukan sesuai kebijakan Event, hasil koordinasi PARA PIHAK, dan ketentuan kanal pembayaran yang relevan.',
                'PIHAK PERTAMA dapat membantu pelaksanaan proses refund secara administratif dan sistem, tanpa mengambil alih tanggung jawab utama PIHAK KEDUA atas dasar pembatalan atau perubahan Event.',
            ],
        ],
        8 => [
            'title' => 'Hak dan Kewajiban PIHAK PERTAMA',
            'clauses' => [
                'PIHAK PERTAMA berhak menerima data dan instruksi operasional Event yang benar, jelas, dan tepat waktu dari PIHAK KEDUA.',
                'PIHAK PERTAMA berkewajiban menyediakan Platform secara layak untuk kebutuhan penjualan tiket, pemantauan transaksi, dan validasi tiket sesuai kapasitas layanan yang tersedia.',
                'PIHAK PERTAMA berhak melakukan penyesuaian teknis, moderasi konten, atau tindakan pengamanan apabila diperlukan untuk menjaga integritas layanan dan kepatuhan operasional.',
                'PIHAK PERTAMA berkewajiban menjaga kerahasiaan data dan menggunakan informasi yang diterima dari PIHAK KEDUA hanya untuk pelaksanaan kerja sama ini sejauh relevan.',
            ],
        ],
        9 => [
            'title' => 'Hak dan Kewajiban PIHAK KEDUA',
            'clauses' => [
                'PIHAK KEDUA berhak memperoleh akses atas layanan penjualan tiket dan dukungan koordinasi yang wajar dari PIHAK PERTAMA sesuai ruang lingkup kerja sama ini.',
                'PIHAK KEDUA wajib memastikan Event memiliki dasar penyelenggaraan, perizinan, materi promosi, dan informasi publik yang sah serta dapat dipertanggungjawabkan.',
                'PIHAK KEDUA wajib menindaklanjuti kebutuhan klarifikasi, verifikasi, dan penyesuaian data yang diminta PIHAK PERTAMA untuk kepentingan pelaksanaan layanan.',
                'PIHAK KEDUA bertanggung jawab terhadap isi Event, pelaksanaan di lapangan, keselamatan, pihak ketiga yang dilibatkan, dan kepatuhan terhadap ketentuan yang berlaku bagi penyelenggaraan Event.',
            ],
        ],
        10 => [
            'title' => 'Gate System dan Dukungan Hari Event',
            'clauses' => [
                'Apabila digunakan, Gate System dipergunakan sebagai sarana validasi tiket, pencatatan akses masuk, dan dukungan kontrol kehadiran pada Event.',
                'PIHAK KEDUA wajib berkoordinasi dengan PIHAK PERTAMA terkait kebutuhan implementasi Gate System, alur pemeriksaan tiket, dan kesiapan operasional pada hari Event.',
                'PIHAK PERTAMA memberikan dukungan sistem sesuai ketersediaan layanan, sementara kebutuhan personel lapangan, perangkat tambahan, atau skema operasional khusus mengikuti kesepakatan terpisah apabila ada.',
            ],
        ],
        11 => [
            'title' => 'Publikasi, Merek dan Materi Promosi',
            'clauses' => [
                'Masing-masing pihak tetap memiliki hak atas nama, merek, logo, dan materi promosinya masing-masing.',
                'PIHAK KEDUA memberikan izin kepada PIHAK PERTAMA untuk menggunakan nama Event, materi promosi, dan identitas yang diperlukan semata-mata untuk penayangan, pemasaran, dan pelaksanaan kerja sama ini.',
                'PIHAK PERTAMA tidak berhak menggunakan materi PIHAK KEDUA di luar keperluan kerja sama tanpa persetujuan lebih lanjut dari PIHAK KEDUA.',
            ],
        ],
        12 => [
            'title' => 'Data Pribadi, Keamanan dan Kerahasiaan',
            'clauses' => [
                'PARA PIHAK sepakat bahwa data pribadi Pembeli dan data lain yang diperoleh dalam pelaksanaan kerja sama ini hanya digunakan sejauh diperlukan untuk pelayanan transaksi, validasi tiket, komunikasi, dan penyelesaian operasional Event.',
                'Masing-masing pihak wajib menerapkan langkah pengamanan yang wajar untuk mencegah akses tidak sah, penyalahgunaan, kehilangan, atau pengungkapan data tanpa hak.',
                'Informasi bisnis, komersial, maupun operasional yang bersifat tidak untuk umum wajib dijaga kerahasiaannya oleh PARA PIHAK, kecuali diwajibkan untuk diungkapkan berdasarkan permintaan yang sah atau persetujuan pihak yang berhak.',
            ],
        ],
        13 => [
            'title' => 'Kekayaan Intelektual',
            'clauses' => [
                'Hak kekayaan intelektual yang telah dimiliki masing-masing pihak sebelum perjanjian ini tetap menjadi milik pihak yang bersangkutan.',
                'Pelaksanaan kerja sama ini tidak dapat ditafsirkan sebagai pengalihan hak kekayaan intelektual, kecuali dinyatakan tegas secara tertulis oleh PARA PIHAK.',
                'Setiap penggunaan materi, desain, sistem, atau konten milik pihak lain harus dibatasi pada kebutuhan pelaksanaan perjanjian ini dan sesuai izin yang diberikan.',
            ],
        ],
        14 => [
            'title' => 'Fraud, Chargeback dan Penahanan Dana',
            'clauses' => [
                'Apabila ditemukan indikasi fraud, penyalahgunaan transaksi, pelanggaran ketentuan kanal pembayaran, atau chargeback yang memerlukan investigasi, PIHAK PERTAMA berhak melakukan langkah pengamanan yang proporsional.',
                'Langkah pengamanan tersebut dapat berupa pemeriksaan tambahan, penundaan proses tertentu, penyesuaian hasil rekonsiliasi, atau penahanan dana sementara sepanjang diperlukan untuk mitigasi risiko.',
                'PIHAK KEDUA wajib bekerja sama menyediakan informasi dan klarifikasi yang dibutuhkan untuk penanganan fraud, dispute, atau chargeback dimaksud.',
            ],
        ],
        15 => [
            'title' => 'Ketersediaan Layanan dan Pemeliharaan',
            'clauses' => [
                'PIHAK PERTAMA berupaya menjaga ketersediaan layanan Platform secara wajar dan profesional sesuai kemampuan teknis serta kondisi operasional yang ada.',
                'PIHAK PERTAMA dapat melakukan pemeliharaan, perbaikan, peningkatan fitur, atau tindakan teknis lain yang diperlukan untuk keamanan maupun keberlangsungan layanan.',
                'PARA PIHAK memahami bahwa gangguan sistem dapat terjadi karena faktor internal, eksternal, atau pihak ketiga, sehingga koordinasi penanganannya dilakukan berdasarkan prinsip best effort.',
            ],
        ],
        16 => [
            'title' => 'Jangka Waktu dan Pengakhiran',
            'clauses' => [
                'Perjanjian ini berlaku sejak ditandatangani PARA PIHAK dan tetap mengikat sampai seluruh kewajiban terkait Event, transaksi, dan penyelesaian administratif yang relevan dinyatakan selesai.',
                'Perjanjian ini dapat diakhiri lebih awal berdasarkan kesepakatan PARA PIHAK atau karena salah satu pihak tidak memenuhi kewajiban materialnya setelah diberikan pemberitahuan dan kesempatan yang wajar untuk melakukan perbaikan.',
                'Pengakhiran perjanjian tidak menghapus kewajiban yang menurut sifatnya tetap harus dipenuhi setelah pengakhiran, termasuk kewajiban kerahasiaan, penyelesaian transaksi, dan pertanggungjawaban yang masih berjalan.',
            ],
        ],
        17 => [
            'title' => 'Keadaan Kahar / Force Majeure',
            'clauses' => [
                'Keadaan kahar adalah peristiwa di luar kemampuan dan kendali wajar pihak yang terdampak yang secara langsung mempengaruhi pelaksanaan sebagian atau seluruh kewajiban dalam perjanjian ini.',
                'Pihak yang mengalami keadaan kahar wajib memberitahukan kepada pihak lainnya sesegera mungkin disertai penjelasan yang memadai mengenai dampaknya terhadap pelaksanaan perjanjian.',
                'PARA PIHAK akan bermusyawarah untuk menentukan langkah penanganan yang paling wajar selama keadaan kahar berlangsung, termasuk kemungkinan penyesuaian jadwal atau tindakan administratif lain.',
            ],
        ],
        18 => [
            'title' => 'Tanggung Jawab dan Ganti Rugi',
            'clauses' => [
                'Masing-masing pihak bertanggung jawab atas tindakan, kelalaian, pernyataan, dan kewajiban yang berada dalam ruang lingkup kendali serta kewenangannya sendiri.',
                'PIHAK KEDUA bertanggung jawab atas penyelenggaraan Event, kebenaran materi Event, serta klaim pihak ketiga yang timbul dari konten atau pelaksanaan Event yang berada di bawah penguasaannya.',
                'PIHAK PERTAMA bertanggung jawab atas pelaksanaan layanan Platform sesuai ruang lingkup peran yang disepakati dan berdasarkan data yang diterima dari PIHAK KEDUA.',
                'Ganti rugi, apabila timbul, dibahas dan diselesaikan secara proporsional sesuai sebab, kontribusi, dan bukti yang dapat dipertanggungjawabkan.',
            ],
        ],
        19 => [
            'title' => 'Hukum yang Berlaku dan Penyelesaian Sengketa',
            'clauses' => [
                'Perjanjian ini ditafsirkan dan dilaksanakan berdasarkan hukum Republik Indonesia.',
                'Setiap perselisihan yang timbul sehubungan dengan perjanjian ini sedapat mungkin terlebih dahulu diselesaikan melalui musyawarah untuk mufakat oleh PARA PIHAK.',
                'Apabila musyawarah tidak mencapai penyelesaian, sengketa akan diselesaikan melalui mekanisme hukum yang berwenang sesuai ketentuan yang berlaku.',
            ],
        ],
        20 => [
            'title' => 'Ketentuan Lain-Lain',
            'clauses' => [
                'Setiap perubahan, penambahan, atau pengurangan atas perjanjian ini hanya sah apabila dibuat secara tertulis dan disetujui oleh PARA PIHAK.',
                'Perubahan contractual terhadap data atau ketentuan tertentu setelah perjanjian berjalan dapat dituangkan dalam addendum apabila dipandang perlu oleh PARA PIHAK.',
                'Komunikasi resmi terkait pelaksanaan perjanjian ini dilakukan melalui kontak yang dicantumkan PARA PIHAK atau media resmi lain yang kemudian disepakati.',
                'Apabila suatu ketentuan dalam perjanjian ini dinyatakan tidak berlaku atau tidak dapat dilaksanakan, ketentuan lainnya tetap berlaku sepanjang tujuan utama perjanjian masih dapat dijalankan.',
                'Tidak ada pihak yang dapat mengalihkan hak atau kewajiban materialnya berdasarkan perjanjian ini tanpa persetujuan tertulis dari pihak lainnya, sepanjang relevan menurut sifat kewajibannya.',
            ],
        ],
        21 => [
            'title' => 'Penutup',
            'clauses' => [
                'PARA PIHAK menyatakan telah membaca, memahami, dan menyetujui seluruh isi perjanjian ini tanpa adanya paksaan dari pihak mana pun.',
                'Perjanjian ini dibuat sebagai dokumen kerja sama yang menjadi dasar pelaksanaan layanan penjualan dan pengelolaan tiket Event melalui Platform Gotik.',
                'Naskah perjanjian ini berlaku sebagai template unsigned sampai ditandatangani secara sah oleh PARA PIHAK sesuai mekanisme penandatanganan yang berlaku dan disepakati PARA PIHAK.',
            ],
        ],
    ];
@endphp

<!-- mou-v2-contract-shared-body -->
<div class="mou-v2-document">
    <section class="mou-v2-cover">
        <div class="cover-rule"></div>
        <p class="cover-kicker">Dokumen Kerja Sama</p>
        <h1>PERJANJIAN KERJA SAMA</h1>
        <h2>PENJUALAN DAN PENGELOLAAN TIKET EVENT<br>MELALUI PLATFORM GOTIK</h2>

        <div class="cover-parties">
            <p>Antara:</p>
            <div class="cover-party-block">
                <span>PIHAK PERTAMA</span>
                <strong>{{ $display($platformParty['company_name'] ?? null) }}</strong>
            </div>
            <p class="cover-and">dan</p>
            <div class="cover-party-block">
                <span>PIHAK KEDUA</span>
                <strong>{{ $display($organizer['organizer_name'] ?? null) }}</strong>
            </div>
        </div>

        <div class="cover-event-box">
            <p class="cover-event-label">Untuk Event:</p>
            <p class="cover-event-name">{{ $eventName }}</p>
            <dl class="cover-meta">
                <div>
                    <dt>Tanggal Event</dt>
                    <dd>{{ $eventStart }}</dd>
                </div>
                <div>
                    <dt>Venue</dt>
                    <dd>{{ $eventVenue }}</dd>
                </div>
                <div>
                    <dt>Nomor Dokumen</dt>
                    <dd>{{ $documentNumber }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="mou-v2-section">
        <div class="section-heading">
            <span class="section-badge">Pembukaan</span>
            <h3>Pembukaan dan Para Pihak</h3>
        </div>
        <p>
            Pada hari dan tanggal penandatanganan dokumen ini, PARA PIHAK sepakat untuk mengikatkan diri dalam
            Perjanjian Kerja Sama Penjualan dan Pengelolaan Tiket Event melalui Platform Gotik untuk Event
            <strong>{{ $eventName }}</strong> yang selanjutnya diatur menurut syarat dan ketentuan dalam perjanjian ini.
        </p>
        <p>
            PIHAK PERTAMA dan PIHAK KEDUA selanjutnya secara bersama-sama disebut sebagai
            <strong>PARA PIHAK</strong> dan secara sendiri-sendiri disebut sebagai <strong>PIHAK</strong>.
        </p>

        <div class="party-card-grid">
            <article class="party-card">
                <h4>PIHAK PERTAMA</h4>
                <table class="identity-table">
                    @foreach ($platformRows as $label => $value)
                        <tr>
                            <th>{{ $label }}</th>
                            <td>{{ $display($value) }}</td>
                        </tr>
                    @endforeach
                </table>
            </article>

            <article class="party-card">
                <h4>PIHAK KEDUA</h4>
                <table class="identity-table">
                    @foreach ($organizerRows as $label => $value)
                        <tr>
                            <th>{{ $label }}</th>
                            <td>{{ $display($value) }}</td>
                        </tr>
                    @endforeach
                </table>
            </article>
        </div>

        <div class="event-summary">
            <h4>Ringkasan Event</h4>
            <table class="identity-table">
                <tr>
                    <th>Nama Event</th>
                    <td>{{ $eventName }}</td>
                </tr>
                <tr>
                    <th>Tanggal Event</th>
                    <td>{{ $eventStart }}</td>
                </tr>
                <tr>
                    <th>Tanggal Selesai</th>
                    <td>{{ $eventEnd }}</td>
                </tr>
                <tr>
                    <th>Venue</th>
                    <td>{{ $eventVenue }}</td>
                </tr>
                <tr>
                    <th>Lokasi</th>
                    <td>{{ $eventVenueAddress }}{{ $eventLocation !== '-' ? ' - '.$eventLocation : '' }}</td>
                </tr>
                <tr>
                    <th>Nomor Dokumen</th>
                    <td>{{ $documentNumber }}</td>
                </tr>
            </table>
        </div>
    </section>

    @foreach ($articles as $number => $article)
        <section class="mou-v2-section">
            <div class="section-heading">
                <span class="section-badge">PASAL {{ $number }}</span>
                <h3>{{ strtoupper($article['title']) }}</h3>
            </div>
            <ol class="clause-list">
                @foreach ($article['clauses'] as $clause)
                    <li>{{ $clause }}</li>
                @endforeach
            </ol>
        </section>
    @endforeach

    <section class="signature-page">
        <div class="section-heading">
            <span class="section-badge">Halaman Tanda Tangan</span>
            <h3>Persetujuan PARA PIHAK</h3>
        </div>
        <p>
            Dokumen ini merupakan template unsigned. Area di bawah ini disediakan untuk penandatanganan sah oleh
            PARA PIHAK sesuai mekanisme yang berlaku.
        </p>

        <div class="signature-grid">
            <div class="signature-column">
                <h4>PIHAK PERTAMA</h4>
                <div class="signature-space"></div>
                <table class="signature-meta">
                    <tr>
                        <th>Nama</th>
                        <td>{{ $display($platformParty['representative_name'] ?? null) }}</td>
                    </tr>
                    <tr>
                        <th>Jabatan</th>
                        <td>{{ $display($platformParty['representative_position'] ?? null) }}</td>
                    </tr>
                </table>
            </div>

            <div class="signature-column">
                <h4>PIHAK KEDUA</h4>
                <div class="signature-space"></div>
                <table class="signature-meta">
                    <tr>
                        <th>Nama</th>
                        <td>{{ $display($organizer['responsible_name'] ?? null) }}</td>
                    </tr>
                    <tr>
                        <th>Jabatan</th>
                        <td>{{ $display($organizer['responsible_position'] ?? null) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </section>

    <footer class="audit-footer">
        <span>Agreement UID: {{ $agreementUid }}</span>
        <span>Template Version: {{ $templateVersion }}</span>
        <span>Paraf PIHAK PERTAMA: __________</span>
        <span>Paraf PIHAK KEDUA: __________</span>
    </footer>
</div>
