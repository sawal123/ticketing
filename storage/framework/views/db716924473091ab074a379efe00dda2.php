<header class="gap-4">

    <a href="<?php echo e(url('/')); ?>" class="logo pr-5">
        <img src="<?php echo e(asset('storage/logo/' . $logo[0]->logo)); ?>" style="width: 100px" alt="" class="">
    </a>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user): ?>
   

        <div class="nav-search-wrap">
            <span class="nav-search-icon">⌕</span>

            <form action="<?php echo e(url('/cari')); ?>" method="GET" style="display:flex; align-items:center; flex:1; ">
                <input type="text" placeholder="Cari event, konser, festival…" name="cari"
                    value="<?php echo e(old('cari')); ?>">

                <button type="submit" class="btn-search">Search</button>
            </form>


        </div>

        <div class="nav-right">
            
            <div class="nav-avatar-wrap">
                <div class="nav-avatar-btn" id="avatarBtn" onclick="toggleDropdown()">
                    <div class="nav-avatar"> <?php echo e(strtoupper(substr($user->name, 0, 1))); ?></div>
                    <span class="nav-username"><?php echo e($user->name); ?></span>
                    <span class="nav-chevron">▾</span>
                </div>

                <div class="user-dropdown " id="userDropdown">
                    <div class="dropdown-user-info">
                        <div class="dropdown-name"><?php echo e($user->name); ?></div>
                        <div class="dropdown-email"><?php echo e($user->email); ?></div>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()->role === 'penyewa'): ?>
                        <a class="dropdown-item  text-white" href="<?php echo e(url('/dashboard')); ?>">
                            <div class="di-icon " style="background:rgba(108,92,231,0.12);">👤</div>Dashboard
                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()->role === 'admin'): ?>
                        <a class="dropdown-item text-white" href="<?php echo e(url('/admin')); ?>">
                            <div class="di-icon " style="background:rgba(108,92,231,0.12);">👤</div>Dashboard
                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <a href="<?php echo e(url('/profile')); ?>" class="dropdown-item text-white ">
                        <div class="di-icon " style="background:rgba(108,92,231,0.12);">👤</div>Profil Saya
                    </a>
                    <a href="<?php echo e(url('/transaksi')); ?>" class="dropdown-item text-white ">
                        <div class="di-icon " style="background:rgba(61,217,196,0.12);">🛒</div>Transaksi
                    </a>
                    
                    

                    <div class="dropdown-sep"></div>
                    <a href="<?php echo e(url('/logout')); ?>" class="dropdown-item logout">
                        <span class="di-icon" style="background:rgba(232,84,122,0.12);">🚪</span>
                        Keluar
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="nav-search-wrap">
            <span class="nav-search-icon">⌕</span>
            <form action="<?php echo e(url('/cari')); ?>" method="GET" style="display:flex; align-items:center; flex:1;">
                <input type="text" placeholder="Cari event, konser, festival…" name="cari"
                    value="<?php echo e(old('cari')); ?>">

                <button type="submit" class="btn-search">Search</button>
            </form>
        </div>
        <div class="gap-2">
            <a href="<?php echo e(url('/login')); ?>" class="btn-masuk ">
                <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7" />
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.7"
                        stroke-linecap="round" />
                </svg>
               <span class="btn-text">Sign In</span> 
            </a>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>



</header>
<?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/frontend/partial/header-new.blade.php ENDPATH**/ ?>