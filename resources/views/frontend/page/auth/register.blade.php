@extends('frontend.authIndex')

@section('auth')
    <div class="page">
        <div class="">
            <!-- Theme-Layout -->

            <!-- CONTAINER OPEN -->
            <div class="col col-login mx-auto mt-7">
                <div class="text-center">
                    <a href="{{ url('/') }}"><img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" height="100"
                            class="header-brand-img" alt=""></a>
                </div>
            </div>
            <div class="container-login100">
                <div class="wrap-login100 p-6">
                    <form class="login100-form validate-form" method="POST" action="{{ route('register-user') }}">
                        @csrf
                        <span class="login100-form-title">
                            Registration
                        </span>
                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label" for="nama">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="user" class="form-control" id="nama"
                                placeholder="Nama Kamu :">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" id="email"
                                placeholder="Email Kamu :">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="nomor">Nomor WhatsApp <span
                                    class="text-danger">*</span></label>
                            <input type="number" name="nomor" class="form-control" id="nomor"
                                placeholder="Nomor WhatsApp Kamu :">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="date">Tanggal Lahir <span
                                    class="text-danger">*</span></label>
                            <input type="date" name="birthday" class="form-control" id="date" ">
                                        </div>


                                        <div class="mb-3">
                                            <label class="form-label" for="date">Gender <span class="text-danger">*</span></label>
                                            <select class="form-select" aria-label="Default select example" required name="gender">
                                                <option selected disabled>Choose Gender..</option>
                                                <option value="wanita">Wanita</option>
                                                <option value="pria">Pria</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="date">Provinsi <span class="text-danger">*</span></label>
                                            <select class="form-select" aria-label="Default select example" required name="kota">
                                                <option selected disabled>Choose City..</option>
                                                    @foreach ($provinsi as $provinsi)
                            <option value="{{ $provinsi['name'] }}">{{ $provinsi['name'] }}</option>
                            @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="alamat">Alamat <span class="text-danger">*</span></label>
                            <input type="text" name="alamat" class="form-control" id="alamat"
                                placeholder="Alamat Kamu :">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" required class="form-control" id="password"
                                    placeholder="Password Kamu :">
                                <span class="input-group-text">
                                    <i class="zmdi zmdi-eye" id="togglePassword" style="cursor: pointer;"
                                        aria-hidden="true"></i>
                                </span>
                            </div>
                            <div id="password-error" class="text-danger mt-1"></div>
                            @error('password')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>


                        <label class="custom-control custom-checkbox mt-4">
                            <input type="checkbox" class="custom-control-input" required>
                            <span class="custom-control-label">Agree the <a href="{{ url('/term') }}">terms and
                                    policy</a></span>
                        </label>
                        <div class="container-login100-form-btn">
                            <button type="submit" class="login100-form-btn btn-primary">
                                Register
                            </button>
                        </div>
                        <div class="text-center pt-3">
                            <p class="text-dark mb-0 d-inline-flex">Already have account ?<a href="{{ url('/login') }}"
                                    class="text-primary ms-1">Sign In</a></p>
                        </div>
                        {{-- <label class="login-social-icon"><span>Register with Social</span></label>
                        <div class="d-flex justify-content-center">
                            <a href="javascript:void(0)">
                                <div class="social-login me-4 text-center">
                                    <i class="fa fa-google"></i>
                                </div>
                            </a>

                        </div> --}}
                    </form>
                </div>
            </div>
            <!-- CONTAINER CLOSED -->
        </div>
    </div>
    
@endsection
