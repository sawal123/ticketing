@extends('frontend.index')
@section('content')
<link rel="stylesheet" href="{{ asset('landing/css/list-transaksi.css') }}">

    <div class="" style="height: 150px; "></div>
    <!-- PAGE -->

    <div class="page-wraping">

        <!-- HEADER -->
        <div class="page-header">
            <div>
                <div class="page-eyebrow">Akun · Riwayat</div>
                <h1 class="page-title">Riwayat Transaksi</h1>
            </div>
            <div class="header-meta">
                <div class="count-pill">
                    <strong id="txCount">{{ count($transaksi) }}</strong> Transaksi
                </div>
            </div>
        </div>

        <!-- FILTER -->
        <div class="filter-bar">
            <span class="filter-label">Filter</span>
            <div class="filter-chips">
                <button class="chip all active" onclick="filterTx('all', this)">Semua</button>
                <button class="chip paid" onclick="filterTx('paid', this)">Lunas</button>
                <button class="chip unpaid" onclick="filterTx('unpaid', this)">Belum Bayar</button>
                <button class="chip pending" onclick="filterTx('pending', this)">Pending</button>
            </div>
        </div>

        <!-- TRANSACTION LIST -->
        <div class="tx-list" id="txList">

            @forelse ($transaksi as $item)
                <div class="tx-card" data-status="{{ strtolower($item->status) }}">
                    <div class="tx-card-inner">

                        <!-- THUMB -->
                        <div class="tx-thumb">
                            <img src="{{ asset('storage/cover/' . $item->cover) }}"
                                style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
                        </div>

                        <!-- INFO -->
                        <div class="tx-info">
                            <div class="tx-top">
                                <span class="tx-event-name">{{ $item->event_name ?? 'Event Tidak Diketahui' }}</span>

                                {{-- @php
                                    $status = strtolower($item->status);

                                    $statusLabel = match ($status) {
                                        'paid' => 'Lunas',
                                        'unpaid' => 'Belum Bayar',
                                        'pending' => 'Pending',
                                        'cancelled' => 'Dibatalkan',
                                        default => ucfirst($status),
                                    };
                                @endphp --}}
                                <span class="tx-status 
                {{ strtolower($item->status) }}">
                                    {{ ucfirst(strtolower($item->status)) }}
                                </span>
                            </div>

                            <div class="tx-meta">
                                <div class="tx-meta-item">
                                    <span class="tx-meta-label">Invoice</span>
                                    <span class="tx-meta-value invoice">{{ $item->invoice }}</span>
                                </div>

                                <div class="tx-meta-item">
                                    <span class="tx-meta-label">Tanggal</span>
                                    <span class="tx-meta-value">
                                        {{ \Carbon\Carbon::parse($item->created_at)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                                    </span>
                                </div>

                                <div class="tx-meta-item">
                                    <span class="tx-meta-label">Qty</span>
                                    <span class="tx-meta-value">
                                        {{ $item->total_quantity }} Tiket
                                    </span>
                                </div>

                                <div class="tx-meta-item">
                                    <span class="tx-meta-label">Total</span>
                                    <span class="tx-meta-value total">
                                        Rp {{ number_format($item->total_harga ?? 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- ACTION -->
                        <div class="tx-actions">

                            {{-- BUTTON BAYAR --}}
                            @if ($item->status === 'UNPAID')
                                <a href="{{ url('/payment/' . $item->uid) }}" class="btn-pay">
                                    Bayar
                                </a>
                            @endif

                            {{-- DETAIL --}}
                            <a href="{{ url('/detail-ticket/' . $item->uid . '/' . Auth::user()->uid) }}"
                                class="btn-detail">
                                Detail
                            </a>

                            {{-- DELETE --}}
                            @if ($item->status === 'UNPAID' || $item->status === 'CANCELLED')
                                <a href="javascript:void(0)"
                                    onclick="confirmDelete('{{ url('/detail-ticket/delete/' . $item->uid . '/' . Auth::user()->uid) }}')"
                                    class="btn-delete">
                                    Hapus
                                </a>
                            @endif

                        </div>

                    </div>
                </div>

            @empty
                <div class="empty-state" id="emptyState">
                    <div class="empty-icon">🎫</div>
                    <div class="empty-title">Tidak ada transaksi</div>
                    <div class="empty-sub">Belum ada transaksi yang tersedia.</div>
                </div>
            @endforelse

        </div>

    </div>

    <script>
        // ================= DROPDOWN =================




        // ================= FILTER =================
        let currentFilter = 'all';

        function filterTx(status, el) {
            currentFilter = status;

            // update active chip
            document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
            if (el) el.classList.add('active');

            applyFilter();
        }

        function applyFilter() {
            const cards = document.querySelectorAll('.tx-card');
            let visible = 0;

            cards.forEach(card => {
                const cardStatus = card.dataset.status;

                const show = currentFilter === 'all' || cardStatus === currentFilter;

                if (show) {
                    card.style.display = '';
                    visible++;
                } else {
                    card.style.display = 'none';
                }
            });

            // update counter
            const counter = document.getElementById('txCount');
            if (counter) counter.textContent = visible;

            // empty state
            const empty = document.getElementById('emptyState');
            if (empty) {
                empty.style.display = visible === 0 ? 'flex' : 'none';
            }
        }


        // ================= DELETE =================
        function confirmDelete(url) {
            Swal.fire({
                title: 'Hapus Transaksi?',
                text: "Data transaksi yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff4d4d',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#1a1625',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            })
        }


        // ================= INIT =================
        document.addEventListener('DOMContentLoaded', function() {
            applyFilter(); // initial load

            // Session Success Handler
            @if (session('deleteList') || session('delete') || session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: "{{ session('deleteList') ?? (session('delete') ?? session('success')) }}",
                    showConfirmButton: false,
                    timer: 2000,
                    background: '#1a1625',
                    color: '#ffffff'
                });
            @endif
        });
    </script>
@endsection
