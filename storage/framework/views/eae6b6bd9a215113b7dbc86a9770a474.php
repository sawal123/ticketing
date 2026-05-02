<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo e($type === 'penarikan' ? $penarikan->uid : $cart->invoice); ?></title>
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
            background-image: url('<?php echo e(asset("storage/logo/" . $logo[0]->logo)); ?>');
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
                border: 1px solid #e2e8f0 !important; /* Keep a light border for print structure */
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
                        <img src="<?php echo e(asset('storage/logo/' . $logo[0]->logo)); ?>" alt="Logo" class="h-10 mb-2">
                        <h1 class="text-xs font-bold uppercase tracking-widest text-slate-400">
                            <?php echo e($type === 'penarikan' ? 'Bukti Penarikan Saldo' : 'Bukti Pembayaran Tiket'); ?>

                        </h1>
                    </div>
                    <div class="text-right">
                        <div
                            class="bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold border border-emerald-100 inline-block mb-2">
                            <?php echo e(strtoupper($type === 'penarikan' ? $penarikan->status : $cart->status)); ?>

                        </div>
                        <p class="text-sm font-mono text-slate-500">
                            #<?php echo e($type === 'penarikan' ? $penarikan->uid : $cart->invoice); ?>

                        </p>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($type === 'penarikan'): ?>
                    <!-- Main Amount Section for Penarikan -->
                    <div class="text-center py-10 border-y border-slate-100 mb-8 bg-slate-50/50 rounded-2xl">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Penarikan</p>
                        <h2 class="text-4xl font-extrabold text-slate-900">
                            <span class="text-indigo-600">Rp</span> <?php echo e(number_format($penarikan->amount, 0, ',', '.')); ?>

                        </h2>
                        <p class="text-xs text-slate-400 mt-3">
                            <?php echo e(\Carbon\Carbon::parse($penarikan->created_at)->format('d F Y, H:i')); ?>

                        </p>
                    </div>

                    <!-- Entity Details -->
                    <div class="grid grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Pengirim (Admin)</h3>
                            <div class="space-y-2">
                                <p class="text-sm font-bold text-slate-800"><?php echo e($bankPengirim[0]->nama); ?></p>
                                <p class="text-xs text-slate-500"><?php echo e($bankPengirim[0]->bank); ?></p>
                                <p class="text-xs font-mono text-slate-600"><?php echo e($bankPengirim[0]->norek); ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Penerima</h3>
                            <div class="space-y-2">
                                <p class="text-sm font-bold text-slate-800"><?php echo e($bankPenyewa->nama); ?></p>
                                <p class="text-xs text-slate-500"><?php echo e($bankPenyewa->bank); ?></p>
                                <p class="text-xs font-mono text-slate-600"><?php echo e($bankPenyewa->norek); ?></p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Main Amount Section for Transaction -->
                    <div class="text-center py-10 border-y border-slate-100 mb-8 bg-slate-50/50 rounded-2xl">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Pembayaran</p>
                        <h2 class="text-4xl font-extrabold text-slate-900">
                            <span class="text-indigo-600">Rp</span> <?php echo e(number_format($cart->hargaCarts->sum(fn($i) => $i->quantity * $i->harga_ticket), 0, ',', '.')); ?>

                        </h2>
                        <p class="text-sm font-bold text-slate-800 mt-2"><?php echo e($cart->event->event); ?></p>
                        <p class="text-xs text-slate-400 mt-1">
                            <?php echo e(\Carbon\Carbon::parse($cart->created_at)->format('d F Y, H:i')); ?>

                        </p>
                    </div>

                    <!-- User & Items -->
                    <div class="mb-8 space-y-6">
                        <div>
                            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Pembeli</h3>
                            <p class="text-sm font-bold text-slate-800"><?php echo e($cart->users->name ?? 'Guest'); ?></p>
                            <p class="text-xs text-slate-500"><?php echo e($cart->users->email ?? ''); ?></p>
                        </div>

                        <div>
                            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Rincian Tiket</h3>
                            <div class="space-y-2">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $cart->hargaCarts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <div class="flex justify-between items-center text-sm">
                                        <div class="text-slate-600">
                                            <span class="font-bold text-slate-800"><?php echo e($item->masterHarga->kategori ?? 'Kategori Dihapus'); ?></span>
                                            <span class="text-xs mx-1">x</span> <?php echo e($item->quantity); ?>

                                        </div>
                                        <div class="font-bold text-slate-800">
                                            Rp <?php echo e(number_format($item->quantity * $item->harga_ticket, 0, ',', '.')); ?>

                                        </div>
                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Metode Bayar</span>
                            <span class="text-sm font-bold text-indigo-600"><?php echo e(strtoupper($cart->payment_type)); ?></span>
                        </div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <!-- Footer Note -->
                <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100/50 mb-8">
                    <div class="flex gap-3">
                        <i data-lucide="info" class="w-4 h-4 text-indigo-500 flex-shrink-0"></i>
                        <p class="text-[11px] text-indigo-700 leading-relaxed">
                            <strong>Catatan:</strong> <?php echo e($type === 'penarikan' ? 'Dana telah berhasil ditransfer ke rekening penerima sesuai dengan detail di atas.' : 'Transaksi ini adalah bukti pembayaran yang sah untuk tiket event yang disebutkan.'); ?> Simpan bukti ini sebagai referensi resmi.
                        </p>
                    </div>
                </div>

                <!-- QR & Signature Area (Visual only) -->
                <div class="flex justify-between items-end opacity-50">
                    <div
                        class="w-16 h-16 bg-slate-100 rounded-lg flex items-center justify-center border border-slate-200">
                        <i data-lucide="qr-code" class="w-8 h-8 text-slate-400"></i>
                    </div>
                    <div class="text-right text-[10px] text-slate-400">
                        <p>Verified Digitally</p>
                        <p class="font-mono"><?php echo e(md5($type === 'penarikan' ? $penarikan->uid : $cart->uid)); ?></p>
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
                <i data-lucide="printer" class="w-5 h-5"></i> Cetak PDF
            </button>
        </div>

        <p class="text-center text-slate-400 text-xs mt-8 no-print">&copy; <?php echo e(date('Y')); ?> <?php echo e(config('app.name')); ?> -
            Dokumen ini sah tanpa tanda tangan basah.</p>
    </div>

    <script src="<?php echo e(asset('assets/js/html2canvas.min.js')); ?>"></script>
    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Print Function
        document.getElementById("printButton").addEventListener("click", function () {
            window.print();
        });

        // Download as Image Function
        document.getElementById("downloadButton").addEventListener("click", function () {
            const element = document.querySelector(".invoice-card");

            // Adjust for high quality
            html2canvas(element, {
                scale: 2,
                backgroundColor: "#ffffff",
                useCORS: true
            }).then(function (canvas) {
                const link = document.createElement("a");
                link.href = canvas.toDataURL("image/png");
                link.download = "Invoice-<?php echo e($type === 'penarikan' ? $penarikan->uid : $cart->invoice); ?>.png";
                link.click();
            });
        });
    </script>
</body>

</html><?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/invoice.blade.php ENDPATH**/ ?>