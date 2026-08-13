<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi - {{ $event->event }}</title>
    <style>
        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            line-height: 1.5;
            padding: 2rem;
            background: #fff;
        }

        .header {
            margin-bottom: 2rem;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 1rem;
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
        }

        th {
            background: #f8fafc;
            text-align: left;
            padding: 0.75rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 0.75rem;
            font-size: 0.875rem;
            border-bottom: 1px solid #f1f5f9;
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

    <table>
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
            @php $seenCartTaxes = []; @endphp
            @foreach ($transactions as $trx)
                @php
                    $lineTotal = (int) $trx->quantity * (int) $trx->harga_ticket - (int) ($trx->disc ?? 0);
                    $taxSnapshot = empty($seenCartTaxes[$trx->cart_uid]) ? (int) ($trx->tax_snapshot ?? 0) : 0;
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
                    <td class="text-right">Rp {{ number_format($lineTotal + $taxSnapshot, 0, ',', '.') }}</td>
                    <td>
                        @if ($isVerified)
                            <span class="badge" style="background: #ecfdf5; color: #065f46;">Terverifikasi</span>
                        @else
                            <span class="badge" style="background: #f1f5f9; color: #475569;">Belum Diverifikasi</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h2>Ringkasan Laporan</h2>
        <p class="total-label">TOTAL OMZET SELURUH DATA</p>
        <p class="total-value">Rp {{ number_format((int) ($exportTotals['owner_revenue'] ?? 0), 0, ',', '.') }}</p>
        <p class="summary-note">Seluruh transaksi SUCCESS sesuai filter laporan.<br>Omzet sudah termasuk pajak.</p>
    </div>

    <div class="footer">
        Laporan ini digenerate secara otomatis oleh sistem TiketKonser Dashboard.
    </div>
</body>

</html>
