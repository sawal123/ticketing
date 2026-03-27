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
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('storage/logo/' . $logo[0]->icon) }}">
    <link rel="stylesheet" href="https://drive.google.com/uc?export=view&id=1yTLwNiCZhIdCWolQldwq4spHQkgZDqkG">

    <title>{{ $title }}</title>
    @include('penyewa.partial.link')



</head>

<body class="app sidebar-mini ltr light-mode">


    <!-- GLOBAL-LOADER -->
    <div id="global-loader">
        <img src="{{ asset('/assets/images/loader.svg') }}" class="loader-img" alt="Loader">
    </div>
    <!-- /GLOBAL-LOADER -->

    <!-- PAGE -->
    <div class="page">
        <div class="page-main">

            <!-- app-Header -->
            @include('penyewa.partial.appHeader')
            <!-- /app-Header -->

            <!--APP-SIDEBAR-->
            @include('penyewa.partial.appSidebar')
            <!--/APP-SIDEBAR-->

            <!--app-content open-->
            <div class="main-content app-content mt-0">
                <div class="side-app">

                    <!-- CONTAINER -->
                    @yield('content')
                    <!-- CONTAINER END -->
                </div>
            </div>
            <!--app-content close-->

        </div>



        <!-- FOOTER -->
        @include('penyewa.partial.footer')
        <!-- FOOTER END -->

        <!-- BACK-TO-TOP -->
        <a href="#top" id="back-to-top"><i class="fa fa-angle-up"></i></a>
        @include('penyewa.partial.script')



        @include('penyewa.partial.myScript')
        {{-- js untuk sweet alert dan update show modal data --}}
        <script src="{{ asset('penyewa/js/style.js') }}?v={{ time() }}"></script>
        {{-- Js untuk button more --}}
        <script src="{{ asset('penyewa/js/styleMore.js') }}?v={{ time() }}"></script>

        {{-- <script>
            $(document).on("show.bs.modal", "#modalEditStaff", function(event) {
                var button = $(event.relatedTarget);

                var uid = button.data("uid");
                var name = button.data("name");
                var email = button.data("email");
                var actionUrl = button.data("url"); // Ambil URL dari data-url

                console.log("Data tertangkap:", name, actionUrl);

                var modal = $(this);
                modal.find("#edit_name").val(name);
                modal.find("#edit_email").val(email);

                // Langsung pasang URL yang dibawa tombol ke form action
                modal.find("#formEditStaff").attr("action", actionUrl);
            });
        </script> --}}


</body>

</html>
