@extends('penyewa.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Transaksi Online {{ $event->event }}</h1>
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
                            <h6 class="">Total Uang Ticket Yang Terjual</h6>
                            <h2 class="mb-0 number-font">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</h2>
                        </div>
                    </div>
                    {{-- <span class="text-muted fs-12"><span class="text-secondary"><i
                                class="fe fe-arrow-up-circle  text-secondary"></i> 5%</span>
                        Last week</span> --}}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="mt-2">
                            <h6 class="">Total Ticket Yang Terjual</h6>
                            <h2 class="mb-0 number-font">{{ number_format($totalFee, 0, ',', '.') }} Ticket</h2>
                        </div>
                    </div>
                    {{-- <span class="text-muted fs-12"><span class="text-secondary"><i
                                class="fe fe-arrow-up-circle  text-secondary"></i> 5%</span>
                        Last week</span> --}}
                </div>
            </div>
        </div>
    </div>
    <!-- ROW-1 OPEN -->
    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{ url('dashboard/transaksi') }}" method="get">
                    {{-- @csrf --}} <!-- Tidak diperlukan untuk GET request -->

                    <div class="card-header d-flex justify-content-between">
                        <h3 class="card-title">File Export</h3>
                        <div class="input-group w-md w-25">
                            <input type="date" class="form-control" name="filter" value="{{ request('filter') }}">
                            <input type="hidden" name="uid" value="{{ $uid }}">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </div>
                </form>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableTransPenyewa"
                            class="table table-hover table-bordered text-nowrap key-buttons border-bottom">
                            <thead>
                                <tr>
                                    <th class="border-bottom-0">No</th>
                                    <th class="border-bottom-0">Invoice</th>
                                    <th class="border-bottom-0" style="width: 10%">Event</th>
                                    <th class="border-bottom-0">Tanggal</th>
                                    <th class="border-bottom-0">Name</th>
                                    <th class="border-bottom-0">Qty</th>
                                    <th class="border-bottom-0">Jmlh</th>
                                    <th class="border-bottom-0">Disc</th>
                                    <th class="border-bottom-0">Total</th>
                                    <th class="border-bottom-0">Voucher</th>
                                    <th class="border-bottom-0">Payment</th>
                                    <th class="border-bottom-0">Status</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider">
                                @foreach ($cart as $key => $carts)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $carts->invoice }}</td>
                                        <td>{{ strlen($carts->event) > 10 ? substr($carts->event, 0, 15) . '...' : $carts->event }}
                                        </td>
                                        <td>{{ date('d M Y H:i', strtotime($carts->created_at)) }}</td>

                                        <td>{{ $carts->user_name }}</td>

                                        <td>
                                            <a class="modal-effect btn btn-primary-light d-grid mb-3"
                                                data-bs-effect="effect-scale" data-bs-toggle="modal"
                                                href="#modaldemo8{{ $key }}">
                                                {{ $carts->total_quantity }} Ticket
                                            </a>
                                            <div class="modal fade" id="modaldemo8{{ $key }}">
                                                <div class="modal-dialog modal-dialog-centered text-center" role="document">
                                                    <div class="modal-content modal-content-demo">
                                                        <div class="modal-header">
                                                            <h6 class="modal-title">Detail Ticket</h6>
                                                            <button aria-label="Close" class="btn-close"
                                                                data-bs-dismiss="modal"><span
                                                                    aria-hidden="true">&times;</span></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            @foreach ($qtyTiket as $qt)
                                                                @if ($qt->uid === $carts->uid)
                                                                    <div class="d-flex justify-content-between">
                                                                        <p>{{ $qt->kategori_harga }}</p>
                                                                        <p>{{ $qt->quantity }}</p>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button class="btn btn-light"
                                                                data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td>Rp {{ number_format($carts->subtotal_harga, 0, ',', '.') }}</td>

                                        <td>
                                            @if ($carts->unit === 'rupiah')
                                                Rp {{ number_format($carts->voucher_disc, 0, ',', '.') }}
                                            @elseif($carts->unit === 'persen')
                                                {{ $carts->voucher_disc }}%
                                            @else
                                                -
                                            @endif
                                        </td>

                                        <td class="fw-bold text-primary">Rp
                                            {{ number_format($carts->final_amount, 0, ',', '.') }}</td>

                                        <td>{{ $carts->voucher ?: '-' }}</td>
                                        <td><span
                                                class="badge bg-primary-transparent rounded-pill text-primary p-2 px-3">{{ strtoupper($carts->payment_type) }}</span>
                                        </td>
                                        <td><span
                                                class="badge bg-success-transparent rounded-pill text-success p-2 px-3">{{ $carts->status }}</span>
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
