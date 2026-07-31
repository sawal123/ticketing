@extends('frontend.index')

@section('content')
    @php
        $emailModalErrors = $errors->has('new_email') || $errors->has('otp');
        $passwordModalErrors = $errors->has('current_password') || $errors->has('password') || $errors->has('password_confirmation');
        $pendingEmailChange = old('new_email', session('pending_email_change'));
        $shouldOpenEmailModal = $emailModalErrors || session()->has('pending_email_change');
    @endphp

    <div class="mt-5 page-wrap">
        <aside class="left-panel">
            @if ($errors->any() && ! $emailModalErrors && ! $passwordModalErrors)
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
                    <div class="profile-handle">{{ $dataUser->email }}</div>
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
                    <div class="card-head-title">Akun & Keamanan</div>
                </div>

                <div class="card-body">
                    <div class="security-panel">
                        <div>
                            <label>Email Saat Ini</label>
                            <div class="security-email">{{ $dataUser->email }}</div>
                        </div>
                        <div class="security-actions">
                            <button type="button" class="btn-save" data-open-modal="email-change-modal">Ganti Email</button>
                            <button type="button" class="btn-cancel" data-open-modal="password-change-modal">Ganti Password</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="email-change-modal" class="profile-modal" aria-hidden="true">
        <div class="profile-modal__overlay" data-close-modal></div>
        <div class="profile-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="email-change-title">
            <div class="profile-modal__head">
                <div>
                    <h3 id="email-change-title">Ganti Email</h3>
                    <p>Kode OTP akan dikirim ke email baru. Email belum berubah sebelum OTP diverifikasi.</p>
                </div>
                <button type="button" class="profile-modal__close" data-close-modal aria-label="Tutup">x</button>
            </div>

            <div class="profile-modal__body">
                <form action="{{ route('profile.email.request-otp') }}" method="post" class="modal-step">
                    @csrf
                    <div class="modal-step__label">Step 1</div>
                    <div class="form-group full">
                        <label>Email Baru</label>
                        <input type="email" name="new_email" class="form-input" value="{{ old('new_email') }}">
                        @error('new_email')
                            <small style="color:red;">{{ $message }}</small>
                        @enderror
                    </div>
                    <button type="submit" class="btn-save modal-button">Kirim OTP</button>
                </form>

                @if ($pendingEmailChange)
                <form action="{{ route('profile.email.verify-otp') }}" method="post" class="modal-step">
                    @csrf
                    <div class="modal-step__label">Step 2</div>
                    <div class="otp-target">
                        Kode OTP dikirim ke: <strong>{{ $pendingEmailChange }}</strong>
                    </div>
                    <input type="hidden" name="new_email" value="{{ $pendingEmailChange }}">
                    <div class="form-group full">
                        <label>OTP</label>
                        <input type="text" name="otp" class="form-input" inputmode="numeric" maxlength="6">
                        @error('otp')
                            <small style="color:red;">{{ $message }}</small>
                        @enderror
                    </div>
                    <button type="submit" class="btn-save modal-button">Verifikasi & Ganti Email</button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <div id="password-change-modal" class="profile-modal" aria-hidden="true">
        <div class="profile-modal__overlay" data-close-modal></div>
        <div class="profile-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="password-change-title">
            <div class="profile-modal__head">
                <div>
                    <h3 id="password-change-title">Ganti Password</h3>
                    <p>Gunakan password minimal 8 karakter dengan huruf dan angka.</p>
                </div>
                <button type="button" class="profile-modal__close" data-close-modal aria-label="Tutup">x</button>
            </div>

            <form action="{{ route('profile.password.update') }}" method="post" class="profile-modal__body">
                @csrf
                <div class="form-group full">
                    <label>Password Lama</label>
                    <input type="password" name="current_password" class="form-input">
                    @error('current_password')
                        <small style="color:red;">{{ $message }}</small>
                    @enderror
                </div>
                <div class="form-group full">
                    <label>Password Baru</label>
                    <input type="password" name="password" class="form-input">
                    @error('password')
                        <small style="color:red;">{{ $message }}</small>
                    @enderror
                </div>
                <div class="form-group full">
                    <label>Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" class="form-input">
                    @error('password_confirmation')
                        <small style="color:red;">{{ $message }}</small>
                    @enderror
                </div>
                <button type="submit" class="btn-save modal-button">Ganti Password</button>
            </form>
        </div>
    </div>

    <style>
        .security-panel {
            align-items: center;
            display: flex;
            gap: 20px;
            justify-content: space-between;
        }

        .security-email {
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            margin-top: 8px;
            overflow-wrap: anywhere;
        }

        .security-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .profile-modal {
            align-items: center;
            display: none;
            inset: 0;
            justify-content: center;
            padding: 20px;
            position: fixed;
            z-index: 9999;
        }

        .profile-modal.is-open {
            display: flex;
        }

        .profile-modal__overlay {
            background: rgba(5, 4, 18, 0.72);
            inset: 0;
            position: absolute;
        }

        .profile-modal__dialog {
            background: #16152a;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 22px;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.45);
            max-height: calc(100vh - 40px);
            max-width: 520px;
            overflow-y: auto;
            position: relative;
            width: 100%;
        }

        .profile-modal__head {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            padding: 24px 24px 12px;
        }

        .profile-modal__head h3 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 800;
            margin: 0 0 8px;
        }

        .profile-modal__head p {
            color: #b7b5ca;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
        }

        .profile-modal__close {
            background: rgba(255, 255, 255, 0.08);
            border: 0;
            border-radius: 999px;
            color: #ffffff;
            cursor: pointer;
            flex: 0 0 auto;
            height: 34px;
            width: 34px;
        }

        .profile-modal__body {
            display: grid;
            gap: 18px;
            padding: 12px 24px 24px;
        }

        .modal-step {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: grid;
            gap: 14px;
            padding-top: 18px;
        }

        .modal-step:first-child {
            border-top: 0;
            padding-top: 0;
        }

        .modal-step__label {
            color: #f5a524;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .otp-target {
            background: rgba(245, 165, 36, 0.12);
            border: 1px solid rgba(245, 165, 36, 0.24);
            border-radius: 14px;
            color: #f8f4eb;
            font-size: 13px;
            padding: 12px 14px;
            overflow-wrap: anywhere;
        }

        .modal-button {
            width: 100%;
        }

        @media (max-width: 640px) {
            .profile-modal {
                align-items: flex-start;
                padding: 14px;
            }

            .security-panel {
                align-items: stretch;
                flex-direction: column;
            }

            .security-actions {
                flex-direction: column;
            }
        }
    </style>

    <script>
        function previewGambar(event) {
            const preview = document.getElementById('preview-image');
            const file = event.target.files[0];

            if (file) {
                preview.src = URL.createObjectURL(file);
            }
        }

        function openProfileModal(id) {
            const modal = document.getElementById(id);

            if (!modal) {
                return;
            }

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeProfileModal(modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-open-modal]').forEach(function(button) {
                button.addEventListener('click', function() {
                    openProfileModal(button.dataset.openModal);
                });
            });

            document.querySelectorAll('[data-close-modal]').forEach(function(element) {
                element.addEventListener('click', function() {
                    closeProfileModal(element.closest('.profile-modal'));
                });
            });

            document.addEventListener('keydown', function(event) {
                if (event.key !== 'Escape') {
                    return;
                }

                document.querySelectorAll('.profile-modal.is-open').forEach(closeProfileModal);
            });

            @if ($shouldOpenEmailModal)
                openProfileModal('email-change-modal');
            @endif

            @if ($passwordModalErrors)
                openProfileModal('password-change-modal');
            @endif

            @if (session('editProfile'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: @json(session('editProfile')),
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4f46e5'
                });
            @endif
        });
    </script>
@endsection
