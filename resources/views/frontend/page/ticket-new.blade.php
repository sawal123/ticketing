@extends('frontend.index')
@section('content')
    <link rel="stylesheet" href="{{ asset('landing/css/ticket.css') }}">

    <div style="height:50px;"></div>
    <!-- HERO -->
    <div class="hero ">
        <div class="hero-visual">
            <!-- Using a gradient + art placeholder since no external img -->
            <div
                style="width:100%;height:100%;background:linear-gradient(135deg,#1a0050 0%,#3b006b 30%,#0d3b8c 60%,#0a2060 100%);display:flex;align-items:center;justify-content:center;position:relative;">
                <img src="{{ asset('/storage/cover/' . $ticket->cover) }}" alt="" class="hero-img ">
            </div>

        </div>

        <!-- TICKET PANEL -->

        <div class="ticket-panel gap-2">
            <div class="panel-header">✦ Ticket Kategori</div>

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ url('/checkout') }}" method="post" class="ticket-purchase-form">
                @csrf
                <input type="hidden" name="eventUid" value="{{ $ticket->uid }}">

                @if ($list->count() > 0)
                    <div class="panel-body" style="overflow-y: scroll; max-height: 300px;">
                        @foreach ($list as $key => $hargaItem)
                            @php
                                $kategori = $hargaItem->kategori;
                                $qty = $hargaItem->qty;
                                $sold = $jmlhQty[$kategori] ?? 0;
                            @endphp

                            <div class="ticket-tier">
                                <div class="tier-info">
                                    <div class="tier-name">
                                        {{ $hargaItem->kategori }}
                                    </div>
                                    <div class="tier-price">
                                        <span class="currency">Rp</span>
                                        {{ number_format($hargaItem->harga, 0, ',', '.') }}
                                    </div>
                                </div>

                                @if ($sold < $qty && $hargaItem->status === 'active')
                                    <div class="qty-control ticket-quantity-control input-wrapper"
                                        data-target="quantity{{ $loop->index }}" data-price="{{ $hargaItem->harga }}" data-max="5">

                                        <input type="hidden" class="ticket-price-input price-input" name="harga{{ $loop->index }}"
                                            value="{{ $hargaItem->harga }}">
                                        <input type="hidden" name="kategori{{ $loop->index }}" value="{{ $hargaItem->kategori }}">

                                        <button type="button" class="qty-btn btn-minus"
                                            data-target="quantity{{ $loop->index }}">−</button>

                                        <input type="text"
                                            class="form-control qty-num p-0 qty-input text-center border-0 bg-transparent quantity{{ $loop->index }}"
                                            value="0" max="5" step="1" name="ticket{{ $loop->index }}" readonly>

                                        <input type="hidden" name="orderBy{{ $loop->index }}" value="{{ $loop->index + 1 }}">

                                        <button type="button" class="qty-btn btn-plus"
                                            data-target="quantity{{ $loop->index }}">+</button>
                                    </div>
                                @else
                                    <div class="sold-out-badge">Sold Out</div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="panel-total text-muted">
                        <div>
                            <div class="total-label">Total</div>
                            <div class=" total">Rp 0</div>
                        </div>
                        <button type="submit" class="btn-checkout checkButton" disabled>
                            Checkout →
                        </button>
                    </div>
                @else
                    <p>Ticket Belum Tersedia...</p>
                @endif
            </form>

        </div>

    </div>

    <!-- CONTENT -->
    <div class="content-area">
        <div class="event-info">

            <!-- TITLE -->
            <div class="section-block">
                <div
                    style="display:flex;align-items:flex-center;justify-content:space-between;gap:16px;margin-bottom:14px;">
                    <h1 class="event-title">{{ $ticket->event }}</h1>
                    <div style="padding-top:12px;">
                        <div class="status-pill">{{ $ticket->status === 'active' ? 'Active' : 'Close' }}</div>
                    </div>
                </div>
                <div class="event-meta">
                    <div class="meta-item">
                        <div class="meta-icon">📅</div>
                        <span>{{ date('Y-m-d H:i', strtotime($ticket->tanggal)) }}</span>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon">📍</div>
                        <span>{{ $ticket->alamat }}</span>
                    </div>
                </div>
                <a href="{{ $ticket->map }}" class="btn-location">🗺 View Location</a>
            </div>

            <style>
            </style>

            <!-- DESCRIPTION -->
            <div class="section-block">
                <div class="section-label">Deskripsi</div>


                <div class="description-text" id="descText">
                    {!! $ticket->deskripsi !!}
                </div>


                <button class="read-more" id="readMoreBtn" onclick="toggleDescription()">
                    Baca Selengkapnya ↓
                </button>
            </div>

            {{-- <p class="description-highlight">
                PURNAMA BERSANTAI 2026 is back — we won't stop, we will continue the journey to be your favorite
                next-level Music Festival in Medan!!!
            </p> --}}

            <!-- TALENT -->
            <div class="talent-section">
                <div class="section-label">Lineup Talent</div>
                <div class="talent-grid">
                    @foreach ($tickets as $talent)
                        <div class="talent-card">
                            <img src="{{ asset('/storage/talent/' . $talent->gambar) }}" class="talent-avatar"
                                style="width: 100px; height:100px; object-fit: cover" alt="{{ $talent->talent }}">

                            <div>
                                <div class="talent-name">{{ $talent->talent }}</div>
                                {{-- <div class="talent-tag">Pop · Indie</div> --}}
                            </div>
                        </div>
                    @endforeach


                </div>
            </div>
        </div>

        <div class="location-card">
            <div class="section-label">Lokasi Event</div>
            <div class="map-placeholder">
                @if ($ticket->map)
                    <iframe src="https://www.google.com/maps?q={{ urlencode($ticket->alamat) }}&output=embed" loading="lazy"
                        allowfullscreen>
                    </iframe>
                @else
                    🗺️
                @endif
            </div>
            <div class="talent-name" style="margin-bottom:4px;">{{ $ticket->alamat }}</div>
            <a href="{{ $ticket->map }}" class="map-label">Lihat di Google Map →</a>
        </div> --}}

        {{-- <div class="location-card">
            <div class="section-label">Informasi Event</div>
            <div style="display:flex;flex-direction:column;gap:14px;margin-top:4px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="color:var(--muted);font-size:13px;">Tanggal</span>
                    <span style="font-family:'Space Mono',monospace;font-size:12px;color:var(--text);">21 Agu
                        2026</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="color:var(--muted);font-size:13px;">Waktu Mulai</span>
                    <span style="font-family:'Space Mono',monospace;font-size:12px;color:var(--text);">15:00 WIB</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="color:var(--muted);font-size:13px;">Kategori</span>
                    <span style="font-family:'Space Mono',monospace;font-size:12px;color:var(--gold);">Music
                        Festival</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="color:var(--muted);font-size:13px;">Status</span>
                    <div class="status-pill">● Active</div>
                </div>
            </div>
        </div> --}}
    </div>
    </div>

    <!-- MOBILE BOTTOM BAR -->
    <div class="mobile-buy-bar">
        <div class="price-info">
            <div class="price-from">Mulai dari</div>
            <div class="price-value">Rp 160.000</div>
        </div>
        <button class="btn-beli" onclick="openModal()">Beli Tiket</button>
    </div>

    <!-- BOTTOM SHEET MODAL -->
    <div class="modal-overlay" id="ticketModal" onclick="handleOverlayClick(event)">
        <div class="modal-sheet" id="modalSheet">
            <div class="sheet-handle"></div>

            <div class="sheet-header">
                <div class="sheet-title">Beli Tiket</div>
                <button class="sheet-close" onclick="closeModal()">×</button>
            </div>

            <div class="offcanvas-body small text-start">
                <form action="{{ url('/checkout') }}" method="post" class="ticket-purchase-form">
                    <input type="hidden" name="eventUid" value="{{ $ticket->uid }}">

                    @if ($lists->count() > 0)
                        @csrf

                        @foreach ($lists as $key => $hargaItemMobile)
                            @php
                                $kategoriMob = $hargaItemMobile->kategori;
                                $qtyMob = $hargaItemMobile->qty;
                                $soldMob = $jmlhQty[$kategoriMob] ?? 0;
                            @endphp

                            <div class="sheet-tier">
                                <div class="tier-info">
                                    <div class="tier-name">
                                        {{ $hargaItemMobile->kategori }}
                                    </div>
                                    <div class="tier-price">
                                        <span class="currency">Rp</span>
                                        {{ number_format($hargaItemMobile->harga, 0, ',', '.') }}
                                    </div>
                                </div>

                                @if ($soldMob < $qtyMob && $hargaItemMobile->status === 'active')
                                    <div class="qty-control ticket-quantity-control input-wrapper"
                                        data-target="quantity{{ $loop->index }}" data-price="{{ $hargaItemMobile->harga }}"
                                        data-max="5">

                                        <input type="hidden" class="ticket-price-input price-input" name="harga{{ $loop->index }}"
                                            value="{{ $hargaItemMobile->harga }}">

                                        <input type="hidden" name="kategori{{ $loop->index }}" value="{{ $hargaItemMobile->kategori }}">

                                        <button type="button" class="qty-btn btn-minus" data-target="quantity{{ $loop->index }}">
                                            <i class="fa fa-minus"></i>
                                        </button>

                                        <input type="text"
                                            class="qty-num qty-input text-center border-0 bg-transparent quantity{{ $loop->index }}"
                                            min="0" max="5" step="1" value="0" name="ticket{{ $loop->index }}" readonly>

                                        <input type="hidden" name="orderBy{{ $loop->index }}" value="{{ $loop->index + 1 }}">

                                        <button type="button" class="qty-btn btn-plus" data-target="quantity{{ $loop->index }}">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>
                                @else
                                    <div class="sold-out-badge">Sold Out</div>
                                @endif
                            </div>
                        @endforeach

                        <div class="sheet-total">
                            <div>
                                <div class="sheet-total-label">Total</div>
                                <div class="sheet-total-amount total">Rp 0</div>
                            </div>
                            <button type="submit" class="btn-checkout checkButton" disabled style="padding:14px 24px;">
                                Check Out →
                            </button>
                        </div>
                    @else
                        <p>Ticket Belum Tersedia...</p>
                    @endif
                </form>


            </div>
        </div>
    </div>

    <div class="footer-space"></div>

    <script>
        function openModal() {
            const modal = document.getElementById('ticketModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            // small timeout for animation
            setTimeout(() => modal.classList.add('open'), 10);
        }

        function closeModal() {
            const modal = document.getElementById('ticketModal');
            const sheet = document.getElementById('modalSheet');
            sheet.style.animation = 'slideDown 0.25s cubic-bezier(0.32,0.72,0,1) both';
            setTimeout(() => {
                modal.style.display = 'none';
                modal.classList.remove('open');
                sheet.style.animation = '';
                document.body.style.overflow = '';
            }, 250);
        }

        function handleOverlayClick(e) {
            if (e.target === document.getElementById('ticketModal')) closeModal();
        }

        function toggleMenu() {
            // simple mobile menu toggle placeholder
            alert('Menu navigasi\n\n• Beranda\n• Jelajahi Event\n• Sign In\n• Sign Up');
        }

        // Inject slideDown animation
        const style = document.createElement('style');
        style.textContent =
            `@keyframes slideDown { from { transform: translateY(0); } to { transform: translateY(100%); } }`;
        document.head.appendChild(style);


        // pertga total       document.addEventListener("DOMContentLoaded", function() {
        // Ambil semua elemen total dan tombol checkout (baik Desktop maupun Mobile)
        const totalDisplays = document.querySelectorAll(".total");
        const checkButtons = document.querySelectorAll(".checkButton");

        // 🔥 FUNGSI HITUNG TOTAL
        function calculateGrandTotal() {
            let grandTotal = 0;
            let totalQty = 0;

            const categories = new Map();

            document.querySelectorAll(".qty-input").forEach(input => {
                const classList = Array.from(input.classList);
                const targetClass = classList.find(c => c.startsWith('quantity'));

                if (targetClass) {
                    const qty = parseInt(input.value) || 0;
                    const wrapper = input.closest(".input-wrapper");
                    const price = wrapper ? parseFloat(wrapper.getAttribute("data-price")) : 0;

                    categories.set(targetClass, {
                        qty,
                        price
                    });
                }
            });

            categories.forEach(item => {
                grandTotal += (item.qty * item.price);
                totalQty += item.qty;
            });

            const formattedTotal = "Rp " + grandTotal.toLocaleString("id-ID");
            totalDisplays.forEach(el => {
                el.textContent = formattedTotal;
            });

            checkButtons.forEach(btn => {
                btn.disabled = (grandTotal <= 0);
            });

            // Update state tombol plus berdasarkan totalQty
            const plusButtons = document.querySelectorAll(".btn-plus");
            plusButtons.forEach(btn => {
                if (totalQty >= 5) {
                    btn.classList.add("opacity-50", "cursor-not-allowed");
                } else {
                    btn.classList.remove("opacity-50", "cursor-not-allowed");
                }
            });

            return totalQty;
        }

        // 🔥 EVENT LISTENER UNTUK KLIK (DELEGASI)
        document.addEventListener("click", function (e) {
            const btnPlus = e.target.closest(".btn-plus");
            const btnMinus = e.target.closest(".btn-minus");

            if (btnPlus) {
                const targetId = btnPlus.getAttribute("data-target");
                const inputs = document.querySelectorAll("." + targetId);

                // Hitung total saat ini sebelum menambah
                let currentTotal = 0;
                const categories = new Set();
                document.querySelectorAll(".qty-input").forEach(input => {
                    const classList = Array.from(input.classList);
                    const tClass = classList.find(c => c.startsWith('quantity'));
                    if (tClass && !categories.has(tClass)) {
                        currentTotal += parseInt(input.value) || 0;
                        categories.add(tClass);
                    }
                });

                if (currentTotal < 5) {
                    let currentQty = parseInt(inputs[0].value) || 0;
                    currentQty++;
                    inputs.forEach(input => input.value = currentQty);
                    calculateGrandTotal();
                } else {
                    // Optional: SweetAlert atau toast jika ada
                    // alert('Maksimal total pemesanan adalah 5 tiket.');
                }
            }

            if (btnMinus) {
                const targetId = btnMinus.getAttribute("data-target");
                const inputs = document.querySelectorAll("." + targetId);

                let currentQty = parseInt(inputs[0].value) || 0;

                if (currentQty > 0) {
                    currentQty--;
                    inputs.forEach(input => input.value = currentQty);
                    calculateGrandTotal();
                }
            }
        });

        // Inisialisasi awal
        calculateGrandTotal();

    </script>
@endsection