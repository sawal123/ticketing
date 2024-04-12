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
                            <h2 class="mb-0 number-font">Rp {{ number_format($totalHargaCart, 0, ',', '.') }}</h2>
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
                <form action="{{ url('admin/transaksi/') }}" method="get">
                    @csrf
                    <div class="card-header d-flex justify-content-between">
                        <h3 class="card-title">File Export</h3>
                        <div class="input-group w-md w-25">
                            <input type="date" class="form-control " name="filter" value="{{ $filter }}">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>

                    </div>
                </form>
                @if (session('success'))
                    <div class="alert alert-primary">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="file-datatable" class="table table-bordered text-nowrap key-buttons border-bottom">
                            <thead>
                                <tr>
                                    <th class="border-bottom-0">No</th>
                                    <th class="border-bottom-0">Invoice</th>
                                    <th class="border-bottom-0" style="width: 10%">Event</th>
                                    <th class="border-bottom-0">Tanggal</th>
                                    <th class="border-bottom-0">Name</th>
                                    <th class="border-bottom-0">Qty</th>
                                    <th class="border-bottom-0">Total</th>
                                    <th class="border-bottom-0">Fee</th>
                                    <th class="border-bottom-0">Type</th>
                                    <th class="border-bottom-0">Status</th>
                                    <th class="border-bottom-0">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cart as $key => $carts)
                                    <tr>
                                        <td>{{ $key += 1 }}</td>
                                        <td>{{ $carts->invoice }}</td>
                                        <td>{{ strlen($carts->event > 10) ? substr($carts->event, 0, 15) . '...' : $carts->event }}
                                        </td>
                                        <td>{{ $carts->created_at }}</td>
                                        <td>
                                            @foreach ($use as $users)
                                                @if ($users->uid == $carts->user_uid)
                                                    {{ $users->name }}
                                                @endif
                                            @endforeach
                                        </td>
                                        <td>
                                            <span
                                                class="badge rounded-pill text-bg-primary">{{ $carts->total_quantity }}</span>
                                        </td>
                                        <td>{{ $carts->total_harga }}</td>
                                        <td>{{ $carts->payment_type === 'cash' ? 0 : $carts->fee }}</td>
                                        <td>{{ $carts->payment_type }}</td>
                                        <td>
                                            <div class="mt-sm-1 d-block">
                                                <span
                                                    class="badge bg-success-transparent rounded-pill text-success p-2 px-3">{{ $carts->status }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="g-2">

                                                <button type="submit" class="btn text-primary btn-sm"
                                                    data-bs-original-title="Edit" data-bs-target="#editTransaksi"
                                                    data-bs-effect="effect-sign" data-bs-toggle="modal"
                                                    data-uid="{{ $carts->uid }}" data-inv="{{ $carts->invoice }}"
                                                    data-name="@foreach ($use as $users)
                                                    @if ($users->uid == $carts->user_uid)
                                                        {{ $users->name }}
                                                    @endif @endforeach"
                                                    data-status="{{ $carts->status }}"><span
                                                        class="fe fe-edit fs-14"></span></button>
                                                @if ($carts->status !== 'SUCCESS')
                                                    <a href="{{ url('admin/deleteTransksi/' . $carts->uid) }}"
                                                        class="btn text-danger btn-sm delete" data-bs-toggle="tooltip"
                                                        data-bs-original-title="Delete"><span
                                                            class="fe fe-trash-2 fs-14"></span></a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                @include('backend.molecul.modalTransaksi')
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
