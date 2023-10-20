@extends('penyewa.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Dashboard Transaksi</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Transaksi</li>
            </ol>
        </div>
    </div>
    <!-- PAGE-HEADER END -->
    <div class="row row-sm">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
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
        </div>

    </div>
    <!-- ROW-1 OPEN -->
    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header " style="display: inline">
                    @if (session('penarikan'))
                        <div class="alert alert-primary">
                            {{ session('penarikan') }}
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
                                <button type="submit" class="btn btn-primary  my-2" data-bs-target="#modalMoney"
                                    data-bs-effect="effect-sign" data-bs-toggle="modal"><i
                                        class="fa fa-plus-square me-2"></i>Penarikan</button>
                                @include('penyewa.molecul.modalMoney')
                            </div>
                        </div>

                    </div>

                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableMoney" class="table table-bordered text-nowrap key-buttons border-bottom">
                            <thead>
                                <tr>
                                    <th class="border-bottom-0">No</th>
                                    <th class="border-bottom-0">Amount</th>
                                    <th class="border-bottom-0" style="width: 10%">Note</th>
                                    <th class="border-bottom-0">Pengajuan</th>
                                    <th class="border-bottom-0">Disetujui</th>
                                    <th class="border-bottom-0">Invoice</th>
                                    <th class="border-bottom-0">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($money as $key => $moneys)
                                    <tr>
                                        <td>{{ $key += 1 }}</td>
                                        <td>Rp{{ number_format ($moneys->amount, 0, ',', '.') }}</td>
                                        <td>{{ $moneys->note }}
                                        </td>
                                        <td>{{ date('d M Y', strtotime($moneys->created_at))}}</td>
                                        <td>{{  $moneys->created_at == $moneys->updated_at ? '-' : date('d M Y', strtotime($moneys->updated_at))}}</td>
                                        <td>
                                            <a href="{{ $moneys->created_at == $moneys->updated_at ? '#' : url('/invoice/' . $moneys->uid) }}"
                                                class="btn btn-sm btn-success">
                                                {{ $moneys->created_at == $moneys->updated_at ? 'Belum Tersedia' : 'Tersedia' }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="mt-sm-1 d-block">
                                                <span
                                                    class="badge bg-success-transparent rounded-pill text-success p-2 px-3">{{ $moneys->status }}</span>
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
