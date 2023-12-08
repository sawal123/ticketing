@extends('backend.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Seo</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Tiket</a></li>
                <li class="breadcrumb-item active" aria-current="page">Seo</li>
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
        @if (session('success'))
            <div class="alert alert-primary">
                {{ session('success') }}
            </div>
        @endif
        <div class="col-xl-4 col-lg-4">
            <div class="row">
                <div class="col">
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
                <div class="col">
                    <div class="card p-0">
                        <div class="card-body p-4">
                            <h5>Setting Icon</h5>
                            <hr>
                            <div class="text-center">
                                <img src="{{ url('storage/logo/' . $logo[0]->icon) }}" alt="" srcset=""
                                    width="100">
                            </div>
                            <form class="mt-3" action="{{ url('admin/editIcon') }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" value="{{ $logo[0]->id }}" name="id">
                                <input class="form-control" type="file" name="icon" id="icon">
                                <button type="submit" class="btn btn-primary w-100 mt-2" type="submit">Update</button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="col">
            <div class="row">
                <div class="col-xl-6 col-lg-6">
                    <div class="card p-0">
                        <div class="card-body p-4">
                            <h5>Setting Meta</h5>
                            <form class="mt-3" action="{{ url('/admin/edit/seoDeskripsi') }}" method="post">
                                @csrf
                                <div class="form-floating">
                                    <input type="hidden" value="{{ $logo[0]->id }}" name="id">
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
                                        editButton.style.display = 'block'
                                        text.setAttribute('readonly', 'true');
                                    });
                                    editButton.addEventListener('click', function() {
                                        myForm.style.display = 'block';
                                        editButton.style.display = 'none'
                                        text.removeAttribute('readonly');
                                    });
                                });
                            </script>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-lg-6">
                    <div class="card p-0">
                        <div class="card-body p-4">
                            <h5>Setting keyword</h5>
                            <form class="mt-3" action="{{ url('admin/edit/seoKeyword') }}" method="post">
                                @csrf
                                <div class="form-floating">
                                    <input type="hidden" value="{{ $logo[0]->id }}" name="id">
                                    <textarea class="form-control textarea" placeholder="Leave a comment here" id="text" name="keyword" readonly>{{ $logo[0]->keyword }}</textarea>
                                    <label for="text">Meta</label>
                                </div>
                                <button type="button" class="btn btn-primary w-100 mt-3" id="editKeyword">Edit</button>
                                <div class="bungkus" id="myForms" style="display: none;">
                                    <button type="submit" class="btn btn-success w-100 mt-2"
                                        type="submit">Simpan</button>
                                    <button type="button" class="btn btn-danger w-100 mt-2" id="batal"
                                        type="submit">Cancel</button>
                                </div>
                            </form>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const editKeyword = document.getElementById('editKeyword');
                                    const batalButton = document.getElementById('batal');
                                    const myForms = document.getElementById('myForms');
                                    const text = document.getElementById('text');
                                    console.log(text)
                                    batalButton.addEventListener('click', function() {
                                        myForms.style.display = 'none';
                                        editKeyword.style.display = 'block'
                                        text.setAttribute('readonly', 'true');
                                    });
                                    editKeyword.addEventListener('click', function() {
                                        myForms.style.display = 'block';
                                        editKeyword.style.display = 'none';
                                        text.removeAttribute('readonly');
                                    });
                                });
                            </script>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    @include('backend.content.contact')
                </div>
            </div>
        </div>
    </div>
@endsection
