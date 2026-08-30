@php
    $agreement = is_array($payload['agreement'] ?? null) ? $payload['agreement'] : [];
    $platformParty = is_array($payload['platform_party'] ?? null) ? $payload['platform_party'] : [];
    $organizer = is_array($payload['organizer'] ?? null) ? $payload['organizer'] : [];
    $bankAccount = is_array($payload['bank_account'] ?? null) ? $payload['bank_account'] : [];
    $organizerLetter = is_array($payload['organizer_letter'] ?? null) ? $payload['organizer_letter'] : [];
    $responsibleIdentity = is_array($payload['responsible_identity'] ?? null) ? $payload['responsible_identity'] : [];

    $display = static fn ($value, $fallback = '-') => filled($value) ? $value : $fallback;
    $documentNumber = $display($agreement['document_number'] ?? null);
    $agreementUid = $display($agreement['uid'] ?? null);
    $templateVersion = $display($agreement['template_version'] ?? null);

    $mapVerificationStatus = static function ($value) use ($display): string {
        return match ($value) {
            'verified' => 'TERVERIFIKASI',
            'rejected' => 'DITOLAK',
            'pending' => 'MENUNGGU VERIFIKASI',
            'missing' => 'BELUM TERSEDIA',
            default => $display($value),
        };
    };

    $organizerComplete = collect([
        $organizer['organizer_name'] ?? null,
        $organizer['responsible_name'] ?? null,
        $organizer['responsible_position'] ?? null,
        $organizer['phone'] ?? null,
        $organizer['email'] ?? null,
        $organizer['address'] ?? null,
    ])->every(fn ($value) => filled($value));

    $bankComplete = collect([
        $bankAccount['bank_name'] ?? null,
        $bankAccount['account_number'] ?? null,
        $bankAccount['account_holder_name'] ?? null,
    ])->every(fn ($value) => filled($value));

    $organizerLetterComplete = collect([
        $organizerLetter['document_number'] ?? null,
        $organizerLetter['document_date'] ?? null,
        $organizerLetter['original_name'] ?? null,
    ])->every(fn ($value) => filled($value));

    $readinessRows = [
        [
            'item' => 'Data Penyelenggara',
            'status' => $organizerComplete ? 'LENGKAP' : 'BELUM LENGKAP',
            'note' => 'Berdasarkan data identitas penyelenggara yang tersedia pada frozen snapshot.',
        ],
        [
            'item' => 'Data Rekening Event',
            'status' => $bankComplete ? 'TERSEDIA' : 'BELUM LENGKAP',
            'note' => 'Berdasarkan nama bank, nomor rekening, dan atas nama pada frozen snapshot.',
        ],
        [
            'item' => 'Verifikasi Rekening Event',
            'status' => $mapVerificationStatus($bankAccount['verification_status'] ?? null),
            'note' => 'Status mengikuti nilai verifikasi rekening pada frozen snapshot.',
        ],
        [
            'item' => 'Buku Rekening Fisik',
            'status' => 'BELUM TERSEDIA',
            'note' => 'Status keberadaan file buku rekening tidak tersedia pada frozen snapshot.',
        ],
        [
            'item' => 'Surat Penyelenggara',
            'status' => $organizerLetterComplete ? 'TERSEDIA' : 'BELUM LENGKAP',
            'note' => 'Berdasarkan nomor surat, tanggal surat, dan nama file yang tersedia pada frozen snapshot.',
        ],
        [
            'item' => 'Verifikasi Surat Penyelenggara',
            'status' => $mapVerificationStatus($organizerLetter['verification_status'] ?? null),
            'note' => 'Status mengikuti nilai verifikasi surat pada frozen snapshot.',
        ],
        [
            'item' => 'Identitas Penanggung Jawab',
            'status' => $mapVerificationStatus($responsibleIdentity['verification_status'] ?? 'missing'),
            'note' => 'Status mengikuti nilai verifikasi identitas penanggung jawab pada frozen snapshot.',
        ],
    ];

    $organizerLetterRows = [
        'Jenis Dokumen' => $organizerLetter['document_type'] ?? null,
        'Nomor Surat' => $organizerLetter['document_number'] ?? null,
        'Tanggal Surat' => $organizerLetter['document_date'] ?? null,
        'Nama File' => $organizerLetter['original_name'] ?? null,
        'Status Verifikasi' => $mapVerificationStatus($organizerLetter['verification_status'] ?? null),
    ];

    $platformContactRows = [
        'Nama Badan Usaha / Pengelola' => $platformParty['company_name'] ?? null,
        'Nama Wakil / Penanggung Jawab' => $platformParty['representative_name'] ?? null,
        'Jabatan' => $platformParty['representative_position'] ?? null,
        'Email Resmi' => $platformParty['email'] ?? null,
        'WhatsApp / Telepon' => $platformParty['phone'] ?? null,
        'Website / Platform' => $platformParty['website'] ?? null,
    ];

    $organizerContactRows = [
        'Nama Penyelenggara' => $organizer['organizer_name'] ?? null,
        'Nama Penanggung Jawab' => $organizer['responsible_name'] ?? null,
        'Jabatan' => $organizer['responsible_position'] ?? null,
        'Email' => $organizer['email'] ?? null,
        'WhatsApp / Telepon' => $organizer['phone'] ?? null,
    ];
