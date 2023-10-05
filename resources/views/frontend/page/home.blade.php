@extends('frontend.index')

@section('content')
    {{-- @include('frontend.page.home.heroSection') --}}
    <!-- End fugu-hero-section -->
    <div class="" style="height: 150px; background-color: #13111A;"></div>
    @include('frontend.page.home.slider')
    <!-- End slider section -->

    <div class="fugu--portfolio-section fugu--section-padding" >
        <h4 class="text-center" style="color: white">Event Terbaru</h4>
        <p class="text-center" style="color: white">Temukan acara favorit Anda, dan mari bersenang-senang</p>
        @include('frontend.page.home.menu')
        <div class="fugu--shape2">
            <img src="{{asset('assets/images/shape2/shape2.png')}}" alt="">
        </div>
        <!--- End container -->
    </div>
    <div class="fugu-go-top">
        <img src="{{asset('landing/images/svg/arrow-black-right.svg')}}" alt="">
    </div>


   
@endsection
