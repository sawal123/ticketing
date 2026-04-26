@extends('frontend.index')

@section('content')

    <div class=" mt-5">

        <!-- ✅ SATU FORM UNTUK SEMUA -->
        <form action="{{ url('/profile/update-profile') }}" method="post" enctype="multipart/form-data" class="page-wrap">
            @csrf



            <!-- LEFT PANEL -->
            <aside class="left-panel">
                <!-- GLOBAL ERROR -->
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin:0;padding-left:18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- SUCCESS -->
                @if (session('editProfile'))
                    <div class="alert alert-success">
                        {{ session('editProfile') }}
                    </div>
                @endif

                <div class="avatar-card">

                    <!-- AVATAR -->
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

                        <!-- 🔥 SATU INPUT GAMBAR -->
                        <input type="file" id="gambar" name="gambar" accept="image/*" style="display:none"
                            onchange="previewGambar(event)">
                    </div>

                    <!-- ERROR GAMBAR -->
                    @error('gambar')
                        <small style="color:red;">{{ $message }}</small>
                    @enderror

                    <!-- BUTTON -->
                    <button type="button" onclick="document.getElementById('gambar').click()" class="avatar-upload-btn">
                        📷 Ubah Photo
                    </button>

                    <!-- INFO -->
                    <div>
                        <div class="profile-name">{{ $dataUser->name }}</div>
                        <div class="profile-handle">
                            {{ '@' . strtolower(str_replace(' ', '', $dataUser->name)) }}
                        </div>

                        <div class="profile-badges">
                            <span class="badge verified">✓ Terverifikasi</span>
                            <span class="badge member">★ Member</span>
                        </div>
                    </div>

                </div>

                <!-- STATS -->
                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-num">12</div>
                        <div class="stat-label">Event</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">7</div>
                        <div class="stat-label">Tiket</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">3</div>
                        <div class="stat-label">Transaksi</div>
                    </div>
                </div>

            </aside>

            <!-- RIGHT PANEL -->
            <div class="right-panel">

                <!-- INFORMASI -->
                <div class="section-card">
                    <div class="card-head">
                        <div class="card-head-icon">✏️</div>
                        <div class="card-head-title">Informasi Pribadi</div>
                    </div>

                    <div class="card-body">
                        <div class="form-grid">

                            <!-- NAMA -->
                            <div class="form-group">
                                <label>Nama</label>
                                <input type="text" name="name" class="form-input"
                                    value="{{ old('name', $dataUser->name) }}">
                                @error('name')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- GENDER -->
                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <select name="gender" class="form-input">
                                    <option disabled>Choose Gender</option>
                                    <option value="wanita"
                                        {{ old('gender', $dataUser->gender) == 'wanita' ? 'selected' : '' }}>
                                        Wanita
                                    </option>
                                    <option value="pria"
                                        {{ old('gender', $dataUser->gender) == 'pria' ? 'selected' : '' }}>
                                        Pria
                                    </option>
                                </select>
                                @error('gender')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- EMAIL -->
                            <div class="form-group full">
                                <label>Email</label>
                                <input type="email" name="email" class="form-input"
                                    value="{{ old('email', $dataUser->email) }}">
                                @error('email')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- NOMOR -->
                            <div class="form-group">
                                <label>Nomor</label>
                                <input type="tel" name="nomor" class="form-input"
                                    value="{{ old('nomor', $dataUser->nomor) }}">
                                @error('nomor')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- TANGGAL -->
                            <div class="form-group">
                                <label>Tanggal Lahir</label>
                                <input type="date" name="birthday" class="form-input"
                                    value="{{ old('birthday', $dataUser->birthday) }}">
                                @error('birthday')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- KOTA -->
                            <div class="form-group full">
                                <label>Provinsi</label>
                                <select name="kota" class="form-input">
                                    @foreach ($provinsi as $provinsis)
                                        <option value="{{ $provinsis['name'] }}"
                                            {{ old('kota', $dataUser->kota) == $provinsis['name'] ? 'selected' : '' }}>
                                            {{ $provinsis['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('kota')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- ALAMAT -->
                            <div class="form-group full">
                                <label>Alamat</label>
                                <input type="text" name="alamat" class="form-input"
                                    value="{{ old('alamat', $dataUser->alamat) }}">
                                @error('alamat')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                <!-- PASSWORD -->
                <div class="section-card mt-3">
                    <div class="card-head">
                        <div class="card-head-icon">🔐</div>
                        <div class="card-head-title">Keamanan & Password</div>
                    </div>

                    <div class="card-body">
                        <div class="form-grid">

                            <div class="form-group">
                                <label>Password Baru</label>
                                <input type="password" name="password" class="form-input">
                                @error('password')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div>

                        </div>

                        <div class="form-actions" style="margin-top:20px;">
                            <button type="reset" class="btn-cancel">Batal</button>
                            <button type="submit" class="btn-save">Simpan Perubahan</button>
                        </div>

                    </div>
                </div>

            </div>

        </form>
    </div>

    <script>
        function previewGambar(event) {
            const preview = document.getElementById('preview-image');
            const file = event.target.files[0];

            if (file) {
                preview.src = URL.createObjectURL(file);
            }
        }
    </script>


@endsection
