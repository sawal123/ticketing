@extends('backend.app')

@section('content')
    <!-- CONTAINER -->
    <div class="main-container container-fluid">

        <!-- PAGE-HEADER -->
        <div class="page-header">
            <h1 class="page-title">Edit Profile</h1>
            <div>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Pages</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Profile</li>
                </ol>
            </div>
        </div>
        <!-- PAGE-HEADER END -->

        <!-- ROW-1 OPEN -->
        <div class="row">
            @if (session('editProfile'))
                <div class="alert alert-primary">
                    {{ session('editProfile') }}
                </div>
            @endif
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Edit Rekening</div>
                    </div>
                    <form action="{{ url('admin/editRekening') }}" method="post">
                        @csrf
                        <div class="card-body">
                            <div class="text-center chat-image mb-5">
                                <div class="avatar avatar-xxl chat-profile mb-3 brround border-4" >
                                    <img alt="" src="{{ asset('storage/user/' . $profile->gambar) }}"
                                        class="brround avatar-xxl" style="object-fit: cover;border: white 5px solid" >
                                </div>
                                <div class="main-chat-msg-name">
                                    <a href="#">
                                        <h5 class="mb-1 text-dark fw-semibold">{{ $profile->name }}</h5>
                                    </a>
                                    <p class="text-muted mt-0 mb-0 pt-0 fs-13">{{ strtoupper($profile->role) }}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="text-start">
                                <p>Harap Isi Data Rekening Anda Dengan Benar!</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nama</label>
                                <div class="wrap-input100 validate-input input-group" id="Password-toggl">
                                    <a href="javascript:void(0)" class="input-group-text bg-white text-mute">
                                        <i class="zmdi zmdi-account text-muted" aria-hidden="true"></i>
                                    </a>
                                    <input class="input100 form-control" type="text" value="{{ $profile->nama }}"
                                        name="nama" placeholder="Nama Rekening ..">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nama Bank</label>
                                <select class="form-control select2-show-search form-select" name="bank"
                                    data-placeholder="Choose one">
                                    <option label="Choose one"></option>
                                    @foreach ($bi as $bis)
                                        <option value="{{ $bis->name }}"
                                            {{ $profile->bank == $bis->name ? 'selected' : '' }}>{{ $bis->name }}
                                        </option>
                                    @endforeach

                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rekening</label>
                                <div class="wrap-input100 validate-input input-group" id="Password-toggl">
                                    <a href="javascript:void(0)" class="input-group-text bg-white text-mute">
                                        <i class="zmdi zmdi-collection-text text-mute" aria-hidden="true"></i>
                                    </a>
                                    <input class="input100 form-control" type="text" value="{{ $profile->norek }}"
                                        placeholder="No Rekening .." name="norek">
                                </div>
                            </div>

                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary w-100">Update</button>
                        </div>
                    </form>
                </div>

            </div>
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Edit Profile</h3>
                    </div>
                    <form action="{{ url('admin/editPro') }}" method="post" method="post"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6 col-md-12">
                                    <input type="hidden" name="uid" value="{{ $profile->uid }}">
                                    <div class="form-group">

                                        <label for="exampleInputname">Nama</label>
                                        <input type="text" class="form-control" id="exampleInputname" name="nama"
                                            value="{{ $profile->name }}" placeholder="Nama ..">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <div class="form-group">
                                        <label for="exampleInputname1">Nomor Handphone</label>
                                        <input type="number" class="form-control" id="exampleInputname1"
                                            value="{{ $profile->nomor }}" name="nomor" placeholder="Number ..">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Email address</label>
                                <input type="email" class="form-control" id="exampleInputEmail1"
                                    value="{{ $profile->email }}" name="email" placeholder="Email address">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputnumber">Birthday</label>
                                <input type="date" class="form-control" value="{{ $profile->birthday }}"
                                    id="exampleInputnumber" name="date">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Gender</label>
                                <select class="form-control select2-show-search form-select" name="gender">
                                    <option value="wanita" {{ $profile->gender == 'wanita' ? 'selected' : '' }}>Male
                                    </option>
                                    <option value="pria" {{ $profile->gender == 'pria' ? 'selected' : '' }}>Female
                                    </option>

                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Provinsi</label>
                                <select class="form-control select2-show-search form-select" name="provinsi">
                                    <option label="Choose one provinsi"></option>
                                    @foreach ($pr as $key => $prs)
                                        <option value="{{ $prs['name'] }}"
                                            {{ $prs['name'] == $profile->kota ? 'selected' : '' }}>{{ $prs['name'] }}
                                        </option>
                                    @endforeach

                                </select>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputnumber">Alamat</label>
                                <input type="text" class="form-control" value="{{ $profile->alamat }}"
                                    id="exampleInputnumber" name="alamat">
                            </div>
                            <div class="form-group">
                                <label for="gambar">Profile</label>
                                <input type="file" class="form-control" id="gambar" name="img">
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary my-1 w-100">Update</a>
                        </div>
                    </form>
                </div>

            </div>
        </div>
        <!-- ROW-1 CLOSED -->

    </div>
    <!--CONTAINER CLOSED -->
@endsection
