



<footer  style="display: flex; justify-content: space-evenly; gap: 32px; flex-wrap: wrap;">
    <div class="f-brand">
        <a href="<?php echo e(url('/')); ?>" class="logo">
            <span class="t">
                <img
                    src="<?php echo e(asset('storage/logo/' . $logo[0]->logo)); ?>"
                    style="width: 100px"
                    alt="Logo GoTik"
                >
            </span>
        </a>

        <p>Platform tiket konser dan event terpercaya di Indonesia. Pembayaran online lebih gampang.</p>

        <div style="display: flex; gap: 8px; align-items: center;">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $contact; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <a href="<?php echo e($item->link); ?>" target="_blank" rel="noopener noreferrer">
                    <img
                        width="16"
                        height="16"
                        src="<?php echo e(asset('storage/sosmed/' . $item->icon)); ?>"
                        alt="Sosial media perusahaan"
                    >
                </a>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    </div>

    <div style="display: flex; gap: 40px; flex-wrap: wrap; ">
        <div class="f-col">
            <h5>Platform</h5>
            <a href="<?php echo e(url('/')); ?>">Beranda</a>
            <a href="#">Event Terbaru</a>
            <a href="#">Semua Event</a>
            <a href="#">Kategori</a>
        </div>

        <div class="f-col">
            <h5>Bantuan</h5>
            <a href="#">Coming Soon</a>
            
            <a href="<?php echo e(url('/term')); ?>">Syarat & Ketentuan</a>
            
        </div>
    </div>
</footer>

<div class="footer-bottom">
    <span>&copy; 2026 GoTik. All rights reserved.</span>
    <span>Made with love in Indonesia</span>
</div>
<?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/frontend/partial/footer.blade.php ENDPATH**/ ?>