@php
    $agreement = is_array($payload['agreement'] ?? null) ? $payload['agreement'] : [];
    $event = is_array($payload['event'] ?? null) ? $payload['event'] : [];
    $organizer = is_array($payload['organizer'] ?? null) ? $payload['organizer'] : [];
    $bankAccount = is_array($payload['bank_account'] ?? null) ? $payload['bank_account'] : [];
    $commercial = is_array($payload['commercial'] ?? null) ? $payload['commercial'] : [];

    $display = static fn ($value, $fallback = '-') => filled($value) ? $value : $fallback;
    $formatIdr = static fn ($value) => 'Rp '.number_format((float) ($value ?? 0), 0, ',', '.');
    $formatPercent = static function ($value): string {
        return rtrim(rtrim(number_format((float) ($value ?? 0), 4, '.', ''), '0'), '.') ?: '0';
    };

    $buyerFee = is_array($commercial['buyer_fee'] ?? null)
        ? $commercial['buyer_fee']
        : (is_array($event['buyer_fee'] ?? null) ? $event['buyer_fee'] : ['mode' => 'none', 'value' => 0]);

    $buyerFeeLabel = match ($buyerFee['mode'] ?? 'none') {
        'fixed' => $formatIdr($buyerFee['value'] ?? 0),
        'percent' => $formatPercent($buyerFee['value'] ?? 0).'%',
        default => 'Rp 0 / 0%',
    };

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

    $paymentGateways = collect($commercial['payment_gateways'] ?? [])
        ->filter(fn ($gateway) => is_array($gateway))
        ->values();

    $documentNumber = $display($agreement['document_number'] ?? null);
    $agreementUid = $display($agreement['uid'] ?? null);
    $templateVersion = $display($agreement['template_version'] ?? null);
@endphp

<!-- mou-v2-annex-i-shared-body -->
<section class="mou-v2-annex">
    <div class="annex-heading">
        <p class="annex-kicker">Lampiran Kontraktual</p>
        <h3>LAMPIRAN I</h3>
        <h4>DATA EVENT DAN KETENTUAN KOMERSIAL</h4>
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
            <h5>Biaya Pembeli / Event Fee</h5>
        </div>

        <table class="annex-table">
            <tr>
                <th>Biaya Pembeli / Event Fee</th>
                <td>{{ $buyerFeeLabel }}</td>
            </tr>
        </table>
    </div>

    <div class="annex-section">
        <div class="annex-section-header">
            <span class="annex-section-badge">Bagian C</span>
            <h5>Kanal Pembayaran</h5>
        </div>

        @if ($paymentGateways->isEmpty())
            <div class="annex-empty-state">Belum ada kanal pembayaran yang tercatat dalam snapshot.</div>
        @else
            <table class="gateway-fee-table">
                <thead>
                    <tr>
                        <th>Metode Pembayaran</th>
                        <th>Status Efektif</th>
                        <th>Biaya Kanal Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($paymentGateways as $gateway)
                        <tr>
                            <td>{{ $display($gateway['payment'] ?? null) }}</td>
                            <td>{{ ! empty($gateway['effective_is_active']) ? 'Aktif' : 'Nonaktif' }}</td>
                            <td>{{ $formatIdr($gateway['resolved_fee_fixed'] ?? 0) }} + {{ $formatPercent($gateway['resolved_fee_percent'] ?? 0) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="annex-section">
        <div class="annex-section-header">
            <span class="annex-section-badge">Bagian D</span>
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
        Nilai dalam Lampiran I ini mengikuti data kontraktual yang dibekukan pada saat Agreement difinalisasi.
    </div>

    <div class="annex-audit">
        <span>Agreement UID: {{ $agreementUid }}</span>
        <span>Template Version: {{ $templateVersion }}</span>
    </div>
</section>
