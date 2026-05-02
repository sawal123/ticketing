<div class="modal fade" id="modalPartner">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Tambah Partner</h6>
                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <form action="<?php echo e(url('dashboard/addPartner')); ?>" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <input type="hidden" name="referensi" value="<?php echo e(Auth::user()->uid); ?>">
                    <div class="row mb-4">
                        <label class="col-md-3 form-label  d-flex justify-content-start">Nama :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="name" placeholder="Masukan nama.."
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label  d-flex justify-content-start">Email :</label>
                        <div class="col-md-9">
                            <input type="email" class="form-control" name="email" placeholder="Masukan email.."
                                required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-md-3 form-label  d-flex justify-content-start">Kota :</label>
                        <div class="col-md-9">
                            <div class="wrap-input100 validate-input input-group">
                                <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                    <i class="zmdi zmdi-city" aria-hidden="true"></i>
                                </a>
                                <select class="form-select" aria-label="Default select example" required 
                                    name="city">
                                    <option selected disabled>Choose City..</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $provinsi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $provinsi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($provinsi['name']); ?>"><?php echo e($provinsi['name']); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-md-3 form-label  d-flex justify-content-start">alamat :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="alamat" placeholder="Masukan alamat.."
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label  d-flex justify-content-start">Nomor :</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="nomor" placeholder="Masukan nomor.."
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



<div class="modal fade" id="upPartner">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Edit Partner</h6>
                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <form action="<?php echo e(url('dashboard/editPartner')); ?>" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <input type="hidden" name="uid" id="uid" >
                    <div class="row mb-4">
                        <label class="col-md-3 form-label  d-flex justify-content-start">Nama :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="name" id="name" placeholder="Masukan nama.."
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label  d-flex justify-content-start">Email :</label>
                        <div class="col-md-9">
                            <input type="email" class="form-control" name="email" id="email" placeholder="Masukan email.."
                                required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-md-3 form-label  d-flex justify-content-start">Kota :</label>
                        <div class="col-md-9">
                            <div class="wrap-input100 validate-input input-group">
                                <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                    <i class="zmdi zmdi-city" aria-hidden="true"></i>
                                </a>
                                <select class="form-select" aria-label="Default select example" required id="kota"
                                    name="city">
                                    <option selected disabled>Choose City..</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $prop; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $prop): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($prop['name']); ?>"><?php echo e($prop['name']); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-md-3 form-label  d-flex justify-content-start">alamat :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="alamat" id="alamat" placeholder="Masukan alamat.."
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label  d-flex justify-content-start">Nomor :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="nomor" id="nomor" placeholder="Masukan nomor.."
                                required>
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
<?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/molecul/modalPartner.blade.php ENDPATH**/ ?>