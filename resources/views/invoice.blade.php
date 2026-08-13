<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $type === 'penarikan' ? $penarikan->uid : $cart->invoice }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .invoice-card {
            background: white;
            position: relative;
            overflow: hidden;
        }

        .invoice-card::before {
            content: "";
            background-image: url('{{ asset('storage/logo/' . $logo[0]->logo) }}');
            opacity: 0.03;
            background-size: 150px;
            background-repeat: repeat;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            transform: rotate(-15deg);
            pointer-events: none;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
                padding: 0 !important;
            }

            .invoice-card {
                box-shadow: none !important;
                border: 1px solid #e2e8f0 !important;
                /* Keep a light border for print structure */
                border-radius: 0 !important;
                margin: 0 !important;
            }

            .max-w-xl {
                max-width: 100% !important;
                width: 100% !important;
            }
        }
    </style>
</head>

<body class="bg-slate-50 py-10 px-4">
    <div class="max-w-xl mx-auto">
        <!-- Receipt Card -->
        <div class="invoice-card bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden relative">
            <!-- Top Header Decor -->
            <div class="h-2 bg-indigo-600"></div>

            <div class="p-8 relative z-10">
                <!-- Header -->
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" alt="Logo" class="h-10 mb-2">
                        <h1 class="text-xs font-bold uppercase tracking-widest text-slate-400">
                            {{ $type === 'penarikan' ? 'Invoice Penarikan Saldo' : 'Bukti Pembayaran Tiket' }}
                        </h1>
                    </div>
                    <div class="text-right">
                        @php
                            $statusBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-100';
                            if ($type === 'penarikan') {
                                $statusBadgeClass = match (strtoupper((string) $penarikan->status)) {
                                    'PENDING' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    'PROCESSING' => 'bg-sky-50 text-sky-700 border-sky-100',
                                    'SUCCESS' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    default => 'bg-rose-50 text-rose-700 border-rose-100',
                                };
                            }
                        @endphp
                        <div
                            class="{{ $statusBadgeClass }} px-3 py-1 rounded-full text-xs font-bold border inline-block mb-2">
                            {{ strtoupper($type === 'penarikan' ? $penarikan->status : $cart->status) }}
                        </div>
                        <p class="text-sm font-mono text-slate-500">
                            #{{ $type === 'penarikan' ? $penarikan->uid : $cart->invoice }}
                        </p>
                    </div>
                </div>

                @if ($type === 'penarikan')
                    @php
                        $sectionTitleClass = 'text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3';
                    @endphp
                    <!-- Main Amount Section for Penarikan -->
                    <div class="text-center py-10 border-y border-slate-100 mb-8 bg-slate-50/50 rounded-2xl">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Penarikan
                        </p>
                        <h2 class="text-4xl font-extrabold text-slate-900">
                            <span class="text-indigo-600">Rp</span> {{ number_format($penarikan->amount, 0, ',', '.') }}
                        </h2>
                        <p class="text-xs text-slate-400 mt-3">
                            {{ \Carbon\Carbon::parse($penarikan->created_at)->format('d F Y, H:i') }}
                        </p>
                    </div>

                    <!-- Entity Details -->
                    <div class="grid grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 class="{{ $sectionTitleClass }}">Rekening Admin Tercatat</h3>
                            <div class="space-y-2">
                                <p class="text-sm font-bold text-slate-800">{{ $bankPengirim[0]->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $bankPengirim[0]->bank }}</p>
                                <p class="text-xs font-mono text-slate-600">{{ $bankPengirim[0]->norek }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <h3 class="{{ $sectionTitleClass }}">Rekening Tujuan Penarikan</h3>
                            <div class="space-y-2">
                                <p class="text-sm font-bold text-slate-800">{{ $bankPenyewa->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $bankPenyewa->bank }}</p>
                                <p class="text-xs font-mono text-slate-600">{{ $bankPenyewa->norek }}</p>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Main Amount Section for Transaction -->
                    <div class="text-center py-10 border-y border-slate-100 mb-8 bg-slate-50/50 rounded-2xl">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Pembayaran
                        </p>
                        <h2 class="text-4xl font-extrabold text-slate-900">
                            <span class="text-indigo-600">Rp</span>
                            {{ number_format($cart->hargaCarts->sum(fn($i) => $i->quantity * $i->harga_ticket), 0, ',', '.') }}
                        </h2>
                        <p class="text-sm font-bold text-slate-800 mt-2">{{ $cart->event->event }}</p>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ \Carbon\Carbon::parse($cart->created_at)->format('d F Y, H:i') }}
                        </p>
                    </div>

                    <!-- User & Items -->
                    <div class="mb-8 space-y-6">
                        <div>
                            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Pembeli</h3>
                            <p class="text-sm font-bold text-slate-800">{{ $cart->users->name ?? 'Guest' }}</p>
                            <p class="text-xs text-slate-500">{{ $cart->users->email ?? '' }}</p>
                        </div>

                        <div>
                            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Rincian
                                Tiket</h3>
                            <div class="space-y-2">
                                @foreach ($cart->hargaCarts as $item)
                                    <div class="flex justify-between items-center text-sm">
                                        <div class="text-slate-600">
                                            <span
                                                class="font-bold text-slate-800">{{ $item->masterHarga->kategori ?? 'Kategori Dihapus' }}</span>
                                            <span class="text-xs mx-1">x</span> {{ $item->quantity }}
                                        </div>
                                        <div class="font-bold text-slate-800">
                                            Rp {{ number_format($item->quantity * $item->harga_ticket, 0, ',', '.') }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Metode
                                Bayar</span>
                            <span
                                class="text-sm font-bold text-indigo-600">{{ strtoupper($cart->payment_type) }}</span>
                        </div>
                    </div>
                @endif

                <!-- Footer Note -->
                <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100/50 mb-8">
                    <div class="flex gap-3">
                        <i data-lucide="info" class="w-4 h-4 text-indigo-500 flex-shrink-0"></i>
                        @if ($type === 'penarikan')
                            @php
                                $penarikanNote = match (strtoupper((string) $penarikan->status)) {
                                    'PENDING'
                                        => 'Permintaan penarikan telah diterima sistem dan sedang menunggu proses administrasi.',
                                    'PROCESSING'
                                        => 'Permintaan penarikan sedang diproses. Estimasi penyelesaian maksimal 1×24 jam.',
                                    'SUCCESS'
                                        => 'Permintaan penarikan telah selesai diproses. Invoice ini merupakan dokumen administrasi dan bukan bukti transaksi perbankan. Bukti transfer, jika tersedia, dapat dilihat secara terpisah.',
                                    'REJECTED'
                                        => 'Permintaan penarikan ditolak. Dana tidak ditarik dari saldo Anda. Hubungi admin untuk informasi lebih lanjut.',
                                    'CANCELLED'
                                        => 'Permintaan penarikan dibatalkan. Dana tidak ditarik dari saldo Anda.',
                                    'FAILED'
                                        => 'Permintaan penarikan gagal diproses. Hubungi admin untuk informasi lebih lanjut.',
                                    default
                                        => 'Status penarikan tidak dikenali. Hubungi admin untuk informasi lebih lanjut.',
                                };
                            @endphp
                            <p class="text-[11px] text-indigo-700 leading-relaxed">
                                <strong>Catatan:</strong> {{ $penarikanNote }}
                            </p>
                        @else
                            <p class="text-[11px] text-indigo-700 leading-relaxed">
                                <strong>Catatan:</strong> Transaksi ini adalah bukti pembayaran yang sah untuk tiket
                                event yang disebutkan. Simpan bukti ini sebagai referensi resmi.
                            </p>
                        @endif
                    </div>
                </div>

                <!-- QR & Reference Area (Visual only) -->
                <div class="flex justify-between items-end opacity-50">
                    <div
                        class="w-16 h-16 bg-slate-100 rounded-lg flex items-center justify-center border border-slate-200">
                        <i data-lucide="qr-code" class="w-8 h-8 text-slate-400"></i>
                    </div>
                    <div class="text-right text-[10px] text-slate-400">
                        <p>{{ $type === 'penarikan' ? 'Referensi Sistem' : 'Verified Digitally' }}</p>
                        <p class="font-mono">{{ md5($type === 'penarikan' ? $penarikan->uid : $cart->uid) }}</p>
                    </div>
                </div>
            </div>

            <!-- Bottom Decor -->
            <div class="h-1 bg-indigo-600/20"></div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 flex gap-4 no-print">
            <button id="downloadButton"
                class="flex-1 bg-white hover:bg-slate-50 text-slate-700 font-bold py-4 px-6 rounded-2xl border-2 border-slate-200 shadow-lg transition-all flex items-center justify-center gap-3">
                <i data-lucide="download" class="w-5 h-5"></i> Simpan Gambar
            </button>
            <button id="printButton"
                class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-6 rounded-2xl shadow-lg shadow-indigo-200 transition-all flex items-center justify-center gap-3">
                <i data-lucide="printer" class="w-5 h-5"></i> Cetak Invoice
            </button>
        </div>

        <p class="text-center text-slate-400 text-xs mt-8 no-print">&copy; {{ date('Y') }}
            {{ config('app.name') }} -
            @if ($type === 'penarikan')
                Dokumen ini diterbitkan otomatis oleh sistem GoTik sebagai referensi administrasi.
            @else
                Dokumen ini sah tanpa tanda tangan basah.
            @endif
        </p>
    </div>

    <script src="{{ asset('assets/js/html2canvas.min.js') }}"></script>
    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Print Function
        document.getElementById("printButton").addEventListener("click", function() {
            window.print();
        });

        // Download as Image Function
        document.getElementById("downloadButton").addEventListener("click", function() {
            const element = document.querySelector(".invoice-card");

            // Adjust for high quality
            html2canvas(element, {
                scale: 2,
                backgroundColor: "#ffffff",
                useCORS: true
            }).then(function(canvas) {
                const link = document.createElement("a");
                link.href = canvas.toDataURL("image/png");
                link.download =
                    "Invoice-{{ $type === 'penarikan' ? $penarikan->uid : $cart->invoice }}.png";
                link.click();
            });
        });
    </script>
</body>

</html>
