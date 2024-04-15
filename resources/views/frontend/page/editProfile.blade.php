@extends('frontend.index')

@section('content')
    <style>
        .image-container {
            /* width: 250px;
                height: 250px; */
            overflow: hidden;
        }
    </style>
    <div class="container d-flex justify-content-center" style="margin-top:150px">

        <div class="card " style="max-width: 600px !important; width: 600px;">
            <div class="card body">
                <div class="container">
                    {{-- <h4 class="text-center">Edit Profile</h4> --}}
                    {{-- <hr> --}}
                    <form action="{{ url('/profile/update-profile') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        {{-- @method('PUT') --}}

                        @if (session('editProfile'))
                            <div class="alert alert-warning alert-dismissible fade show mt-2" role="alert">
                                {{ session('editProfile') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="text-center image-container my-5">
                            @if ($dataUser->gambar === '')
                                <img id="profile-image" src="{{ url('/storage/logo/logo.png') }}" alt=""
                                    style="border-radius: 50%; width: 200px; height: 200px; object-fit: cover; cursor: pointer;">
                                <input id="gambar-input" class="form-control " type="file" name="gambar"
                                    accept="image/*" style="display: none;" onchange="previewGambar()">
                            @else
                                <img id="profile-image" src="{{ url('/storage/user/' . $dataUser->gambar) }}" alt=""
                                    style="border: 10px solid #ebebeb ; border-radius: 50%; width: 200px; height: 200px; object-fit: cover; cursor: pointer;">
                                <input id="gambar-input" class="form-control " type="file" name="gambar"
                                    accept="image/*" style="display: none;" onchange="previewGambar()">
                            @endif

                            <div class="text-center mt-2">
                                <button type="button" onclick="ubahGambar()" class="btn btn-outline-primary">Ubah
                                    Photo</button>
                            </div>
                        </div>
                        {{-- <input class="form-control mb-2" type="file" name="gambar"
                                            placeholder="Nama ... " aria-label="default input example"> --}}



                        <input class="form-control mb-2" type="text" name="name" value="{{ $dataUser->name }}"
                            placeholder="Nama ... " aria-label="default input example">
                        <input class="form-control mb-2" type="email" name="email" value="{{ $dataUser->email }}"
                            placeholder="Email ... " aria-label="default input example">
                        <input class="form-control mb-2" type="number" name="nomor" value="{{ $dataUser->nomor }}"
                            placeholder="Number ... " aria-label="default input example">
                        <select class="form-select mb-2" style="height: 40px !important ; padding-left: 30px"
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
                        <input class=" form-control mb-2" type="text" value="{{ $dataUser->alamat }}" name="alamat"
                            placeholder="Address..">
                        <input class="form-control mb-2" type="date" name="birthday" value="{{ $dataUser->birthday }}"
                            placeholder="birthday ... " aria-label="default input example">
                        <input class="form-control mb-2" type="password" name="password" placeholder="Ganti Password ..."
                            aria-label="default input example">
                        <div class="my-3">
                            <button class="btn btn-primary w-100">
                                Update
                            </button>

                        </div>



                    </form>
                </div>
            </div>
        </div>

    </div>
    <script>
        function ubahGambar() {
            document.getElementById('gambar-input').click();
        }

        function previewGambar() {
            var input = document.getElementById('gambar-input');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-image').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endsection