@endphp

<!-- mou-v2-annex-ii-shared-body -->
<section class="mou-v2-annex mou-v2-annex-ii">
    <div class="annex-heading">
        <p class="annex-kicker">Lampiran Kontraktual</p>
        <h3>LAMPIRAN II</h3>
        <h4>DOKUMEN, KESIAPAN EVENT DAN KONTAK RESMI</h4>
        <p class="annex-subtitle">
            Merupakan bagian yang tidak terpisahkan dari Perjanjian Kerja Sama Penjualan dan Pengelolaan Tiket Event
            melalui Platform Gotik.
        </p>
        <p class="annex-doc-number">Nomor Dokumen: {{ $documentNumber }}</p>
    </div>

    <div class="annex-section">
        <div class="annex-section-header">
            <span class="annex-section-badge">Bagian A</span>
            <h5>Checklist Dokumen dan Kesiapan</h5>
        </div>

        <table class="annex-table readiness-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($readinessRows as $row)
                    <tr>
                        <td>{{ $row['item'] }}</td>
                        <td class="readiness-status">{{ $row['status'] }}</td>
                        <td>{{ $row['note'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="annex-section">
        <div class="annex-section-header">
            <span class="annex-section-badge">Bagian B</span>
            <h5>Ringkasan Surat Penyelenggara</h5>
        </div>

        <table class="annex-table">
            @foreach ($organizerLetterRows as $label => $value)
                <tr>
                    <th>{{ $label }}</th>
                    <td>{{ $display($value) }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="annex-section">
        <div class="annex-section-header">
            <span class="annex-section-badge">Bagian C</span>
            <h5>Kontak Resmi Pihak Pertama</h5>
        </div>

        <table class="annex-table official-contact-table">
            @foreach ($platformContactRows as $label => $value)
                <tr>
                    <th>{{ $label }}</th>
                    <td>{{ $display($value) }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="annex-section">
        <div class="annex-section-header">
            <span class="annex-section-badge">Bagian D</span>
            <h5>Kontak Resmi Pihak Kedua</h5>
        </div>

        <table class="annex-table official-contact-table">
            @foreach ($organizerContactRows as $label => $value)
                <tr>
                    <th>{{ $label }}</th>
                    <td>{{ $display($value) }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="annex-note">
        Status "BELUM TERSEDIA" pada Lampiran II berarti source aman tidak tersedia pada frozen snapshot dan tidak
        dimaksudkan sebagai kesimpulan atas keberadaan file atau hasil review di luar snapshot tersebut.
    </div>

    <div class="annex-audit">
        <span>Agreement UID: {{ $agreementUid }}</span>
        <span>Template Version: {{ $templateVersion }}</span>
    </div>
</section>
