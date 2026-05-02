<div class="sm:mx-auto sm:w-full sm:max-w-md">
    <!-- Logo & Title -->
    <div class="flex justify-center mb-6">
        <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-200">
            <i data-lucide="zap" class="w-10 h-10 text-white"></i>
        </div>
    </div>
    <h2 class="text-center text-3xl font-extrabold text-slate-900 tracking-tight">
        Aktivasi Akun Staff
    </h2>
    <p class="mt-2 text-center text-sm text-slate-500">
        Selamat datang, <span class="font-bold text-indigo-600"><?php echo e($staff->name); ?></span>. Lengkapi profil Anda untuk mulai menggunakan dashboard.
    </p>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow-2xl shadow-slate-200/50 sm:rounded-3xl sm:px-10 border border-slate-100">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$isSuccess): ?>
                <form wire:submit.prevent="save" class="space-y-6">
                    
                    <!-- Section 1: Security -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Atur Keamanan</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label for="password" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Password Baru</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="key" class="w-4 h-4"></i>
                                    </div>
                                    <input id="password" wire:model="password" type="password" required
                                        class="block w-full pl-10 pr-4 py-3 bg-slate-50 border-0 ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 placeholder-slate-400 transition-all duration-200"
                                        placeholder="••••••••">
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-rose-500 ml-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Konfirmasi Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                                    </div>
                                    <input id="password_confirmation" wire:model="password_confirmation" type="password" required
                                        class="block w-full pl-10 pr-4 py-3 bg-slate-50 border-0 ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 placeholder-slate-400 transition-all duration-200"
                                        placeholder="••••••••">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="h-px bg-slate-100 my-6"></div>

                    <!-- Section 2: Personal Data -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                                <i data-lucide="user" class="w-4 h-4"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Lengkapi Data Diri</h3>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="nomor" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">WhatsApp / HP</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="phone" class="w-4 h-4"></i>
                                    </div>
                                    <input id="nomor" wire:model="nomor" type="text" required
                                        class="block w-full pl-10 pr-4 py-3 bg-slate-50 border-0 ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 placeholder-slate-400 transition-all duration-200"
                                        placeholder="0812...">
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['nomor'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-rose-500 ml-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <div>
                                <label for="birthday" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Tanggal Lahir</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="calendar" class="w-4 h-4"></i>
                                    </div>
                                    <input id="birthday" wire:model="birthday" type="date" required
                                        class="block w-full pl-10 pr-4 py-3 bg-slate-50 border-0 ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 placeholder-slate-400 transition-all duration-200">
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['birthday'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-rose-500 ml-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <label for="alamat" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Alamat Lengkap</label>
                            <div class="relative">
                                <div class="absolute top-3 left-3 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="map-pin" class="w-4 h-4"></i>
                                </div>
                                <textarea id="alamat" wire:model="alamat" rows="3" required
                                    class="block w-full pl-10 pr-4 py-3 bg-slate-50 border-0 ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 placeholder-slate-400 transition-all duration-200"
                                    placeholder="Masukkan alamat lengkap Anda..."></textarea>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['alamat'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-rose-500 ml-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full flex justify-center py-4 px-4 border border-transparent rounded-2xl shadow-lg text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]">
                            <span wire:loading.remove>Simpan & Aktifkan Akun</span>
                            <span wire:loading class="flex items-center gap-2">
                                <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center py-6">
                    <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="party-popper" class="w-10 h-10"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-2">Selamat!</h3>
                    <p class="text-slate-500 mb-8 leading-relaxed">
                        Akun staff Anda berhasil diaktifkan. Sekarang Anda dapat masuk ke dashboard menggunakan email dan password yang baru saja Anda buat.
                    </p>
                    <a href="https://go-tik.com/signin" 
                        class="inline-flex items-center justify-center w-full py-4 px-6 border border-transparent rounded-2xl shadow-lg text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]">
                        Masuk ke Dashboard
                        <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                    </a>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        </div>
    </div>
</div>
<?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/livewire/auth/staff-verify.blade.php ENDPATH**/ ?>