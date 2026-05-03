<?php $__env->startSection('content'); ?>

    <link rel="stylesheet" href="<?php echo e(asset('landing/css/bayartiket.css')); ?>">

    <div class="" style="height: 50px; "></div>

    <!-- BREADCRUMB -->
    <div class="breadcrumb ">
        <span>Event</span>
        <span class="breadcrumb-sep">›</span>
        <span><?php echo e($event->event); ?></span>
        <span class="breadcrumb-sep">›</span>
        <span style="color:var(--gold);">Checkout</span>
    </div>

    <!-- SUCCESS BANNER (Show only when status is SUCCESS) -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cart->status === 'SUCCESS'): ?>
        <div class="success-hero">
            <div class="success-hero-inner">
                <div class="success-icon-large">✅</div>
                <div class="success-content">
                    <h2 class="success-title">Pembayaran Berhasil!</h2>
                    <p class="success-desc">
                        Selamat, tiket Anda telah terkonfirmasi. E-Ticket & Barcode telah dikirim ke email:
                        <strong><?php echo e(Auth::user()->email); ?></strong>.<br>
                        <small>(Silakan periksa kotak masuk atau folder SPAM Anda)</small>
                    </p>
                </div>
                <div class="success-actions">
                    <a href="<?php echo e(url('/transaksi')); ?>" class="btn-success-action primary">
                        <span>🎫</span>
                        <span>Lihat Tiket Saya</span>
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- EMAIL ALERT (Show only when status is NOT SUCCESS) -->
        <div class="email-alert">
            <div class="email-alert-inner">
                <span class="alert-icon">✉️</span>
                <span class="alert-text">Pastikan email Anda aktif: <span
                        class="alert-email"><?php echo e(Auth::user()->email); ?></span></span>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- MAIN -->
    <div class="checkout-layout">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success') && !str_contains(session('success'), 'Pembayaran Berhasil')): ?>
            <div class="alert alert-primary">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <!-- LEFT: EVENT CARD -->
        <div class="event-card">
            <div class="event-banner">
                <img src="<?php echo e(asset('storage/cover/' . $event->cover)); ?>" class="event-banner" alt="...">
            </div>
            <div class="event-info-card">
                <div class="event-name"><?php echo e($event->event); ?></div>
                <div class="event-meta-row">📅 <span><?php echo e($event->tanggal); ?></span></div>
                <div class="event-meta-row">📍 <span><?php echo e($event->alamat); ?></span></div>
                <div class="invoice-tag">
                    <div>
                        <div class="invoice-label">No. Invoice</div>
                        <div class="invoice-value"><?php echo e($cart->invoice); ?></div>
                    </div>
                    <button class="copy-btn" onclick="copyInvoice()" title="Salin invoice">⎘</button>
                </div>
            </div>
        </div>

        <!-- RIGHT: CHECKOUT FORMS -->
        <div class="checkout-right">

            <!-- TICKET DETAIL -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="background:rgba(245,200,66,0.12);">🎫</div>
                    <div class="card-title">Ticket Detail</div>
                </div>
                <div class="card-body">
                    <?php
                        $fee = 0;
                        $total1 = 0;
                    ?>
                    <table class="ticket-detail-table">
                        <thead>
                            <tr>
                                <th>Qty</th>
                                <th style="text-align:right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $harga; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $harga): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr class="ticket-row">

                                    <td>
                                        <div class="ticket-tier-badge"><?php echo e($harga->kategori_harga); ?></div>
                                        <div class="ticket-qty-info">Rp
                                            <?php echo e(number_format($harga->harga_ticket, 0, ',', '.')); ?> ×
                                            <?php echo e($harga->quantity); ?>

                                        </div>
                                        <?php
                                            $fee += $harga->quantity;
                                        ?>
                                        <div class="ticket-qty-sub"><?php echo e($fee); ?> tiket · Harga per tiket</div>
                                    </td>

                                    <td class="ticket-total-cell">
                                        <?php
                                            $total1 = (int) $harga->quantity * (int) $harga->harga_ticket;
                                        ?>
                                        Rp<?php echo e(number_format($total1 ?? 0)); ?></td>


                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </tbody>
                    </table>
                    <div class="ticket-summary-row">
                        <div class="ticket-count-pill">✓ Total <?php echo e($fee); ?> Tiket</div>
                        <span style="font-size:12px;color:var(--muted);">Tiket akan dikirim via email</span>
                    </div>
                </div>
            </div>

            <!-- PAYMENT METHOD -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$cart->link): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon" style="background:rgba(108,92,231,0.12);">💳</div>
                        <div class="card-title">Metode Pembayaran</div>
                    </div>
                    <div class="card-body" style="padding-top:0;">
                        <div class="pay-accordion">
                            <div class="pay-accordion-header" onclick="toggleAccordion(this)">
                                <span class="pay-accordion-label">Pilih Metode</span>
                                <span class="pay-chevron open">▲</span>
                            </div>

                            <div class="pay-options-grid" id="payOptions">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $payment; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gateway): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <div class="pay-option" id="card<?php echo e($gateway->id); ?>" style="cursor:pointer;"
                                        onclick="selectPayment(<?php echo e($gateway->id); ?>,<?php echo e($gateway->biaya); ?>, '<?php echo e($gateway->biaya_type); ?>', this)">

                                        <div class="pay-logo" style="padding:8px;">
                                            <img src="<?php echo e(asset('storage/' . $gateway->icon)); ?>"
                                                style="width:40px; height:22px; object-fit:contain;"
                                                alt="<?php echo e($gateway->payment); ?>">
                                        </div>

                                        <div>
                                            <div class="pay-name"><?php echo e($gateway->payment); ?></div>
                                            <div class="pay-fee">
                                                Biaya:
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($gateway->biaya_type == 'rupiah'): ?>
                                                    Rp<?php echo e(number_format($gateway->biaya, 0, ',', '.')); ?>

                                                <?php else: ?>
                                                    <?php echo e($gateway->biaya); ?>%
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                        </div>

                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <!-- VOUCHER -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="background:rgba(61,217,196,0.12);">🏷️</div>
                    <div class="card-title">Voucher</div>
                </div>

                <div class="card-body">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cart->status !== 'SUCCESS'): ?>
                        <form action="<?php echo e(url('/checkVoucer')); ?>" method="post" class="voucher-input-wrap">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="event" value="<?php echo e($event->uid); ?>">
                            <input type="hidden" name="cartUid" value="<?php echo e($cart->uid); ?>">

                            <input type="text" class="voucher-input" id="voucherInput" name="code"
                                placeholder="Masukan Code Voucher.." value="<?php echo e($voucher->code); ?>"
                                <?php echo e($cart->link ? 'readonly' : ''); ?>>

                            <button type="submit" class="btn-voucher" <?php echo e($cart->link ? 'disabled' : ''); ?>>
                                Gunakan
                            </button>
                        </form>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


                    <!-- MESSAGE -->
                    <div id="voucherMsg" style="margin-top:10px;font-size:12px;">

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('vError')): ?>
                            <span style="color:#e8547a;">
                                <?php echo e(session('vError')); ?>

                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('voucher')): ?>
                            <span style="color:#3dd9c4;">
                                <?php echo e(session('voucher')); ?>

                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    </div>

                    <!-- REMOVE VOUCHER -->
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$cart->link && $voucher->code): ?>
                        <div style="margin-top:10px;">
                            <form action="<?php echo e(url('/closeVoucher')); ?>" method="post">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="event" value="<?php echo e($event->uid); ?>">
                                <input type="hidden" name="cartUid" value="<?php echo e($cart->uid); ?>">
                                <input type="hidden" name="code" value="<?php echo e($voucher->code); ?>">

                                <span style="font-size:12px;">
                                    Tidak ingin menggunakan voucher <b><?php echo e($voucher->code); ?></b>?
                                </span>

                                <button type="submit"
                                    style="background:#e8547a;color:#fff;border:none;padding:4px 8px;border-radius:6px;">
                                    ✕
                                </button>
                            </form>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </div>
            </div>

            <!-- PAYMENT DETAIL -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="background:rgba(232,84,122,0.12);">🧾</div>
                    <div class="card-title">Payment Detail</div>
                </div>

                <div class="card-body">
                    <div class="payment-detail-rows">

                        
                        <div class="detail-row">
                            <span class="label">Metode Pembayaran</span>
                            <span class="value" style="text-transform: uppercase;">
                                <?php echo e($iFee->payment ?? str_replace('_', ' ', $cart->payment_type)); ?>

                            </span>
                        </div>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($voucher && $voucher->code): ?>
                            <div class="detail-row">
                                <span class="label">Voucher</span>
                                <span class="value"><?php echo e($voucher->code); ?></span>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


                        
                        <div class="detail-row">
                            <span class="label">Ticket</span>
                            <span class="value" id="subtotal-display">
                                Rp <?php echo e(number_format($total, 0, ',', '.')); ?>

                            </span>
                        </div>

                        
                        <div class="detail-row">
                            <span class="label">Discount</span>
                            <span class="value discount" id="discount-display">
                                -Rp <?php echo e(number_format($diskon, 0, ',', '.')); ?>

                            </span>
                        </div>

                        
                        <div class="detail-row">
                            <span class="label">Internet Fee</span>
                            <span class="value" id="fee-display">
                                Rp <?php echo e(number_format($selectInternetFee ?? 0, 0, ',', '.')); ?>

                            </span>

                        </div>

                        
                        <div class="detail-row">
                            <span class="label">
                                Pajak / Fee 
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pajakPersen > 0): ?>
                                    (<?php echo e($pajakPersen); ?>%)
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </span>
                            <span class="value tax">
                                Rp <?php echo e(number_format($nilaiPajak, 0, ',', '.')); ?>

                            </span>
                        </div>

                    </div>

                    
                    <div class="total-row">
                        <div class="total-label">Grand Total</div>
                        <div class="total-value" id="grand-total">
                            Rp <?php echo e(number_format($total - $diskon + $nilaiPajak + ($selectInternetFee ?? 0), 0, ',', '.')); ?>

                        </div>

                    </div>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cart->status === 'SUCCESS'): ?>
                        <div
                            style="margin-top: 15px; padding: 10px; background: rgba(61, 217, 196, 0.1); border-radius: 10px; border: 1px solid rgba(61, 217, 196, 0.2); display: flex; align-items: center; gap: 10px;">
                            <div
                                style="width: 8px; height: 8px; background: #3dd9c4; border-radius: 50%; box-shadow: 0 0 10px #3dd9c4;">
                            </div>
                            <span style="color: #3dd9c4; font-weight: 600; font-size: 13px; letter-spacing: 0.5px;">
                                TRANSAKSI SELESAI
                            </span>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cart->status === 'UNPAID' || $cart->status === 'PENDING'): ?>
                        <form action="<?php echo e(url('/paynow')); ?>" method="post" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>

                            <input type="hidden" id="selectedPayment" name="payment_id" value="">
                            <input type="hidden" name="invoice" value="<?php echo e($cart->invoice); ?>">
                            <input type="hidden" name="person" value="<?php echo e(Auth::user()->uid); ?>">
                            <input type="hidden" name="event" value="<?php echo e($event->uid); ?>">
                            <input type="hidden" name="cartUid" value="<?php echo e($cart->uid); ?>">

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cart->status === 'UNPAID'): ?>
                                <button type="button" class="btn-pay" onclick="showConfirmModal(event)">
                                    <span>🔐</span>
                                    <span>Bayar Sekarang</span>
                                </button>
                            <?php else: ?>
                                <a href="<?php echo e($cart->link); ?>" class="btn-pay"
                                    style="text-decoration:none;display:flex;justify-content:center;">
                                    <span>🔐</span>
                                    <span>Lanjutkan Pembayaran</span>
                                </a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </form>
                    <?php else: ?>
                        <button type="submit" class="btn-pay" style="background:#3dd9c4;">
                            <?php echo e($cart->status); ?>

                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <div class="security-note">
                        <span>🔒</span>
                        <span>Transaksi dijamin aman & terenkripsi</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    </div>


    <script>
        function parseRupiah(text) {
            return parseInt(text.replace(/[^\d]/g, '')) || 0;
        }

        function formatRupiah(angka) {
            return 'Rp ' + angka.toLocaleString('id-ID');
        }

        function selectPayment(paymentId, biaya, biayaType, card) {
            // set payment id ke form
            document.getElementById('selectedPayment').value = paymentId;

            let diskon = <?php echo e($diskon ?? 0); ?>;
            let nilaiPajak = <?php echo e($nilaiPajak ?? 0); ?>;

            // UI SELECTED
            document.querySelectorAll('.pay-option').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            // ambil subtotal dari UI
            let total = parseRupiah(document.getElementById('subtotal-display').textContent);

            let fee = 0;
            if (biayaType === 'rupiah') {
                fee = biaya;
            } else if (biayaType === 'persen') {
                fee = (biaya / 100) * total;
            }

            // TOTAL AKHIR
            let totalAkhir = (total - diskon) + nilaiPajak + fee;

            // update UI
            document.getElementById('fee-display').textContent = formatRupiah(fee);
            document.getElementById('grand-total').textContent = formatRupiah(totalAkhir);
        }

        document.addEventListener('DOMContentLoaded', function() {
            <?php if($cart->status === 'UNPAID'): ?>
                // Hanya inisialisasi jika status UNPAID
                // Jika iFee sudah ada (kembali dari pilihan sebelumnya), hitung ulang
                <?php if($iFee): ?>
                    let total = parseRupiah(document.getElementById('subtotal-display').textContent);
                    let diskon = <?php echo e($diskon ?? 0); ?>;
                    let nilaiPajak = <?php echo e($nilaiPajak ?? 0); ?>;
                    let biaya = <?php echo e($iFee->biaya ?? 0); ?>;
                    let biayaType = '<?php echo e($iFee->biaya_type ?? 'rupiah'); ?>';
                    
                    let fee = 0;
                    if (biayaType === 'rupiah') {
                        fee = biaya;
                    } else if (biayaType === 'persen') {
                        fee = (biaya / 100) * total;
                    }

                    let totalAkhir = (total - diskon) + nilaiPajak + fee;
                    document.getElementById('fee-display').textContent = formatRupiah(fee);
                    document.getElementById('grand-total').textContent = formatRupiah(totalAkhir);
                <?php endif; ?>
            <?php endif; ?>
        });

        function showConfirmModal(e) {
            e.preventDefault();

            // Check if payment selected
            const paymentId = document.getElementById('selectedPayment').value;
            if (!paymentId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Metode Pembayaran Belum Dipilih',
                    text: 'Silakan pilih salah satu metode pembayaran yang tersedia terlebih dahulu.',
                    background: '#1a1a1a',
                    color: '#fff',
                    confirmButtonColor: '#6c5ce7',
                    customClass: {
                        popup: 'swal-dark-popup'
                    }
                });
                return;
            }

            // Prepare Data for SweetAlert
            const ticketRows = document.querySelectorAll('.ticket-row');
            let ticketHtml = '<div style="text-align: left; background: #252525; padding: 15px; border-radius: 12px; margin-top: 15px; font-size: 14px; border: 1px solid #333;">';
            
            ticketRows.forEach(row => {
                const category = row.querySelector('.ticket-tier-badge').textContent;
                const qtyInfo = row.querySelector('.ticket-qty-info').textContent;
                const total = row.querySelector('.ticket-total-cell').textContent;
                const qty = qtyInfo.split(' × ')[1];
                
                ticketHtml += `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; border-bottom: 1px solid #333; padding-bottom: 8px;">
                        <span style="color: #aaa;">${category} (${qty}x)</span>
                        <span style="font-weight: 600; color: #fff;">${total}</span>
                    </div>`;
            });

            const selectedPayElement = document.querySelector('.pay-option.selected .pay-name');
            const paymentName = selectedPayElement ? selectedPayElement.textContent : 'N/A';
            const grandTotal = document.getElementById('grand-total').textContent;

            ticketHtml += `
                <div style="margin-top: 15px; padding-top: 10px; border-top: 2px dashed #444;">
                    <div style="display: flex; justify-content: space-between; font-weight: 800; font-size: 18px; color: #6c5ce7;">
                        <span>Total Bayar</span>
                        <span>${grandTotal}</span>
                    </div>
                </div>
            </div>`;

            Swal.fire({
                title: 'Konfirmasi Pesanan',
                html: `
                    <div style="text-align: left;">
                        <p style="font-size: 13px; color: #aaa; margin-bottom: 15px;">Mohon periksa kembali detail pesanan Anda sebelum melanjutkan pembayaran.</p>
                        <div style="margin-bottom: 15px;">
                            <label style="font-size: 10px; text-transform: uppercase; font-weight: 800; color: #666; letter-spacing: 1px; display: block; margin-bottom: 5px;">Email Pembeli</label>
                            <div style="background: #252525; padding: 10px; border-radius: 10px; color: #eee; font-weight: 600;"><?php echo e(Auth::user()->email); ?></div>
                            <small style="color: #f5c842; font-size: 11px; margin-top: 4px; display: block;">*E-Ticket akan dikirim ke email ini</small>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="font-size: 10px; text-transform: uppercase; font-weight: 800; color: #666; letter-spacing: 1px; display: block; margin-bottom: 5px;">Metode Pembayaran</label>
                            <div style="background: #252525; padding: 10px; border-radius: 10px; color: #eee; font-weight: 600;">${paymentName}</div>
                        </div>
                        <label style="font-size: 10px; text-transform: uppercase; font-weight: 800; color: #666; letter-spacing: 1px; display: block;">Item Tiket</label>
                        ${ticketHtml}
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Lanjutkan Pembayaran',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                background: '#1a1a1a',
                color: '#fff',
                confirmButtonColor: '#6c5ce7',
                cancelButtonColor: '#252525',
                width: '450px',
                customClass: {
                    popup: 'swal-dark-popup',
                    confirmButton: 'swal-confirm-btn',
                    cancelButton: 'swal-cancel-btn'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Harap tunggu sebentar',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        background: '#1a1a1a',
                        color: '#fff',
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    const form = document.querySelector('form[action="<?php echo e(url('/paynow')); ?>"]');
                    if (form) form.submit();
                }
            });
        }
    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.index', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/frontend/page/bayartiket.blade.php ENDPATH**/ ?>