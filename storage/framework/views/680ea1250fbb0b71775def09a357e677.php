<?php $__env->startSection('auth'); ?>
    <div class="page">
        <div class="">
            <!-- Theme-Layout -->
            <!-- CONTAINER OPEN -->
           

            <!-- CONTAINER OPEN -->
            <div class="container-login100">
                <div class="wrap-login100 p-6">
                    <form class="login100-form validate-form" action="<?php echo e(url('/email')); ?>" method="post">
                        <span class="login100-form-title pb-5">
                            Forgot Password
                        </span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
                            <div class="alert alert-primary">
                                <?php echo e(session('success')); ?>

                            </div>
                        <?php elseif(session('error')): ?>
                            <div class="alert alert-danger">
                                <?php echo e(session('error')); ?>

                            </div>
                        <?php else: ?>
                            <p class="text-muted">Enter the email address registered on your account</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


                        <?php echo csrf_field(); ?>
                        <div class="wrap-input100 validate-input input-group"
                            data-bs-validate="Valid email is required: ex@abc.xyz">
                            <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                <i class="zmdi zmdi-email" aria-hidden="true"></i>
                            </a>
                            <input class="input100 border-start-0 ms-0 form-control" name="email" type="email"
                                placeholder="Email" required>
                        </div>
                        <div class="submit w-100">
                            <button class="btn btn-primary w-100" type="submit">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.authIndex', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/frontend/page/auth/forgot-password.blade.php ENDPATH**/ ?>