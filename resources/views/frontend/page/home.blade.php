@extends('frontend.index')
@section('content')
    {{-- @include('frontend.page.home.heroSection') --}}
    <!-- End fugu-hero-section -->
    {{-- <div class="" style="height: 150px; background-color: #13111A;"></div> --}}
    @include('frontend.page.home.slider')
    <!-- End slider section -->
    @include('frontend.page.home.menu')
    {{-- <div class="fugu-go-top">
        <img src="{{asset('landing/images/svg/arrow-black-right.svg')}}" alt="">
    </div> --}}

<script src="{{asset('landing/js/menu/home.js')}}"></script>
@endsection
