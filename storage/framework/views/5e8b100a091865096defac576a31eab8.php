<?php $__env->startSection('content'); ?>

    <div class=" mt-5">

        <!-- ✅ SATU FORM UNTUK SEMUA -->
        <form action="<?php echo e(url('/profile/update-profile')); ?>" method="post" enctype="multipart/form-data" class="page-wrap">
            <?php echo csrf_field(); ?>



            <!-- LEFT PANEL -->
            <aside class="left-panel">
                <!-- GLOBAL ERROR -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                    <div class="alert alert-danger">
                        <ul style="margin:0;padding-left:18px;">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <li><?php echo e($error); ?></li>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <!-- SUCCESS -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('editProfile')): ?>
                    <div class="alert alert-success">
                        <?php echo e(session('editProfile')); ?>

                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div class="avatar-card">

                    <!-- AVATAR -->
                    <div class="avatar-ring">
                        <svg class="avatar-ring-svg" viewBox="0 0 122 122">
                            <circle cx="61" cy="61" r="57" stroke="url(#ring-grad)" stroke-width="2"
                                stroke-dasharray="8 6" stroke-linecap="round" />
                        </svg>

                        <div class="avatar-img">
                            <img id="preview-image" onclick="document.getElementById('gambar').click()"
                                src="<?php echo e($dataUser->gambar === '' ? url('/storage/logo/logo.png') : url('/storage/user/' . $dataUser->gambar)); ?>"
                                style="width:100%;height:100%;object-fit:cover;border-radius:50%;cursor:pointer;">
                        </div>

                        <!-- 🔥 SATU INPUT GAMBAR -->
                        <input type="file" id="gambar" name="gambar" accept="image/*" style="display:none"
                            onchange="previewGambar(event)">
                    </div>

                    <!-- ERROR GAMBAR -->
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['gambar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <small style="color:red;"><?php echo e($message); ?></small>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <!-- BUTTON -->
                    <button type="button" onclick="document.getElementById('gambar').click()" class="avatar-upload-btn">
                        📷 Ubah Photo
                    </button>

                    <!-- INFO -->
                    <div>
                        <div class="profile-name"><?php echo e($dataUser->name); ?></div>
                        <div class="profile-handle">
                            <?php echo e('@' . strtolower(str_replace(' ', '', $dataUser->name))); ?>

                        </div>

                        <div class="profile-badges">
                            <span class="badge verified">✓ Terverifikasi</span>
                            <span class="badge member">★ Member</span>
                        </div>
                    </div>

                </div>

                <!-- STATS -->
                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-num">12</div>
                        <div class="stat-label">Event</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">7</div>
                        <div class="stat-label">Tiket</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">3</div>
                        <div class="stat-label">Transaksi</div>
                    </div>
                </div>

            </aside>

            <!-- RIGHT PANEL -->
            <div class="right-panel">

                <!-- INFORMASI -->
                <div class="section-card">
                    <div class="card-head">
                        <div class="card-head-icon">✏️</div>
                        <div class="card-head-title">Informasi Pribadi</div>
                    </div>

                    <div class="card-body">
                        <div class="form-grid">

                            <!-- NAMA -->
                            <div class="form-group">
                                <label>Nama</label>
                                <input type="text" name="name" class="form-input"
                                    value="<?php echo e(old('name', $dataUser->name)); ?>">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <small style="color:red;"><?php echo e($message); ?></small>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <!-- GENDER -->
                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <select name="gender" class="form-input">
                                    <option disabled>Choose Gender</option>
                                    <option value="wanita"
                                        <?php echo e(old('gender', $dataUser->gender) == 'wanita' ? 'selected' : ''); ?>>
                                        Wanita
                                    </option>
                                    <option value="pria"
                                        <?php echo e(old('gender', $dataUser->gender) == 'pria' ? 'selected' : ''); ?>>
                                        Pria
                                    </option>
                                </select>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <small style="color:red;"><?php echo e($message); ?></small>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <!-- EMAIL -->
                            <div class="form-group full">
                                <label>Email</label>
                                <input type="email" name="email" class="form-input"
                                    value="<?php echo e(old('email', $dataUser->email)); ?>">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <small style="color:red;"><?php echo e($message); ?></small>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <!-- NOMOR -->
                            <div class="form-group">
                                <label>Nomor</label>
                                <input type="tel" name="nomor" class="form-input"
                                    value="<?php echo e(old('nomor', $dataUser->nomor)); ?>">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['nomor'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <small style="color:red;"><?php echo e($message); ?></small>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <!-- TANGGAL -->
                            <div class="form-group">
                                <label>Tanggal Lahir</label>
                                <input type="date" name="birthday" class="form-input"
                                    value="<?php echo e(old('birthday', $dataUser->birthday)); ?>">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['birthday'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <small style="color:red;"><?php echo e($message); ?></small>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <!-- KOTA -->
                            <div class="form-group full">
                                <label>Provinsi</label>
                                <select name="kota" class="form-input">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $provinsi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $provinsis): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($provinsis['name']); ?>"
                                            <?php echo e(old('kota', $dataUser->kota) == $provinsis['name'] ? 'selected' : ''); ?>>
                                            <?php echo e($provinsis['name']); ?>

                                        </option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['kota'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <small style="color:red;"><?php echo e($message); ?></small>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <!-- ALAMAT -->
                            <div class="form-group full">
                                <label>Alamat</label>
                                <input type="text" name="alamat" class="form-input"
                                    value="<?php echo e(old('alamat', $dataUser->alamat)); ?>">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['alamat'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <small style="color:red;"><?php echo e($message); ?></small>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- PASSWORD -->
                <div class="section-card mt-3">
                    <div class="card-head">
                        <div class="card-head-icon">🔐</div>
                        <div class="card-head-title">Keamanan & Password</div>
                    </div>

                    <div class="card-body">
                        <div class="form-grid">

                            <div class="form-group">
                                <label>Password Baru</label>
                                <input type="password" name="password" class="form-input">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <small style="color:red;"><?php echo e($message); ?></small>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                        </div>

                        <div class="form-actions" style="margin-top:20px;">
                            <button type="reset" class="btn-cancel">Batal</button>
                            <button type="submit" class="btn-save">Simpan Perubahan</button>
                        </div>

                    </div>
                </div>

            </div>

        </form>
    </div>

    <script>
        function previewGambar(event) {
            const preview = document.getElementById('preview-image');
            const file = event.target.files[0];

            if (file) {
                preview.src = URL.createObjectURL(file);
            }
        }
    </script>


<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.index', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/frontend/page/editProfile.blade.php ENDPATH**/ ?>