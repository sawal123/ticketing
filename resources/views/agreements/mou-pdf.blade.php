@php
    $agreement = $payload['agreement'];
    $event = $payload['event'];
    $organizer = $payload['organizer'];
    $bankAccount = $payload['bank_account'];
    $organizerLetter = $payload['organizer_letter'];
    $commercial = $payload['commercial'];

    $buyerFee = $commercial['buyer_fee'] ?? $event['buyer_fee'] ?? ['mode' => 'none', 'value' => 0];
    $buyerFeeMode = $buyerFee['mode'] ?? 'none';
    $buyerFeeValue = (float) ($buyerFee['value'] ?? 0);

    $buyerFeeLabel = match ($buyerFeeMode) {
        'percent' => number_format($buyerFeeValue, 0, ',', '.') . '%',
        'fixed' => 'Rp ' . number_format($buyerFeeValue, 0, ',', '.'),
        default => 'Rp 0 / 0%',
    };

    $buyerFeeModeLabel = match ($buyerFeeMode) {
        'percent' => 'Persentase',
        'fixed' => 'Nominal Tetap',
        default => '-',
    };

    $formatIdr = fn ($value) => 'Rp ' . number_format((float) ($value ?? 0), 0, ',', '.');
    $formatPercent = function ($value) {
        $normalized = rtrim(rtrim(number_format((float) ($value ?? 0), 4, '.', ''), '0'), '.');

        return $normalized === '' ? '0' : $normalized;
    };
    $display = fn ($value) => filled($value) ? $value : '-';
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>MOU Event {{ $display($event['event_name']) }}</title>
    <style>
        @page {
            margin: 24mm 18mm 22mm 18mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5pt;
            color: #0f172a;
            line-height: 1.5;
        }

        .doc-header {
            border-bottom: 3px solid #1e293b;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .doc-title {
            font-size: 19pt;
            font-weight: bold;
            color: #1e293b;
            margin: 0;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .doc-subtitle {
            font-size: 9pt;
            color: #475569;
            margin-top: 2px;
        }

        .badge {
            display: inline-block;
            border: 1px solid #0ea5e9;
            color: #0369a1;
            background-color: #e0f2fe;
            border-radius: 8px;
            padding: 2px 10px;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .meta-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 9pt;
            vertical-align: top;
        }

        .meta-table td.label {
            width: 30%;
            background-color: #f1f5f9;
            font-weight: bold;
            color: #334155;
        }

        .meta-table td.value {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 8.5pt;
        }

        h2 {
            font-size: 11pt;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #94a3b8;
            padding-bottom: 4px;
            margin: 18px 0 8px 0;
        }

        .section-note {
            font-size: 8.5pt;
            color: #64748b;
            margin: 2px 0 8px 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }

        .data-table th {
            text-align: left;
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: top;
        }

        .data-table td.label {
            width: 32%;
            color: #475569;
            background-color: #f8fafc;
        }

        .footer-note {
            margin-top: 20px;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-size: 8pt;
            color: #64748b;
        }

        .gateway-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-top: 6px;
        }

        .gateway-table th,
        .gateway-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            text-align: left;
        }

        .gateway-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 8pt;
            text-transform: uppercase;
        }

        .gateway-active {
            color: #047857;
            font-weight: bold;
        }

        .gateway-inactive {
            color: #b91c1c;
        }
    </style>
