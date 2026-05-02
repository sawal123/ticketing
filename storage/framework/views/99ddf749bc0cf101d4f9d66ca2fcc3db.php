<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun Staff | Gotik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- STYLE CSS -->
    <link href="<?php echo e(asset('/assets/css/style.css')); ?>" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }

        .verify-container {
            max-width: 600px;
            margin: 50px auto;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #0d6efd;
            color: white;
            text-align: center;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
    </style>
</head>

<body>

    <div class="container verify-container">
        <div class="card">
            <div class="card-header d-block">
                <h4 class="mb-0">Selamat Datang, <?php echo e($staff->name); ?>!</h4>
                <p class="mb-0 text-light"><small>Satu langkah lagi untuk mengaktifkan akun Staff Anda.</small></p>
            </div>
            <div class="card-body p-4">

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <li><?php echo e($error); ?></li>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <form action="<?php echo e(url('/staff/complete-profile/' . $staff->uid)); ?>" method="POST">
                    <?php echo csrf_field(); ?>

                    <h5 class="mb-3 border-bottom pb-2">1. Atur Keamanan</h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control" name="password" required minlength="8"
                                placeholder="Minimal 8 karakter">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ulangi Password</label>
                            <input type="password" class="form-control" name="password_confirmation" required
                                minlength="8" placeholder="Ketik ulang password">
                        </div>
                    </div>

                    <h5 class="mb-3 mt-4 border-bottom pb-2">2. Lengkapi Data Diri</h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor WhatsApp / HP</label>
                            <input type="text" class="form-control" name="nomor" value="<?php echo e(old('nomor')); ?>"
                                required placeholder="Contoh: 08123456789">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" name="birthday" value="<?php echo e(old('birthday')); ?>"
                                required>
                        </div>
                    </div>



                    <div class="mb-4">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea class="form-control" name="alamat" rows="2" required placeholder="Masukkan alamat lengkap"><?php echo e(old('alamat')); ?></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Simpan & Aktifkan Akun</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.css"></script>
</body>

</html>
<?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/page/verify.blade.php ENDPATH**/ ?>