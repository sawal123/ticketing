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
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablePenyewa" class="table table-bordered text-nowrap key-buttons border-bottom">
                        <thead>
                            <tr>
                                <th class="border-bottom-0">No</th>
                                <th class="border-bottom-0">Code</th>
                                <th class="border-bottom-0" style="width: 10%">Nominal</th>
                                <th class="border-bottom-0">Min Beli</th>
                                <th class="border-bottom-0">Max Disc</th>
                                <th class="border-bottom-0">Digunakan</th>
                                <th class="border-bottom-0">Maks Digunakan</th>
                                <th class="border-bottom-0">Status</th>
                                <th class="border-bottom-0">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($voucher as $key => $v)
                                <tr>
                                    <td>{{ $key += 1 }}</td>
                                    <td>{{ $v->code }}</td>
                                    <td> {{$v->nominal}}
                                    </td>
                                    <td>{{ $v->min_beli }}</td>
                                    <td>
                                       {{$v->max_disc}}
                                    </td>
                                    <td>{{$v->digunakan}}</td>
                                    <td>{{ $v->limit }}</td>
                                    {{-- <td>{{ $v->fee }}</td> --}}
                                    <td>
                                        <div class="mt-sm-1 d-block">
                                            <span
                                                class="badge bg-success-transparent rounded-pill text-success p-2 px-3">{{ $v->status }}</span>
                                        </div>
                                    </td>
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