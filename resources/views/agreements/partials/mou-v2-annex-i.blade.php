@php
    $agreement = is_array($payload['agreement'] ?? null) ? $payload['agreement'] : [];
    $event = is_array($payload['event'] ?? null) ? $payload['event'] : [];
    $organizer = is_array($payload['organizer'] ?? null) ? $payload['organizer'] : [];
    $bankAccount = is_array($payload['bank_account'] ?? null) ? $payload['bank_account'] : [];

    $display = static fn ($value, $fallback = '-') => filled($value) ? $value : $fallback;

    $eventRows = [
        'Nama Event' => $event['event_name'] ?? $event['name'] ?? null,
        'Nama Penyelenggara' => $organizer['organizer_name'] ?? null,
        'Tanggal/Waktu Mulai' => $event['start'] ?? null,
        'Tanggal/Waktu Selesai' => $event['end'] ?? null,
        'Venue' => $event['venue_name'] ?? null,
        'Alamat Venue' => $event['venue_address'] ?? null,
        'Kota/Kabupaten' => $event['venue_city'] ?? null,
        'Provinsi' => $event['venue_province'] ?? null,
        'Mulai Penjualan' => $event['start_sale'] ?? null,
    ];

    $bankRows = [
        'Nama Bank' => $bankAccount['bank_name'] ?? null,
        'Nomor Rekening' => $bankAccount['account_number'] ?? null,
        'Atas Nama' => $bankAccount['account_holder_name'] ?? null,
        'Status Verifikasi' => $bankAccount['verification_status'] ?? null,
    ];

    $documentNumber = $display($agreement['document_number'] ?? null);
    $agreementUid = $display($agreement['uid'] ?? null);
    $templateVersion = $display($agreement['template_version'] ?? null);
@endphp

<!-- mou-v2-annex-i-shared-body -->
<section class="mou-v2-annex">
    <div class="annex-heading">
        <p class="annex-kicker">Lampiran Kontraktual</p>
        <h3>LAMPIRAN I</h3>
        <h4>DATA EVENT DAN INFORMASI PENCAIRAN</h4>
        <p class="annex-subtitle">
            Merupakan bagian yang tidak terpisahkan dari Perjanjian Kerja Sama Penjualan dan Pengelolaan Tiket Event
            melalui Platform Gotik.
        </p>
        <p class="annex-doc-number">Nomor Dokumen: {{ $documentNumber }}</p>
    </div>

    <div class="annex-section">
        <div class="annex-section-header">
            <span class="annex-section-badge">Bagian A</span>
            <h5>Data Event</h5>
        </div>

        <table class="annex-table">
            @foreach ($eventRows as $label => $value)
                <tr>
                    <th>{{ $label }}</th>
                    <td>{{ $display($value) }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="annex-section">
        <div class="annex-section-header">
            <span class="annex-section-badge">Bagian B</span>
            <h5>Rekening Pencairan</h5>
        </div>

        <table class="annex-table">
            @foreach ($bankRows as $label => $value)
                <tr>
                    <th>{{ $label }}</th>
                    <td>{{ $display($value) }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="annex-note">
        Data dalam Lampiran I ini mengikuti data kontraktual yang dibekukan pada saat Agreement difinalisasi.
    </div>

    <div class="annex-audit">
        <span>Agreement UID: {{ $agreementUid }}</span>
        <span>Template Version: {{ $templateVersion }}</span>
    </div>
</section>
