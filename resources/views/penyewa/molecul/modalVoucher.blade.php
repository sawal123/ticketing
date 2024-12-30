<h4 class="mb-5 mt-3 fw-bold">
    <button type="submit" class="modal-effect btn btn-primary" data-bs-target="#modalVoucher" data-bs-effect="effect-sign"
        data-bs-toggle="modal">Add Voucher</button>
</h4>
<div class="modal fade" id="modalVoucher">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Tambah Voucher</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ url('dashboard/addVoucher') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    {{-- <input type="hidden" name="uid" value="{{$eventDetail->uid}}"> --}}
                    <div class="row mb-4">
                        <label class="col-md-3 form-label d-flex justify-content-start">Code Voucher :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="code" placeholder="Masukan Code"
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label d-flex justify-content-start">Type :</label>
                        <div class="col-md-9">
                            <div class="custom-controls-stacked d-flex align-items-center mt-2">
                                <label class="custom-control mx-2 custom-radio">
                                    <input type="radio" class="custom-control-input" name="unit" value="rupiah"
                                        checked onclick="toggleInput()">
                                    <span class="custom-control-label">Rupiah</span>
                                </label>
                                <label class="custom-control mx-2 custom-radio">
                                    <input type="radio" class="custom-control-input" name="unit" value="persen"
                                        onclick="toggleInput()">
                                    <span class="custom-control-label">Persen</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label d-flex justify-content-start">Discount</label>
                        <div class="col-md-9">
                            <!-- Input Rupiah -->
                            <div class="input-group" id="input-rupiah">
                                <span class="input-group-text" id="basic-addon1">Rp</span>
                                <input type="number" class="form-control" placeholder="100000" name="nominalRupiah"
                                    aria-label="nominal" aria-describedby="basic-addon1">
                            </div>
                            <!-- Input Persen -->
                            <div class="input-group" id="input-persen" style="display: none;">
                                <input type="number" class="form-control" placeholder="20" name="nominalPersen"
                                    aria-describedby="basic-addon2">
                                <span class="input-group-text" id="basic-addon2">%</span>
                            </div>
                        </div>
                    </div>
                    <script>
                        function toggleInput() {
                            const isRupiahChecked = document.querySelector('input[name="unit"]:checked').value === "rupiah";
                            document.getElementById('input-rupiah').style.display = isRupiahChecked ? 'flex' : 'none';
                            document.getElementById('input-persen').style.display = isRupiahChecked ? 'none' : 'flex';
                        }

                        // Call toggleInput on page load to set initial state
                        toggleInput();
                    </script>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label d-flex justify-content-start">Min Beli</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="min" placeholder="Rp 100.000" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label d-flex justify-content-start">Max Discount</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="max" placeholder="Maksimal Discount"
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label d-flex justify-content-start">Max Digunakan</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="maxUse" placeholder="Maksimal Digunakan"
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



<div class="modal fade" id="updateVoucher">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Update Voucher</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ url('dashboard/updateVoucher') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div class="row mb-4">
                        <label class="col-md-3 form-label d-flex justify-content-start">Code Voucher :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="code" id="codeV"
                                placeholder="Masukan Code" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label d-flex justify-content-start">Type :</label>
                        <div class="col-md-9">
                            <div class="custom-controls-stacked d-flex align-items-center mt-2">
                                <label class="custom-control mx-2 custom-radio">
                                    <input type="radio" class="custom-control-input" name="unit" id="URupiah"
                                        value="rupiah" checked onclick="toggleNom()">
                                    <span class="custom-control-label">Rupiah</span>
                                </label>
                                <label class="custom-control mx-2 custom-radio">
                                    <input type="radio" class="custom-control-input" name="unit" id="UPersen"
                                        value="persen" onclick="toggleNom()">
                                    <span class="custom-control-label">Persen</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label d-flex justify-content-start">Discount</label>
                        <div class="col-md-9">
                            <!-- Input Rupiah -->
                            <div class="input-group" id="inputrupiah">
                                <span class="input-group-text" id="basic-addon1">Rp</span>
                                <input type="number" class="form-control" placeholder="100000" id="nominalRupiah"
                                    name="nominalRupiah" aria-label="nominal" aria-describedby="basic-addon1">
                            </div>
                            <!-- Input Persen -->
                            <div class="input-group" id="inputpersen" style="display: none;">
                                <input type="number" class="form-control" placeholder="20" id="nominalPersen"
                                    name="nominalPersen" aria-describedby="basic-addon2">
                                <span class="input-group-text" id="basic-addon2">%</span>
                            </div>
                        </div>
                    </div>
                   
                    <div class="row mb-4">
                        <label class="col-md-3 form-label d-flex justify-content-start">Min Beli :</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="min" id="minV"
                                placeholder="Minimal Beli" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label d-flex justify-content-start">Max Disc :</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="max" id="maxV"
                                placeholder="Maksimal Discount" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label d-flex justify-content-start">Max Use :</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="maxUse" id="maxUseV"
                                placeholder="Maksimal Digunakan" required>
                        </div>
                    </div>


                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
