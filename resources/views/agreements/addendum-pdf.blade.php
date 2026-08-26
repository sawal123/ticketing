@php
    $agreement = $payload['agreement'];
    $parentAgreement = $payload['parent_agreement'] ?? null;
    $event = $payload['event'];
    $organizer = $payload['organizer'];
    $diffs = $payload['diffs'] ?? [];

    $display = fn ($value) => filled($value) ? $value : '-';
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Addendum v{{ $agreement['version'] }} - {{ $display($event['event_name']) }}</title>
    <style>
        @page {
            margin: 24mm 18mm 22mm 18mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #0f172a;
            line-height: 1.5;
        }

        .doc-header {
            border-bottom: 3px solid #1e293b;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .doc-title {
            font-size: 17pt;
            font-weight: bold;
            color: #1e293b;
            margin: 0;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .doc-subtitle {
            font-size: 9.5pt;
            color: #475569;
            margin-top: 3px;
        }

        .badge {
            display: inline-block;
            border: 1px solid #6366f1;
            color: #4338ca;
            background-color: #eef2ff;
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 14px;
        }

        .meta-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            font-size: 8.5pt;
            vertical-align: top;
        }

        .meta-label {
            width: 25%;
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
        }

        .meta-value {
            width: 75%;
            color: #0f172a;
        }

        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #1e293b;
            margin-top: 14px;
            margin-bottom: 6px;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
        }

        .diff-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 14px;
        }

        .diff-table th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 8.5pt;
            font-weight: bold;
            text-align: left;
            color: #334155;
        }

        .diff-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 8.5pt;
            vertical-align: top;
        }

        .diff-before {
            color: #b91c1c;
            text-decoration: line-through;
        }

        .diff-after {
            color: #047857;
            font-weight: bold;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }

        .signature-table td {
            width: 50%;
            vertical-align: top;
            padding: 8px 12px;
        }

        .signature-space {
            height: 60px;
        }
    </style>
</head>
<body>
    <div class="doc-header">
        <table style="width: 100%;">
            <tr>
                <td style="vertical-align: top;">
                    <div class="doc-title">ADDENDUM PERJANJIAN KERJASAMA</div>
                    <div class="doc-subtitle">
                        Addendum Dokumen Perjanjian Penyelenggaraan Event & Layanan Tiket
                    </div>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <span class="badge">Addendum v{{ $agreement['version'] }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">ID Dokumen Addendum</td>
            <td class="meta-value" style="font-family: monospace;">{{ $agreement['uid'] }}</td>
        </tr>
        <tr>
            <td class="meta-label">Dokumen Perjanjian Induk</td>
            <td class="meta-value">
                <strong>{{ strtoupper($parentAgreement['type'] ?? 'MOU') }} v{{ $parentAgreement['version'] ?? '1' }}</strong>
                @if(!empty($parentAgreement['uid']))
                    (UID: {{ $parentAgreement['uid'] }})
                @endif
            </td>
        </tr>
        <tr>
            <td class="meta-label">Nama Event</td>
            <td class="meta-value"><strong>{{ $display($event['event_name']) }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Pihak Penyelenggara</td>
            <td class="meta-value">{{ $display($organizer['organizer_name']) }} ({{ $display($organizer['responsible_name']) }})</td>
        </tr>
    </table>

    <div class="section-title">Pasal 1: Dasar & Tujuan Addendum</div>
    <p style="margin: 6px 0; text-align: justify;">
        Dokumen Addendum ini merupakan satu kesatuan dan bagian yang tidak terpisahkan dari Dokumen Perjanjian Kerjasama (MOU) Induk sebelumnya. Addendum ini dibuat dan ditandatangani untuk memperbarui butir-butir kesepakatan kontraktual antara Pihak Platform dan Pihak Penyelenggara terkait pelaksanaan Event <strong>{{ $display($event['event_name']) }}</strong>.
    </p>

    <div class="section-title">Pasal 2: Perubahan Ketentuan Kontraktual</div>
    @if (!empty($diffs))
        <table class="diff-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Kategori</th>
                    <th style="width: 30%;">Ketentuan / Parameter</th>
                    <th style="width: 25%;">Ketentuan Sebelumnya</th>
                    <th style="width: 25%;">Ketentuan Baru / Pengganti</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($diffs as $diff)
                    <tr>
                        <td><strong>{{ $diff['section'] }}</strong></td>
                        <td>{{ $diff['label'] }}</td>
                        <td class="diff-before">{{ $diff['before'] }}</td>
                        <td class="diff-after">{{ $diff['after'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="margin: 6px 0; color: #64748b; font-style: italic;">
            (Tidak ada butir kontraktual spesifik yang diubah).
        </p>
    @endif

    <div class="section-title">Pasal 3: Keberlakuan Ketentuan Lain</div>
    <p style="margin: 6px 0; text-align: justify;">
        Segala ketentuan, hak, kewajiban, dan syarat-syarat yang tercantum dalam Perjanjian Induk yang tidak secara tegas diubah dalam Addendum ini dinyatakan tetap berlaku sah dan mengikat kedua belah pihak.
    </p>

    <table class="signature-table">
        <tr>
            <td>
                <strong>PIHAK PERTAMA</strong><br>
                <span>Pihak Pengelola Platform</span>
                <div class="signature-space"></div>
                <strong>( Tim Gotik Indonesia )</strong>
            </td>
            <td>
                <strong>PIHAK KEDUA</strong><br>
                <span>{{ $display($organizer['organizer_name']) }}</span>
                <div class="signature-space"></div>
                <strong>( {{ $display($organizer['responsible_name']) }} )</strong>
            </td>
        </tr>
    </table>
</body>
</html>
