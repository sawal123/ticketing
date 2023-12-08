

<div class="modal fade" id="modalAdmin">
    <div class="modal-dialog modal-dialog-centered text-start" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Tambah Contact</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ url('admin/addContact') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="uid" id="uid" value="">
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Sosmed :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="sosmed"  placeholder="Masukan sosmed.."
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Nama :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="nama" placeholder="Masukan nama.."
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Link :</label>
                        <div class="col-md-9">
                            <input type="link" class="form-control" name="link"  placeholder="Masukan link.."
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Icon :</label>
                        <div class="col-md-9">
                            <input type="file" class="form-control" name="icon" 
                                required>
                        </div>
                    </div>
                   
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>




{{-- ============================ --}}



    <div class="modal fade" id="upContact">
        <div class="modal-dialog modal-dialog-centered text-start" role="document">
            <div class="modal-content modal-content-demo">
                <div class="modal-header">
                    <h6 class="modal-title">Ubah User</h6><button aria-label="Close" class="btn-close"
                        data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                </div>
                <form action="{{ url('admin/editContact') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="id" value="">
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Sosmed :</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" name="sosmed" id="sosmed" placeholder="Masukan sosmed.."
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Nama :</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" name="nama" id="nama" placeholder="Masukan nama.."
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Link :</label>
                            <div class="col-md-9">
                                <input type="link" class="form-control" name="link" id="link" placeholder="Masukan link.."
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Icon :</label>
                            <div class="col-md-9">
                                <input type="file" class="form-control" name="icon" 
                                    >
                            </div>
                        </div>
                       
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    
    
    
    