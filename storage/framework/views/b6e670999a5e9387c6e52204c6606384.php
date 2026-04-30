<div class="row">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($talent) > 0): ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $talent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $talents): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <div class="col col-xl-3 col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="product-grid6">
                        <div class="product-image6 p-2">

                            <img class="img-fluid br-7 " style="height: 150px; object-fit: cover"
                                src="<?php echo e(asset('storage/talent/' . $talents->gambar)); ?>" alt="img">
                        </div>
                        <div class="card-body pt-0">
                            <div class="product-content text-center">
                                
                                <div class="price"><?php echo e($talents->talent); ?></span>
                                </div>
                            </div>
                            <div class="btn-list mt-4 d-flex justify-content-center">
                                <button type="submit" class="btn ripple btn-primary me-2" data-bs-target="#updateTalent"
                                    data-bs-effect="effect-sign" data-bs-toggle="modal" data-uid="<?php echo e($talents->id); ?>" data-talent="<?php echo e($talents->talent); ?>"><i class="fe fe-edit"> </i>
                                </button>
                                <a href="<?php echo e(url('dashboard/delete/'.$talents->uid)); ?>" class="delete btn ripple btn-danger"><i class="fe fe-trash"> </i></a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    <?php else: ?>
        <p>Tidak Ada Talent ...</p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

</div>
<?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/molecul/cardTalent.blade.php ENDPATH**/ ?>