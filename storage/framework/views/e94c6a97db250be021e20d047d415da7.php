

<?php $__env->startSection('content'); ?>
    <div class="page-header">
        <h1 class="page-title">Dashboard Staff</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Staff</li>
            </ol>
        </div>
    </div>
    <!-- PAGE-HEADER END -->

    <!-- ROW-1 OPEN -->
    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header " style="display: inline">

                    <div class="row">
                        <div class="col">
                            <div class="d-flex justify-content-between align-items-center w-full">
                                <h3 class="card-title">File Export</h3>
                                <button type="submit" class="btn btn-primary  my-2" data-bs-target="#modalStaff"
                                    data-bs-effect="effect-sign" data-bs-toggle="modal"><i
                                        class="fa fa-plus-square me-2"></i>Tambah Staff</button>
                                <?php echo $__env->make('penyewa.page.staff.modal-new', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                                <script>
                                    document.addEventListener("DOMContentLoaded", function() {
                                        // Asumsi kamu menggunakan Bootstrap 5
                                        var myModal = new bootstrap.Modal(document.getElementById('modalStaff'));
                                        myModal.show();
                                    });
                                </script>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                    </div>

                </div>



                <div class="card-body">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
                        <div class="alert alert-primary">
                            <?php echo e(session('success')); ?>

                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="table-responsive">
                        <table id="tableStaff" class="table table-bordered text-nowrap key-buttons border-bottom">
                            <thead>
                                <tr>
                                    <th class="border-bottom-0" style="width: 5%">No</th>
                                    <th class="border-bottom-0">Nama Staff</th>
                                    <th class="border-bottom-0">Email</th>
                                    <th class="border-bottom-0">No. HP</th>
                                    <th class="border-bottom-0 text-center">Status</th>
                                    <th class="border-bottom-0 text-center" style="width: 15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $staffs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $staff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <tr>
                                        <td><?php echo e($key + 1); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo e(asset('storage/' . $staff->gambar)); ?>" alt="img"
                                                    class="avatar avatar-sm me-2 rounded-circle"
                                                    onerror="this.src='https://ui-avatars.com/api/?name=<?php echo e(urlencode($staff->name)); ?>&background=random'">
                                                <span class="fw-bold"><?php echo e($staff->name); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo e($staff->email); ?></td>
                                        <td><?php echo e($staff->nomor !== '-' ? $staff->nomor : 'Belum diisi'); ?></td>
                                        <td class="text-center">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($staff->email_verified_at): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pending (Belum Verifikasi)</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-primary btn-edit-staff"
                                                data-bs-toggle="modal" data-bs-target="#modalEditStaff"
                                                data-uid="<?php echo e($staff->uid); ?>" data-name="<?php echo e($staff->name); ?>"
                                                data-email="<?php echo e($staff->email); ?>"
                                                data-url="<?php echo e(route('staff.update', $staff->uid)); ?>"> 
                                                <i class="fa fa-edit"></i> Edit
                                            </button>

                                            <a href="<?php echo e(url('dashboard/staff/delete/' . $staff->uid)); ?>" class="delete">
                                                <button type="button" class="btn btn-sm btn-danger" title="Hapus Staff">
                                                    <i class="fa fa-trash text-white"></i> Hapus
                                                </button>
                                            </a>
                                        </td>
                                    </tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Belum ada data staff. Silakan tambahkan staff baru.
                                        </td>
                                    </tr>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </tbody>
                        </table>
                        <?php echo $__env->make('penyewa.page.staff.modal-update', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('penyewa.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/page/staff.blade.php ENDPATH**/ ?>