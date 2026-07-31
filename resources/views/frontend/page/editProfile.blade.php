@extends('frontend.index')

@section('content')
    <div class="mt-5 page-wrap">
        <aside class="left-panel">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul style="margin:0;padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="avatar-card">
                <div class="avatar-ring">
                    <svg class="avatar-ring-svg" viewBox="0 0 122 122">
                        <circle cx="61" cy="61" r="57" stroke="url(#ring-grad)" stroke-width="2"
                            stroke-dasharray="8 6" stroke-linecap="round" />
                    </svg>

                    <div class="avatar-img">
                        <img id="preview-image" onclick="document.getElementById('gambar').click()"
                            src="{{ $dataUser->gambar === '' ? url('/storage/logo/logo.png') : url('/storage/user/' . $dataUser->gambar) }}"
                            style="width:100%;height:100%;object-fit:cover;border-radius:50%;cursor:pointer;">
                    </div>

                    <input form="profile-data-form" type="file" id="gambar" name="gambar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        style="display:none" onchange="previewGambar(event)">
                </div>

                @error('gambar')
                    <small style="color:red;">{{ $message }}</small>
                @enderror

                <button type="button" onclick="document.getElementById('gambar').click()" class="avatar-upload-btn">
                    Ubah Photo
                </button>

                <div>
                    <div class="profile-name">{{ $dataUser->name }}</div>
                    <div class="profile-handle">{{ '@' . strtolower(str_replace(' ', '', $dataUser->name)) }}</div>
                    <div class="profile-badges">
                        <span class="badge verified">Terverifikasi</span>
                        <span class="badge member">Member</span>
                    </div>
                </div>
            </div>
        </aside>

        <div class="right-panel">
            <div class="section-card">
                <div class="card-head">
                    <div class="card-head-icon">i</div>
                    <div class="card-head-title">Data Profile</div>
                </div>

                <form id="profile-data-form" action="{{ url('/profile/update-profile') }}" method="post" enctype="multipart/form-data" class="card-body">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="name" class="form-input" value="{{ old('name', $dataUser->name) }}">
                            @error('name')
                                <small style="color:red;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Nomor</label>
                            <input type="tel" name="nomor" class="form-input" value="{{ old('nomor', $dataUser->nomor) }}">
                            @error('nomor')
                                <small style="color:red;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Jenis Kelamin</label>
                            <select name="gender" class="form-input">
                                <option value="wanita" {{ old('gender', $dataUser->gender) == 'wanita' ? 'selected' : '' }}>Wanita</option>
                                <option value="pria" {{ old('gender', $dataUser->gender) == 'pria' ? 'selected' : '' }}>Pria</option>
                            </select>
                            @error('gender')
                                <small style="color:red;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="birthday" class="form-input" value="{{ old('birthday', $dataUser->birthday) }}">
                            @error('birthday')
                                <small style="color:red;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group full">
                            <label>Provinsi</label>
                            <select name="kota" class="form-input">
                                @forelse ($provinsi as $provinsis)
                                    <option value="{{ $provinsis['name'] }}"
                                        {{ old('kota', $dataUser->kota) == $provinsis['name'] ? 'selected' : '' }}>
                                        {{ $provinsis['name'] }}
                                    </option>
                                @empty
                                    <option value="{{ old('kota', $dataUser->kota) }}">{{ old('kota', $dataUser->kota) }}</option>
                                @endforelse
                            </select>
                            @error('kota')
                                <small style="color:red;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group full">
                            <label>Alamat</label>
                            <input type="text" name="alamat" class="form-input" value="{{ old('alamat', $dataUser->alamat) }}">
                            @error('alamat')
                                <small style="color:red;">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top:20px;">
                        <button type="reset" class="btn-cancel">Batal</button>
                        <button type="submit" class="btn-save">Simpan Data Profile</button>
                    </div>
                </form>
            </div>

            <div class="section-card mt-3">
                <div class="card-head">
                    <div class="card-head-icon">@</div>
                    <div class="card-head-title">Ganti Email</div>
                </div>

                <div class="card-body">
                    <div class="form-group full">
                        <label>Email Saat Ini</label>
                        <input type="email" class="form-input" value="{{ $dataUser->email }}" disabled>
                    </div>

                    <form action="{{ route('profile.email.request-otp') }}" method="post" class="form-grid">
                        @csrf
                        <div class="form-group full">
                            <label>Email Baru</label>
                            <input type="email" name="new_email" class="form-input" value="{{ old('new_email') }}">
                            @error('new_email')
                                <small style="color:red;">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-actions full">
                            <button type="submit" class="btn-save">Kirim OTP</button>
                        </div>
                    </form>

                    <form action="{{ route('profile.email.verify-otp') }}" method="post" class="form-grid" style="margin-top:20px;">
                        @csrf
                        <div class="form-group">
                            <label>Email Baru</label>
                            <input type="email" name="new_email" class="form-input" value="{{ old('new_email') }}">
                        </div>
                        <div class="form-group">
                            <label>OTP</label>
                            <input type="text" name="otp" class="form-input" inputmode="numeric" maxlength="6">
                            @error('otp')
                                <small style="color:red;">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-actions full">
                            <button type="submit" class="btn-save">Verifikasi & Ganti Email</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="section-card mt-3">
                <div class="card-head">
                    <div class="card-head-icon">*</div>
                    <div class="card-head-title">Ganti Password</div>
                </div>

                <form action="{{ route('profile.password.update') }}" method="post" class="card-body">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Password Lama</label>
                            <input type="password" name="current_password" class="form-input">
                            @error('current_password')
                                <small style="color:red;">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="password" class="form-input">
                            @error('password')
                                <small style="color:red;">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" class="form-input">
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top:20px;">
                        <button type="reset" class="btn-cancel">Batal</button>
                        <button type="submit" class="btn-save">Ganti Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewGambar(event) {
            const preview = document.getElementById('preview-image');
            const file = event.target.files[0];

            if (file) {
                preview.src = URL.createObjectURL(file);
            }
        }

        @if (session('editProfile'))
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: @json(session('editProfile')),
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4f46e5'
                });
            });
        @endif
    </script>
@endsection
