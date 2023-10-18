@extends('frontend.authIndex')

@section('auth')
    <div class="page">
        <div class="">
            <!-- Theme-Layout -->
            <!-- CONTAINER OPEN -->
            <div class="col col-login mx-auto">
                <div class="text-center">
                    <a href="index.html"><img src="../assets/images/brand/logo-white.png" class="header-brand-img m-0"
                            alt=""></a>
                </div>
            </div>

            <!-- CONTAINER OPEN -->
            <div class="container-login100">
                <div class="wrap-login100 p-6">
                    <form class="login100-form validate-form" action="{{ url('/new-password') }}" method="post">
                        <span class="login100-form-title pb-5">
                            Reset Password
                        </span>
                        @csrf
                        <div class="wrap-input100 validate-input input-group" id="Password-toggle">
                            <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                <i class="zmdi zmdi-eye text-muted" aria-hidden="true"></i>
                            </a>
                            <input class="input100 border-start-0 form-control ms-0" type="password"
                                name="password" placeholder="Password" max="7" required>
                                <input type="hidden" name="data" value="{{$data}}">
                        </div>
                        <div class="submit w-100">
                            <button class="btn btn-primary w-100" type="submit">Reset Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
