<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="{{ $seo[0]->keyword }}">
    <meta name="description" content="{{ $seo[0]->description }}">
    <title>GOTIK {{ $title }}</title>
    <link rel="shortcut icon" href="{{ asset('storage/logo/' . $logo[0]->icon) }}" type="image/x-icon">

    {{-- <link rel="stylesheet" href="{{ asset('assets/css/styleMore.css') }}"> --}}
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Public+Sans:wght@500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- End google font  -->
    {{-- <style>
        .input {
            width: 80px !important;
            height: 40px !important;
            border-radius: 0px !important;
        }
    </style> --}}

      <link rel="stylesheet" href="{{ asset('landing/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert2/dist/sweetalert2.min.css') }}">
    <link href="{{ asset('/assets/css/icons.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('landing/css/main-new.css') }}" />
    <style>
        a {
            text-decoration: none;
        }
    </style>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @livewireStyles
</head>

<body>



    @include('frontend.partial.header-new')
    <!--End landex-header-section -->

    @yield('content')

    {{-- 
    <div class="fugu-preloader">
        <div class="fugu-spinner">
            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="46" />
            </svg>
        </div>
        <div class="fugu-title">loading...</div>
    </div> --}}


    @include('frontend.partial.footer')
    <!-- scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>

    <script src="{{ asset('landing/js/jquery-3.6.0.min.js') }}"></script>
   <script src="{{ asset('landing/js/wow.min.js') }}"></script>
    <script src="{{ asset('landing/js/countdown.js') }}"></script>
    <script src="https://maps.googleapis.com/maps/api/js?v=3&key=AIzaSyArZVfNvjnLNwJZlLJKuOiWHZ6vtQzzb1Y"></script>

    <script src="{{ asset('assets/plugins/sweetalert2/dist/sweetalert2.min.js') }}"></script>

    <script src="{{ asset('landing/js/menu/togle.js') }}"></script>
    <!-- <script src="{{ asset('landing/js/app.js') }}"></script> -->
    {{-- <script src="{{ asset('penyewa/js/styleMore.js') }}"></script> --}}






    {{-- @vite([]) --}}
    @livewireScripts
</body>

</html>
