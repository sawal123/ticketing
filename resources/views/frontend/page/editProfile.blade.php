@extends('frontend.index')

@section('content')
    <div class="container" style="margin-top:150px">
        <div class="row">
            <div class="col">
                <div class="card ">
                    <div class="card body">
                        <div class="container p-5">
                            <h4 class="text-center">Edit Profile</h4>
                            <hr>
                            <form action="{{url('/profile/update-profile')}}" method="post" enctype="multipart/form-data">
                                @csrf
                                {{-- @method('PUT') --}}
                            <div class="row mt-2">
                                @if(session('editProfile'))
                                    <div class="alert alert-primary">
                                        {{session('editProfile')}}
                                    </div>
                                @endif
                                    <div class="col-12 col-lg-6">

                                        <div class="d-flex justify-content-center">
                                            <img src="http://172.21.208.1:8000/storage/logo/logo.png" alt=""
                                                width="200">
                                        </div>
                                        <input class="form-control mb-2" type="file" name="gambar" placeholder="Nama ... "
                                            aria-label="default input example">
                                    </div>
                                    <div class="col-12 col-lg-6">

                                        <input class="form-control mb-2" type="text" name="name" value="{{ $dataUser->name }}"
                                            placeholder="Nama ... " aria-label="default input example">
                                        <input class="form-control mb-2" type="email" name="email" value="{{ $dataUser->email }}"
                                            placeholder="Email ... " aria-label="default input example">
                                        <input class="form-control mb-2" type="number" name="nomor" value="{{ $dataUser->nomor }}"
                                            placeholder="Number ... " aria-label="default input example">
                                        <input class="form-control mb-2" type="password" name="password" placeholder="Ganti Password ..."
                                            aria-label="default input example">
                                        <div class="">
                                            <button class="btn btn-primary">
                                                Edit
                                            </button>
                                            {{-- <button class="btn btn-primary">
                                                Edit
                                            </button> --}}
                                        </div>

                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
