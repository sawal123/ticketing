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
                    <form class="login100-form validate-form" action="{{ url('/email') }}" method="post">
                        <span class="login100-form-title pb-5">
                            Forgot Password
                        </span>
                        @if (session('success'))
                            <div class="alert alert-primary">
                                {{ session('success') }}
                            </div>
                        @elseif (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @else
                            <p class="text-muted">Enter the email address registered on your account</p>
                        @endif


                        @csrf
                        <div class="wrap-input100 validate-input input-group"
                            data-bs-validate="Valid email is required: ex@abc.xyz">
                            <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                <i class="zmdi zmdi-email" aria-hidden="true"></i>
                            </a>
                            <input class="input100 border-start-0 ms-0 form-control" name="email" type="email"
                                placeholder="Email" required>
                        </div>
                        <div class="submit w-100">
                            <button class="btn btn-primary w-100" type="submit">Submit</button>
                        </div>

                        <div class="text-center mt-4">
                            <p class="text-dark mb-0 d-inline-flex">Forgot It ?<a class="text-primary ms-1"
                                    href="#">Send me Back</a></p>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
