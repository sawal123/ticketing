@extends('penyewa.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Dashboard Voucher</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Vopucher</li>
            </ol>
        </div>
    </div>
    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h3 class="card-title">List Voucher</h3>
                    @include('penyewa.molecul.modalVoucher')
                </div>
                <div class="mx-5 my-5">
                    @if (session('vError'))
                        <div class="alert alert-danger">
                            {{ session('vError') }}
                        </div>
                    @endif
                    @if (session('voucher'))
                        <div class="alert alert-success">
                            {{ session('voucher') }}
                        </div>
                    @endif
                    @if (session('deleteVoucher'))
                        <div class="alert alert-danger">
                            {{ session('deleteVoucher') }}
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table id="tableVoucher" class="table table-hover table-bordered text-nowrap key-buttons border-bottom">
                            <thead>
                                <tr>
                                    <th class="border-bottom-0">No</th>
                                    <th class="border-bottom-0">Code</th>
                                    <th class="border-bottom-0">Unit</th>
                                    <th class="border-bottom-0" style="width: 10%">Nominal</th>
                                    <th class="border-bottom-0">Min Beli</th>
                                    <th class="border-bottom-0">Max Disc</th>
                                    <th class="border-bottom-0">Digunakan</th>
                                    <th class="border-bottom-0">Maks Digunakan</th>
                                    <th class="border-bottom-0">Status</th>
                                    <th class="border-bottom-0">Action</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider">
                               
                                @foreach ($voucher as $key => $v)
                                    <tr>
                                        <td >{{ $key += 1 }}</td>
                                        <td >{{ $v->code }}</td>
                                        <td >{{ $v->unit }}</td>
                                        <td >
                                            @if ($v->unit === 'rupiah')
                                                Rp {{ number_format($v->nominal, 0, ',', '.') }}
                                            @else
                                                {{ number_format($v->nominal, 0, ',', '.') }}%
                                            @endif

                                        </td>
                                        <td >Rp {{ number_format($v->min_beli, 0, ',', '.') }}</td>
                                        <td >
                                            Rp {{ number_format($v->max_disc, 0, ',', '.') }}

                                        </td>
                                        <td >{{ $v->digunakan }}</td>
                                        <td >{{ $v->limit }}</td>
                                        {{-- <td >{{ $v->fee }}</td> --}}
                                        <td >
                                            <div class="mt-sm-1 d-block">
                                                <span
                                                    class="badge bg-success-transparent rounded-pill text-success p-2 px-3">{{ $v->status }}</span>
                                            </div>
                                        </td>
                                        <td >
                                            <div class="g-2">
                                                <a type="submit" class="btn text-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateVoucher"
                                                    data-bs-original-title="Edit"><span class="fe fe-edit fs-14"></span></a>

                                                <a class="btn text-danger btn-sm delete" href="{{url('dashboard/delete/voucher/'.$v->uid)}}" data-bs-toggle="tooltip"
                                                    data-bs-original-title="Delete"><span
                                                        class="fe fe-trash-2 fs-14"></span>
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
