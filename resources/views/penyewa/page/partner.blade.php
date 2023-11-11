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
                                    <th class="border-bottom-0">Status</th>
                                    <th class="border-bottom-0">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($partner as $key => $ps)
                                    <tr>
                                        <td>{{ $key += 1 }}</td>
                                        <td>{{ $ps->name }}</td>
                                        <td>{{ $ps->email }}
                                        <td>{{ $ps->city }}
                                        <td>{{ $ps->alamat }}
                                        </td>
                                        <td>{{ $ps->hp }}</td>
                                        <td>{{ $ps->status }}</td>
                                        <td>
                                            <div class="g-2">
                                                <button type="submit" class="btn text-primary btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#upPartner"
                                                    data-bs-original-title="Edit" data-uid="{{$ps->uid}}" data-name="{{ $ps->name }}"
                                                    data-email="{{ $ps->email }}" data-city="{{ $ps->city }}"
                                                    data-alamat="{{ $ps->alamat }}"
                                                    data-nomor="{{ $ps->hp }}"><span
                                                        class="fe fe-edit fs-14"></span></button>

                                                <a class="btn text-danger btn-sm delete"
                                                    href="{{ url('dashboard/delete/partner/' . $ps->uid) }}"
                                                    data-bs-toggle="tooltip" data-bs-original-title="Delete"><span
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
