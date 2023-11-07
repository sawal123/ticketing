<div class="modal fade" id="modalAdmin">
    <div class="modal-dialog modal-dialog-centered text-start" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Tambah Admin</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ url('admin/addAdmin') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="uid" value="">
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Nama :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="nama" placeholder="Masukan nama.."
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Email :</label>
                        <div class="col-md-9">
                            <input type="email" class="form-control" name="email" placeholder="Masukan email.."
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Tanggal Lahir :</label>
                        <div class="col-md-9">
                            <input type="date" class="form-control" name="tanggal" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Kota :</label>
                        <div class="col-md-9">
                            <div class="wrap-input100 validate-input input-group">
                                <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                    <i class="zmdi zmdi-city" aria-hidden="true"></i>
                                </a>
                                <select class="form-select" aria-label="Default select example" required id="kota"
                                    name="kota">
                                    <option selected disabled>Choose City..</option>
                                    @foreach ($provinsi as $provinsi)
                                        <option value="{{ $provinsi['name'] }}">{{ $provinsi['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-md-3 form-label">alamat :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="alamat" placeholder="Masukan alamat.."
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Nomor :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="nomor" placeholder="Masukan nomor.."
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Gender :</label>
                        <div class="col-md-9">
                            <div class="wrap-input100 validate-input input-group">
                                <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                    <i class="zmdi zmdi-local-wc" aria-hidden="true"></i>
                                </a>
                                <select class="form-select" aria-label="Default select example" required name="gender">
                                    <option selected disabled>Choose Gender..</option>
                                    <option value="wanita">Male</option>
                                    <option value="pria">Female</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    @if (request()->is('admin/user/penyewa'))
                        <input type="hidden" name="role" value="penyewa">
                    @else
                        <input type="hidden" name="role" value="admin">
                    @endif


                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Password :</label>
                        <div class="col-md-9">
                            <input type="password" class="form-control" name="password" placeholder="Masukan Password.."
                                required>
                        </div>
                    </div>
                    <div class="row">
                        <label class="col-md-3 form-label">Gambar :</label>
                        <div class="col-md-9">
                            <input type="file" class="form-control" name="gambar">
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
