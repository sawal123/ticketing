<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi - {{ $event->event }}</title>
    <style>
        @page {
            margin: 12mm 10mm;
        }

        thead {
            display: table-header-group;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            line-height: 1.4;
            padding: 0.5rem;
            background: #fff;
        }

        .header {
            margin-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 0.6rem;
        }

        .header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #0f172a;
        }

        .meta {
            width: 100%;
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.5rem;
        }

        .meta td {
            border: none;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            table-layout: fixed;
        }

        .page-block {
            page-break-after: always;
        }

        .page-block.last {
            page-break-after: auto;
        }

        th {
            background: #f8fafc;
            text-align: left;
            padding: 0.45rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 0.45rem;
            font-size: 0.8rem;
            border-bottom: 1px solid #f1f5f9;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .text-right {
            text-align: right;
        }

        .font-mono {
            font-family: monospace;
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #f1f5f9;
        }

        .summary {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid #e2e8f0;
            page-break-inside: avoid;
        }

        .summary h2 {
            margin: 0;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #0f172a;
        }

        .summary .total-label {
            margin: 0.6rem 0 0;
            font-weight: 800;
            font-size: 0.8rem;
            color: #334155;
        }

        .summary .total-label-gap {
            margin-top: 0.9rem;
        }

        .summary .total-value {
            margin: 0.1rem 0 0.5rem;
            font-size: 1.4rem;
            font-weight: 800;
            color: #4f46e5;
        }

        .summary .summary-note {
            margin: 0;
            font-size: 0.75rem;
            color: #64748b;
        }

        .footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Laporan Transaksi</h1>
        <table class="meta">
            <tr>
                <td>
                    <strong>Event:</strong> {{ $event->event }}<br>
                    <strong>Tanggal Event:</strong> {{ \Carbon\Carbon::parse($event->tanggal)->format('d F Y') }}
                </td>
                <td class="text-right">
                    <strong>Dicetak pada:</strong> {{ now()->format('d M Y, H:i') }}<br>
                    <strong>Filter:</strong> {{ $filter_info }}
                </td>
            </tr>
        </table>
    </div>

    @php $seenCartTaxes = []; @endphp
    @if ($transactions->isEmpty())
        <p style="margin-top: 2rem; text-align: center; color: #64748b;">Tidak ada transaksi yang sesuai dengan filter.
        </p>
    @else
        @foreach ($transactions->chunk(20) as $chunk)
            <div class="page-block @if ($loop->last) last @endif">
                <table>
                    <colgroup>
                        <col style="width: 10%;">
                        <col style="width: 13%;">
                        <col style="width: 15%;">
                        <col style="width: 11%;">
                        <col style="width: 5%;">
                        <col style="width: 11%;">
                        <col style="width: 10%;">
                        <col style="width: 11%;">
                        <col style="width: 14%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Invoice</th>
                            <th>Nama Pembeli</th>
                            <th>Kategori Tiket</th>
                            <th>Qty</th>
                            <th class="text-right">Harga Satuan</th>
                            <th class="text-right">Diskon</th>
                            <th class="text-right">Total</th>
                            <th>Status Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($chunk as $trx)
                            @php
                                $lineTotal = (int) $trx->quantity * (int) $trx->harga_ticket - (int) ($trx->disc ?? 0);
                                $taxSnapshot = empty($seenCartTaxes[$trx->cart_uid])
                                    ? (int) ($trx->tax_snapshot ?? 0)
                                    : 0;
                                $seenCartTaxes[$trx->cart_uid] = true;
                                $scannedAt = filled($trx->scanned_at) ? \Carbon\Carbon::parse($trx->scanned_at) : null;
                                $isVerified = $scannedAt !== null || (string) $trx->konfirmasi === '1';
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($trx->created_at)->format('d/m/y H:i') }}</td>
                                <td class="font-mono">{{ $trx->invoice }}</td>
                                <td>{{ $trx->buyer_name }}</td>
                                <td>{{ $trx->kategori_harga }}</td>
                                <td>{{ $trx->quantity }}</td>
                                <td class="text-right">Rp {{ number_format($trx->harga_ticket, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($trx->disc ?? 0, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($lineTotal + $taxSnapshot, 0, ',', '.') }}
                                </td>
                                <td>
                                    @if ($isVerified)
                                        <span class="badge"
                                            style="background: #ecfdf5; color: #065f46;">Terverifikasi</span>
                                    @else
                                        <span class="badge" style="background: #f1f5f9; color: #475569;">Belum
                                            Diverifikasi</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif

    <div class="summary">
        <h2>Ringkasan Laporan</h2>

        <p class="total-label">TOTAL PAJAK</p>
        <p class="total-value">Rp {{ number_format((int) ($exportTotals['tax_total'] ?? 0), 0, ',', '.') }}</p>
        <p class="summary-note">Pajak sudah termasuk dalam Total Omzet.</p>

        <p class="total-label total-label-gap">TOTAL OMZET SELURUH DATA</p>
        <p class="total-value">Rp {{ number_format((int) ($exportTotals['owner_revenue'] ?? 0), 0, ',', '.') }}</p>
        <p class="summary-note">Seluruh transaksi SUCCESS sesuai filter laporan.<br>Omzet sudah termasuk pajak.</p>
    </div>

    <div class="footer">
        Laporan ini digenerate secara otomatis oleh sistem TiketKonser Dashboard.
    </div>
</body>

</html>
