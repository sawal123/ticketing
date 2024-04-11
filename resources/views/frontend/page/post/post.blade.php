@extends('frontend.index')

@section('content')
{{-- <div class="" style="height: 150px; background-color: #13111A;"></div> --}}
    <div class="row" style="background-color: #13111A;">
        <div class="col ">
            
            <div class="container pt-lg-5 mt-5">
                <div class="text-center"style="margin-top: 100px">
                    {{-- <h2 class=" mb-5" >Temukan Event Lebih Banyak</h2> --}}
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
