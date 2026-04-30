

<?php $__env->startSection('content'); ?>
    <div class="page-header">
        <h1 class="page-title">Transaksi Online <?php echo e($event->event); ?></h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Transaksi</li>
            </ol>
        </div>
    </div>
    <!-- PAGE-HEADER END -->
    <div class="row row-sm">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="mt-2">
                            <h6 class="">Total Uang Ticket Yang Terjual</h6>
                            <h2 class="mb-0 number-font">Rp <?php echo e(number_format($totalPenjualan, 0, ',', '.')); ?></h2>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="mt-2">
                            <h6 class="">Total Ticket Yang Terjual</h6>
                            <h2 class="mb-0 number-font"><?php echo e(number_format($totalFee, 0, ',', '.')); ?> Ticket</h2>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
    <!-- ROW-1 OPEN -->
    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card">
                <form action="<?php echo e(url('dashboard/transaksi')); ?>" method="get">
                     <!-- Tidak diperlukan untuk GET request -->

                    <div class="card-header d-flex justify-content-between">
                        <h3 class="card-title">File Export</h3>
                        <div class="input-group w-md w-25">
                            <input type="date" class="form-control" name="filter" value="<?php echo e(request('filter')); ?>">
                            <input type="hidden" name="uid" value="<?php echo e($uid); ?>">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </div>
                </form>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableTransPenyewa"
                            class="table table-hover table-bordered text-nowrap key-buttons border-bottom">
                            <thead>
                                <tr>
                                    <th class="border-bottom-0">No</th>
                                    <th class="border-bottom-0">Invoice</th>
                                    <th class="border-bottom-0" style="width: 10%">Event</th>
                                    <th class="border-bottom-0">Tanggal</th>
                                    <th class="border-bottom-0">Name</th>
                                    <th class="border-bottom-0">Qty</th>
                                    <th class="border-bottom-0">Jmlh</th>
                                    <th class="border-bottom-0">Disc</th>
                                    <th class="border-bottom-0">Total</th>
                                    <th class="border-bottom-0">Voucher</th>
                                    <th class="border-bottom-0">Payment</th>
                                    <th class="border-bottom-0">Status</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $cart; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $carts): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <tr>
                                        <td><?php echo e($key + 1); ?></td>
                                        <td><?php echo e($carts->invoice); ?></td>
                                        <td><?php echo e(strlen($carts->event) > 10 ? substr($carts->event, 0, 15) . '...' : $carts->event); ?>

                                        </td>
                                        <td><?php echo e(date('d M Y H:i', strtotime($carts->created_at))); ?></td>

                                        <td><?php echo e($carts->user_name); ?></td>

                                        <td>
                                            <a class="modal-effect btn btn-primary-light d-grid mb-3 btn-show-ticket"
                                                data-bs-effect="effect-scale" data-bs-toggle="modal"
                                                href="#modalDetailTicketGlobal"
                                                data-tiket="<?php echo e(json_encode($qtyTiketGrouped[$carts->uid] ?? [])); ?>">
                                                <?php echo e($carts->total_quantity); ?> Ticket
                                            </a>
                                        </td>

                                        <td>Rp <?php echo e(number_format($carts->subtotal_harga, 0, ',', '.')); ?></td>

                                        <td>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($carts->unit === 'rupiah'): ?>
                                                Rp <?php echo e(number_format($carts->voucher_disc, 0, ',', '.')); ?>

                                            <?php elseif($carts->unit === 'persen'): ?>
                                                <?php echo e($carts->voucher_disc); ?>%
                                            <?php else: ?>
                                                -
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>

                                        <td class="fw-bold text-primary">Rp
                                            <?php echo e(number_format($carts->final_amount, 0, ',', '.')); ?></td>

                                        <td><?php echo e($carts->voucher ?: '-'); ?></td>
                                        <td><span
                                                class="badge bg-primary-transparent rounded-pill text-primary p-2 px-3"><?php echo e(strtoupper($carts->payment_type)); ?></span>
                                        </td>
                                        <td><span
                                                class="badge bg-success-transparent rounded-pill text-success p-2 px-3"><?php echo e($carts->status); ?></span>
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

    <!-- Modal Global untuk Detail Ticket -->
    <div class="modal fade" id="modalDetailTicketGlobal">
        <div class="modal-dialog modal-dialog-centered text-center" role="document">
            <div class="modal-content modal-content-demo">
                <div class="modal-header">
                    <h6 class="modal-title">Detail Ticket</h6>
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalGlobalTicketBody">
                    <!-- Data injeksi via Javascript -->
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Event delegation untuk menangkap klik meskipun datatable di-paginate / render ulang
            document.body.addEventListener('click', function(e) {
                let target = e.target.closest('.btn-show-ticket');
                if (target) {
                    let tiketData = JSON.parse(target.getAttribute('data-tiket'));
                    let html = '';
                    if (tiketData && tiketData.length > 0) {
                        tiketData.forEach(function(item) {
                            html += '<div class="d-flex justify-content-between">';
                            html += '<p>' + item.kategori + '</p>';
                            html += '<p>x ' + item.qty + '</p>';
                            html += '</div>';
                        });
                    } else {
                        html = '<p>Data tidak ditemukan</p>';
                    }
                    document.getElementById('modalGlobalTicketBody').innerHTML = html;
                }
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('penyewa.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/page/transaksi.blade.php ENDPATH**/ ?>