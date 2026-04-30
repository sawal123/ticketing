<?php $__env->startSection('content'); ?>
    
    <!-- End fugu-hero-section -->
    
    <?php echo $__env->make('frontend.page.home.slider', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <!-- End slider section -->
    <?php echo $__env->make('frontend.page.home.menu', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    

<script src="<?php echo e(asset('landing/js/menu/home.js')); ?>"></script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.index', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/frontend/page/home.blade.php ENDPATH**/ ?>