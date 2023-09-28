
<h4 class="mb-5 mt-3 fw-bold">
    <button type="submit" class="modal-effect btn btn-primary" data-bs-target="#modalHarga"  data-bs-effect="effect-sign"
        data-bs-toggle="modal" >Add Harga</button>
</h4>
<div class="modal fade" id="modalHarga">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Tambah Harga</h6><button aria-label="Close" class="btn-close" data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{url('dashboard/addHarga')}}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="uid" value="{{$eventDetail->uid}}">
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Harga :</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="harga" placeholder="Masukan Harga" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Kategory :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="kategori" placeholder="Masukan Kategori" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Qty Tiket :</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="qty" placeholder="Masukan Quantity Tiket" required>
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


<div class="modal fade" id="updateHarga">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Ubah Harga</h6><button aria-label="Close" class="btn-close" data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{url('admin/editHarga')}}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id" id="idHarga">
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Harga :</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="harga" id="updateHarga" placeholder="Masukan Harga" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Kategory :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="kategori" id="kategoriHarga" placeholder="Masukan Kategori" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Qty Tiket :</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="qty" id="qtyHarga" placeholder="Masukan Quantity Tiket" required>
                        </div>
                    </div>
                  
                  
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Ubah</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>