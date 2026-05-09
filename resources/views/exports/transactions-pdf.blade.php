<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi - {{ $event->event }}</title>
    <style>
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
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
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.5rem;
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
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #f1f5f9;
        }
        .footer {
            margin-top: 3rem;
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
        }
        .btn-print {
            background: #4f46e5;
            color: #white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right;">
        <button onclick="window.print()" class="btn-print" style="color: white;">Cetak Laporan / Simpan PDF</button>
    </div>

    <div class="header">
        <h1>Laporan Transaksi</h1>
        <div class="meta">
            <div>
                <strong>Event:</strong> {{ $event->event }}<br>
                <strong>Tanggal Event:</strong> {{ \Carbon\Carbon::parse($event->tanggal)->format('d F Y') }}
            </div>
            <div class="text-right">
                <strong>Dicetak pada:</strong> {{ now()->format('d M Y, H:i') }}<br>
                <strong>Filter:</strong> {{ $filter_info }}
            </div>
        </div>
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
                <th class="text-right">Total</th>
                <th>Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @php $totalGrand = 0; @endphp
            @foreach($transactions as $trx)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($trx->created_at)->format('d/m/y H:i') }}</td>
                    <td class="font-mono">{{ $trx->invoice }}</td>
                    <td>{{ $trx->user_name }}</td>
                    <td>{{ $trx->kategori_harga }}</td>
                    <td>{{ $trx->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($trx->harga_ticket, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($trx->quantity * $trx->harga_ticket, 0, ',', '.') }}</td>
                    <td>
                        @if($trx->konfirmasi == '1')
                            <span class="badge" style="background: #ecfdf5; color: #065f46;">Hadir</span>
                        @else
                            <span class="badge" style="background: #f1f5f9; color: #475569;">Belum Hadir</span>
                        @endif
                    </td>
                </tr>
                @php $totalGrand += ($trx->quantity * $trx->harga_ticket); @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc; font-weight: bold;">
                <td colspan="6" class="text-right">TOTAL PENDAPATAN KOTOR (TIKET)</td>
                <td class="text-right">Rp {{ number_format($totalGrand, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Laporan ini digenerate secara otomatis oleh sistem TiketKonser Dashboard.
    </div>

    <script>
        // Auto open print dialog if directed from export
        window.onload = function() {
            if (window.location.search.indexOf('autoPrint=true') > -1) {
                // window.print();
            }
        }
    </script>
</body>
</html>
