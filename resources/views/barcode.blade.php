<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="E-Ticket Gotik">
    <meta name="author" content="Gotik">
    <title>E-Ticket - {{ $event->event }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Space+Mono:wght@700&family=Playfair+Display:wght@700;900&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --bg: #0a0612;
            --surface: #161122;
            --surface2: #1f192e;
            --border: rgba(108, 92, 231, 0.2);
            --gold: #f5c842;
            --indigo: #6c5ce7;
            --rose: #e8547a;
            --teal: #3dd9c4;
            --text: #f0ecff;
            --muted: #a098b5;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
        }

        .ticket-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        .ticket-card::before {
            content: "";
            position: absolute;
            top: -100px;
            right: -100px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(108, 92, 231, 0.15) 0%, transparent 70%);
            z-index: 0;
        }

        .qr-container {
            background: #fff;
            padding: 16px;
            border-radius: 20px;
            display: inline-block;
            box-shadow: 0 0 20px rgba(61, 217, 196, 0.2);
        }

        .qr-container svg {
            width: 200px !important;
            height: 200px !important;
        }

        .dashed-line {
            border-top: 2px dashed var(--border);
            position: relative;
        }

        .dashed-line::before,
        .dashed-line::after {
            content: "";
            position: absolute;
            top: -10px;
            width: 24px;
            height: 24px;
            background: var(--bg);
            border-radius: 50%;
            z-index: 10;
        }

        .dashed-line::before {
            left: -44px;
            border-right: 1px solid var(--border);
        }

        .dashed-line::after {
            right: -44px;
            border-left: 1px solid var(--border);
        }

        .mono {
            font-family: 'Space Mono', monospace;
        }

        .playfair {
            font-family: 'Playfair Display', serif;
        }
    </style>
</head>

<body class="min-h-screen py-10 px-4 flex items-center justify-center">
    <div class="max-w-md w-full relative">
        <!-- Back Link -->
        <div class="mb-6 text-center">
            <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" class="h-10 mx-auto opacity-80" alt="Logo">
        </div>

        <div class="ticket-card relative z-10">
            <!-- Event Cover -->
            <div class="relative h-48 overflow-hidden">
                <img src="{{ asset('storage/cover/' . $event->cover) }}" class="w-full h-full object-cover"
                    alt="Event Cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[#161122] to-transparent"></div>
                <div class="absolute bottom-4 left-6 right-6">
                    <span
                        class="inline-block px-3 py-1 bg-[#6c5ce7] text-white text-[10px] uppercase tracking-widest font-bold rounded-md mb-2 mono">E-Ticket</span>
                    <h1 class="text-2xl font-bold playfair leading-tight text-[#f5c842]">{{ $event->event }}</h1>
                </div>
            </div>

            <!-- Ticket Body -->
            <div class="p-6">
                <!-- User Info -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-[10px] uppercase tracking-wider text-[#a098b5] mono mb-1">Customer</p>
                        <p class="text-sm font-bold">{{ $userBarcode->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] uppercase tracking-wider text-[#a098b5] mono mb-1">Date</p>
                        <p class="text-sm font-bold">{{ \Carbon\Carbon::parse($event->tanggal)->format('d M Y') }}</p>
                    </div>
                </div>

                <div class="mb-6">
                    <p class="text-[10px] uppercase tracking-wider text-[#a098b5] mono mb-1">Location</p>
                    <p class="text-sm leading-relaxed">{{ $event->alamat }}</p>
                </div>

                <!-- QR Section -->
                <div class="text-center py-4">
                    <div class="qr-container mb-4">
                        {!! $barcodeData !!}
                    </div>
                    <p class="text-lg font-bold mono tracking-[4px] text-[#3dd9c4]">{{ $invoice }}</p>
                    <p class="text-[11px] text-[#a098b5] mt-2 italic text-center">Tunjukkan QR Code ini kepada panitia
                    </p>
                </div>

                <!-- Dashed Divider -->
                <div class="my-8 dashed-line"></div>

                <!-- Ticket Details -->
                <div class="mb-6">
                    <h3
                        class="text-[11px] uppercase tracking-[2px] text-[#f5c842] mono mb-4 border-b border-white/5 pb-2">
                        Ticket Summary</h3>
                    <div class="space-y-3">
                        @foreach ($hargaC as $hc)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-[#f0ecff]/80">{{ $hc->kategori_harga }}</span>
                                <span
                                    class="px-3 py-1 bg-white/5 rounded-lg text-xs font-bold mono">x{{ $hc->quantity }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Disclaimer -->
                <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                    <div class="flex gap-3">
                        <span class="text-xl">⚠️</span>
                        <p class="text-[10px] leading-normal text-[#a098b5]">
                            E-Ticket ini bersifat rahasia. Jangan membagikan QR Code atau nomor invoice kepada siapapun.
                            Pihak penyelenggara tidak bertanggung jawab atas penyalahgunaan tiket.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-[11px] text-[#a098b5] mt-8 uppercase tracking-[2px] mono">
            &copy; {{ date('Y') }} Gotik Event . All Rights Reserved
        </p>
    </div>
</body>

</html>