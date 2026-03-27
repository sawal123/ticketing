<div class="modal fade" id="modalEditStaff">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Edit Data Staff</h6>
                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {{-- Action dibiarkan kosong sementara, akan diisi oleh jQuery --}}
            <form id="formEditStaff" action="" method="post">
                @csrf
                @method('PUT')

                <div class="modal-body text-start">
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Nama Lengkap</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="name" id="edit_name" required
                                autocomplete="off">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Email</label>
                        <div class="col-md-9">
                            <input type="email" class="form-control" name="email" id="edit_email" required
                                autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
