<div class="modal fade" id="modalStaff">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Tambah Staff Baru</h6>
                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {{-- Action mengarah ke route untuk memproses data staff --}}
            <form action="{{ route('staff.store') }}" method="post">
                @csrf
                <div class="modal-body text-start">

                    {{-- Pesan Error Validasi (Opsional, tapi bagus untuk UX) --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Nama Lengkap</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="name"
                                placeholder="Masukkan Nama Staff..." value="{{ old('name') }}" autocomplete="off"
                                required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Email</label>
                        <div class="col-md-9">
                            <input type="email" class="form-control" name="email"
                                placeholder="Masukkan Email Staff..." value="{{ old('email') }}" autocomplete="off"
                                required>
                            <small class="text-muted d-block mt-1">
                                <i class="fa fa-info-circle"></i> Link verifikasi password akan dikirim ke email ini.
                            </small>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Kirim Undangan</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
