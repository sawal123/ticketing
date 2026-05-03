
<?php $__env->startSection('content'); ?>
<link rel="stylesheet" href="<?php echo e(asset('landing/css/list-transaksi.css')); ?>">

    <div class="" style="height: 150px; "></div>
    <!-- PAGE -->

    <div class="page-wraping">

        <!-- HEADER -->
        <div class="page-header">
            <div>
                <div class="page-eyebrow">Akun · Riwayat</div>
                <h1 class="page-title">Riwayat Transaksi</h1>
            </div>
            <div class="header-meta">
                <div class="count-pill">
                    <strong id="txCount"><?php echo e(count($transaksi)); ?></strong> Transaksi
                </div>
            </div>
        </div>

        <!-- FILTER -->
        <div class="filter-bar">
            <span class="filter-label">Filter</span>
            <div class="filter-chips">
                <button class="chip all active" onclick="filterTx('all', this)">Semua</button>
                <button class="chip paid" onclick="filterTx('paid', this)">Lunas</button>
                <button class="chip unpaid" onclick="filterTx('unpaid', this)">Belum Bayar</button>
                <button class="chip pending" onclick="filterTx('pending', this)">Pending</button>
            </div>
        </div>

        <!-- TRANSACTION LIST -->
        <div class="tx-list" id="txList">

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $transaksi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div class="tx-card" data-status="<?php echo e(strtolower($item->status)); ?>">
                    <div class="tx-card-inner">

                        <!-- THUMB -->
                        <div class="tx-thumb">
                            <img src="<?php echo e(asset('storage/cover/' . $item->cover)); ?>"
                                style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
                        </div>

                        <!-- INFO -->
                        <div class="tx-info">
                            <div class="tx-top">
                                <span class="tx-event-name"><?php echo e($item->event_name ?? 'Event Tidak Diketahui'); ?></span>

                                
                                <span class="tx-status 
                <?php echo e(strtolower($item->status)); ?>">
                                    <?php echo e(ucfirst(strtolower($item->status))); ?>

                                </span>
                            </div>

                            <div class="tx-meta">
                                <div class="tx-meta-item">
                                    <span class="tx-meta-label">Invoice</span>
                                    <span class="tx-meta-value invoice"><?php echo e($item->invoice); ?></span>
                                </div>

                                <div class="tx-meta-item">
                                    <span class="tx-meta-label">Tanggal</span>
                                    <span class="tx-meta-value">
                                        <?php echo e(\Carbon\Carbon::parse($item->created_at)->locale('id')->isoFormat('dddd, D MMMM Y')); ?>

                                    </span>
                                </div>

                                <div class="tx-meta-item">
                                    <span class="tx-meta-label">Qty</span>
                                    <span class="tx-meta-value">
                                        <?php echo e($item->total_quantity); ?> Tiket
                                    </span>
                                </div>

                                <div class="tx-meta-item">
                                    <span class="tx-meta-label">Total</span>
                                    <span class="tx-meta-value total">
                                        Rp <?php echo e(number_format($item->total_harga ?? 0)); ?>

                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- ACTION -->
                        <div class="tx-actions">

                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->status === 'UNPAID'): ?>
                                <a href="<?php echo e(url('/payment/' . $item->uid)); ?>" class="btn-pay">
                                    Bayar
                                </a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            
                            <a href="<?php echo e(url('/detail-ticket/' . $item->uid . '/' . Auth::user()->uid)); ?>"
                                class="btn-detail">
                                Detail
                            </a>

                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->status === 'UNPAID' || $item->status === 'CANCELLED'): ?>
                                <a href="javascript:void(0)"
                                    onclick="confirmDelete('<?php echo e(url('/detail-ticket/delete/' . $item->uid . '/' . Auth::user()->uid)); ?>')"
                                    class="btn-delete">
                                    Hapus
                                </a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        </div>

                    </div>
                </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <div class="empty-state" id="emptyState">
                    <div class="empty-icon">🎫</div>
                    <div class="empty-title">Tidak ada transaksi</div>
                    <div class="empty-sub">Belum ada transaksi yang tersedia.</div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        </div>

    </div>

    <script>
        // ================= DROPDOWN =================




        // ================= FILTER =================
        let currentFilter = 'all';

        function filterTx(status, el) {
            currentFilter = status;

            // update active chip
            document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
            if (el) el.classList.add('active');

            applyFilter();
        }

        function applyFilter() {
            const cards = document.querySelectorAll('.tx-card');
            let visible = 0;

            cards.forEach(card => {
                const cardStatus = card.dataset.status;

                const show = currentFilter === 'all' || cardStatus === currentFilter;

                if (show) {
                    card.style.display = '';
                    visible++;
                } else {
                    card.style.display = 'none';
                }
            });

            // update counter
            const counter = document.getElementById('txCount');
            if (counter) counter.textContent = visible;

            // empty state
            const empty = document.getElementById('emptyState');
            if (empty) {
                empty.style.display = visible === 0 ? 'flex' : 'none';
            }
        }


        // ================= DELETE =================
        function confirmDelete(url) {
            Swal.fire({
                title: 'Hapus Transaksi?',
                text: "Data transaksi yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff4d4d',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#1a1625',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            })
        }


        // ================= INIT =================
        document.addEventListener('DOMContentLoaded', function() {
            applyFilter(); // initial load

            // Session Success Handler
            <?php if(session('deleteList') || session('delete') || session('success')): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: "<?php echo e(session('deleteList') ?? (session('delete') ?? session('success'))); ?>",
                    showConfirmButton: false,
                    timer: 2000,
                    background: '#1a1625',
                    color: '#ffffff'
                });
            <?php endif; ?>
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.index', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/frontend/page/transaksi/list-transaksi-new.blade.php ENDPATH**/ ?>