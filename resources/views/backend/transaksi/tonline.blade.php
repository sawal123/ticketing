@extends('backend.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Transaksi {{ $nameEvent->event }}</h1>
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

                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="mt-2">
                            <h6 class="">Total Ticket Yang Terjual</h6>
                            <h2 class="mb-0 number-font">{{ $jmlh }} Ticket</h2>
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
                            <h6 class="">Total Uang Fee Yang SUCCESS</h6>
                            <h2 class="mb-0 number-font">Rp {{ number_format($totalFee, 0, ',', '.') }}</h2>
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">File Export</h3>
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select ml-2" aria-label="Default select example" onchange="handleSelectChange(this)">
                            <option value="">Select Status</option>
                            <option value="{{ url('admin/transaksi/' . $uidEvent . '?status=SUCCESS') }}">SUCCESS</option>
                            <option value="{{ url('admin/transaksi/' . $uidEvent . '?status=UNPAID') }}">UNPAID</option>
                            <option value="{{ url('admin/transaksi/' . $uidEvent . '?status=PENDING') }}">PENDING</option>
                            <option value="{{ url('admin/transaksi/' . $uidEvent . '?status=CANCELLED') }}">CANCELLED</option>
                        </select>
                        <script>
                            function handleSelectChange(selectElement) {
                                const url = selectElement.value;
                                if (url) {
                                    console.log('Fetching URL:', url); // Debugging
                                    fetch(url, { method: 'GET' })
                                        .then(response => {
                                            if (!response.ok) {
                                                throw new Error('Network response was not ok ' + response.statusText);
                                            }
                                            return response.text(); // Change to `response.json()` if the response is JSON
                                        })
                                        .then(data => {
                                            console.log('Response data:', data); // Debugging
                                        
                                        })
                                        .catch(error => console.error('Fetch error:', error));
                                }
                            }
                            </script>
                        <form class="d-flex  ml-2 align-items-center" action="{{ url('admin/transaksi/' . $uidEvent) }}"
                            method="get">
                            @csrf
                            <input type="date" class="form-control mr-2" name="filter" value="{{ $filter }}">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </form>

                    </div>
                </div>

                @if (session('success'))
                    <div class="container mt-3">
                        <div class="alert alert-primary">
                            {{ session('success') }}
                        </div>
                    </div>
                @endif
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablePenyewa" class="table table-bordered text-nowrap key-buttons border-bottom">
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
                                    <th class="border-bottom-0">Fee</th>
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

                                            <a class="modal-effect btn btn-primary-light d-grid mb-3"
                                                data-bs-effect="effect-scale" data-bs-toggle="modal"
                                                href="#modaldemo8{{ $key }}">{{ $carts->total_quantity }}
                                                Ticket</a>
                                            <div class="modal fade" id="modaldemo8{{ $key }}">

                                                <div class="modal-dialog modal-dialog-centered text-center" role="document">
                                                    <div class="modal-content modal-content-demo">
                                                        <div class="modal-header">
                                                            <h6 class="modal-title">Detail Ticket</h6><button
                                                                aria-label="Close" class="btn-close"
                                                                data-bs-dismiss="modal"><span
                                                                    aria-hidden="true">&times;</span></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            @foreach ($qtyTiket as $qt)
                                                                @if ($qt->uid === $carts->uid)
                                                                    <div class="d-flex justify-content-between">
                                                                        <p>{{ $qt->kategori_harga }} </p>
                                                                        <p>{{ $qt->quantity }} </p>
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

                                        <td>{{ $carts->total_harga / $carts->total_quantity }}</td>
                                        <td>{{ $carts->disc }}</td>
                                        <td>{{ $carts->total_harga - $carts->disc }}</td>
                                        <td>{{ $carts->voucher }}</td>
                                        <td>{{ $carts->payment_type }}</td>
                                        <td>{{ $carts->payment_type === 'cash' ? 0 : $carts->fee }}</td>
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
