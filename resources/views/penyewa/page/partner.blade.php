@extends('penyewa.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Dashboard Partner</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Partner</li>
            </ol>
        </div>
    </div>
    <!-- PAGE-HEADER END -->
    <div class="row row-sm">
        {{-- <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="mt-2">
                            <h6 class="">Total Saldo</h6>
                            <h2 class="mb-0 number-font">Rp {{ number_format($totalMoney, 0, ',', '.') }}</h2>
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
                            <h6 class="">Total Cash</h6>
                            <h2 class="mb-0 number-font">Rp {{ number_format($cash, 0, ',', '.') }}</h2>
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
                            <h6 class="">Pending</h6>
                            <h2 class="mb-0 number-font">Rp {{ number_format($pending, 0, ',', '.') }}</h2>
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
                            <h6 class="">Paid</h6>
                            <h2 class="mb-0 number-font">Rp {{ number_format($paid, 0, ',', '.') }}</h2>
                        </div>
                    </div>

                </div>
            </div>
        </div> --}}

    </div>
    <!-- ROW-1 OPEN -->
    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header " style="display: inline">
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
                    <div class="row">
                        <div class="col">
                            <div class="d-flex justify-content-between align-items-center w-full">
                                <h3 class="card-title">File Export</h3>
                                <button type="submit" class="btn btn-primary  my-2" data-bs-target="#modalPartner"
                                    data-bs-effect="effect-sign" data-bs-toggle="modal"><i
                                        class="fa fa-plus-square me-2"></i>Partner</button>
                                @include('penyewa.molecul.modalPartner')
                            </div>
                        </div>

                    </div>

                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablePartner" class="table table-bordered text-nowrap key-buttons border-bottom">
                            <thead>
                                <tr>
                                    <th class="border-bottom-0">No</th>
                                    <th class="border-bottom-0">Nama</th>
                                    <th class="border-bottom-0" style="width: 10%">Email</th>
                                    <th class="border-bottom-0" style="width: 10%">City</th>
                                    <th class="border-bottom-0" style="width: 10%">Alamat</th>
                                    <th class="border-bottom-0">No Hp</th>
                                    <th class="border-bottom-0">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($partner as $key => $ps)
                                    <tr>
                                        <td>{{ $key +=1 }}</td>
                                        <td>{{ $ps->name }}</td>
                                        <td>{{ $ps->email }}
                                        <td>{{ $ps->city }}
                                        <td>{{ $ps->alamat }}
                                        </td>
                                        <td>{{$ps->hp}}</td>
                                        <td>
                                            <div class="g-2">
                                                <a class="btn text-primary btn-sm" data-bs-toggle="tooltip"
                                                    data-bs-original-title="Edit"><span class="fe fe-edit fs-14"></span></a>
                                                <a class="btn text-danger btn-sm" data-bs-toggle="tooltip"
                                                    data-bs-original-title="Delete"><span
                                                        class="fe fe-trash-2 fs-14"></span></a>
                                            </div>
                                        </td>
                                      

                                    </tr>
                                @endforeach

                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
