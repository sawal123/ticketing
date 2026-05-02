<div class="modal fade" id="modalStaff">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Tambah Staff Baru</h6>
                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            
            <form action="<?php echo e(route('staff.store')); ?>" method="post">
                <?php echo csrf_field(); ?>
                <div class="modal-body text-start">

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <li><?php echo e($error); ?></li>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Nama Lengkap</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="name"
                                placeholder="Masukkan Nama Staff..." value="<?php echo e(old('name')); ?>" autocomplete="off"
                                required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Email</label>
                        <div class="col-md-9">
                            <input type="email" class="form-control" name="email"
                                placeholder="Masukkan Email Staff..." value="<?php echo e(old('email')); ?>" autocomplete="off"
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
<?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/page/staff/modal-new.blade.php ENDPATH**/ ?>