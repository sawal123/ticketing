<h4 class="mb-5 mt-3 fw-bold">
    <button type="submit" class="modal-effect btn btn-primary" data-bs-target="#modaldemo8" data-bs-effect="effect-sign"
        data-bs-toggle="modal">Add Talent</button>
</h4>
<div class="modal fade" id="modaldemo8">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Tambah Talent</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ url('admin/addTalent') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="uid" value="{{ $eventDetail->uid }}">
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Nama Talent :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="talent" placeholder="Nama Talent"
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

<div class="modal fade" id="updateTalent">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Ubah Talent</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ url('admin/editTalent') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="uid" id="uidTalent">
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Nama Talent :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="talent" id="namaTalent" placeholder="Nama Talent"
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
                    <button type="submit" class="btn btn-primary">Ubah</button>
                    <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
