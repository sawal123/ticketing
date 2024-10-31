<!doctype html>
<html lang="en" dir="ltr">

<head>

    <!-- META DATA -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="{{ $seo[0]->keyword }}">
    <meta name="description" content="{{ $seo[0]->description }}">
    <meta name="author" content="Gotik">


    <!-- FAVICON -->
    <link rel="shortcut icon" href="{{ asset('storage/logo/' . $logo[0]->icon) }}" type="image/x-icon">
    <link rel="stylesheet" href="https://drive.google.com/uc?export=view&id=1yTLwNiCZhIdCWolQldwq4spHQkgZDqkG">
    <!-- TITLE -->
    <title>{{ $title }}</title>
    @include('backend.partial.link')

    {{-- <script src="https://unpkg.com/feather-icons"></script> --}}


</head>

<body class="app sidebar-mini ltr light-mode login-img">


    <!-- GLOBAL-LOADER -->
    <div id="global-loader">
        <img src="../assets/images/loader.svg" class="loader-img" alt="Loader">
    </div>
    <!-- /GLOBAL-LOADER -->

    <!-- PAGE -->
    <div class="page">
        <div class="page-main">

            <!--app-content open-->

            <!-- CONTAINER -->
            @yield('auth')
            <!-- CONTAINER END -->

            <!--app-content close-->

        </div>


        {{-- <!-- BACK-TO-TOP --> --}}
        <a href="#top" id="back-to-top"><i class="fa fa-angle-up"></i></a>
        {{-- @include('backend.partial.script') --}}
        <script src="{{ asset('/assets/js/jquery.min.js') }}"></script>
        {{-- <!-- BOOTSTRAP JS --> --}}
        <script src="{{ asset('/assets/plugins/bootstrap/js/popper.min.js') }}"></script>
        <script src="{{ asset('/assets/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
        {{-- <!-- Color Theme js --> --}}
        <script src="{{ asset('/assets/js/themeColors.js') }}"></script>
        {{-- <!-- CUSTOM JS --> --}}
        <script src="{{ asset('/assets/js/custom.js') }}"></script>
        {{-- <!-- SHOW PASSWORD JS --> --}}
        <script src="{{ asset('/assets/js/show-password.min.js') }}"></script>


       

        <script>
            // Validasi password saat input
            document.getElementById('password').addEventListener('input', function() {
                const password = this.value;
                const errorDiv = document.getElementById('password-error');
                const minLength = 8;
                const hasLetter = /[A-Za-z]/.test(password);
                const hasNumber = /\d/.test(password);

                // Reset error message
                errorDiv.textContent = '';

                // Validasi minimal 8 karakter
                if (password.length < minLength) {
                    errorDiv.textContent = 'Password harus memiliki minimal 8 karakter.';
                }
                // Validasi kombinasi huruf dan angka
                else if (!hasLetter || !hasNumber) {
                    errorDiv.textContent = 'Password harus mengandung huruf dan angka.';
                } else {
                    errorDiv.textContent = 'Password Lulus Validasi.';
                }
            });

            // Toggle visibility of the password
            document.getElementById('togglePassword').addEventListener('click', function() {
                const passwordInput = document.getElementById('password');
                const icon = this;
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.replace('zmdi-eye', 'zmdi-eye-off'); // Ganti ikon menjadi "eye-off"
                } else {
                    passwordInput.type = 'password';
                    icon.classList.replace('zmdi-eye-off', 'zmdi-eye'); // Ganti ikon menjadi "eye"
                }
            });
        </script>

</body>

</html>
