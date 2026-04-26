@extends('frontend.index')

@section('content')

    <link rel="stylesheet" href="{{ asset('landing/css/bayartiket.css') }}">

    <div class="" style="height: 50px; "></div>

    <!-- BREADCRUMB -->
    <div class="breadcrumb ">
        <span>Event</span>
        <span class="breadcrumb-sep">›</span>
        <span>{{ $event->event }}</span>
        <span class="breadcrumb-sep">›</span>
        <span style="color:var(--gold);">Checkout</span>
    </div>

    <!-- EMAIL ALERT -->
    <div class="email-alert">
        <div class="email-alert-inner">
            @if ($cart->status === 'SUCCESS')
                <div class="alert alert-info" role="alert">
                    <small>Barcode telah dikirim ke email Anda (periksa juga folder SPAM)!</small>
                </div>
            @endif
            <span class="alert-icon">✉️</span>
            <span class="alert-text">Pastikan email Anda aktif: <span
                    class="alert-email">{{ Auth::user()->email }}</span></span>
        </div>
    </div>

    <!-- MAIN -->
    <div class="checkout-layout">
        @if (session('success'))
            <div class="alert alert-primary">
                {{ session('success') }}
            </div>
        @endif

        <!-- LEFT: EVENT CARD -->
        <div class="event-card">
            <div class="event-banner">
                <img src="{{ asset('storage/cover/' . $event->cover) }}" class="event-banner" alt="...">
            </div>
            <div class="event-info-card">
                <div class="event-name">{{ $event->event }}</div>
                <div class="event-meta-row">📅 <span>{{ $event->tanggal }}</span></div>
                <div class="event-meta-row">📍 <span>{{ $event->alamat }}</span></div>
                <div class="invoice-tag">
                    <div>
                        <div class="invoice-label">No. Invoice</div>
                        <div class="invoice-value">{{ $cart->invoice }}</div>
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
                    @php
                        $fee = 0;
                        $total1 = 0;
                    @endphp
                    <table class="ticket-detail-table">
                        <thead>
                            <tr>
                                <th>Qty</th>
                                <th style="text-align:right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>


                            @foreach ($harga as $harga)
                                <tr class="ticket-row">

                                    <td>
                                        <div class="ticket-tier-badge">{{ $harga->kategori_harga }}</div>
                                        <div class="ticket-qty-info">Rp
                                            {{ number_format($harga->harga_ticket, 0, ',', '.') }} ×
                                            {{ $harga->quantity }}
                                        </div>
                                        @php
                                            $fee += $harga->quantity;
                                        @endphp
                                        <div class="ticket-qty-sub">{{ $fee }} tiket · Harga per tiket</div>
                                    </td>

                                    <td class="ticket-total-cell">
                                        @php
                                            $total1 = (int) $harga->quantity * (int) $harga->harga_ticket;
                                        @endphp
                                        Rp{{ number_format($total1 ?? 0) }}</td>


                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="ticket-summary-row">
                        <div class="ticket-count-pill">✓ Total {{ $fee }} Tiket</div>
                        <span style="font-size:12px;color:var(--muted);">Tiket akan dikirim via email</span>
                    </div>
                </div>
            </div>

            <!-- PAYMENT METHOD -->
            @if (!$cart->link)
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
                                @foreach ($payment as $gateway)
                                    <div class="pay-option" id="card{{ $gateway->id }}" style="cursor:pointer;"
                                        onclick="selectPayment({{ $gateway->id }},{{ $gateway->biaya }}, '{{ $gateway->biaya_type }}', this)">

                                        <div class="pay-logo" style="padding:8px;">
                                            <img src="{{ asset('storage/' . $gateway->icon) }}"
                                                style="width:40px; height:22px; object-fit:contain;"
                                                alt="{{ $gateway->payment }}">
                                        </div>

                                        <div>
                                            <div class="pay-name">{{ $gateway->payment }}</div>
                                            <div class="pay-fee">
                                                Biaya:
                                                @if ($gateway->biaya_type == 'rupiah')
                                                    Rp{{ number_format($gateway->biaya, 0, ',', '.') }}
                                                @else
                                                    {{ $gateway->biaya }}%
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                @endforeach
                            </div>

                        </div>
                    </div>
                </div>
            @endif

            <!-- VOUCHER -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="background:rgba(61,217,196,0.12);">🏷️</div>
                    <div class="card-title">Voucher</div>
                </div>

                <div class="card-body">
                    <div class="voucher-input-wrap">

                        <form action="{{ url('/checkVoucer') }}" method="post" style="display:flex; width:100%;"
                            class="voucher-input-wrap">
                            @csrf

                            @if ($cart->status !== 'SUCCESS')
                                <input type="hidden" name="event" value="{{ $event->uid }}">
                                <input type="hidden" name="cartUid" value="{{ $cart->uid }}">

                                <input type="text" class="voucher-input form-control" id="voucherInput " name="code"
                                    placeholder="Masukan Code Voucher.." value="{{ $voucher->code }}"
                                    {{ $cart->link ? 'readonly' : '' }}>

                                <button type="submit" class="btn-voucher" {{ $cart->link ? 'disabled' : '' }}>
                                    Gunakan
                                </button>
                            @endif
                        </form>

                    </div>

                    <!-- MESSAGE -->
                    <div id="voucherMsg" style="margin-top:10px;font-size:12px;">

                        @if (session('vError'))
                            <span style="color:#e8547a;">
                                {{ session('vError') }}
                            </span>
                        @endif

                        @if (session('voucher'))
                            <span style="color:#3dd9c4;">
                                {{ session('voucher') }}
                            </span>
                        @endif

                    </div>

                    <!-- REMOVE VOUCHER -->
                    @if (!$cart->link && $voucher->code)
                        <div style="margin-top:10px;">
                            <form action="{{ url('/closeVoucher') }}" method="post">
                                @csrf
                                <input type="hidden" name="event" value="{{ $event->uid }}">
                                <input type="hidden" name="cartUid" value="{{ $cart->uid }}">
                                <input type="hidden" name="code" value="{{ $voucher->code }}">

                                <span style="font-size:12px;">
                                    Tidak ingin menggunakan voucher <b>{{ $voucher->code }}</b>?
                                </span>

                                <button type="submit"
                                    style="background:#e8547a;color:#fff;border:none;padding:4px 8px;border-radius:6px;">
                                    ✕
                                </button>
                            </form>
                        </div>
                    @endif

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

                        {{-- VOUCHER (jika SUCCESS) --}}
                        @if ($cart->status === 'SUCCESS')
                            <div class="detail-row">
                                <span class="label">Voucher</span>
                                <span class="value">{{ $voucher->code }}</span>
                            </div>
                        @endif

                        {{-- TICKET --}}
                        <div class="detail-row">
                            <span class="label">Ticket</span>
                            <span class="value" id="subtotal-display">
                                Rp {{ number_format($total, 0, ',', '.') }}
                            </span>
                        </div>

                        {{-- DISCOUNT --}}
                        <div class="detail-row">
                            <span class="label">Discount</span>
                            <span class="value discount" id="discount-display">
                                -Rp {{ number_format($diskon, 0, ',', '.') }}
                            </span>
                        </div>

                        {{-- INTERNET FEE --}}
                        <div class="detail-row">
                            <span class="label">Internet Fee</span>
                            <span class="value" id="fee-display">Rp 0</span>
                        </div>

                        {{-- PAJAK --}}
                        <div class="detail-row">
                            <span class="label">Pajak ({{ $pajakPersen }}%)</span>
                            <span class="value tax">
                                Rp {{ number_format($nilaiPajak, 0, ',', '.') }}
                            </span>
                        </div>

                    </div>

                    {{-- TOTAL --}}
                    <div class="total-row">
                        <div class="total-label">Grand Total</div>
                        <div class="total-value" id="grand-total">
                            Rp {{ number_format($total, 0, ',', '.') }}
                        </div>
                    </div>

                    {{-- BUTTON --}}
                    @if ($cart->status === 'UNPAID' || $cart->status === 'PENDING')
                        <form action="{{ url('/paynow') }}" method="post" enctype="multipart/form-data">
                            @csrf

                            <input type="hidden" id="selectedPayment" name="payment_id" value="">
                            <input type="hidden" name="invoice" value="{{ $cart->invoice }}">
                            <input type="hidden" name="person" value="{{ Auth::user()->uid }}">
                            <input type="hidden" name="event" value="{{ $event->uid }}">
                            <input type="hidden" name="cartUid" value="{{ $cart->uid }}">

                            @if ($cart->status === 'UNPAID')
                                <button type="submit" class="btn-pay">
                                    <span>🔐</span>
                                    <span>Bayar Sekarang</span>
                                </button>
                            @else
                                <a href="{{ $cart->link }}" class="btn-pay"
                                    style="text-decoration:none;display:flex;justify-content:center;">
                                    <span>🔐</span>
                                    <span>Lanjutkan Pembayaran</span>
                                </a>
                            @endif
                        </form>
                    @else
                        <button type="submit" class="btn-pay" style="background:#3dd9c4;">
                            {{ $cart->status }}
                        </button>
                    @endif

                    <div class="security-note">
                        <span>🔒</span>
                        <span>Transaksi dijamin aman & terenkripsi</span>
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

            let diskon = {{ $diskon ?? 0 }};
            let nilaiPajak = {{ $nilaiPajak ?? 0 }};

            // UI SELECTED
            document.querySelectorAll('.pay-option').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            // ambil subtotal dari UI baru
            let total = parseRupiah(document.getElementById('subtotal-display').textContent);

            let fee = 0;

            if (biayaType === 'rupiah') {
                fee = biaya;
            } else if (biayaType === 'persen') {
                fee = (biaya / 100) * total;
            }

            // TOTAL AKHIR
            let totalAkhir = (total - diskon) + nilaiPajak + fee;

            // update UI baru
            document.getElementById('fee-display').textContent = formatRupiah(fee);
            document.getElementById('grand-total').textContent = formatRupiah(totalAkhir);
        }

        document.addEventListener('DOMContentLoaded', function() {

            let diskon = {{ $diskon ?? 0 }};
            let nilaiPajak = {{ $nilaiPajak ?? 0 }};

            let paymentId = {{ $iFee->id ?? 0 }};
            let biaya = {{ $iFee->biaya ?? 0 }};
            let biayaType = '{{ $iFee->biaya_type ?? 'rupiah' }}';

            let firstPaymentCard = document.querySelector('.pay-option');

            let total = parseRupiah(document.getElementById('subtotal-display').textContent);

            let fee = 0;

            if (biayaType === 'rupiah') {
                fee = biaya;
            } else if (biayaType === 'persen') {
                fee = (biaya / 100) * total;
            }

            let totalAkhir = (total - diskon) + nilaiPajak + fee;

            // set awal
            document.getElementById('fee-display').textContent = formatRupiah(fee);
            document.getElementById('grand-total').textContent = formatRupiah(totalAkhir);

            // auto select pertama
            if (firstPaymentCard) {
                selectPayment(paymentId, biaya, biayaType, firstPaymentCard);
            }
        });
    </script>
@endsection
