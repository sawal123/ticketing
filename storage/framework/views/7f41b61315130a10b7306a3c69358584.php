<div class="sm:mx-auto sm:w-full sm:max-w-md p-2">
    <div class="flex justify-center mb-8">
        <a href="/" wire:navigate>
             <img src="<?php echo e(asset('storage/logo/' . ($logo[0]->logo ?? ''))); ?>" alt="Logo" class="h-16 w-auto object-contain">
        </a>
    </div>
    
    <div class="bg-[#16152a] border border-white/5 py-10 px-6 shadow-2xl rounded-[2.5rem] sm:px-10 relative overflow-hidden">
        <!-- Glow Effect -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-600/10 blur-[80px] rounded-full"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-purple-600/10 blur-[80px] rounded-full"></div>

        <div class="relative">
            <h2 class="text-3xl font-extrabold text-white text-center mb-2 tracking-tight">Lupa Password?</h2>
            <p class="text-slate-400 text-center text-sm mb-10">Masukkan email Anda untuk menerima tautan pemulihan kata sandi.</p>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has('error')): ?>
                <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-medium flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has('success')): ?>
                <div class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-medium flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <form wire:submit.prevent="submit" class="space-y-6">
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i data-lucide="mail" class="h-4 w-4 text-slate-500"></i>
                        </div>
                        <input wire:model="email" id="email" type="email" required class="block w-full pl-11 pr-4 py-4 bg-[#1e1d35] border-0 ring-1 ring-white/5 focus:ring-2 focus:ring-indigo-500 rounded-2xl text-white text-sm placeholder-slate-500 transition-all duration-200" placeholder="nama@email.com">
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-xs text-rose-500 ml-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-4 px-4 border border-transparent rounded-2xl shadow-lg text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 transform active:scale-[0.98] shadow-indigo-500/20">
                        <span wire:loading.remove>Kirim Tautan Reset</span>
                        <span wire:loading class="flex items-center gap-2">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                            Memproses...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <p class="mt-10 text-center text-sm text-slate-400">
        Kembali ke
        <a href="/login" wire:navigate class="font-bold text-indigo-400 hover:text-indigo-300 transition-colors ml-1">Halaman Login</a>
    </p>
</div>
<?php /**PATH F:\PROJECT\GOTIK\TiketKonser\resources\views/livewire/auth/forgot-password.blade.php ENDPATH**/ ?>