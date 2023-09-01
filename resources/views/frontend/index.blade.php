<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>

    <link rel="shortcut icon" href="landing/images/favicon.ico" type="image/x-icon">
    <link rel="icon" href="landing/images/favicon.ico" type="image/x-icon">
    <!--- End favicon-->

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Public+Sans:wght@500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- End google font  -->
    <style>
        .input {
            width: 80px !important;
            height: 40px !important;
            border-radius: 0px !important;
            /* padding: 15px 35px !important; */
        }
    </style>

    <link rel="stylesheet" href="{{ asset('landing/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('landing/css/fontawesome.css') }}" />
    <link rel="stylesheet" href="{{ asset('landing/css/slick.css') }}" />
    <link rel="stylesheet" href="{{ asset('landing/css/magnific-popup.css') }}" />
    <link rel="stylesheet" href="{{ asset('landing/css/swiper-bundle.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('landing/css/icomoon-font.css') }}" />
    <link rel="stylesheet" href="{{ asset('landing/css/animate.css') }}" />
    <link rel="stylesheet" href="{{asset('assets/plugins/sweetalert2/dist/sweetalert2.min.css')}}">


    <!-- Code Editor  -->

    <link rel="stylesheet" href="{{ asset('landing/css/main.css') }}" />
    <link rel="stylesheet" href="{{ asset('landing/css/app.min.css') }}" />
</head>

<body class="light">



    @include('frontend.partial.header')
    <!--End landex-header-section -->

    @yield('content')
    <div class="fugu-preloader">
        <div class="fugu-spinner">
            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="46" />
            </svg>
        </div>
        <div class="fugu-title">loading...</div>
    </div>

    <!-- scripts -->
    <script src="{{ asset('landing/js/jquery-3.6.0.min.js') }}"></script>
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script> --}}
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script> --}}
    <script src="{{ asset('landing/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('landing/js/menu/menu.js') }}"></script>
    <script src="{{ asset('landing/js/slick.js') }}"></script>
    <script src="{{ asset('landing/js/isotope.pkgd.min.js') }}"></script>
    <script src="{{ asset('landing/js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('landing/js/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('landing/js/countdown.js') }}"></script>
    <script src="{{ asset('landing/js/wow.min.js') }}"></script>
    <script src="https://maps.googleapis.com/maps/api/js?v=3&key=AIzaSyArZVfNvjnLNwJZlLJKuOiWHZ6vtQzzb1Y"></script>

    <script src="{{asset('assets/plugins/sweetalert2/dist/sweetalert2.min.js')}}"></script>

    <script src="{{ asset('landing/js/app.js') }}"></script>


    <script>
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



    <script>
        $(document).ready(function() {
            $('.tes').slick({
                infinite: true,
                slidesToShow: 3,
                slidesToScroll: 3
            });
        });

        $(document).ready(function() {
            const totalDisplay = $(".total");
            let cart = {};

            function updateTotal() {
                let total = 0;
                for (const productId in cart) {
                    total += cart[productId].quantity * cart[productId].price;
                }
                totalDisplay.text("Rp " + total.toLocaleString("id-ID"));
            }

            // Initialize cart data with the price from the input
            $(".input-wrapper").each(function() {
                const productId = $(this).find(".btn-plus").data("target");
                const price = parseFloat($(this).find(".price-input").val()) || 0;
                cart[productId] = {
                    quantity: 0,
                    price: price
                };
            });

            $(".btn-plus").on("click", function() {
                const targetId = $(this).data("target");
                if (!cart[targetId]) {
                    cart[targetId] = {
                        quantity: 0,
                        price: 0
                    };
                }
                if (cart[targetId].quantity < 5) {
                    cart[targetId].quantity++;
                    $("." + targetId).val(cart[targetId].quantity);
                    updateTotal();
                    console.log(updateTotal());
                }
            });
            $(".btn-minus").on("click", function() {
                const targetId = $(this).data("target");
                if (cart[targetId].quantity > 0) {
                    cart[targetId].quantity--;
                    $("." + targetId).val(cart[targetId].quantity);
                    updateTotal();
                }
            });
        });
    </script>
</body>

</html>
