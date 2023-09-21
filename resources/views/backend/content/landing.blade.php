@extends('backend.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Landing</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Tiket</a></li>
                <li class="breadcrumb-item active" aria-current="page">Event</li>
            </ol>
        </div>
    </div>
    <!-- PAGE-HEADER END -->

    <!-- ROW-1 OPEN -->
    <div class="row row-cards">
        @if (session('editLogo'))
            <div class="alert alert-primary">
                {{ session('editLogo') }}
            </div>
        @endif
        @if (session('editSlide'))
            <div class="alert alert-primary">
                {{ session('editSlide') }}
            </div>
        @endif
        <div class="col-xl-3 col-lg-4">
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="card p-0">

                        <div class="card-body p-4">
                            <h5>Setting Logo</h5>
                            <hr>
                            <div class="text-center">
                                <img src="{{ url('storage/logo/' . $logo[0]->logo) }}" alt="" srcset=""
                                    width="100">
                            </div>
                            <form class="mt-3" action="{{ url('admin/editLogo') }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" value="{{ $logo[0]->id }}" name="id">
                                <input class="form-control" type="file" name="logo" id="logo">
                                <button type="submit" class="btn btn-primary w-100 mt-2" type="submit">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="card p-0">

                        <div class="card-body p-4">
                            <h5>Setting Meta</h5>

                            <form class="mt-3" action="" method="post">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Leave a comment here" id="floatingTextarea" name="description" readonly>{{ $logo[0]->description }}</textarea>
                                    <label for="floatingTextarea">Meta</label>
                                </div>
                                <button type="button" class="btn btn-primary w-100 mt-3" id="editButton">Edit</button>
                                <div class="bungkus" id="myForm" style="display: none;">
                                    <button type="submit" class="btn btn-success w-100 mt-2" type="submit">Simpan</button>
                                    <button type="button" class="btn btn-danger w-100 mt-2" id="cancel"
                                        type="submit">Cancel</button>
                                </div>
                            </form>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const editButton = document.getElementById('editButton');
                                    const cancelButton = document.getElementById('cancel');
                                    const myForm = document.getElementById('myForm');
                                    const text = document.querySelector('textarea');

                                    cancelButton.addEventListener('click', function() {
                                        myForm.style.display = 'none';
                                        text.setAttribute('readonly', 'true');
                                    });
                                    // Tambahkan event listener untuk tombol "Edit"
                                    editButton.addEventListener('click', function() {
                                        // Tampilkan formulir saat tombol "Edit" ditekan
                                        myForm.style.display = 'block';
                                        text.removeAttribute('readonly');
                                    });

                                });
                            </script>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- COL-END -->
        <div class="col-xl-9 col-lg-8">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card p-0">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-xl-3 col-lg-12">
                                    @include('backend.molecul.landing.modalSlide')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-content">
                <div class="tab-pane active" id="tab-11">
                    @if (session('addSlide'))
                        <div class="alert alert-success">
                            {{ session('addSlide') }}
                        </div>
                    @endif

                    @if (session('deleteSlide'))
                        <div class="alert alert-success">
                            {{ session('deleteSlide') }}
                        </div>
                    @endif
                    <div class="row">
                        @include('backend.molecul.landing.cardSlide')
                    </div>
                </div>
            </div>
            <!-- COL-END -->
        </div>
        <!-- ROW-1 CLOSED -->
    </div>
@endsection
