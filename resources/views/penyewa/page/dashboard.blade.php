@extends('penyewa.app')

@section('content')
    <div class="main-container container-fluid">

        <!-- PAGE-HEADER -->
        <div class="page-header d-flex justify-content-between">
            <h1 class="page-title">Dashboard</h1>
            <button class="btn btn-primary" data-bs-target="#modalCash" data-bs-effect="effect-sign" data-bs-toggle="modal">
                Jual Ticket
            </button>
            @include('penyewa.molecul.modalCash')
        </div>
        <!-- PAGE-HEADER END -->

        <!-- ROW-1 -->
        <div class="row">
            @if (session('success'))
                <div class="alert alert-primary">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-primary">
                    {{ session('error') }}
                </div>
            @endif
            <div class="col-lg-12 col-md-12 col-sm-12 col-xl-12">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xl-4">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="mt-2">
                                        <h6>Total Omset (Seluruh)</h6>
                                        <h2 class="mb-0 number-font">Rp {{ number_format($totalAmount, 0, ',', '.') }}</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="mt-2">
                                        <h6>Total Transaksi Success</h6>
                                        <h2 class="mb-0 number-font">{{ number_format($totalTransaksi, 0, ',', '.') }}</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="mt-2">
                                        <h6>Total Tiket Terjual</h6>
                                        <h2 class="mb-0 number-font">{{ number_format($totalTiket, 0, ',', '.') }}</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-6 col-sm-12 col-xl-2">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="mt-2">
                                        <h6>Total Event</h6>
                                        <h2 class="mb-0 number-font">{{ $eventCount }}</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ROW-1 END -->
        <hr>
        <!-- ROW-2 -->
        <div class="row my-4 d-lg-flex align-items-center">
            <div class="alert alert-info justify-content-between text-center">
                <strong>Data Gender Semua transaksi</strong>
                <div class="d-flex gap-4 justify-content-center">
                    <div class=""> <span class="fw-bold">Persentase Pria:</span> {{ number_format($dataUser[2], 2) }}%
                    </div>
                    <div class=""><span class="fw-bold">Persentase Wanita:</span>
                        {{ number_format($dataUser[3], 2) }}%</div>
                </div>
            </div>
            <div class="col-lg-3 offset my-2">
                <div class="alert alert-primary d-lg-flex  justify-content-between align-items-center">

                    <div>
                        <span class="fw-bold">Pria:</span> {{ $dataUser[0] }}
                        <br>
                        <span class="fw-bold">Wanita:</span> {{ $dataUser[1] }}
                    </div>

                </div>
            </div>
            @foreach ($birtday as $index => $genders)
                <div class="col-lg-3 my-2">
                    <div class="alert alert-primary justify-content-between align-items-center">
                        <strong>{{ $index }}</strong>
                        <br>
                        <div class="d-flex gap-2">
                            <p>Pria: {{ $genders['pria'] ?? 0 }}</p>
                            <p>Wanita: {{ $genders['wanita'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                {{-- <h4>{{ $index }}</h4> --}}
            @endforeach

        </div>
        <hr>

        <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sales Analytics (Cash vs Online)</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex mx-auto text-center justify-content-center mb-4 flex-wrap">
                            <div class="d-flex align-items-center me-4">
                                <span class="dot-label my-auto me-2" style="background-color: #6c5ffc;"></span> Omset Online
                            </div>
                            <div class="d-flex align-items-center me-4">
                                <span class="dot-label my-auto me-2" style="background-color: #05c3fb;"></span> Omset Cash
                            </div>
                            <div class="d-flex align-items-center me-4">
                                <span class="dot-label my-auto me-2" style="background-color: #19b159;"></span> Tiket Online
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="dot-label my-auto me-2" style="background-color: #f5b849;"></span> Tiket Cash
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chart" class="h-275"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>
@endsection