@extends('frontend.index')

@section('content')
    {{-- <link rel="stylesheet" href="{{ asset('../public/landing/css/main-new.css') }}"> --}}
    <style>
        .card-img {
            position: relative;
            height: 180px;
            overflow: hidden;
            border-radius: 16px;
        }

        .img-bg {
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
        }
    </style>
    {{-- <div class="" style="height: 150px; background-color: #13111A;"></div> --}}
    <div class="row search-page-wrap" style="background-color: var(--bg); min-height: 85vh; transition: background-color 0.25s ease;">
        <div class="col ">

            <div class="container pt-lg-5 mt-5">
                <div class="text-center" style="margin-top: 60px">
                </div>
                @if (session('deleteList'))
                    <div class="alert alert-danger">
                        {{ session('deleteList') }}
                    </div>
                @endif
                @include('frontend.page.post.post-menu')
            </div>
        </div>
    </div>
@endsection
