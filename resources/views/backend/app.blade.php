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
    <!-- TITLE -->
    <title>{{ $title }}</title>
    @include('backend.partial.link')

</head>

<body class="app sidebar-mini ltr light-mode">


    

    <!-- PAGE -->
    <div class="page">
        <div class="page-main">

            <!-- app-Header -->
            @include('backend.partial.appHeader')
            <!-- /app-Header -->

            <!--APP-SIDEBAR-->
            @include('backend.partial.appSidebar')
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
        @include('backend.partial.sidebarRight')
        <!--/Sidebar-right-->

        <!-- Country-selector modal-->
        @include('backend.partial.countryModal')
        <!-- Country-selector modal-->

        <!-- FOOTER -->
        @include('backend.partial.footer')
        <!-- FOOTER END -->

        <!-- BACK-TO-TOP -->
        <a href="#top" id="back-to-top"><i class="fa fa-angle-up"></i></a>
        @include('backend.partial.script')
        @include('backend.partial.myScript')




      

</body>

</html>
