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

                                <img class="img-thumbnail" src="{{ asset('storage/cover/' . $eventDetail->cover) }}"
                                    alt="">
                            </div>
                            <div class="details col-xl-7 col-lg-12 col-md-12 mt-4 mt-xl-0">
                                <div class="mt-2 mb-4">
                                    <h3 class="mb-3 fw-semibold">{{ $eventDetail->event }}</h3>
                                    <p>Status : <span style="font-weight: 800"
                                            class="font-weight-bold">{{ $eventDetail->status }}</span></p>
                                    <p>{{ $eventDetail->alamat }} <a href="{{ $eventDetail->map }}" target="blank"
                                            class="btn btn-primary">Kunjungi</a></p>
                                    <p>Biaya Layanan : Rp {{ $eventDetail->fee }}</p>
                                    {{-- <p class="text-muted float-start me-3">
                                        <span class="fa fa-star text-warning"></span>
                                        <span class="fa fa-star text-warning"></span>
                                        <span class="fa fa-star text-warning"></span>
                                        <span class="fa fa-star-half-o text-warning"></span>
                                        <span class="fa fa-star-o text-warning"></span>
                                    </p>
                                    <p class="text-muted mb-4">( 40 Customers Reviews) </p> --}}
                                    <h4 class="mt-4"><b> Description</b></h4>
                                    <p>{!! $eventDetail->deskripsi !!}</p>
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
                <div class="card productdesc">
                    <div class="card-body">
                        <div class="panel panel-primary">
                            <div class=" tab-menu-heading">
                                <div class="tabs-menu1">
                                    <!-- Tabs -->
                                    <ul class="nav panel-tabs">
                                        <li><a href="#tab5" class="active" data-bs-toggle="tab">Talent</a></li>
                                        <li><a href="#tab6" data-bs-toggle="tab">Harga</a></li>

                                    </ul>

                                </div>
                            </div>
                            <div class="panel-body tabs-menu-body">
                                <div class="tab-content">
                                    <div class="tab-pane active" id="tab5">

                                        <!-- PAGE-HEADER END -->
                                        @include('penyewa.molecul.modalTalent')
                                        @if (session('talent'))
                                            <div class="alert alert-success">
                                                {{ session('talent') }}
                                            </div>
                                        @endif
                                        @if (session('hapus'))
                                            <div class="alert alert-danger">
                                                {{ session('hapus') }}
                                            </div>
                                        @endif

                                        @include('penyewa.molecul.cardTalent')
                                    </div>


                                    <div class="tab-pane " id="tab6">
                                        <div class="row">
                                            @include('penyewa.molecul.modalHarga')
                                            @if (session('harga'))
                                                <div class="alert alert-success">
                                                    {{ session('harga') }}
                                                </div>
                                            @endif
                                            @if (session('deleteHarga'))
                                                <div class="alert alert-success">
                                                    {{ session('deleteHarga') }}
                                                </div>
                                            @endif

                                            @if (session('editHarga'))
                                                <div class="alert alert-success">
                                                    {{ session('editHarga') }}
                                                </div>
                                            @endif
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
        <!-- ROW-1 CLOSED -->
    </div>
@endsection
