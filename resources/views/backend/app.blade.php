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
    <link rel="shortcut icon" type="image/x-icon" href="../assets/images/brand/favicon.ico">
    <link rel="stylesheet" href="https://drive.google.com/uc?export=view&id=1yTLwNiCZhIdCWolQldwq4spHQkgZDqkG">
    <!-- TITLE -->
    <title>{{ $title }}</title>
    @include('backend.partial.link')



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

        <script>
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const activeTabId = $(e.target).attr('href'); // Ambil ID tab yang aktif
                localStorage.setItem('activeTab', activeTabId);
            });
            $(document).ready(function() {
                const activeTabId = localStorage.getItem('activeTab');

                if (activeTabId) {
                    // Aktifkan tab yang disimpan dalam local storage
                    $('a[href="' + activeTabId + '"]').tab('show');
                } else {
                    // Aktifkan tab default (misalnya tab pertama)
                    $('a[data-bs-toggle="tab"]').first().tab('show');
                }
            });


            $(document).on("show.bs.modal", "#updateTalent", function(e) {
                var tombol = $(e.relatedTarget);
                var uid = tombol.data('uid');
                var talent = tombol.data('talent');
                var modal = $(this);
                modal.find("#uidTalent").val(uid);
                modal.find("#namaTalent").val(talent);
            })
            $(document).on("show.bs.modal", "#updateHarga", function(e) {
                var tombol = $(e.relatedTarget);
                var id = tombol.data('id');
                var harga = tombol.data('harga');
                var kategori = tombol.data('kategori');
                var qty = tombol.data('qty');
                var modal = $(this);
                modal.find("#idHarga").val(id);
                modal.find("#updateHarga").val(harga);
                modal.find("#qtyHarga").val(qty);
                modal.find("#kategoriHarga").val(kategori);
            })
            $(document).on("show.bs.modal", "#updateSlide", function(e) {
                var tombol = $(e.relatedTarget);
                var uid = tombol.data('uid');
                var title = tombol.data('title');
                var sort = tombol.data('sort');
                var url = tombol.data('url');
                var modal = $(this);
                modal.find('#uidSlide').val(uid)
                modal.find('#titleSlide').val(title)
                // modal.find('#sortSlide').val(sort)
                modal.find('#urlSlide').val(url)
                $('#sortSelect').val(sort);
            })


            $(document).ready(function() {
                $(document).on('click', '.delete', function() {
                    var getLink = $(this).attr('href');
                    Swal.fire({
                        title: "Yakin hapus data?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Ya',
                        cancelButtonColor: '#3085d6',
                        cancelButtonText: "Batal"
                    }).then(result => {
                        //jika klik ya maka arahkan ke proses.php
                        if (result.isConfirmed) {
                            window.location.href = getLink;
                        }
                    });
                    return false;
                });
            });

            
        </script>
        {{-- @if ($msg = Session::get('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Horeee!!!',
                text: '{{ $msg }}',
                timer: 3000,
                showConfirmButton: false
            })
        </script> --}}
    {{-- @endif --}}
</body>

</html>
