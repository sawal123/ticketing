@extends('penyewa.app')

@section('content')
    <div class="main-container container-fluid">

        <div class="page-header">
            <h1 class="page-title">Pengaturan Akun</h1>
            <div>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Profile</li>
                </ol>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                @if (session('editProfile'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Sukses!</strong> {{ session('editProfile') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Gagal!</strong> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            </div>

            <div class="col-xl-4">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom-0">
                        <div class="card-title">Informasi Rekening</div>
                    </div>
                    <form action="{{ url('dashboard/editRekening') }}" method="post">
                        @csrf
                        <div class="card-body pt-0">
                            <div class="text-center chat-image mb-5">
                                <div class="avatar avatar-xxl chat-profile mb-3 brround border-4 shadow-sm">
                                    <img alt="Profile Image"
                                        src="{{ $profile->gambar ? asset('storage/user/' . $profile->gambar) : 'https://ui-avatars.com/api/?name=' . urlencode($profile->name) . '&background=6c5ffc&color=fff' }}"
                                        class="brround avatar-xxl" style="object-fit: cover; border: white 5px solid;">
                                </div>
                                <div class="main-chat-msg-name">
                                    <h5 class="mb-1 text-dark fw-bold">{{ $profile->name }}</h5>
                                    <span class="badge bg-primary-transparent text-primary px-3 py-1 rounded-pill fs-12">
                                        {{ strtoupper($profile->role) }}
                                    </span>
                                </div>
                            </div>
                            <hr class="mb-4">

                            <div class="text-start mb-3">
                                <p class="text-muted fs-13 mb-0">Atur rekening pencairan dana event Anda di bawah ini.</p>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Atas Nama</label>
                                <div class="wrap-input100 validate-input input-group">
                                    <span class="input-group-text bg-light text-muted"><i
                                            class="zmdi zmdi-account"></i></span>
                                    <input class="input100 form-control" type="text"
                                        value="{{ $profile->nama_pemilik ?? '' }}" name="nama"
                                        placeholder="Nama Pemilik Rekening" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nama Bank</label>
                                <select class="form-control select2-show-search form-select" name="bank" required>
                                    <option value="" selected disabled>Pilih Bank</option>
                                    @foreach ($bi as $bis)
                                        <option value="{{ $bis->name }}"
                                            {{ ($profile->nama_bank ?? '') == $bis->name ? 'selected' : '' }}>
                                            {{ $bis->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nomor Rekening</label>
                                <div class="wrap-input100 validate-input input-group">
                                    <span class="input-group-text bg-light text-muted"><i
                                            class="zmdi zmdi-collection-text"></i></span>
                                    <input class="input100 form-control" type="number"
                                        value="{{ $profile->no_rek ?? '' }}" placeholder="Contoh: 123456789" name="norek"
                                        required>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end bg-light">
                            <button type="submit" class="btn btn-primary w-100"><i class="fe fe-save me-2"></i>Simpan
                                Rekening</button>
                        </div>
                    </form>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-header border-bottom-0">
                        <div class="card-title">Ubah Password</div>
                    </div>
                    <form action="{{ url('dashboard/updatePassword') }}" method="post">
                        @csrf
                        <div class="card-body pt-0">
                            @if ($errors->any())
                                <div class="alert alert-danger pb-0">
                                    <ul class="mb-2">
                                        @foreach ($errors->all() as $error)
                                            <li class="fs-12">{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="form-group">
                                <label class="form-label">Password Saat Ini</label>
                                <div class="wrap-input100 validate-input input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="zmdi zmdi-lock"></i></span>
                                    <input class="input100 form-control" type="password" name="current_password"
                                        placeholder="Masukkan password lama" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password Baru</label>
                                <div class="wrap-input100 validate-input input-group">
                                    <span class="input-group-text bg-light text-muted"><i
                                            class="zmdi zmdi-lock-outline"></i></span>
                                    <input class="input100 form-control" type="password"
                                        name="new_password"iu655yj,,kyutrfgb.kuytrerh <div class="col-xl-8">
                                    <div class="card shadow-sm">
                                        <div class="card-header border-bottom-0">
                                            <h3 class="card-title">Edit Data Diri</h3>
                                        </div>
                                        <form action="{{ url('dashboard/editProfile') }}" method="post"
                                            enctype="multipart/form-data">
                                            @csrf
                                            <div class="card-body">
                                                <input type="hidden" name="uid" value="{{ $profile->uid }}">

                                                <div class="row">
                                                    <div class="col-lg-6 col-md-12">
                                                        <div class="form-group">
                                                            <label for="namaLengkap" class="form-label">Nama
                                                                Lengkap</label>
                                                            <input type="text" class="form-control" id="namaLengkap"
                                                                name="nama" value="{{ $profile->name }}"
                                                                placeholder="Masukkan nama lengkap" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 col-md-12">
                                                        <div class="form-group">
                                                            <label for="noHp" class="form-label">Nomor
                                                                WhatsApp</label>
                                                            <input type="number" class="form-control" id="noHp"
                                                                value="{{ $profile->nomor }}" name="nomor"
                                                                placeholder="Contoh: 0812345678" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-6 col-md-12">
                                                        <div class="form-group">
                                                            <label for="emailAddr" class="form-label">Alamat Email</label>
                                                            <input type="email" class="form-control" id="emailAddr"
                                                                value="{{ $profile->email }}" name="email"
                                                                placeholder="email@contoh.com" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 col-md-12">
                                                        <div class="form-group">
                                                            <label for="tglLahir" class="form-label">Tanggal Lahir</label>
                                                            <input type="date" class="form-control"
                                                                value="{{ $profile->birthday }}" id="tglLahir"
                                                                name="date" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-6 col-md-12">
                                                        <div class="form-group">
                                                            <label class="form-label">Jenis Kelamin</label>
                                                            <select class="form-control select2-show-search form-select"
                                                                name="gender" required>
                                                                <option value="" disabled>Pilih Gender</option>
                                                                <option value="pria"
                                                                    {{ $profile->gender == 'pria' ? 'selected' : '' }}>Pria
                                                                    (Male)</option>
                                                                <option value="wanita"
                                                                    {{ $profile->gender == 'wanita' ? 'selected' : '' }}>
                                                                    Wanita (Female)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 col-md-12">
                                                        <div class="form-group">
                                                            <label class="form-label">Provinsi</label>
                                                            <select class="form-control select2-show-search form-select"
                                                                name="provinsi" required>
                                                                <option value="" selected disabled>Pilih Provinsi
                                                                </option>
                                                                @if (!empty($pr))
                                                                    @foreach ($pr as $prs)
                                                                        <option value="{{ $prs['name'] }}"
                                                                            {{ $prs['name'] == $profile->kota ? 'selected' : '' }}>
                                                                            {{ $prs['name'] }}
                                                                        </option>
                                                                    @endforeach
                                                                @else
                                                                    <option value="{{ $profile->kota }}" selected>
                                                                        {{ $profile->kota }}
                                                                    </option>
                                                                @endif
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="alamatLengkap" class="form-label">Alamat Lengkap</label>
                                                    <textarea class="form-control" id="alamatLengkap" name="alamat" rows="3"
                                                        placeholder="Masukkan alamat lengkap" required>{{ $profile->alamat }}</textarea>
                                                </div>

                                                <div class="form-group mb-0">
                                                    <label for="gambarProfile" class="form-label">Update Foto Profil
                                                        (Opsional)</label>
                                                    <input type="file" class="form-control" id="gambarProfile"
                                                        name="img" accept="image/png, image/jpeg, image/jpg">
                                                    <small class="text-muted">Format: JPG, JPEG, PNG. Maksimal ukuran
                                                        2MB.</small>
                                                </div>
                                            </div>

                                            <div class="card-footer text-end bg-light">
                                                <button type="submit" class="btn btn-primary px-5"><i
                                                        class="fe fe-check-circle me-2"></i>Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endsection
