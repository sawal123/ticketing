

<button type="submit" class="modal-effect btn btn-primary btn-block float-end my-2" data-bs-target="#modalSilde"
    data-bs-effect="effect-sign" data-bs-toggle="modal"><i class="fa fa-plus-square me-2"></i>New Slide</button>

<div class="modal fade" id="modalSilde">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Tambah Slide</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="<?php echo e(url('admin/addSlide')); ?>" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Title :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="title" placeholder="Masukan title.."
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Url :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="url" placeholder="Masukan url.."
                                required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col col-sm-12 mb-4 mb-lg-0">
                            <input type="file" class="dropify" name="gambar" data-bs-height="180">
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



<div class="modal fade" id="updateSlide">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Ubah Slide</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="<?php echo e(url('admin/editSlide')); ?>" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <input type="hidden" name="uid" id="uidSlide">
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Title :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="titleSlide" name="title"
                                placeholder="Masukan title.." required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Url :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="urlSlide" name="url"
                                placeholder="Masukan url.." required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Sort :</label>
                        <div class="col-md-9">
                            <select class="form-select" id="sortSelect" aria-label="Default select example"
                                name="sort">
                                <option disabled>Buat Urutan Slide</option>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $slider; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $slider): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <option value="<?php echo e($key + 1); ?>"><?php echo e($key + 1); ?></option>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col col-sm-12 mb-4 mb-lg-0">
                            <input type="file" class="dropify" name="gambar" data-bs-height="180">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Ubah</button>
                    <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>



<?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/backend/molecul/landing/modalSlide.blade.php ENDPATH**/ ?>