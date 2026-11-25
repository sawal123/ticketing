@extends('frontend.index')

@section('content')
    <link rel="stylesheet"
        href="{{ asset('landing/css/bayartiket.css') }}?v={{ file_exists(public_path('landing/css/bayartiket.css')) ? filemtime(public_path('landing/css/bayartiket.css')) : '1.0' }}">

    <style>
        .guest-finish-wrap {
            max-width: 860px;
            margin: 0 auto;
            padding: 88px 16px 64px;
        }

        .guest-finish-card {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--border-color, rgba(148, 163, 184, 0.25));
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 50px -20px rgba(15, 23, 42, 0.25);
        }

        .guest-finish-hero {
            padding: 32px 24px;
            text-align: center;
            border-bottom: 1px solid var(--border-color, rgba(148, 163, 184, 0.2));
        }

        .guest-finish-hero.is-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.14), rgba(5, 150, 105, 0.05));
        }

        .guest-finish-hero.is-pending {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.16), rgba(217, 119, 6, 0.05));
        }

        .guest-finish-hero.is-failed {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.14), rgba(220, 38, 38, 0.05));
        }

        .guest-finish-icon {
            width: 68px;
            height: 68px;
            margin: 0 auto 14px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .guest-finish-title {
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary, #0f172a);
        }

        .guest-finish-subtitle {
            margin: 0 auto;
            max-width: 560px;
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-secondary, #475569);
        }

        .guest-finish-actions {
            margin-top: 22px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
        }

        .guest-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .guest-btn:hover {
            transform: translateY(-1px);
            opacity: 0.94;
        }

        .guest-btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #ffffff;
        }

        .guest-btn-success {
            background: linear-gradient(135deg, #059669, #10b981);
            color: #ffffff;
        }

        .guest-btn-outline {
            background: transparent;
            color: var(--text-primary, #0f172a);
            border-color: var(--border-color, rgba(148, 163, 184, 0.4));
        }

        .guest-finish-body {
            padding: 24px;
            display: grid;
            gap: 20px;
        }

        .guest-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }

        .guest-info-box {
            padding: 14px 16px;
            border-radius: 14px;
            background: rgba(148, 163, 184, 0.08);
            border: 1px solid var(--border-color, rgba(148, 163, 184, 0.2));
        }

        .guest-info-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-secondary, #64748b);
            margin-bottom: 4px;
        }

        .guest-info-value {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary, #0f172a);
            word-break: break-word;
        }

        .guest-items-box {
            border-radius: 14px;
            border: 1px solid var(--border-color, rgba(148, 163, 184, 0.2));
            padding: 16px;
        }

        .guest-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            font-size: 14px;
            color: var(--text-primary, #0f172a);
        }

        .guest-item-row + .guest-item-row {
            border-top: 1px dashed var(--border-color, rgba(148, 163, 184, 0.25));
        }

        .guest-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            margin-top: 8px;
            border-top: 1px solid var(--border-color, rgba(148, 163, 184, 0.3));
            font-size: 16px;
            font-weight: 800;
            color: var(--text-primary, #0f172a);
        }

        .guest-account-tip {
            padding: 14px 16px;
            border-radius: 14px;
            background: rgba(99, 102, 241, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.22);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 13px;
            color: var(--text-secondary, #475569);
        }
    </style>

    <div class="guest-finish-wrap">
        <div class="guest-finish-card">
            @if ($isPaid)
                <div class="guest-finish-hero is-success">
                    <div class="guest-finish-icon">✅</div>
                    <h1 class="guest-finish-title">Pembayaran Berhasil!</h1>
                    <p class="guest-finish-subtitle">
                        Terima kasih, pesanan tiket Anda telah lunas dan terkonfirmasi. E-Ticket &amp; Barcode juga dikirim ke email
                        <strong>{{ $cart->buyerEmail() }}</strong> (periksa Inbox atau folder Spam).
                    </p>
                    <div class="guest-finish-actions">
                        @if (! empty($guestTicketAccessUrl))
                            <a href="{{ $guestTicketAccessUrl }}" class="guest-btn guest-btn-success">
                                <span>🎫</span>
                                <span>Buka E-Ticket &amp; Barcode</span>
                            </a>
                        @endif
                        <a href="{{ url('/') }}" class="guest-btn guest-btn-outline">
                            <span>Kembali ke Beranda</span>
                        </a>
                    </div>
                </div>
            @elseif ($isPending)
                <div class="guest-finish-hero is-pending">
                    <div class="guest-finish-icon">⏳</div>
                    <h1 class="guest-finish-title">Menunggu Konfirmasi Pembayaran</h1>
                    <p class="guest-finish-subtitle">
                        Jika Anda baru saja menyelesaikan pembayaran, sistem sedang memproses konfirmasi otomatis dari payment gateway.
                        Halaman ini akan diperbarui otomatis dalam beberapa detik, atau klik tombol di bawah.
                    </p>
                    <div class="guest-finish-actions">
                        <button type="button" onclick="window.location.reload()" class="guest-btn guest-btn-primary">
                            <span>🔄</span>
                            <span>Perbarui Status Pembayaran</span>
                        </button>
                        @if ($cart->hasActivePaymentLink())
                            <a href="{{ $cart->link }}" class="guest-btn guest-btn-outline">
                                <span>Lanjutkan Pembayaran</span>
                            </a>
                        @endif
                        <a href="{{ $checkoutUrl }}" class="guest-btn guest-btn-outline">
                            <span>Kembali ke Checkout</span>
                        </a>
                    </div>
                </div>
            @else
                <div class="guest-finish-hero is-failed">
                    <div class="guest-finish-icon">⚠️</div>
                    <h1 class="guest-finish-title">Transaksi Belum Selesai / Kedaluwarsa</h1>
                    <p class="guest-finish-subtitle">
                        Status transaksi Anda saat ini adalah <strong>{{ strtoupper((string) $cart->status) }}</strong>.
                        Silakan lakukan pemesanan ulang jika Anda masih ingin membeli tiket event ini.
                    </p>
                    <div class="guest-finish-actions">
                        <a href="{{ url('/detail-event/' . ($event->slug ?: $event->uid)) }}" class="guest-btn guest-btn-primary">
                            <span>Beli Tiket Lagi</span>
                        </a>
                        <a href="{{ url('/') }}" class="guest-btn guest-btn-outline">
                            <span>Kembali ke Beranda</span>
                        </a>
                    </div>
                </div>
            @endif

            <div class="guest-finish-body">
                <div class="guest-info-grid">
                    <div class="guest-info-box">
                        <div class="guest-info-label">No. Invoice</div>
                        <div class="guest-info-value" style="display:flex;align-items:center;gap:8px;">
                            <span id="guestFinishInvoice">{{ $cart->invoice }}</span>
                            <button type="button" onclick="copyGuestInvoice()" title="Salin invoice"
                                style="background:none;border:none;cursor:pointer;font-size:14px;">⎘</button>
                        </div>
                    </div>
                    <div class="guest-info-box">
                        <div class="guest-info-label">Event</div>
                        <div class="guest-info-value">{{ $event->event }}</div>
                        <div style="font-size:12px;opacity:.75;margin-top:2px;">{{ $event->tanggal }} • {{ $event->alamat }}</div>
                    </div>
                    <div class="guest-info-box">
                        <div class="guest-info-label">Pemegang Tiket</div>
                        <div class="guest-info-value">{{ $cart->buyerName() ?: '-' }}</div>
                        <div style="font-size:12px;opacity:.75;margin-top:2px;">{{ $cart->buyerEmail() ?: '-' }}</div>
                        @if (filled($cart->buyerPhone()))
                            <div style="font-size:12px;opacity:.75;margin-top:2px;">WA: {{ $cart->buyerPhone() }}</div>
                        @endif
                    </div>
                </div>

                <div class="guest-items-box">
                    <div class="guest-info-label" style="margin-bottom:8px;">Rincian Pembelian</div>
                    @foreach ($harga as $item)
                        <div class="guest-item-row">
                            <span>{{ $item->kategori_harga }} <strong>×{{ (int) $item->quantity }}</strong></span>
                            <span>Rp {{ number_format((int) $item->harga_ticket * (int) $item->quantity, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                    @if (($pricing['discount'] ?? 0) > 0)
                        <div class="guest-item-row" style="color:#059669;">
                            <span>Diskon Voucher</span>
                            <span>- Rp {{ number_format((int) $pricing['discount'], 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if (($pricing['tax_amount'] ?? 0) > 0)
                        <div class="guest-item-row">
                            <span>Pajak ({{ $pricing['tax_percent'] ?? 0 }}%)</span>
                            <span>Rp {{ number_format((int) $pricing['tax_amount'], 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if (($pricing['internet_fee'] ?? 0) > 0)
                        <div class="guest-item-row">
                            <span>Biaya Layanan</span>
                            <span>Rp {{ number_format((int) $pricing['internet_fee'], 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="guest-total-row">
                        <span>Total Pembayaran</span>
                        <span>Rp {{ number_format((int) $grandTotal, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="guest-account-tip">
                    <span>Simpan halaman ini atau cek email Anda untuk mengakses E-Ticket kapan saja tanpa perlu login.</span>
                    <a href="{{ route('register') }}" class="guest-btn guest-btn-outline" style="padding:8px 14px;font-size:12px;">
                        Buat Akun (Opsional)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyGuestInvoice() {
            const text = document.getElementById('guestFinishInvoice')?.textContent?.trim();
            if (!text || !navigator.clipboard) return;
            navigator.clipboard.writeText(text);
        }
    </script>

    @if ($isPending)
        <script>
            setTimeout(function () {
                window.location.reload();
            }, 6000);
        </script>
    @endif
@endsection
