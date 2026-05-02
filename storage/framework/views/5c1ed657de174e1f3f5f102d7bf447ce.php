<!doctype html>
<html class="no-js" lang="">

<head>
	<meta charset="utf-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<title>GOTIK | <?php echo e($title); ?></title>
	<meta name="description" content="">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Favicon -->
	<link rel="shortcut icon" href="<?php echo e(asset('storage/logo/' . $logo[0]->icon)); ?>" type="image/x-icon">
	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="<?php echo e(asset('penyewa/css/bootstrap.min.css')); ?>">
	<!-- Fontawesome CSS -->
	<link rel="stylesheet" href="<?php echo e(asset('penyewa/css/fontawesome-all.min.css')); ?>">
	<!-- Flaticon CSS -->
	
	<!-- Google Web Fonts -->
	<link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet">
	<!-- Custom CSS -->
	<link rel="stylesheet" href="<?php echo e(asset('penyewa/style.css')); ?>">
</head>

<body>
	<!--[if lt IE 8]>
        <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->
    <div id="preloader" class="preloader">
        <div class='inner'>
            <div class='line1'></div>
            <div class='line2'></div>
            <div class='line3'></div>
        </div>
    </div>
	<section class="fxt-template-animation fxt-template-layout14" data-bg-image="<?php echo e(asset('penyewa/images/login.jpg')); ?>">
		<?php echo $__env->yieldContent('content'); ?>
	</section>
	<!-- jquery-->
	<script src="<?php echo e(asset('penyewa/js/jquery-3.5.0.min.js')); ?>"></script>
	<!-- Bootstrap js -->
	
	<!-- Imagesloaded js -->
	<script src="<?php echo e(asset('penyewa/js/imagesloaded.pkgd.min.js')); ?>"></script>
	<!-- Validator js -->
	<script src="<?php echo e(asset('penyewa/js/validator.min.js')); ?>"></script>
	<!-- Custom Js -->
	<script src="<?php echo e(asset('penyewa/js/main.js')); ?>"></script>

</body>

</html><?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/auth/index.blade.php ENDPATH**/ ?>