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
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
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