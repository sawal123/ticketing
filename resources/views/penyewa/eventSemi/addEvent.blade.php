@extends('penyewa.app')

@section('content')
    <div class="main-container container-fluid">

        <!-- PAGE-HEADER -->
        <div class="page-header">
            @if (request()->is('dashboard/event/addEvent'))
                <a href="{{ url('/dashboard/event/' . $ubahEvent->uid) }}" class="btn btn-primary"><i class="fa fa-arrow-left"></i>
                    Kembali</a>
            @else
                <a href="{{ url('/dashboard/event/eventDetail/' . $ubahEvent->uid) }}" class="btn btn-primary"><i
                        class="fa fa-arrow-left"></i> Kembali</a>
            @endif

            @if (request()->is('dashboard/event/addEvent') === true)
                <h1 class="page-title">Tambah Event</h1>
            @else
                <h1 class="page-title">Ubah Event</h1>
            @endif
            <div>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Tiket / Event</a></li>

                    @if (request()->is('dashboard/event/addEvent') === true)
                        <li class="breadcrumb-item active" aria-current="page">
                            Add Event
                        </li>
                    @else
                        <li class="breadcrumb-item active" aria-current="page">
                            Edit Event
                        </li>
                    @endif

                </ol>
            </div>
        </div>
        <!-- PAGE-HEADER END -->

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-success">
                {{ session('error') }}
            </div>
        @endif

        <!-- ROW-1 OPEN -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        @if (request()->is('dashboard/event/addEvent') === true)
                            <div class="card-title">Add New Event</div>
                        @else
                            <div class="card-title">Ubah Event</div>
                        @endif

                    </div>
                    @if (request()->is('dashboard/event/addEvent'))
                        <form action="{{ url('/dashboard/addEvents') }}" method="post" enctype="multipart/form-data">
                        @else
                            <form action="{{ url('/dashboard/editEvent') }}" method="post" enctype="multipart/form-data">
                    @endif

                    @csrf
                    <div class="card-body">
                        <div class="row mb-4">
                            <input type="hidden" name="uid" value="{{ $ubahEvent->uid }}">
                            <label class="col-md-3 form-label">Nama Event :</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" value="{{ $ubahEvent->event }}" name="event"
                                    placeholder="Nama Event" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Biaya Layanan :</label>
                            <div class="col-md-9">
                                <input type="number" value="{{ $ubahEvent->fee }}" name="fee" class="form-control"
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Alamat :</label>
                            <div class="col-md-9">
                                <input type="text" value="{{ $ubahEvent->alamat }}" name="alamat" class="form-control"
                                    required>
                            </div>
                        </div>
                        @if (request()->is('dashboard/event/addEvent') === false)
                            <div class="row mb-4">
                                <label class="col-md-3 form-label">Status Event :</label>
                                <div class="col-md-9">
                                    <select class="form-select" aria-label="Default select example" name="status">
                                        {{-- <option selected>Pilih...</option> --}}
                                        <option value="close" {{ $ubahEvent->status == 'selesai' ? 'selected' : '' }}>
                                            Close
                                        </option>
                                        <option value="active" {{ $ubahEvent->status == 'active' ? 'selected' : '' }}>
                                            Active
                                        </option>

                                    </select>
                                </div>
                            </div>
                        @endif

                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Tanggal Event :</label>
                            <div class="col-md-9">
                                <input type="datetime-local" value="{{ $ubahEvent->tanggal }}" name="tanggal"
                                    class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Link Map :</label>
                            <div class="col-md-9">
                                <input type="text" value="{{ $ubahEvent->map }}" name="map" class="form-control"
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Cover Thumbnail :</label>
                            <div class="col-md-9">
                                <input type="file" name="cover" class="form-control">
                            </div>
                        </div>

                        <!-- Row -->
                        <div class="row">
                            <label class="col-md-3 form-label mb-4">Deskripsi Event :</label>
                            <div class="col-md-9 mb-4">
                                <div class="form-floating">
                                    <textarea id="summernote" name="deskripsi" required>
                                        {{ $ubahEvent->deskripsi }}
                                        </textarea>

                                </div>
                            </div>
                        </div>
                        <!--End Row-->

                        <!--Row-->
                        {{-- <div class="row">
                            <label class="col-md-3 form-label mb-4">Product Upload :</label>
                            <div class="col-md-9">
                                <input id="demo" type="file" class="form-control" name="files" accept=".jpg, .png, image/jpeg, image/png" multiple>
                            </div>
                        </div> --}}
                        <!--End Row-->
                    </div>
                    <div class="card-footer">
                        <!--Row-->
                        <div class="row">
                            <div class="col-md-3"></div>
                            <div class="col-md-9">
                                @if (request()->is('dashboard/event/addEvent'))
                                    <button type="submit" class="btn btn-primary">Add Event</button>
                                @else
                                    <button type="submit" class="btn btn-danger">Ubah Event</button>
                                @endif


                            </div>
                        </div>
                        <!--End Row-->
                    </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /ROW-1 CLOSED -->
    </div>
@endsection
