@extends('penyewa.app')

@section('content')
    <style>
        /* CUSTOM UI DASHBOARD GOTIK */
        .dashboard-bg {
            background-color: #F8F9FA !important;
        }

        .card-ui {
            border: none;
            border-radius: 14px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.03);
            background: #fff;
            margin-bottom: 20px;
        }

        .stat-label {
            font-size: 12px;
            color: #8F9BB3;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .text-huge {
            font-size: 28px;
            font-weight: 700;
            color: #1A202C;
            letter-spacing: -0.5px;
        }

        .text-large {
            font-size: 22px;
            font-weight: 700;
            color: #1A202C;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .box-stat {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.03);
            padding: 15px 12px;
        }

        .gender-badge {
            background: #F4F5F7;
            border-radius: 10px;
            padding: 12px;
            text-align: left;
        }

        .gender-label {
            font-size: 11px;
            color: #8F9BB3;
            font-weight: 500;
        }

        .gender-val {
            font-size: 18px;
            font-weight: 700;
            color: #5A67D8;
            margin-top: 4px;
        }

        .btn-jual {
            background-color: #5A67D8;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
        }

        .btn-jual:hover {
            background-color: #4C51BF;
            color: #fff;
        }

        .btn-trx-online {
            background-color: #5A67D8;
            color: white;
            font-weight: 500;
        }

        .btn-trx-cash {
            background-color: #5A67D8;
            color: white;
            font-weight: 500;
        }

        .link-detail {
            font-size: 12px;
            color: #5A67D8;
            text-decoration: underline;
            cursor: pointer;
        }

        /* MODAL GENDER STYLES */
        .modal-gender-content {
            border-radius: 16px;
            border: none;
        }

        .gender-row {
            background-color: #F8F9FA;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .gender-row .title {
            color: #6C757D;
            font-size: 13px;
            font-weight: 500;
        }

        .gender-row .value {
            color: #5A67D8;
            font-weight: 700;
            font-size: 16px;
        }

        @media (max-width: 576px) {
            .stat-grid {
                gap: 10px;
            }
        }
    </style>

    <div class="main-container container-fluid dashboard-bg pt-4 pb-5">

        <!-- PAGE-HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title fw-bold text-dark mb-0" style="font-size: 20px;">Dashboard</h1>
            <button class="btn btn-jual" data-bs-target="#modalCash" data-bs-effect="effect-sign" data-bs-toggle="modal">
                Jual Tiket
            </button>
            @include('penyewa.molecul.modalCash')
        </div>

        @if (session('success'))
            <div class="alert alert-success rounded-3">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
        @endif

        <!-- ROW DESKTOP GRID -->
        <div class="row">

            <!-- KOLOM KIRI (Stats & Gender) -->
            <div class="col-lg-7 col-xl-7">
                <!-- Total Omset Card -->
                <div class="card-ui p-4">
                    <div class="stat-label">Total Omset (Seluruh)</div>
                    <div class="text-huge">Rp {{ number_format($totalAmount, 0, ',', '.') }}</div>
                </div>

                <!-- 3 Mini Stats -->
                <div class="stat-grid mb-4">
                    <div class="box-stat">
                        <div class="stat-label">Total Transaksi</div>
                        <div class="text-large">{{ number_format($totalTransaksi, 0, ',', '.') }}</div>
                    </div>
                    <div class="box-stat">
                        <div class="stat-label">Total Tiket (Sell)</div>
                        <div class="text-large">{{ number_format($totalTiket, 0, ',', '.') }}</div>
                    </div>
                    <div class="box-stat">
                        <div class="stat-label">Total Event</div>
                        <div class="text-large">{{ $eventCount }}</div>
                    </div>
                </div>

                <!-- Data Gender Card -->
                <div class="card-ui p-4 mb-lg-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-label mb-0">Data gender semua transaksi</div>
                        <a class="link-detail" data-bs-toggle="modal" data-bs-target="#modalGenderDetail">Lihat detail</a>
                    </div>

                    <div class="row gx-3">
                        <div class="col-6">
                            <div class="gender-badge">
                                <div class="gender-label">Presentase Pria</div>
                                <div class="gender-val">{{ number_format($dataUser[2], 0) }}%</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="gender-badge">
                                <div class="gender-label">Presentase Wanita</div>
                                <div class="gender-val">{{ number_format($dataUser[3], 0) }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN (Event Aktif) -->
            <div class="col-lg-5 col-xl-5 mt-4 mt-lg-0">
                <div class="d-flex flex-column h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-dark fw-bold" style="font-size: 13px;">Event aktif :</div>
                        <a href="{{ url('/dashboard/event') }}" class="link-detail">Lihat semua</a>
                    </div>

                    <!-- Tampilan Event Aktif Sesuai Screenshot -->
                    <div class="card-ui p-3 flex-grow-1 d-flex flex-column">
                        @if($events)
                            <a href="{{ url('dashboard/event/eventDetail/' . $events->uid) }}"
                                class="w-100 mb-3 flex-grow-1 d-block" style="min-height: 180px;">
                                <img class="img-fluid rounded-3 w-100" src="{{ asset('storage/cover/' . $events->cover) }}"
                                    alt="cover-event" style="height: 100%; min-height: 180px; object-fit: cover;">
                            </a>

                            <div class="mt-auto">
                                <h5 class="fw-bold text-dark mb-3">{{ $events->event ?? 'Event Aktif' }}</h5>
                                <div class="d-flex gap-2">
                                    <a href="{{ url('dashboard/event/eventDetail/' . $events->uid) }}"
                                        class="btn btn-outline-dark rounded-2 flex-grow-1"
                                        style="font-size: 12px; font-weight: 500;">Detail Event</a>
                                    <a href="{{ url('dashboard/transaksi/' . $events->uid) }}"
                                        class="btn btn-trx-online rounded-2 flex-grow-1" style="font-size: 12px;">Trx
                                        Online</a>
                                    <a href="{{ url('dashboard/cash/' . $events->uid) }}"
                                        class="btn btn-trx-cash rounded-2 flex-grow-1" style="font-size: 12px;">Trx
                                        Cash</a>
                                </div>
                            </div>
                        @else
                            <div class="text-center text-muted flex-grow-1 d-flex align-items-center justify-content-center flex-column"
                                style="min-height: 200px;">
                                <i class="fa fa-image mb-3" style="font-size: 40px; opacity: 0.3;"></i>
                                <span style="font-size: 14px;">Belum ada event yang aktif</span>
                                <a href="{{ url('/dashboard/event/addEvent') }}" class="btn btn-primary btn-sm mt-3 px-4"
                                    style="border-radius: 8px;">Buat Event</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
        <!-- END ROW DESKTOP GRID -->

        <!-- ROW BAWAH: Sales Analytics Chart -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card-ui p-4">
                    <h6 class="fw-bold mb-4" style="color: #2F3542; font-size: 15px;">Sales Analytics (Cash vs Online)</h6>
                    <div class="d-flex flex-wrap justify-content-center mb-3 text-muted" style="font-size: 12px;">
                        <div class="me-3 mb-2 d-flex align-items-center">
                            <span class="rounded-circle me-2"
                                style="width: 8px; height: 8px; background-color: #6c5ffc; display: inline-block;"></span>
                            Omset Online
                        </div>
                        <div class="me-3 mb-2 d-flex align-items-center">
                            <span class="rounded-circle me-2"
                                style="width: 8px; height: 8px; background-color: #05c3fb; display: inline-block;"></span>
                            Omset Cash
                        </div>
                        <div class="me-3 mb-2 d-flex align-items-center">
                            <span class="rounded-circle me-2"
                                style="width: 8px; height: 8px; background-color: #19b159; display: inline-block;"></span>
                            Tiket Online
                        </div>
                        <div class="mb-2 d-flex align-items-center">
                            <span class="rounded-circle me-2"
                                style="width: 8px; height: 8px; background-color: #f5b849; display: inline-block;"></span>
                            Tiket Cash
                        </div>
                    </div>
                    <div class="chart-container" style="height: 250px; position: relative;">
                        <canvas id="chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Detail Gender -->
    <div class="modal fade" id="modalGenderDetail" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content modal-gender-content p-2">
                <div class="modal-header border-bottom-0 pb-1">
                    <h6 class="modal-title fw-bold text-dark mb-0">Detail data gender</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <!-- Data Pria/Wanita -->
                    <div class="d-flex gap-2 mb-3">
                        <div class="gender-row mb-0 flex-grow-1 px-3">
                            <span class="title">Pria</span>
                            <span class="value">{{ $dataUser[0] }}</span>
                        </div>
                        <div class="gender-row mb-0 flex-grow-1 px-3">
                            <span class="title">Wanita</span>
                            <span class="value">{{ $dataUser[1] }}</span>
                        </div>
                    </div>

                    <!-- Range Umur -->
                    @forelse ($birtday as $index => $genders)
                        <div class="gender-row">
                            <span class="title">{{ $index }}</span>
                            <!-- Jumlah total Pria + Wanita di range ini -->
                            <span class="value">{{ ($genders['pria'] ?? 0) + ($genders['wanita'] ?? 0) }}</span>
                        </div>
                    @empty
                        <div class="text-center text-muted" style="font-size: 12px;">Belum ada data umur</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

@endsection