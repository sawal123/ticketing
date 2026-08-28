@php
    $agreement = $payload['agreement'];
    $parentAgreement = $payload['parent_agreement'] ?? null;
    $event = $payload['event'];
    $platformParty = is_array($payload['platform_party'] ?? null) ? $payload['platform_party'] : [];
    $organizer = $payload['organizer'];
    $diffs = $payload['diffs'] ?? [];

    $display = fn ($value) => filled($value) ? $value : '-';
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Addendum V2 {{ $display($event['event_name'] ?? null) }}</title>
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
            line-height: 1.55;
        }

        p {
            margin: 0 0 10px;
            text-align: justify;
        }

        .doc-header {
            border-bottom: 3px solid #163b68;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .doc-title {
            margin: 0;
            font-size: 17pt;
            font-weight: bold;
            color: #163b68;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .doc-subtitle {
            margin-top: 4px;
            font-size: 9.5pt;
            color: #475569;
        }

        .badge {
            display: inline-block;
            border: 1px solid #bfdbfe;
            color: #163b68;
            background: #eff6ff;
            border-radius: 999px;
            padding: 3px 10px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .meta-table,
        .party-table,
        .diff-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table {
            margin-bottom: 16px;
        }

        .meta-table td,
        .party-table td,
        .diff-table th,
        .diff-table td,
        .signature-table td {
            border: 1px solid #d5deea;
            padding: 7px 9px;
            vertical-align: top;
        }

        .meta-label,
        .party-label {
            width: 28%;
            background: #eef4fb;
            color: #163b68;
            font-weight: bold;
        }

        .section-title {
            margin: 18px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #d5deea;
            font-size: 11pt;
            font-weight: bold;
            color: #163b68;
            text-transform: uppercase;
        }

        .party-grid {
            margin-bottom: 16px;
        }

        .party-cell {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }

        .party-cell + .party-cell {
            margin-left: 3%;
        }

        .diff-table {
            margin: 8px 0 16px;
        }

        .diff-table th {
            background: #eef4fb;
            color: #163b68;
            text-align: left;
            font-size: 8.5pt;
        }

        .diff-before {
            color: #b91c1c;
            text-decoration: line-through;
        }

        .diff-after {
            color: #047857;
            font-weight: bold;
        }

        .empty-state {
            padding: 10px 12px;
            border: 1px solid #d5deea;
            background: #f8fbff;
            color: #475569;
            font-style: italic;
        }

        .signature-table {
            margin-top: 24px;
        }

        .signature-space {
            height: 70px;
        }
    </style>
</head>
<body>
    <div class="doc-header">
        <table style="width: 100%;">
            <tr>
                <td style="vertical-align: top;">
                    <div class="doc-title">Addendum Perjanjian Kerjasama</div>
                    <div class="doc-subtitle">Perubahan atas perjanjian penyelenggaraan event dan layanan tiket</div>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <span class="badge">{{ strtoupper($display($agreement['template_version'] ?? null)) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Dokumen Addendum</td>
            <td>{{ $display($agreement['document_number'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="meta-label">UID Addendum</td>
            <td style="font-family: monospace;">{{ $agreement['uid'] }}</td>
        </tr>
        <tr>
            <td class="meta-label">Perjanjian Induk</td>
            <td>
                <strong>{{ strtoupper($parentAgreement['type'] ?? 'mou') }} v{{ $parentAgreement['version'] ?? '1' }}</strong>
                @if (!empty($parentAgreement['document_number']))
                    / {{ $parentAgreement['document_number'] }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="meta-label">Template Induk</td>
            <td>{{ strtoupper($display($parentAgreement['template_version'] ?? null)) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Event</td>
            <td><strong>{{ $display($event['event_name'] ?? null) }}</strong></td>
        </tr>
    </table>

    <div class="section-title">Para Pihak</div>
    <div class="party-grid">
        <div class="party-cell">
            <table class="party-table">
                <tr>
                    <td class="party-label">PIHAK PERTAMA</td>
                    <td>{{ $display($platformParty['company_name'] ?? null) }}</td>
                </tr>
                <tr>
                    <td class="party-label">Wakil</td>
                    <td>{{ $display($platformParty['representative_name'] ?? null) }}</td>
                </tr>
                <tr>
                    <td class="party-label">Jabatan</td>
                    <td>{{ $display($platformParty['representative_position'] ?? null) }}</td>
                </tr>
            </table>
        </div>
        <div class="party-cell">
            <table class="party-table">
                <tr>
                    <td class="party-label">PIHAK KEDUA</td>
                    <td>{{ $display($organizer['organizer_name'] ?? null) }}</td>
                </tr>
                <tr>
                    <td class="party-label">Wakil</td>
                    <td>{{ $display($organizer['responsible_name'] ?? null) }}</td>
                </tr>
                <tr>
                    <td class="party-label">Jabatan</td>
                    <td>{{ $display($organizer['responsible_position'] ?? null) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section-title">Pasal 1: Dasar Addendum</div>
    <p>
        Addendum ini merupakan bagian yang tidak terpisahkan dari Perjanjian Induk antara PIHAK PERTAMA dan
        PIHAK KEDUA terkait penyelenggaraan event <strong>{{ $display($event['event_name'] ?? null) }}</strong>.
        Addendum ini dibuat untuk mencatat perubahan ketentuan kontraktual yang disepakati PARA PIHAK setelah
        Perjanjian Induk berlaku.
    </p>

    <div class="section-title">Pasal 2: Perubahan Ketentuan</div>
    @if (!empty($diffs))
        <table class="diff-table">
            <thead>
                <tr>
                    <th style="width: 18%;">Kategori</th>
                    <th style="width: 30%;">Ketentuan</th>
                    <th style="width: 26%;">Sebelumnya</th>
                    <th style="width: 26%;">Menjadi</th>
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
        <div class="empty-state">(Tidak ada perubahan kontraktual yang tercatat.)</div>
    @endif

    <div class="section-title">Pasal 3: Ketentuan Lain</div>
    <p>
        Ketentuan dalam Perjanjian Induk yang tidak diubah secara tegas dalam Addendum ini tetap berlaku sah
        dan mengikat PARA PIHAK.
    </p>

    <table class="signature-table">
        <tr>
            <td style="width: 50%; padding-right: 12px;">
                <strong>PIHAK PERTAMA</strong><br>
                <span>{{ $display($platformParty['company_name'] ?? null) }}</span>
                <div class="signature-space"></div>
                <strong>( {{ $display($platformParty['representative_name'] ?? null) }} )</strong><br>
                <span>{{ $display($platformParty['representative_position'] ?? null) }}</span>
            </td>
            <td style="width: 50%; padding-left: 12px;">
                <strong>PIHAK KEDUA</strong><br>
                <span>{{ $display($organizer['organizer_name'] ?? null) }}</span>
                <div class="signature-space"></div>
                <strong>( {{ $display($organizer['responsible_name'] ?? null) }} )</strong><br>
                <span>{{ $display($organizer['responsible_position'] ?? null) }}</span>
            </td>
        </tr>
    </table>
</body>
</html>
