<!doctype html>
<html lang="en" dir="ltr">

<head>

    <!-- META DATA -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Sash – Bootstrap 5  Admin & Dashboard Template">
    <meta name="author" content="Spruko Technologies Private Limited">
    <meta name="keywords"
        content="admin,admin dashboard,admin panel,admin template,bootstrap,clean,dashboard,flat,jquery,modern,responsive,premium admin templates,responsive admin,ui,ui kit.">

    <!-- FAVICON -->
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('storage/logo/'. $logo[0]->icon)}}">
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

        <!-- Sidebar-right -->
        @include('penyewa.partial.sidebarRight')
        <!--/Sidebar-right-->

        <!-- Country-selector modal-->
        @include('penyewa.partial.countryModal')
        <!-- Country-selector modal-->

        <!-- FOOTER -->
        @include('penyewa.partial.footer')
        <!-- FOOTER END -->

        <!-- BACK-TO-TOP -->
        <a href="#top" id="back-to-top"><i class="fa fa-angle-up"></i></a>
        @include('penyewa.partial.script')

        @include('penyewa.partial.myScript')
        <script src="{{asset('penyewa/js/style.js')}}"></script>
        <script src="{{ asset('penyewa/js/styleMore.js') }}"></script>
        <script>
            // document.addEventListener("DOMContentLoaded", function() {
            //     var content = document.querySelector(".content");
            //     var container = document.querySelector(".container");
            //     var readMoreBtn = document.getElementById("readMoreBtn");

            //     readMoreBtn.addEventListener("click", function() {
            //         container.classList.toggle("expanded");

            //         if (container.classList.contains("expanded")) {
            //             readMoreBtn.textContent = "Baca Lebih Sedikit";
            //         } else {
            //             readMoreBtn.textContent = "Baca Selengkapnya";
            //         }
            //     });
            // });
        </script>
      
</body>

</html>
