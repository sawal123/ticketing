<!-- ══ EVENTS ══ -->
<section class="events-wrap">
    <div class="section-top reveal">
        <div>
            <div class="section-label">Event Terbaru</div>
            <h2>Temukan Acaramu</h2>
            <p>Temukan acara favorit Anda, dan mari bersenang-senang</p>
        </div>
        <a href="/search" class="btn-viewall">
            View All Events
            <svg viewBox="0 0 16 16" fill="none">
                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </a>
    </div>

    <div class="event-grid">

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <a href="<?php echo e(url('/ticket/' . $event->slug)); ?>" class="event-card <?php echo e($key == 0 ? 'featured' : ''); ?> reveal"
                style="transition-delay:<?php echo e(0.05 + $key * 0.05); ?>s">

                <div class="card-img">
                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($event->cover): ?>
                        <img src="<?php echo e(asset('storage/cover/' . $event->cover)); ?>" class="img-bg" alt="cover"
                            style="object-fit:cover; height: 100% !important; ">
                    <?php else: ?>
                        <div class="img-bg bg-<?php echo e(($key % 3) + 1); ?>"></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <div class="img-glow"></div>

                    
                    <span class="card-status <?php echo e($event->status === 'close' ? 'status-close' : 'status-open'); ?>">
                        <?php echo e($event->status === 'close' ? 'Close' : 'On Sale'); ?>

                    </span>

                    
                    <div class="card-loc">
                        <svg viewBox="0 0 16 16" fill="none">
                            <path
                                d="M8 1.5C5.5 1.5 3.5 3.5 3.5 6c0 3.5 4.5 8.5 4.5 8.5S12.5 9.5 12.5 6c0-2.5-2-4.5-4.5-4.5z"
                                stroke="currentColor" stroke-width="1.4" />
                            <circle cx="8" cy="6" r="1.5" stroke="currentColor" stroke-width="1.4" />
                        </svg>
                        <?php echo e(implode(' ', array_slice(explode(' ', $event->alamat), 0, 3)) . '...'); ?>

                    </div>
                </div>

                <div class="card-body">
                    <div class="card-title"><?php echo e($event->event); ?></div>

                    <div class="card-meta">
                        <div class="meta-row">
                            <svg viewBox="0 0 16 16" fill="none">
                                <path
                                    d="M8 1.5C5.5 1.5 3.5 3.5 3.5 6c0 3.5 4.5 8.5 4.5 8.5S12.5 9.5 12.5 6c0-2.5-2-4.5-4.5-4.5z"
                                    stroke="currentColor" stroke-width="1.4" />
                                <circle cx="8" cy="6" r="1.5" stroke="currentColor"
                                    stroke-width="1.4" />
                            </svg>
                            <?php echo e(implode(' ', array_slice(explode(' ', $event->alamat), 0, 3)) . '...'); ?>

                        </div>

                        <div class="meta-row">
                            <svg viewBox="0 0 16 16" fill="none">
                                <rect x="2" y="3" width="12" height="11" rx="2" stroke="currentColor"
                                    stroke-width="1.3" />
                                <path d="M5 1.5v3M11 1.5v3M2 7h12" stroke="currentColor" stroke-width="1.3"
                                    stroke-linecap="round" />
                            </svg>
                            <?php echo e(date('Y-m-d H:i', strtotime($event->tanggal))); ?>

                        </div>
                    </div>

                    <div class="card-footer">
                        <div>
                            <div class="price-from">Start From</div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($event->harga): ?>
                                <div class="price-val">
                                    Rp <?php echo e(number_format($event->harga->harga, 0, ',', '.')); ?>

                                </div>
                            <?php else: ?>
                                <div class="price-na">Ticket Belum Tersedia</div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <div class="card-by">By: <?php echo e($event->user->name ?? 'Unknown'); ?></div>
                        </div>

                        <span class="btn-beli <?php echo e($event->status === 'close' ? 'disabled' : ''); ?>">
                            <?php echo e($event->status === 'close' ? 'Close' : 'Beli'); ?>

                            <svg viewBox="0 0 16 16" fill="none">
                                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                </div>

            </a>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

    </div>

    <div class="events-footer reveal">
        <a href="/search" class="btn-view-all-big">
            View All Events
            <svg viewBox="0 0 16 16" fill="none">
                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </a>
    </div>
</section>
<?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/frontend/page/home/menu.blade.php ENDPATH**/ ?>