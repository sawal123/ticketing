<!doctype html>
<html lang="en" dir="ltr">

<head>

    <!-- META DATA -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="<?php echo e($seo[0]->keyword); ?>">
    <meta name="description" content="<?php echo e($seo[0]->description); ?>">
    <meta name="author" content="Gotik">
    <!-- FAVICON -->
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo e(asset('storage/logo/' . $logo[0]->icon)); ?>">
    <link rel="stylesheet" href="https://drive.google.com/uc?export=view&id=1yTLwNiCZhIdCWolQldwq4spHQkgZDqkG">

    <title><?php echo e($title); ?></title>
    <?php echo $__env->make('penyewa.partial.link', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>



</head>

<body class="app sidebar-mini ltr light-mode">


    <!-- GLOBAL-LOADER -->
    <div id="global-loader">
        <img src="<?php echo e(asset('assets/images/loader.svg')); ?>" class="loader-img" alt="Loader">
    </div>
    <!-- /GLOBAL-LOADER -->

    <!-- PAGE -->
    <div class="page">
        <div class="page-main">

            <!-- app-Header -->
            <?php echo $__env->make('penyewa.partial.appHeader', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <!-- /app-Header -->

            <!--APP-SIDEBAR-->
            <?php echo $__env->make('penyewa.partial.appSidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <!--/APP-SIDEBAR-->

            <!--app-content open-->
            <div class="main-content app-content mt-0">
                <div class="side-app">

                    <!-- CONTAINER -->
                    <?php echo $__env->yieldContent('content'); ?>
                    <!-- CONTAINER END -->
                </div>
            </div>
            <!--app-content close-->

        </div>



        <!-- FOOTER -->
        <?php echo $__env->make('penyewa.partial.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <!-- FOOTER END -->

        <!-- BACK-TO-TOP -->
        <a href="#top" id="back-to-top"><i class="fa fa-angle-up"></i></a>
        <?php echo $__env->make('penyewa.partial.script', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>



        <?php echo $__env->make('penyewa.partial.myScript', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        
        <script src="<?php echo e(asset('penyewa/js/style.js')); ?>?v=<?php echo e(time()); ?>"></script>
        
        <script src="<?php echo e(asset('penyewa/js/styleMore.js')); ?>?v=<?php echo e(time()); ?>"></script>




</body>

</html><?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/app.blade.php ENDPATH**/ ?>