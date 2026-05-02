
<div class="modal fade" id="modalMoney">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Penarikan Dana</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="<?php echo e(url('dashboard/addPenarikan')); ?>" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Amount</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="amount" placeholder="Masukan Jumlah Penarikan.." autocomplete="off"
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


<?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/molecul/modalMoney.blade.php ENDPATH**/ ?>