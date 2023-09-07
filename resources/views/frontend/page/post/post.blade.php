@extends('frontend.index')

@section('content')
    <div class="row">
        <div class="col ">
            <div class="container pt-lg-5 mt-5">
                <div class="text-center">
                    <h2 class=" mb-5" style="margin-top: 100px">Temukan Event Lebih Banyak</h2>
                    {{-- <p class=" mb-5" style="margin-top: 100px">Cari Event</p> --}}
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
