@extends('frontend.index')

@section('content')
    
{{-- @include('frontend.page.home.heroSection') --}}
<!-- End fugu-hero-section -->
<div class="" style="height: 150px; background-color: #13111A;" ></div>
@include('frontend.page.home.slider')
<!-- End slider section -->

<div class="fugu--portfolio-section fugu--section-padding">
    @include('frontend.page.home.menu')
    <div class="fugu--shape2">
        {{-- <img src="{{asset('assets/images/shape2/shape2.png')}}" alt=""> --}}
    </div>
    <!--- End container -->
</div>


<div class="fugu-go-top">
    {{-- <img src="landing/images/svg/arrow-black-right.svg" alt=""> --}}
</div>



<!-- Footer  -->

<footer class="fugu--footer-section">
    <div class="container">
        <div class="fugu--footer-top">
            <div class="row">
                <div class="col-lg-3">
                    <div class="fugu--textarea">
                        <div class="fugu--footer-logo">
                            {{-- <img src="assets/images/logo/logo-white.svg" alt=""
                                class="light-version-logo"> --}}
                        </div>
                        <p>Discover NFTs by category, track the latest drops, and follow the collections you love to
                            enjoy it!</p>
                        <div class="fugu--social-icon">
                            {{-- <ul>
                                <li><a href=""><img src="assets/images/social2/twitter.svg"
                                            alt=""></a></li>
                                <li><a href=""><img src="assets/images/social2/facebook.svg"
                                            alt=""></a></li>
                                <li><a href=""><img src="assets/images/social2/instagram.svg"
                                            alt=""></a></li>
                                <li><a href=""><img src="assets/images/social2/github.svg"
                                            alt=""></a></li>
                            </ul> --}}
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-1 col-md-4 col-sm-4">
                    <div class="fugu--footer-menu">
                        <span>Marketplace</span>
                        <ul>
                            <li><a href="">Create A Store</a></li>
                            <li><a href="">Start Selling</a></li>
                            <li><a href="">My Account</a></li>
                            <li><a href="">Job</a></li>
                            <li><a href="">List a Item</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-1 col-md-4 col-sm-4">
                    <div class="fugu--footer-menu">
                        <span>Marketplace</span>
                        <ul>
                            <li><a href="">Art</a></li>
                            <li><a href="">Digital Art</a></li>
                            <li><a href="">Photography</a></li>
                            <li><a href="">Games</a></li>
                            <li><a href="">Music</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-1 col-md-4 col-sm-4">
                    <div class="fugu--footer-menu">
                        <span>Marketplace</span>
                        <ul>
                            <li><a href="">Explore NFTs</a></li>
                            <li><a href="">Platform Status</a></li>
                            <li><a href="">Help center</a></li>
                            <li><a href="">Partners</a></li>
                            <li><a href="">Marketplace</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="fugu--footer-bottom">
            <p>&copy; Copyright 2022, All Rights Reserved by Mthemeus</p>
            <div class="fugu--footer-menu">
                <ul>
                    <li><a href="">Terms</a></li>
                    <li><a href=""> Privacy Policy</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

@endsection