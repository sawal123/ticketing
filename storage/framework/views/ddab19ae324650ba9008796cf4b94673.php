

<?php $__env->startSection('content'); ?>
    <div class="main-container container-fluid">

        <!-- PAGE-HEADER -->
        <div class="page-header">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request()->is('dashboard/event/addEvent')): ?>
                <a href="<?php echo e(url('/dashboard/event/' . $ubahEvent->uid)); ?>" class="btn btn-primary"><i
                        class="fa fa-arrow-left"></i>
                    Kembali</a>
            <?php else: ?>
                <a href="<?php echo e(url('/dashboard/event/eventDetail/' . $ubahEvent->uid)); ?>" class="btn btn-primary"><i
                        class="fa fa-arrow-left"></i> Kembali</a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request()->is('dashboard/event/addEvent') === true): ?>
                <h1 class="page-title">Tambah Event</h1>
            <?php else: ?>
                <h1 class="page-title">Ubah Event</h1>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Tiket / Event</a></li>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request()->is('dashboard/event/addEvent') === true): ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            Add Event
                        </li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            Edit Event
                        </li>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </ol>
            </div>
        </div>
        <!-- PAGE-HEADER END -->

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
            <div class="alert alert-success">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
            <div class="alert alert-success">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <!-- ROW-1 OPEN -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request()->is('dashboard/event/addEvent') === true): ?>
                            <div class="card-title">Add New Event</div>
                        <?php else: ?>
                            <div class="card-title">Ubah Event</div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request()->is('dashboard/event/addEvent')): ?>
                        <form action="<?php echo e(url('/dashboard/addEvents')); ?>" method="post" enctype="multipart/form-data">
                        <?php else: ?>
                            <form action="<?php echo e(url('/dashboard/editEventPenyewa')); ?>" method="post"
                                enctype="multipart/form-data">
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php echo csrf_field(); ?>
                    <div class="card-body">
                        <div class="row mb-4">
                            <input type="hidden" name="uid" value="<?php echo e($ubahEvent->uid); ?>">
                            <label class="col-md-3 form-label">Nama Event :</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" value="<?php echo e($ubahEvent->event); ?>" name="event"
                                    placeholder="Nama Event" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Pajak Event (%) :</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <input type="number" name="fee" class="form-control" placeholder="Contoh: 10"
                                        
                                        value="<?php echo e(request()->is('dashboard/event/addEvent') ? '10' : $ubahEvent->fee); ?>"
                                        min="0" max="100" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Besaran pajak yang akan ditambahkan ke total transaksi
                                    pembeli.</small>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Alamat :</label>
                            <div class="col-md-9">
                                <input type="text" value="<?php echo e($ubahEvent->alamat); ?>" name="alamat" class="form-control"
                                    required>
                            </div>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request()->is('dashboard/event/addEvent') === false): ?>
                            <div class="row mb-4">
                                <label class="col-md-3 form-label">Status Event :</label>
                                <div class="col-md-9">
                                    <select class="form-select" aria-label="Default select example" name="status">
                                        <option value="close" <?php echo e($ubahEvent->status == 'selesai' ? 'selected' : ''); ?>>
                                            Close
                                        </option>
                                        <option value="active" <?php echo e($ubahEvent->status == 'active' ? 'selected' : ''); ?>>
                                            Active
                                        </option>

                                    </select>
                                </div>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Tanggal Event :</label>
                            <div class="col-md-9">
                                <input type="datetime-local" value="<?php echo e($ubahEvent->tanggal); ?>" name="tanggal"
                                    class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-3">
                                <div class="row mb-4">
                                </div>
                            </div>
                            <div class="col-lg-9">
                                <div class="row mb-4">
                                    <div class="col-6">
                                        <label class="form-label">Start Event :</label>
                                        <input type="datetime-local" value="<?php echo e($ubahEvent->start); ?>" name="start"
                                            class="form-control" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">End Event :</label>
                                        <input type="datetime-local" value="<?php echo e($ubahEvent->end); ?>" name="end"
                                            class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Link Map :</label>
                            <div class="col-md-9">
                                <input type="text" value="<?php echo e($ubahEvent->map); ?>" name="map" class="form-control"
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Cover Thumbnail :</label>
                            <div class="col-md-9">
                                <input type="file" name="cover" class="form-control">
                            </div>
                        </div>

                        <!-- Row -->
                        <div class="row">
                            <label class="col-md-3 form-label mb-4">Deskripsi Event :</label>
                            <div class="col-md-9 mb-4">
                                <div class="form-floating">
                                    <textarea id="summernote" name="deskripsi" required>
                                        <?php echo e($ubahEvent->deskripsi); ?>

                                    </textarea>

                                </div>
                            </div>
                        </div>
                        <!--End Row-->

                        <!--Row-->
                        
                        <!--End Row-->
                    </div>
                    <div class="card-footer">
                        <!--Row-->
                        <div class="row">
                            <div class="col-md-3"></div>
                            <div class="col-md-9">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request()->is('dashboard/event/addEvent')): ?>
                                    <button type="submit" class="btn btn-primary">Add Event</button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-danger">Ubah Event</button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


                            </div>
                        </div>
                        <!--End Row-->
                    </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /ROW-1 CLOSED -->
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('penyewa.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/eventSemi/addEvent.blade.php ENDPATH**/ ?>