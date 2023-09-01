@extends('frontend.authIndex')

@section('auth')
    <div class="page">
        <div class="">
            <!-- Theme-Layout -->

            <!-- CONTAINER OPEN -->
            <div class="col col-login mx-auto mt-7">
                <div class="text-center">
                    <a href="index.html"><img src="../assets/images/brand/logo-white.png" class="header-brand-img m-0"
                            alt=""></a>
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
                        <div class="wrap-input100 validate-input input-group"
                            data-bs-validate="Valid email is required: ex@abc.xyz">
                            <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                <i class="mdi mdi-account" aria-hidden="true"></i>
                            </a>
                            <input class="input100 border-start-0 ms-0 form-control" type="text" name="user"
                                placeholder="User name">
                        </div>
                        <div class="wrap-input100 validate-input input-group"
                            data-bs-validate="Valid email is required: ex@abc.xyz">
                            <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                <i class="zmdi zmdi-email" aria-hidden="true"></i>
                            </a>
                            <input class="input100 border-start-0 ms-0 form-control" type="email" name="email"
                                placeholder="Email" required>
                        </div>
                        <div class="wrap-input100 validate-input input-group"
                            data-bs-validate="Valid email is required: ex@abc.xyz">
                            <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                <i class="zmdi zmdi-whatsapp" aria-hidden="true"></i>
                            </a>
                            <input class="input100 border-start-0 ms-0 form-control" type="number" name="nomor"
                                placeholder="No WhatsApp" required>
                        </div>
                        <div class="wrap-input100 validate-input input-group" id="Password-toggle">
                            <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                <i class="zmdi zmdi-eye" aria-hidden="true"></i>
                            </a>
                            <input class="input100 border-start-0 ms-0 form-control" type="password" name="password"
                                placeholder="Password" required>
                        </div>
                        <label class="custom-control custom-checkbox mt-4">
                            <input type="checkbox" class="custom-control-input" required>
                            <span class="custom-control-label">Agree the <a href="#">terms and policy</a></span>
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
                        <label class="login-social-icon"><span>Register with Social</span></label>
                        <div class="d-flex justify-content-center">
                            <a href="javascript:void(0)">
                                <div class="social-login me-4 text-center">
                                    <i class="fa fa-google"></i>
                                </div>
                            </a>

                        </div>
                    </form>
                </div>
            </div>
            <!-- CONTAINER CLOSED -->
        </div>
    </div>
@endsection
