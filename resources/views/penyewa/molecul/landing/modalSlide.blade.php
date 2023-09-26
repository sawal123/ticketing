{{-- <button type="submit" class="modal-effect btn btn-primary" data-bs-target="#modalSilde" data-bs-effect="effect-sign"
    data-bs-toggle="modal">Add Talent</button> --}}

<button type="submit" class="modal-effect btn btn-primary btn-block float-end my-2" data-bs-target="#modalSilde"
    data-bs-effect="effect-sign" data-bs-toggle="modal"><i class="fa fa-plus-square me-2"></i>New Slide</button>

<div class="modal fade" id="modalSilde">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Tambah Slide</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ url('admin/addSlide') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    {{-- <input type="hidden" name="uid" value="{{ $eventDetail->uid }}"> --}}
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Title :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="title" placeholder="Masukan title.."
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Url :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="url" placeholder="Masukan url.."
                                required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col col-sm-12 mb-4 mb-lg-0">
                            <input type="file" class="dropify" name="gambar" data-bs-height="180">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>



<div class="modal fade" id="updateSlide">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Ubah Slide</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ url('admin/editSlide') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="uid" id="uidSlide">
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Title :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="titleSlide" name="title"
                                placeholder="Masukan title.." required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Url :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="urlSlide" name="url"
                                placeholder="Masukan url.." required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Sort :</label>
                        <div class="col-md-9">
                            <select class="form-select" id="sortSelect" aria-label="Default select example"
                                name="sort">
                                <option disabled>Buat Urutan Slide</option>
                                @foreach ($slider as $key => $slider)
                                    <option value="{{ $key + 1 }}">{{ $key + 1 }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col col-sm-12 mb-4 mb-lg-0">
                            <input type="file" class="dropify" name="gambar" data-bs-height="180">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Ubah</button>
                    <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="modalTerm">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Tambah Term</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ url('admin/addTerm') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Title :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="title" placeholder="Masukan title.."
                                required>
                        </div>
                    </div>
                    <div class="row">
                        <label class="col-md-3 form-label mb-4">Term :</label>
                        <div class="col-md-9 mb-4">
                            <div class="form-floating">
                                <textarea id="summernote" name="term" required>
                                    </textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Tambah</button>
                    <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="updateTerm">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Update Term</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ url('admin/editTerm') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="uid" id="termUid">
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Title :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="termTitle" name="title" placeholder="Masukan title.."
                                required>
                        </div>
                    </div>
                    <div class="row">
                        <label class="col-md-3 form-label mb-4">Term :</label>
                        <div class="col-md-9 mb-4">
                            <div class="form-floating">
                                <textarea class="summernote" name="term" id="termTerm" required>
                                    
                                    </textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
