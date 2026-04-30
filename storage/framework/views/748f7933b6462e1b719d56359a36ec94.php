

<?php $__env->startSection('content'); ?>
    <div class="page-header">
        <h1 class="page-title">Dashboard Voucher</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Vopucher</li>
            </ol>
        </div>
    </div>
    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h3 class="card-title">List Voucher</h3>
                    <?php echo $__env->make('penyewa.molecul.modalVoucher', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
                <div class="mx-5 my-5">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('vError')): ?>
                        <div class="alert alert-danger">
                            <?php echo e(session('vError')); ?>

                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('voucher')): ?>
                        <div class="alert alert-success">
                            <?php echo e(session('voucher')); ?>

                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('deleteVoucher')): ?>
                        <div class="alert alert-danger">
                            <?php echo e(session('deleteVoucher')); ?>

                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="table-responsive">
                        <table id="tableVoucher"
                            class="table table-hover table-bordered text-nowrap key-buttons border-bottom">
                            <thead>
                                <tr>
                                    <th class="border-bottom-0">No</th>
                                    <th class="border-bottom-0">Code</th>
                                    <th class="border-bottom-0">Unit</th>
                                    <th class="border-bottom-0" style="width: 10%">Nominal</th>
                                    <th class="border-bottom-0">Min Beli</th>
                                    <th class="border-bottom-0">Max Disc</th>
                                    <th class="border-bottom-0">Digunakan</th>
                                    <th class="border-bottom-0">Maks Digunakan</th>
                                    <th class="border-bottom-0">Status</th>
                                    <th class="border-bottom-0">Action</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider">

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $voucher; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <tr>
                                        <td><?php echo e($key += 1); ?></td>
                                        <td><?php echo e($v->code); ?></td>
                                        <td><?php echo e($v->unit); ?></td>
                                        <td>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($v->unit === 'rupiah'): ?>
                                                Rp <?php echo e(number_format($v->nominal, 0, ',', '.')); ?>

                                            <?php else: ?>
                                                <?php echo e(number_format($v->nominal, 0, ',', '.')); ?>%
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                        </td>
                                        <td>Rp <?php echo e(number_format($v->min_beli, 0, ',', '.')); ?></td>
                                        <td>
                                            Rp <?php echo e(number_format($v->max_disc, 0, ',', '.')); ?>


                                        </td>
                                        <td><?php echo e($v->digunakan); ?></td>
                                        <td><?php echo e($v->limit); ?></td>
                                        
                                        <td>
                                            <div class="mt-sm-1 d-block">
                                                <span
                                                    class="badge bg-success-transparent rounded-pill text-success p-2 px-3"><?php echo e($v->status); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="g-2">
                                                <a type="button" class="btn text-primary btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#updateVoucher"
                                                    data-id="<?php echo e($v->id); ?>"
                                                    data-code="<?php echo e($v ->code); ?>"
                                                    data-unit="<?php echo e($v->unit); ?>" data-nominal="<?php echo e($v->nominal); ?>"
                                                    data-minbeli="<?php echo e($v->min_beli); ?>" data-maxdisc="<?php echo e($v->max_disc); ?>"
                                                    data-limit="<?php echo e($v->limit); ?>"
                                                     data-event="<?php echo e($v->event_uid); ?>"
                                                    data-bs-original-title="Edit"><span
                                                        class="fe fe-edit fs-14"></span></a>

                                                <a class="btn text-danger btn-sm delete"
                                                    href="<?php echo e(url('dashboard/delete/voucher/' . $v->uid)); ?>"
                                                    data-bs-toggle="tooltip" data-bs-original-title="Delete"><span
                                                        class="fe fe-trash-2 fs-14"></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('penyewa.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/page/voucher.blade.php ENDPATH**/ ?>