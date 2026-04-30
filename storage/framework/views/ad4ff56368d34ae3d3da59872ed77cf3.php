<style>
    .custom-toggle-btn {
        appearance: none;
        -webkit-appearance: none;
        width: 44px;
        height: 24px;
        background-color: #cbd5e1;
        border-radius: 20px;
        position: relative;
        cursor: pointer;
        outline: none;
        margin: 0;
        transition: background-color 0.3s;
    }

    .custom-toggle-btn:checked {
        background-color: #5c6fff;
    }

    .custom-toggle-btn::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 20px;
        height: 20px;
        background-color: white;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        transition: transform 0.3s;
    }

    .custom-toggle-btn:checked::after {
        transform: translateX(20px);
    }

    /* Tambahan CSS Khusus untuk Adaptive Card Background */
    .harga-card-bg {
        background-color: #f1f4f8;
        /* Default Light Mode */
    }

    /* Jika template Anda menggunakan atribut data-theme="dark" atau class .dark-mode di tag body/html */
    [data-theme="dark"] .harga-card-bg,
    body.dark-mode .harga-card-bg,
    .dark-theme .harga-card-bg {
        background-color: #1e293b !important;
        /* Warna gelap lembut untuk background card */
        border: 1px solid #334155 !important;
        /* Border tipis agar lebih elegan */
    }
</style>

<div class="row w-100 mx-0">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($harga->count()): ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $harga; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4 px-2">

                
                <div class="card h-100 border-0 harga-card-bg" style="border-radius: 12px;">
                    <div class="card-body p-4 d-flex flex-column">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            
                            <h6 class="mb-0" style="font-weight: 500; font-size: 1rem; padding-right: 10px;">
                                <?php echo e(strtoupper($h->kategori)); ?>

                            </h6>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($h->status) && $h->status === 'active'): ?>
                                <span class="badge"
                                    style="background-color: #5c6fff; padding: 5px 10px; border-radius: 6px; font-weight: 500; color: white;">Active</span>
                            <?php else: ?>
                                
                                <span class="badge"
                                    style="background-color: #64748b; padding: 5px 10px; border-radius: 6px; font-weight: 500; color: white;">Inactive</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        
                        <h4 class="mb-4" style="font-weight: 700;">
                            Rp <?php echo e(number_format($h->harga, 0, ',', '.')); ?>

                        </h4>

                        <?php
                            $terjual = $terjualPerHarga[$h->id] ?? 0;
                        ?>

                        
                        <div class="d-flex mb-4" style="font-size: 0.85rem;">
                            <span class="me-4 opacity-75">
                                Qty Tiket : <strong><?php echo e($h->qty); ?></strong>
                            </span>
                            <span class="opacity-75">
                                Terjual : <strong><?php echo e($terjual); ?></strong>
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-auto">

                            <form action="<?php echo e(url("dashboard/hargas/toggle-status/{$h->id}")); ?>" method="POST"
                                class="toggle-form m-0">
                                <?php echo csrf_field(); ?>
                                <input class="custom-toggle-btn" type="checkbox" onchange="this.form.submit()"
                                    <?php if(isset($h->status) && $h->status === 'active'): echo 'checked'; endif; ?>>
                            </form>

                            <div class="d-flex" style="gap: 8px;">
                                <a href="<?php echo e(url("dashboard/hargas/delete/{$h->id}")); ?>"
                                    class="btn d-flex align-items-center justify-content-center p-0"
                                    style="width: 38px; height: 38px; background-color: #ff1a55; border-radius: 8px; border: none;">
                                    <i class="fa fa-trash text-white"></i>
                                </a>

                                <button type="button" data-bs-toggle="modal" data-bs-target="#updateHarga"
                                    class="btn d-flex align-items-center justify-content-center p-0"
                                    style="width: 38px; height: 38px; background-color: #5c6fff; border-radius: 8px; border: none;"
                                    data-kategori="<?php echo e($h->kategori); ?>" data-qty="<?php echo e($h->qty); ?>" data-harga="<?php echo e($h->harga); ?>"
                                    data-id="<?php echo e($h->id); ?>">
                                    <i class="fa fa-edit text-white"></i>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    <?php else: ?>
        <div class="col-12 px-2">
            <p class="text-muted mt-2">Tidak ada harga...</p>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>


<script>
    document.addEventListener("DOMContentLoaded", function (event) {
        let scrollpos = sessionStorage.getItem('scrollpos');
        if (scrollpos) {
            window.scrollTo(0, scrollpos);
            sessionStorage.removeItem('scrollpos');
        }
    });

    window.addEventListener("beforeunload", function (e) {
        sessionStorage.setItem('scrollpos', window.scrollY);
    });
</script><?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/molecul/cardHarga.blade.php ENDPATH**/ ?>