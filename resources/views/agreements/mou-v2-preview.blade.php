<style>
    .mou-v2-preview-shell {
        border: 1px solid #dbe3ee;
        border-radius: 28px;
        background:
            radial-gradient(circle at top right, rgba(191, 219, 254, 0.55), transparent 28%),
            linear-gradient(180deg, #f8fbff 0%, #ffffff 18%);
        box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .mou-v2-preview-header {
        padding: 28px 32px 22px;
        border-bottom: 1px solid #dbe3ee;
        background: rgba(255, 255, 255, 0.9);
    }

    .mou-v2-preview-header p {
        margin: 0;
    }

    .mou-v2-preview-header .eyebrow {
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.32em;
        text-transform: uppercase;
        color: #64748b;
    }

    .mou-v2-preview-header h3 {
        margin: 10px 0 0;
        font-size: 29px;
        line-height: 1.1;
        color: #163b68;
    }

    .mou-v2-preview-header .summary {
        margin-top: 10px;
        max-width: 720px;
        font-size: 14px;
        line-height: 1.7;
        color: #475569;
    }

    .mou-v2-preview-body {
        padding: 32px;
    }

    .mou-v2-preview-body .mou-v2-document {
        max-width: 860px;
        margin: 0 auto;
        padding: 40px 42px 28px;
        border: 1px solid #dbe3ee;
        border-radius: 24px;
        background: #ffffff;
        font-family: 'DejaVu Sans', sans-serif;
        color: #0f172a;
        line-height: 1.7;
    }

    .mou-v2-preview-body p {
        margin: 0 0 12px;
        text-align: justify;
    }

    .mou-v2-preview-body .mou-v2-cover {
        min-height: 0;
        padding-bottom: 34px;
        margin-bottom: 34px;
        border-bottom: 1px dashed #cbd5e1;
        text-align: center;
    }

    .mou-v2-preview-body .cover-rule {
        width: 92px;
        height: 4px;
        margin: 0 auto 18px;
        background: #163b68;
    }

    .mou-v2-preview-body .cover-kicker,
    .mou-v2-preview-body .section-badge {
        margin: 0;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.24em;
        text-transform: uppercase;
        color: #36567e;
    }

    .mou-v2-preview-body h1,
    .mou-v2-preview-body h2,
    .mou-v2-preview-body h3,
    .mou-v2-preview-body h4 {
        margin: 0;
        color: #163b68;
    }

    .mou-v2-preview-body h1 {
        margin-top: 42px;
        font-size: 34px;
        letter-spacing: 0.08em;
    }

    .mou-v2-preview-body h2 {
        margin-top: 18px;
        font-size: 22px;
        line-height: 1.5;
    }

    .mou-v2-preview-body h3 {
        font-size: 20px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .mou-v2-preview-body h4 {
        margin-bottom: 12px;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .mou-v2-preview-body .cover-parties {
        margin-top: 42px;
    }

    .mou-v2-preview-body .cover-parties p {
        text-align: center;
    }

    .mou-v2-preview-body .cover-party-block {
        margin: 14px 0;
    }

    .mou-v2-preview-body .cover-party-block span {
        display: block;
        font-size: 12px;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #64748b;
    }

    .mou-v2-preview-body .cover-party-block strong,
    .mou-v2-preview-body .cover-event-name {
        display: block;
        margin-top: 8px;
        font-size: 22px;
        color: #0f172a;
    }

    .mou-v2-preview-body .cover-and {
        margin: 8px 0;
        color: #64748b;
        font-style: italic;
    }

    .mou-v2-preview-body .cover-event-box {
        margin-top: 38px;
        border: 1px solid #dbe3ee;
        border-radius: 18px;
        padding: 18px 20px;
        background: #f8fbff;
        text-align: left;
    }

    .mou-v2-preview-body .cover-event-label {
        text-align: left;
        color: #475569;
        margin-bottom: 6px;
    }

    .mou-v2-preview-body .cover-meta div {
        padding: 10px 0;
        border-top: 1px solid #dbe3ee;
    }

    .mou-v2-preview-body .cover-meta dt {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #64748b;
    }

    .mou-v2-preview-body .cover-meta dd {
        margin: 4px 0 0;
    }

    .mou-v2-preview-body .mou-v2-section,
    .mou-v2-preview-body .signature-page {
        margin-bottom: 24px;
    }

    .mou-v2-preview-body .section-heading {
        margin-bottom: 14px;
        padding: 12px 0 10px;
        border-top: 2px solid #163b68;
        border-bottom: 1px solid #dbe3ee;
    }

    .mou-v2-preview-body .party-card-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
        margin: 18px 0;
    }

    .mou-v2-preview-body .party-card {
        padding: 16px;
        border: 1px solid #dbe3ee;
        border-radius: 16px;
        background: #f8fbff;
    }

    .mou-v2-preview-body .identity-table,
    .mou-v2-preview-body .signature-meta {
        width: 100%;
        border-collapse: collapse;
    }

    .mou-v2-preview-body .identity-table th,
    .mou-v2-preview-body .identity-table td,
    .mou-v2-preview-body .signature-meta th,
    .mou-v2-preview-body .signature-meta td {
        padding: 9px 10px;
        border: 1px solid #dbe3ee;
        text-align: left;
        vertical-align: top;
    }

    .mou-v2-preview-body .identity-table th,
    .mou-v2-preview-body .signature-meta th {
        width: 34%;
        background: #eef4fb;
        color: #1e3a5f;
        font-size: 13px;
    }

    .mou-v2-preview-body .event-summary {
        margin-top: 18px;
    }

    .mou-v2-preview-body .clause-list {
        margin: 0;
        padding-left: 22px;
    }

    .mou-v2-preview-body .clause-list li {
        margin-bottom: 9px;
        text-align: justify;
    }

    .mou-v2-preview-body .signature-page {
        margin-top: 40px;
        padding-top: 10px;
        border-top: 1px dashed #cbd5e1;
    }

    .mou-v2-preview-body .signature-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px;
        margin-top: 22px;
    }

    .mou-v2-preview-body .signature-column {
        padding: 18px;
        border: 1px solid #dbe3ee;
        border-radius: 18px;
        background: #fcfdff;
    }

    .mou-v2-preview-body .signature-space {
        height: 120px;
        margin: 18px 0 14px;
        border-bottom: 1px solid #334155;
    }

    .mou-v2-preview-body .audit-footer {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 18px;
        margin-top: 28px;
        padding-top: 16px;
        border-top: 1px solid #dbe3ee;
        font-size: 12px;
        color: #64748b;
    }

    .mou-v2-preview-body .mou-v2-annex {
        margin-top: 42px;
        padding-top: 12px;
        border-top: 1px dashed #cbd5e1;
    }

    .mou-v2-preview-body .annex-heading {
        margin-bottom: 18px;
        padding: 14px 0 12px;
        border-top: 2px solid #163b68;
        border-bottom: 1px solid #dbe3ee;
    }

    .mou-v2-preview-body .annex-kicker,
    .mou-v2-preview-body .annex-section-badge {
        margin: 0;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.24em;
        text-transform: uppercase;
        color: #36567e;
    }

    .mou-v2-preview-body .annex-heading h3 {
        margin-top: 8px;
        font-size: 24px;
        letter-spacing: 0.06em;
    }

    .mou-v2-preview-body .annex-heading h4,
    .mou-v2-preview-body .annex-section-header h5 {
        margin-top: 8px;
        font-size: 18px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #163b68;
    }

    .mou-v2-preview-body .annex-subtitle,
    .mou-v2-preview-body .annex-doc-number {
        margin: 10px 0 0;
        color: #475569;
    }

    .mou-v2-preview-body .annex-section {
        margin-bottom: 22px;
    }

    .mou-v2-preview-body .annex-section-header {
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #dbe3ee;
    }

    .mou-v2-preview-body .annex-table,
    .mou-v2-preview-body .gateway-fee-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .mou-v2-preview-body .annex-table th,
    .mou-v2-preview-body .annex-table td,
    .mou-v2-preview-body .gateway-fee-table th,
    .mou-v2-preview-body .gateway-fee-table td {
        padding: 10px 11px;
        border: 1px solid #dbe3ee;
        text-align: left;
        vertical-align: top;
        word-break: break-word;
    }

    .mou-v2-preview-body .annex-table th,
    .mou-v2-preview-body .gateway-fee-table th {
        background: #eef4fb;
        color: #1e3a5f;
        font-size: 13px;
    }

    .mou-v2-preview-body .annex-table th {
        width: 34%;
    }

    .mou-v2-preview-body .annex-empty-state,
    .mou-v2-preview-body .annex-note,
    .mou-v2-preview-body .annex-audit {
        padding: 12px 14px;
        border: 1px solid #dbe3ee;
        border-radius: 14px;
        background: #f8fbff;
        color: #475569;
    }

    .mou-v2-preview-body .annex-note {
        margin-top: 10px;
    }

    .mou-v2-preview-body .annex-audit {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 16px;
        margin-top: 12px;
        font-size: 12px;
        color: #64748b;
    }

    .mou-v2-preview-body .readiness-table th,
    .mou-v2-preview-body .readiness-table td {
        width: auto;
    }

    .mou-v2-preview-body .readiness-status {
        width: 24%;
        font-weight: 800;
        letter-spacing: 0.02em;
        color: #163b68;
    }

    @media (max-width: 900px) {
        .mou-v2-preview-header,
        .mou-v2-preview-body {
            padding: 24px 20px;
        }

        .mou-v2-preview-body .mou-v2-document {
            padding: 28px 20px 24px;
            border-radius: 18px;
        }

        .mou-v2-preview-body .party-card-grid,
        .mou-v2-preview-body .signature-grid {
            grid-template-columns: 1fr;
        }

        .mou-v2-preview-body h1 {
            font-size: 28px;
        }

        .mou-v2-preview-body h2 {
            font-size: 18px;
        }
    }
</style>

<div class="mou-v2-preview-shell">
    <div class="mou-v2-preview-header">
        <p class="eyebrow">Preview MOU V2</p>
        <h3>Comprehensive MOU Body</h3>
        <p class="summary">
            Preview ini menggunakan shared legal body yang sama dengan versi PDF dan menerima payload normalized
            agar kompatibel untuk sumber data frozen maupun live.
        </p>
    </div>

    <div class="mou-v2-preview-body">
        @include('agreements.partials.mou-v2-contract', ['payload' => $payload])
        @include('agreements.partials.mou-v2-annex-i', ['payload' => $payload])
        @include('agreements.partials.mou-v2-annex-ii', ['payload' => $payload])
    </div>
</div>