</head>
<body>
    <div class="doc-header">
        <p class="doc-title">MOU Event</p>
        <p class="doc-subtitle">
            Perjanjian Data Kontraktual Penyelenggaraan Event &mdash; Salinan Belum Ditandatangani
        </p>
        <div style="margin-top: 8px;">
            <span class="badge">Unsigned</span>
        </div>
    </div>

    <table class="meta-table">
        <tr>
            <td class="label">Agreement UID</td>
            <td class="value">{{ $display($agreement['uid']) }}</td>
            <td class="label">Status</td>
            <td class="value">{{ $display($agreement['status']) }}</td>
        </tr>
        <tr>
            <td class="label">Versi</td>
            <td class="value">{{ (int) ($agreement['version'] ?? 1) }}</td>
            <td class="label">Template Version</td>
            <td class="value">{{ $display($agreement['template_version']) }}</td>
        </tr>
        <tr>
            <td class="label">Tipe</td>
            <td class="value">{{ strtoupper((string) ($agreement['type'] ?? 'mou')) }}</td>
            <td class="label">Nomor Dokumen</td>
            <td class="value">{{ $display($agreement['document_number']) }}</td>
        </tr>
    </table>

    <h2>Identitas Penyelenggara</h2>
    <table class="data-table">
        <tr>
            <td class="label">Nama Penyelenggara</td>
            <td>{{ $display($organizer['organizer_name']) }}</td>
        </tr>
        <tr>
            <td class="label">Penanggung Jawab</td>
            <td>{{ $display($organizer['responsible_name']) }}
                @if (filled($organizer['responsible_position']))
                    ({{ $organizer['responsible_position'] }})
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Kontak</td>
            <td>{{ $display($organizer['phone']) }} &mdash; {{ $display($organizer['email']) }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td>{{ $display($organizer['address']) }}</td>
        </tr>
    </table>

    <h2>Informasi Event</h2>
    <table class="data-table">
        <tr>
            <td class="label">Nama Event</td>
            <td>{{ $display($event['event_name']) }}</td>
        </tr>
        <tr>
            <td class="label">Mulai Penjualan</td>
            <td>{{ $display($event['start_sale']) }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Mulai Event</td>
            <td>{{ $display($event['start']) }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Selesai Event</td>
            <td>{{ $display($event['end']) }}</td>
        </tr>
        <tr>
            <td class="label">Venue</td>
            <td>{{ $display($event['venue_name']) }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Venue</td>
            <td>{{ $display($event['venue_address']) }}</td>
        </tr>
        <tr>
            <td class="label">Kota / Kabupaten</td>
            <td>{{ $display($event['venue_city']) }}</td>
        </tr>
        <tr>
            <td class="label">Provinsi</td>
            <td>{{ $display($event['venue_province']) }}</td>
        </tr>
        <tr>
            <td class="label">Biaya Pembeli (Buyer Fee)</td>
            <td>{{ $buyerFeeLabel }} ({{ $buyerFeeModeLabel }})</td>
        </tr>
    </table>

    <h2>Rekening Pencairan</h2>
    <table class="data-table">
        <tr>
            <td class="label">Nama Bank</td>
            <td>{{ $display($bankAccount['bank_name']) }}</td>
        </tr>
        <tr>
            <td class="label">Nomor Rekening</td>
            <td>{{ $display($bankAccount['account_number']) }}</td>
        </tr>
        <tr>
            <td class="label">Atas Nama</td>
            <td>{{ $display($bankAccount['account_holder_name']) }}</td>
        </tr>
        <tr>
            <td class="label">Status Verifikasi</td>
            <td>{{ strtoupper((string) $display($bankAccount['verification_status'])) }}</td>
        </tr>
    </table>

    <h2>Surat Penyelenggara</h2>
    <table class="data-table">
        <tr>
            <td class="label">Jenis Dokumen</td>
            <td>{{ $display($organizerLetter['document_type']) }}</td>
        </tr>
        <tr>
            <td class="label">Nomor Surat</td>
            <td>{{ $display($organizerLetter['document_number']) }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Surat</td>
            <td>{{ $display($organizerLetter['document_date']) }}</td>
        </tr>
        <tr>
            <td class="label">Nama File</td>
            <td>{{ $display($organizerLetter['original_name']) }}</td>
        </tr>
        <tr>
            <td class="label">Status Verifikasi</td>
            <td>{{ strtoupper((string) $display($organizerLetter['verification_status'])) }}</td>
        </tr>
    </table>

    <h2>Konfigurasi Komersial</h2>
    <p class="section-note">
        Kondisi komersial di bawah ini dibekukan pada saat finalisasi MOU dan tidak mengikuti perubahan konfigurasi
        terkini.
    </p>
    <table class="data-table">
        <tr>
            <td class="label">Biaya Pembeli (Buyer Fee)</td>
            <td>{{ $buyerFeeLabel }} ({{ $buyerFeeModeLabel }})</td>
        </tr>
        <tr>
            <td class="label">Payment OTP</td>
            <td>{{ ! empty($commercial['payment_otp_enabled']) ? 'Aktif' : 'Nonaktif' }}</td>
        </tr>
    </table>

    @if (! empty($commercial['payment_gateways']))
        <table class="gateway-table">
            <thead>
                <tr>
                    <th>Gateway</th>
                    <th>Status Efektif</th>
                    <th>Fee Mode</th>
                    <th>Resolved Fixed</th>
                    <th>Resolved Percent</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($commercial['payment_gateways'] as $gateway)
                    <tr>
                        <td>{{ $display($gateway['payment']) }}</td>
                        <td class="{{ ! empty($gateway['effective_is_active']) ? 'gateway-active' : 'gateway-inactive' }}">
                            {{ ! empty($gateway['effective_is_active']) ? 'Aktif' : 'Nonaktif' }}
                        </td>
                        <td>{{ strtoupper((string) ($gateway['fee_mode'] ?? 'global')) }}</td>
                        <td>{{ $formatIdr($gateway['resolved_fee_fixed']) }}</td>
                        <td>{{ $formatPercent($gateway['resolved_fee_percent']) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer-note">
        Dokumen ini adalah salinan unsigned (belum ditandatangani) yang dibekukan dari data kontraktual pada saat
        finalisasi MOU (template {{ $display($agreement['template_version']) }}). Perubahan data live setelah finalisasi
        tidak mengubah isi dokumen ini.
    </div>
</body>
</html>
