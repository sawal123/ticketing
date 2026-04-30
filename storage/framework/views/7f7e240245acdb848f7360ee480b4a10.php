<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="<?php echo e($seo[0]->keyword); ?>">
    <meta name="description" content="<?php echo e($seo[0]->description); ?>">
    <title>GOTIK <?php echo e($title); ?></title>
    <link rel="shortcut icon" href="<?php echo e(asset('storage/logo/' . $logo[0]->icon)); ?>" type="image/x-icon">

    
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Public+Sans:wght@500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- End google font  -->
    

      <link rel="stylesheet" href="<?php echo e(asset('landing/css/bootstrap.min.css')); ?>" />
    <link rel="stylesheet" href="<?php echo e(asset('assets/plugins/sweetalert2/dist/sweetalert2.min.css')); ?>">
    <link href="<?php echo e(asset('/assets/css/icons.css')); ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('landing/css/main-new.css')); ?>" />
    <style>
        a {
            text-decoration: none;
        }
    </style>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>

<body>



    <?php echo $__env->make('frontend.partial.header-new', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <!--End landex-header-section -->

    <?php echo $__env->yieldContent('content'); ?>

    


    <?php echo $__env->make('frontend.partial.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <!-- scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>

    <script src="<?php echo e(asset('landing/js/jquery-3.6.0.min.js')); ?>"></script>
   <script src="<?php echo e(asset('landing/js/wow.min.js')); ?>"></script>
    <script src="<?php echo e(asset('landing/js/countdown.js')); ?>"></script>
    <script src="https://maps.googleapis.com/maps/api/js?v=3&key=AIzaSyArZVfNvjnLNwJZlLJKuOiWHZ6vtQzzb1Y"></script>

    <script src="<?php echo e(asset('assets/plugins/sweetalert2/dist/sweetalert2.min.js')); ?>"></script>

    <script src="<?php echo e(asset('landing/js/menu/togle.js')); ?>"></script>
    <!-- <script src="<?php echo e(asset('landing/js/app.js')); ?>"></script> -->
    






    
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>

</html>
<?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/frontend/index.blade.php ENDPATH**/ ?>