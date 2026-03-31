@extends('penyewa.app')

@section('content')
    <div class="main-container container-fluid">

        <!-- PAGE-HEADER -->
        <div class="page-header">
            <a href="{{ url('/dashboard/event') }}" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Kembali</a>
            <h1 class="page-title">Event Details</h1>
            <div>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Event</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Event Details</li>
                </ol>
            </div>
        </div>
        @if (session('addEvent'))
            <div class="alert alert-success">
                {{ session('addEvent') }}
            </div>
        @endif
        <!-- PAGE-HEADER END -->

        <!-- ROW-1 OPEN -->
        <div class="row">

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row row-sm">
                            <div class="col-xl-5 col-lg-12 col-md-12">

                                <img class="img-thumbnail" src="{{ asset('storage/cover/' . $eventDetail->cover) }}" alt="">
                            </div>
                            <div class="details col-xl-7 col-lg-12 col-md-12 mt-4 mt-xl-0">
                                <div class="mt-2 mb-4">
                                    <h3 class="mb-3 fw-semibold">{{ $eventDetail->event }}</h3>
                                    <p>Status : <span style="font-weight: 800"
                                            class="font-weight-bold">{{ $eventDetail->status }}</span></p>
                                    <p>{{ $eventDetail->alamat }} <a href="{{ $eventDetail->map }}" target="blank"
                                            class="btn btn-primary">Kunjungi</a></p>
                                    <p>Biaya Layanan : Rp {{ $eventDetail->fee }}</p>

                                    <h4 class="mt-4"><b> Description</b></h4>
                                    <style>
                                        .container {
                                            max-height: 4em;
                                            /* Tampilkan hanya 3 baris teks */
                                            overflow: hidden;
                                        }

                                        .container.expanded {
                                            max-height: none;
                                            /* Tampilkan semua teks saat diperluas */
                                        }

                                        .content {
                                            margin: 0;
                                        }

                                        #readMoreBtn {
                                            display: block;
                                            margin-top: 10px;
                                        }
                                    </style>
                                    <div class="container">
                                        <p class="content">{!! $eventDetail->deskripsi !!}</p>
                                    </div>
                                    <button id="readMoreBtn" class="btn btn-outline-primary">Baca Selengkapnya</button>

                                    <hr>
                                    <div class="btn-list mt-4">
                                        <a href="{{ url('/dashboard/ubahEvents/' . $eventDetail->uid) }}"
                                            class="btn ripple btn-primary me-2"><i class="fe fe-edit"> </i>
                                            Edit</a>
                                        <button disabled="disabled" class="btn btn-success">
                                            @if ($eventDetail->konfirmasi === null)
                                                Menunggu Persetujuan
                                            @else
                                                Di Setujui
                                            @endif
                                        </button>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12 col-md-12">
                <style>
                    /* Custom Tab Menu Style according to reference */
                    .ref-tabs-container {
                        border-bottom: 1px solid #e2e8f0;
                        margin-bottom: 2rem;
                        position: relative;
                    }

                    .ref-tabs {
                        display: flex;
                        gap: 2rem;
                        padding: 0;
                        margin: 0;
                        list-style: none;
                    }

                    .ref-tabs li a {
                        color: #64748b;
                        /* dark gray */
                        font-weight: 600;
                        font-size: 1.05rem;
                        padding: 0.75rem 0.5rem;
                        text-decoration: none;
                        border-bottom: 3px solid transparent;
                        display: inline-block;
                        transition: all 0.3s ease;
                    }

                    .ref-tabs li a:hover {
                        color: #6366f1;
                        /* purple-blue hover */
                    }

                    .ref-tabs li a.active {
                        color: #6366f1;
                        border-bottom: 3px solid #f97316;
                        /* orange underline */
                    }

                    /* Teleport Wrapper Setup (Default: Desktop) */
                    .tabs-menu-body {
                        position: relative;
                    }

                    .teleport-wrapper {
                        position: absolute;
                        top: -80px;
                        right: 0;
                        z-index: 10;
                        margin: 0 !important;
                    }

                    /* Make Productdesc Card seamless */
                    .productdesc {
                        border: 0;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                        border-radius: 12px;
                    }

                    /* ========================================================= */
                    /* RESPONSIVE MOBILE TWEAKS (Layar HP di bawah 768px)        */
                    /* ========================================================= */
                    @media (max-width: 767px) {

                        /* 1. Tab Menu dibagi dua rata tengah */
                        .ref-tabs {
                            gap: 0;
                            width: 100%;
                        }

                        .ref-tabs li {
                            flex: 1;
                            text-align: center;
                        }

                        .ref-tabs li a {
                            display: block;
                            /* Area klik jadi lebih luas */
                            font-size: 1rem;
                        }

                        /* 2. Tombol Add Harga & Talent turun ke bawah dan full width */
                        .teleport-wrapper {
                            position: relative;
                            /* Lepaskan dari absolute (pojok kanan) */
                            top: auto;
                            right: auto;
                            width: 100%;
                            margin: 0 0 1.5rem 0 !important;
                            /* Beri jarak dengan card di bawahnya */
                        }

                        .teleport-wrapper .btn {
                            width: 100%;
                            /* Tombol jadi lebar penuh */
                            padding: 12px;
                            /* Tombol sedikit lebih tinggi agar mudah dipencet */
                            border-radius: 8px;
                        }

                        /* 3. Penyesuaian padding card utama agar tidak terlalu sesak di HP */
                        .productdesc .card-body.p-5 {
                            padding: 1.5rem !important;
                        }
                    }
                </style>
                <div class="card productdesc">
                    <div class="card-body p-5">
                        <div class="panel panel-primary border-0">
                            <div class="tab-menu-heading p-0 border-0">
                                <div class="tabs-menu1 ref-tabs-container">
                                    <ul class="nav ref-tabs">
                                        <li><a href="#tab5" class="active" data-bs-toggle="tab">Talent</a></li>
                                        <li><a href="#tab6" data-bs-toggle="tab">Harga</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="panel-body tabs-menu-body p-0">
                                <div class="tab-content">

                                    <div class="tab-pane active" id="tab5">
                                        <div class="teleport-wrapper">
                                            <button type="submit" class="modal-effect btn btn-primary"
                                                data-bs-target="#modaldemo8" data-bs-effect="effect-sign"
                                                data-bs-toggle="modal">Add Talent</button>
                                        </div>

                                        @include('penyewa.molecul.modalTalent')

                                        @if (session('talent'))
                                            <div class="alert alert-success mt-4">
                                                {{ session('talent') }}
                                            </div>
                                        @endif
                                        @if (session('hapus'))
                                            <div class="alert alert-danger mt-4">
                                                {{ session('hapus') }}
                                            </div>
                                        @endif

                                        @include('penyewa.molecul.cardTalent')
                                    </div>

                                    <div class="tab-pane" id="tab6">
                                        <div class="teleport-wrapper">
                                            <button type="submit" class="modal-effect btn btn-primary"
                                                data-bs-target="#modalHarga" data-bs-effect="effect-sign"
                                                data-bs-toggle="modal">Add Harga</button>
                                        </div>

                                        @include('penyewa.molecul.modalHarga')

                                        <div class="row m-0">
                                            <div class="col-12 p-0 mt-4">
                                                @if (session('harga'))
                                                    <div class="alert alert-success mb-4">
                                                        {{ session('harga') }}
                                                    </div>
                                                @endif
                                                @if (session('deleteHarga'))
                                                    <div class="alert alert-success mb-4    ">
                                                        {{ session('deleteHarga') }}
                                                    </div>
                                                @endif
                                                @if (session('editHarga'))
                                                    <div class="alert alert-success mb-4">
                                                        {{ session('editHarga') }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="col-12 p-0">
                                                <div class="row m-0">
                                                    @include('penyewa.molecul.cardHarga')
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



        </div>
        <!-- ROW-1 CLOSED -->
    </div>
@endsection