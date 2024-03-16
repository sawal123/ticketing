@extends('backend.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">User</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">User</li>
            </ol>
        </div>
    </div>
    <div class="row">
        <div class="col">
            @if (session('deleteUser'))
                <div class="alert alert-danger">
                    {{ session('deleteUser') }}
                </div>
            @endif
            @if (session('editUser'))
                <div class="alert alert-primary">
                    {{ session('editUser') }}
                </div>
            @endif
            @if (session('addUser'))
                <div class="alert alert-primary">
                    {{ session('addUser') }}
                </div>
            @endif
            <div class="card">
                <div class="card-header d-flex justify-content-between">

                    <h3 class="card-title">Data Admin</h3>
                    <button type="submit" class="modal-effect btn btn-primary " data-bs-target="#modalAdmin"
                        data-bs-effect="effect-sign" data-bs-toggle="modal"><i class="fa fa-plus-square me-2"></i>New
                        Admin</button>

                    @include('backend.molecul.user.modalAdmin')

                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="myTable" class=" table table-bordered text-nowrap key-buttons border-bottom">
                            <thead class="border-top">
                                <tr>
                                    <th class="bg-transparent border-bottom-0" style="width: 5%;">No</th>
                                    <th class="bg-transparent border-bottom-0">
                                        Nama</th>
                                    <th class="bg-transparent border-bottom-0">
                                        Email</th>
                                    <th class="bg-transparent border-bottom-0">
                                        Tanggal Lahir</th>
                                    <th class="bg-transparent border-bottom-0">
                                        Kota</th>
                                    <th class="bg-transparent border-bottom-0">
                                        Alamat</th>
                                    <th class="bg-transparent border-bottom-0">
                                        Nomor Hp/ Wa</th>
                                    <th class="bg-transparent border-bottom-0" style="width: 10%;">Gender</th>
                                    <th class="bg-transparent border-bottom-0" style="width: 5%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $key => $users)
                                    <tr class="border-bottom">
                                        <td class="text-center">
                                            <div class="mt-0 mt-sm-2 d-block">
                                                <h6 class="mb-0 fs-14 fw-semibold">{{ $key + 1 }}</h6>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <span class="avatar bradius"
                                                    style="background-image: url('{{ asset("storage/user/". $users->gambar) }}')"></span>
                                                <div class="ms-3 mt-0 mt-sm-2 d-block">
                                                    <h6 class="mb-0 fs-14 fw-semibold">
                                                        {{ $users->name }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <div class="mt-0 mt-sm-3 d-block">
                                                    <h6 class="mb-0 fs-14 fw-semibold">
                                                        {{ $users->email }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <div class="mt-0 mt-sm-3 d-block">
                                                    <h6 class="mb-0 fs-14 fw-semibold">
                                                        {{ $users->birthday }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="mt-sm-2 d-block">{{ $users->kota }}</span></td>

                                        <td><span class="mt-sm-2 d-block">{{ $users->alamat }}</span></td>
                                        <td><span class="fw-semibold mt-sm-2 d-block">{{ $users->nomor }}</span></td>
                                        <td><span class="fw-semibold mt-sm-2 d-block">{{ $users->gender }}</span></td>
                                        <td>
                                            <div class="g-2">
                                                <button type="submit" class="btn text-primary btn-sm"
                                                    data-uid="{{ $users->uid }}" data-nama="{{ $users->name }}"
                                                    data-email="{{ $users->email }}" data-tanggal="{{ $users->birthday }}"
                                                    data-kota="{{ $users->kota }}" data-alamat="{{ $users->alamat }}"
                                                    data-role="{{ $users->role }}" data-nomor="{{ $users->nomor }}"
                                                    data-gender="{{ $users->gender }}" data-bs-target="#upUser"
                                                    data-bs-effect="effect-sign" data-bs-toggle="modal"><span
                                                        class="fe fe-edit fs-14"></span></button>

                                                <a href="{{ url('admin/user/delete/' . $users->uid) }}"
                                                    class="btn text-danger btn-sm delete" data-bs-toggle="tooltip"
                                                    data-bs-original-title="Delete"><span
                                                        class="fe fe-trash-2 fs-14"></span></a>
                                            </div>

                                        </td>
                                    </tr>
                                @endforeach
                                @include('backend.molecul.user.modalUser')

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
