<div class="modal fade" id="editTransaksi">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Edit Transaksi</h6><button aria-label="Close" class="btn-close" data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{url('admin/editTransaksi')}}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="uid" id="uid" value="">
                    <input type="hidden" name="name" id="name" value="">
                    <input type="hidden" name="inv" id="inv" value="">
                    <div class="row mb-4">
                        <label class="col-md-3 	d-none d-lg-block form-label">Status</label>
                        <div class="col-md-9">
                            <select id="status" class="form-select" name="status"
                                aria-label="Default select example">
                                <option value="SUCCESS">SUCCESS</option>
                                <option value="UNPAID">UNPAID</option>
                                <option value="PENDING">PENDING</option>
                                <option value="CANCELLED">CANCELLED</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Edit</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>