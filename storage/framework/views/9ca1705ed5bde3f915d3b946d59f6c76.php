<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $slide; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slides): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
    <div class="col-md-6 col-xl-4 col-sm-6">
        <div class="card">
            <div class="product-grid6">
                <div class="product-image6 p-5">
                    <ul class="icons">
                        <li>
                            <button type="submit" class="btn btn-primary" data-bs-target="#updateSlide"
                                data-bs-effect="effect-sign" data-bs-toggle="modal" data-uid="<?php echo e($slides->uid); ?>"
                                data-title="<?php echo e($slides->title); ?>" data-sort="<?php echo e($slides->sort); ?>"
                                data-url="<?php echo e($slides->url); ?>">
                                <i class="fe fe-edit"> </i> </button>
                        </li>
                        <li>
                            <a href="<?php echo e(url('/admin/landing/delete/' . $slides->uid)); ?>" class="delete btn btn-danger">
                                <i class="fe fe-trash"> </i> </a>
                        </li>
                        
                    </ul>
                    <a href="#">
                        <img class="img-fluid br-7 w-100" style="object-fit: cover; width: 1920px"
                            src="<?php echo e(asset('storage/slide/' . $slides->gambar)); ?>" alt="img">
                    </a>
                </div>
                <div class="card-body pt-0">
                    <div class="product-content text-center">

                        <h1 class="title fw-bold fs-20"><a href="<?php echo e(url($slides->url)); ?>"><?php echo e($slides->title); ?></a>
                        </h1>
                        <div class="btn btn-success"><?php echo e($slides->sort); ?></div>


                    </div>
                </div>

            </div>
        </div>
    </div>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
<?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/backend/molecul/landing/cardSlide.blade.php ENDPATH**/ ?>