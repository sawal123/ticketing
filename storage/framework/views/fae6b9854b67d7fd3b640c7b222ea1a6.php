

<?php $__env->startSection('content'); ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
        <div class="alert alert-success">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <div class="container">
        <div class="row align-items-center justify-content-center">
            <div class="col-xl-6 col-lg-7 col-sm-12 col-12 fxt-bg-color">
                <div class="fxt-content">
                    <div class="fxt-header">
                        <a href="#" class="fxt-logo"><img src="<?php echo e(asset('storage/logo/' . $logo[0]->logo)); ?>"
                                alt="Logo" width="300"></a>
                        <p>Login into your pages account</p>
                    </div>
                    <div class="fxt-form">
                        <form method="POST" action="<?php echo e(route('cekLogin')); ?>">
                            <?php echo csrf_field(); ?>
                            <div class="form-group">
                                <div class="fxt-transformY-50 fxt-transition-delay-1">
                                    <input type="email" id="email" class="form-control" name="email"
                                        placeholder="Email" required="required">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="fxt-transformY-50 fxt-transition-delay-2">
                                    <input id="password" type="password" class="form-control" name="password"
                                        placeholder="********" required="required" autocomplete="password">
                                    <i toggle="#password" class="fa fa-fw fa-eye toggle-password field-icon"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="fxt-transformY-50 fxt-transition-delay-3">
                                    <div class="fxt-checkbox-area">
                                        <div class="checkbox">
                                            <input id="checkbox1" type="checkbox">
                                            <label for="checkbox1">Keep me logged in</label>
                                        </div>
                                        <a href="<?php echo e(url('forgot-password')); ?>" class="switcher-text">Forgot Password</a>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="fxt-transformY-50 fxt-transition-delay-4">
                                    <button type="submit" class="fxt-btn-fill">Log in</button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('penyewa.auth.index', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/auth/login.blade.php ENDPATH**/ ?>