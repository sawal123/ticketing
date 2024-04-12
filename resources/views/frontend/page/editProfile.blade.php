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
                            <form action="{{ url('/profile/update-profile') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                {{-- @method('PUT') --}}
                                <div class="row mt-2">
                                    @if (session('editProfile'))
                                        <div class="alert alert-primary">
                                            {{ session('editProfile') }}
                                        </div>
                                    @endif
                                    <div class="col-12 col-lg-6">

                                        <div class="d-flex justify-content-center">
                                            @if ($dataUser->gambar === '')
                                                <img src="{{ url('/storage/logo/logo.png') }}" alt=""
                                                    width="200">
                                            @else
                                                <img class="mb-3" src="{{ url('/storage/user/' . $dataUser->gambar) }}"
                                                    alt=""
                                                    style="border-radius: 10px ;width:250px; height: 250px; object-fit: cover">
                                            @endif

                                        </div>
                                        <input class="form-control mb-2" type="file" name="gambar"
                                            placeholder="Nama ... " aria-label="default input example">
                                    </div>
                                    <div class="col-12 col-lg-6">

                                        <input class="form-control mb-2" type="text" name="name"
                                            value="{{ $dataUser->name }}" placeholder="Nama ... "
                                            aria-label="default input example">
                                        <input class="form-control mb-2" type="email" name="email"
                                            value="{{ $dataUser->email }}" placeholder="Email ... "
                                            aria-label="default input example">
                                        <input class="form-control mb-2" type="number" name="nomor"
                                            value="{{ $dataUser->nomor }}" placeholder="Number ... "
                                            aria-label="default input example">
                                        <select class="form-select mb-2"
                                            style="height: 40px !important ; padding-left: 30px"
                                            aria-label="Default select example" required name="gender">
                                            <option selected disabled>Choose Gender..</option>
                                            <option value="wanita" @if ($dataUser->gender === 'wanita') @selected(true) @endif>
                                                Wanita</option>
                                            <option value="pria" @if ($dataUser->gender === 'pria') @selected(true) @endif>
                                                Pria</option>

                                        </select>
                                        <select class="form-select mb-2" style="height: 40px !important;padding-left: 30px"
                                            aria-label="Default select example" required name="kota">
                                            <option selected disabled>Choose City..</option>
                                            @foreach ($provinsi as $provinsis)
                                                <option value="{{ $provinsis['name'] }}"
                                                    @if ($dataUser->kota === $provinsis['name']) @selected(true) @endif>
                                                    {{ $provinsis['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <input class=" form-control mb-2" type="text" value="{{ $dataUser->alamat }}"
                                            name="alamat" placeholder="Address..">
                                        <input class="form-control mb-2" type="date" name="birthday"
                                            value="{{ $dataUser->birthday }}" placeholder="birthday ... "
                                            aria-label="default input example">
                                        <input class="form-control mb-2" type="password" name="password"
                                            placeholder="Ganti Password ..." aria-label="default input example">
                                        <div class="">
                                            <button class="btn btn-primary w-100">
                                                Edit
                                            </button>
                                           
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
