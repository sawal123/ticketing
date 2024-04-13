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


        <!-- BACK-TO-TOP -->
        <a href="#top" id="back-to-top"><i class="fa fa-angle-up"></i></a>
        {{-- @include('backend.partial.script') --}}
        <script src="{{ asset('/assets/js/jquery.min.js') }}"></script>
        <!-- BOOTSTRAP JS -->
        <script src="{{ asset('/assets/plugins/bootstrap/js/popper.min.js') }}"></script>
        <script src="{{ asset('/assets/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
        <!-- Color Theme js -->
        <script src="{{ asset('/assets/js/themeColors.js') }}"></script>
        <!-- CUSTOM JS -->
        <script src="{{ asset('/assets/js/custom.js') }}"></script>
        <!-- SHOW PASSWORD JS -->
        <script src="{{ asset('/assets/js/show-password.min.js') }}"></script>

      

</body>

</html>
