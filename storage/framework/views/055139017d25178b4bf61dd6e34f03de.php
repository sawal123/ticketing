

<?php $__env->startSection('content'); ?>
    <div class="page-header">
        <h1 class="page-title">Dashboard Event</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Event</li>
            </ol>
        </div>
    </div>
    <!-- PAGE-HEADER END -->

    <!-- ROW-1 OPEN -->
    <div class="row row-cards">

        <div class="col-xl-12 col-lg-12">
            <div class="row">
                <div class="col-xl-12">
                    <?php echo $__env->make('penyewa.molecul.cardEventSearch', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('hapus')): ?>
                <div class="alert alert-success">
                    <?php echo e(session('hapus')); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div class="tab-content">
                <div class="tab-pane active" id="tab-11">
                    <div class="row">
                        <?php echo $__env->make('penyewa.molecul.cardEvents', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    </div>
                </div>
                <div class="tab-pane" id="tab-12">
                    <div class="row">
                        <div class="col-xl-12 col-lg-12 col-md-12">
                            <div class="card overflow-hidden">
                                <div class="card-body">
                                    <h2>Coming Soon..</h2>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- COL-END -->
        </div>
        <!-- ROW-1 CLOSED -->
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('penyewa.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/page/event.blade.php ENDPATH**/ ?>