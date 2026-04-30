<?php $__env->startSection('content'); ?>
    <div class="main-container container-fluid">

        <!-- PAGE-HEADER -->
        <div class="page-header">
            <h1 class="page-title">Admin</h1>
            <div>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
            </div>
        </div>
        <!-- PAGE-HEADER END -->

        <!-- ROW-1 -->
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xl-12">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xl-4">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="mt-2">
                                        <h6 class="">Total Users</h6>
                                        <h2 class="mb-0 number-font"><?php echo e($countUser); ?></h2>
                                    </div>
                                    <div class="ms-auto">
                                        <div class="chart-wrapper mt-1">
                                            <canvas id="saleschart" class="h-8 w-9 chart-dropshadow"></canvas>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xl-4">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="mt-2">
                                        <h6 class="">Total Omset</h6>
                                        <h2 class="mb-0 number-font">Rp <?php echo e(number_format($transaction, 0, ',', '.')); ?></h2>
                                    </div>
                                    <div class="ms-auto">
                                        <div class="chart-wrapper mt-1">
                                            <canvas id="leadschart" class="h-8 w-9 chart-dropshadow"></canvas>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xl-4">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="mt-2">
                                        <h6 class="">Total Transaction</h6>
                                        <h2 class="mb-0 number-font"><?php echo e(count($totalTransaksi)); ?></h2>
                                    </div>
                                    <div class="ms-auto">
                                        <div class="chart-wrapper mt-1">
                                            <canvas id="profitchart" class="h-8 w-9 chart-dropshadow"></canvas>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <hr>
        <!-- ROW-1 END -->
        <div class="row my-4 d-lg-flex align-items-center">
            <div class="alert alert-info justify-content-between text-center">
                <strong>Data Gender Semua transaksi</strong>
                <div class="d-flex gap-4 justify-content-center">
                   <div class=""> <span class="fw-bold">Persentase Pria:</span> <?php echo e(number_format($dataUser[2], 2)); ?>%</div>
                    <div class=""><span class="fw-bold">Persentase Wanita:</span> <?php echo e(number_format($dataUser[3], 2)); ?>%</div>
                </div>
            </div>
            <div class="col-lg-3 offset my-2">
                <div class="alert alert-primary d-lg-flex  justify-content-between align-items-center">

                    <div>
                        <span class="fw-bold">Pria:</span> <?php echo e($dataUser[0]); ?>

                        <br>
                        <span class="fw-bold">Wanita:</span> <?php echo e($dataUser[1]); ?>

                    </div>

                </div>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $birtday; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $genders): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div class="col-lg-3 my-2">
                    <div class="alert alert-primary justify-content-between align-items-center">
                        <strong><?php echo e($index); ?></strong>
                        <br>
                        <div class="d-flex gap-2">
                            <p>Pria: <?php echo e($genders['pria'] ?? 0); ?></p>
                        <p>Wanita: <?php echo e($genders['wanita'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>
                
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

        </div>
        <hr>
        <!-- ROW-2 -->
        <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sales Analytics Keseluruhan</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex mx-auto text-center justify-content-center mb-4">
                            <div class="d-flex text-center justify-content-center me-3"><span
                                    class="dot-label bg-primary my-auto"></span>Ticket Terjual</div>
                            <div class="d-flex text-center justify-content-center"><span
                                    class="dot-label bg-secondary my-auto"></span>Total Orders</div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chart" class="h-275"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ROW-2 END -->
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/backend/content/dashboard.blade.php ENDPATH**/ ?>