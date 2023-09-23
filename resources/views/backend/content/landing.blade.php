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

            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="card">
                        <div class="container mt-2">
                            @if (session('deleteTerm'))
                                <div class="alert alert-danger">
                                    {{ session('deleteTerm') }}
                                </div>
                            @endif
                            @if (session('addTerm'))
                                <div class="alert alert-primary">
                                    {{ session('addTerm') }}
                                </div>
                            @endif
                            @if (session('editTerm'))
                                <div class="alert alert-primary">
                                    {{ session('editTerm') }}
                                </div>
                            @endif
                        </div>
                        <div class="card-header d-flex justify-content-between">

                            <h3 class="card-title">Term and Condition</h3>
                            <button type="submit" class="modal-effect btn btn-primary " data-bs-target="#modalTerm"
                                data-bs-effect="effect-sign" data-bs-toggle="modal"><i
                                    class="fa fa-plus-square me-2"></i>New Term</button>

                        </div>


                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="file-datatable"
                                    class="table table-bordered text-nowrap key-buttons border-bottom">
                                    <thead>
                                        <tr>
                                            <th class="border-bottom-0">No</th>
                                            <th class="border-bottom-0">title</th>
                                            <th class="border-bottom-0">action</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($term as $key => $terms)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $terms->title }}</td>
                                                {{-- <td>{{ strlen($terms->term) > 10 ? substr($terms->term, 0, 11) . '...' : $terms->term }} --}}
                                                </td>
                                                <td>
                                                    <div class="g-2">

                                                        <button type="submit" class="modal-effect btn btn-primary "
                                                            data-uid="{{ $terms->uid }}"
                                                            data-title="{{ $terms->title }}"
                                                            data-term='{!! $terms->term !!}'
                                                            data-bs-target="#updateTerm" data-bs-effect="effect-sign"
                                                            data-bs-toggle="modal"><span
                                                                class="fe fe-edit fs-14"></span></button>
                                                        <a class="delete btn btn-danger"
                                                            href="{{ url('/admin/term/delete/' . $terms->uid) }}"
                                                            data-bs-toggle="tooltip" data-bs-original-title="Delete"><span
                                                                class="fe fe-trash-2 fs-14"></span></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ROW-1 CLOSED -->
    </div>
@endsection
