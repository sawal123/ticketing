@extends('backend.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Transaksi</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Tiket</a></li>
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
                            <h6 class="">Total Uang Ticket Yang Terjual</h6>
                            <h2 class="mb-0 number-font">Rp {{ number_format($totalHargapenarikan, 0, ',', '.') }}</h2>
                        </div>
                    </div>
                    <span class="text-muted fs-12"><span class="text-secondary"><i
                                class="fe fe-arrow-up-circle  text-secondary"></i> 5%</span>
                        Last week</span>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="mt-2">
                            <h6 class="">Total Uang Fee Yang SUCCESS</h6>
                            <h2 class="mb-0 number-font">Rp {{ number_format($totalFee, 0, ',', '.') }}</h2>
                        </div>
                    </div>
                    <span class="text-muted fs-12"><span class="text-secondary"><i
                                class="fe fe-arrow-up-circle  text-secondary"></i> 5%</span>
                        Last week</span>
                </div>
            </div>
        </div>
    </div>
    <!-- ROW-1 OPEN -->
    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">File Export</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="file-datatable" class="table table-bordered text-nowrap key-buttons border-bottom">
                            <thead>
                                <tr>
                                    <th class="border-bottom-0">No</th>
                                    <th class="border-bottom-0">Nama</th>
                                    <th class="border-bottom-0">Saldo</th>
                                    <th class="border-bottom-0" style="width: 10%">Penarikan</th>
                                    <th class="border-bottom-0">Tanggal</th>
                                    <th class="border-bottom-0">Konfirmasi</th>
                                    <th class="border-bottom-0">Invoice</th>
                                    <th class="border-bottom-0">Status</th>
                                    <th class="border-bottom-0">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($penarikan as $key => $penarikans)
                                    <tr>
                                        <td>{{ $key += 1 }}</td>
                                        <td>{{ $penarikans->name }}</td>
                                        <td>Rp{{ number_format($penarikans->kwitansi, 0, ',', '.') }}
                                        </td>
                                        <td>Rp{{ number_format($penarikans->amount, 0, ',', '.') }}</td>
                                        <td>{{ $penarikans->created_at }}</td>
                                        <td>{{ $penarikans->created_at == $penarikans->updated_at ? '-' : $penarikans->updated_at }}
                                        </td>
                                        <td>{{ $penarikans->kwitansi }}</td>
                                        <td>
                                            <div class="mt-sm-1 d-block">
                                                <span
                                                    class="badge bg-success-transparent rounded-pill text-success p-2 px-3">{{ $penarikans->status }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="g-2">
                                                <form action="" method="post">
                                                    <button type="submit" class="btn btn-primary btn-sm">Konfirmasi</button>
                                                </form>
                                                <a class="btn text-danger btn-sm delete" data-bs-toggle="tooltip"
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
