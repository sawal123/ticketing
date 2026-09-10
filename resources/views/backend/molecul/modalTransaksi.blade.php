<div class="modal fade" id="editTransaksi">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Konfirmasi Transaksi</h6><button aria-label="Close" class="btn-close" data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ route('old.transactions.settle') }}" method="post">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="uid" id="uid" value="">
                    <input type="hidden" name="name" id="name" value="">
                    <input type="hidden" name="inv" id="inv" value="">
                    <p>Konfirmasi pembayaran akan menjalankan lifecycle settlement dan mengubah transaksi PENDING menjadi SUCCESS.</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Konfirmasi Pembayaran</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
