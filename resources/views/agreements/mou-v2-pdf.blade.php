<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>MOU V2 {{ filled($payload['event']['event_name'] ?? $payload['event']['name'] ?? null) ? $payload['event']['event_name'] ?? $payload['event']['name'] : 'Event' }}</title>
    <style>
        @page {
            margin: 20mm 16mm 24mm 16mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0 0 22mm;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5pt;
            line-height: 1.6;
            color: #0f172a;
            background: #ffffff;
        }

        p {
            margin: 0 0 10px;
            text-align: justify;
        }

        .mou-v2-cover,
        .mou-v2-section,
        .signature-page {
            page-break-inside: avoid;
        }

        .mou-v2-cover {
            min-height: 245mm;
            padding: 18mm 8mm 10mm;
            text-align: center;
            page-break-after: always;
        }

        .cover-rule {
            width: 84px;
            height: 4px;
            margin: 0 auto 16px;
            background: #163b68;
        }

        .cover-kicker,
        .section-badge {
            margin: 0;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: #36567e;
        }

        h1,
        h2,
        h3,
        h4 {
            margin: 0;
            color: #163b68;
        }

        h1 {
            font-size: 23pt;
            letter-spacing: 0.08em;
            margin-top: 12mm;
        }

        h2 {
            font-size: 14pt;
            line-height: 1.55;
            margin-top: 8mm;
        }

        h3 {
            font-size: 13pt;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            page-break-after: avoid;
        }

        h4 {
            font-size: 11pt;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 10px;
        }

        .cover-parties {
            margin-top: 22mm;
        }

        .cover-parties p {
            text-align: center;
        }

        .cover-party-block {
            margin: 12px 0;
        }

        .cover-party-block span {
            display: block;
            font-size: 9pt;
            letter-spacing: 0.16em;
            color: #475569;
            text-transform: uppercase;
        }

        .cover-party-block strong,
        .cover-event-name {
            display: block;
            margin-top: 8px;
            font-size: 14pt;
            color: #0f172a;
        }

        .cover-and {
            margin: 8px 0;
            font-style: italic;
            color: #475569;
        }

        .cover-event-box {
            margin-top: 22mm;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 16px 18px;
            text-align: left;
        }

        .cover-event-label {
            text-align: left;
            color: #475569;
            margin-bottom: 6px;
        }

        .cover-meta,
        .identity-table,
        .signature-meta {
            width: 100%;
            border-collapse: collapse;
        }

        .cover-meta div {
            padding: 8px 0;
            border-top: 1px solid #e2e8f0;
        }

        .cover-meta dt {
            font-size: 8.5pt;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }

        .cover-meta dd {
            margin: 4px 0 0;
            color: #0f172a;
        }

        .mou-v2-section,
        .signature-page {
            margin-bottom: 18px;
        }

        .section-heading {
            border-top: 2px solid #163b68;
            border-bottom: 1px solid #cbd5e1;
            padding: 10px 0 8px;
            margin-bottom: 12px;
        }

        .party-card-grid {
            width: 100%;
            margin: 16px 0;
        }

        .party-card {
            margin-bottom: 16px;
            padding: 14px;
            border: 1px solid #dbe3ee;
            border-radius: 10px;
            background: #f8fbff;
        }

        .identity-table th,
        .identity-table td,
        .signature-meta th,
        .signature-meta td {
            border: 1px solid #d5deea;
            padding: 7px 9px;
            vertical-align: top;
            text-align: left;
        }

        .identity-table th,
        .signature-meta th {
            width: 34%;
            background: #eef4fb;
            color: #1e3a5f;
            font-size: 9pt;
        }

        .event-summary {
            margin-top: 18px;
        }

        .clause-list {
            margin: 0;
            padding-left: 20px;
        }

        .clause-list li {
            margin-bottom: 8px;
            text-align: justify;
        }

        .signature-page {
            page-break-before: always;
            min-height: 220mm;
        }

        .signature-grid {
            width: 100%;
            margin-top: 24px;
        }

        .signature-column {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }

        .signature-column + .signature-column {
            margin-left: 3%;
        }

        .signature-space {
            height: 95px;
            margin: 16px 0 14px;
            border-bottom: 1px solid #334155;
        }

        .audit-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -8mm;
            padding: 0 16mm;
            font-size: 7.5pt;
            color: #64748b;
            border-top: 1px solid #dbe3ee;
        }

        .audit-footer span {
            display: inline-block;
            margin-right: 14px;
        }

        .mou-v2-annex {
            page-break-before: always;
            margin-bottom: 18px;
        }

        .annex-heading {
            margin-bottom: 16px;
            padding: 14px 0 12px;
            border-top: 2px solid #163b68;
            border-bottom: 1px solid #cbd5e1;
            page-break-after: avoid;
        }

        .annex-kicker,
        .annex-section-badge {
            margin: 0;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: #36567e;
        }

        .annex-heading h3 {
            margin-top: 6px;
            font-size: 16pt;
            letter-spacing: 0.06em;
        }

        .annex-heading h4,
        .annex-section-header h5 {
            margin-top: 6px;
            font-size: 11pt;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #163b68;
        }

        .annex-subtitle,
        .annex-doc-number {
            margin: 8px 0 0;
            color: #475569;
        }

        .annex-section {
            margin-bottom: 18px;
        }

        .annex-section-header {
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #dbe3ee;
            page-break-after: avoid;
        }

        .annex-table,
        .gateway-fee-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .annex-table th,
        .annex-table td,
        .gateway-fee-table th,
        .gateway-fee-table td {
            border: 1px solid #d5deea;
            padding: 8px 9px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
        }

        .annex-table th,
        .gateway-fee-table th {
            background: #eef4fb;
            color: #1e3a5f;
            font-size: 9pt;
        }

        .gateway-fee-table {
            page-break-inside: auto;
        }

        .gateway-fee-table thead {
            display: table-header-group;
        }

        .gateway-fee-table tbody {
            display: table-row-group;
        }

        .gateway-fee-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .annex-table th {
            width: 34%;
        }

        .annex-empty-state,
        .annex-note,
        .annex-audit {
            padding: 10px 12px;
            border: 1px solid #dbe3ee;
            border-radius: 8px;
            background: #f8fbff;
            color: #475569;
        }

        .annex-note {
            margin-top: 10px;
        }

        .annex-audit {
            margin-top: 12px;
            font-size: 8pt;
            color: #64748b;
        }

        .annex-audit span {
            display: inline-block;
            margin-right: 14px;
        }
    </style>
</head>
<body>
    @include('agreements.partials.mou-v2-contract', ['payload' => $payload])
    @include('agreements.partials.mou-v2-annex-i', ['payload' => $payload])
</body>
</html>
