<div class="sm:mx-auto sm:w-full sm:max-w-md">
    <div class="flex justify-center mb-8">
        <a href="/" wire:navigate>
             <img src="{{ asset('storage/logo/' . ($logo[0]->logo ?? '')) }}" alt="Logo" class="h-16 w-auto object-contain">
        </a>
    </div>
    
    <div class="bg-[#16152a] border border-white/5 py-10 px-6 shadow-2xl rounded-[2.5rem] sm:px-10 relative overflow-hidden">
        <!-- Glow Effect -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-600/10 blur-[80px] rounded-full"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-purple-600/10 blur-[80px] rounded-full"></div>

        <div class="relative">
            <h2 class="text-3xl font-extrabold text-white text-center mb-2 tracking-tight">Atur Ulang Password</h2>
            <p class="text-slate-400 text-center text-sm mb-10">Silakan masukkan password baru Anda di bawah ini.</p>

            @if (session()->has('error'))
                <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-medium flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    {{ session('error') }}
                </div>
            @endif

            <form wire:submit.prevent="resetPassword" class="space-y-6">
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Password Baru</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="h-4 w-4 text-slate-500"></i>
                        </div>
                        <input wire:model="password" id="password" type="password" required class="block w-full pl-11 pr-4 py-4 bg-[#1e1d35] border-0 ring-1 ring-white/5 focus:ring-2 focus:ring-indigo-500 rounded-2xl text-white text-sm placeholder-slate-500 transition-all duration-200" placeholder="Minimal 8 karakter (huruf & angka)">
                    </div>
                    @error('password') <p class="mt-2 text-xs text-rose-500 ml-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Konfirmasi Password Baru</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i data-lucide="check-square" class="h-4 w-4 text-slate-500"></i>
                        </div>
                        <input wire:model="password_confirmation" id="password_confirmation" type="password" required class="block w-full pl-11 pr-4 py-4 bg-[#1e1d35] border-0 ring-1 ring-white/5 focus:ring-2 focus:ring-indigo-500 rounded-2xl text-white text-sm placeholder-slate-500 transition-all duration-200" placeholder="Ulangi password baru">
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-4 px-4 border border-transparent rounded-2xl shadow-lg text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 transform active:scale-[0.98] shadow-indigo-500/20">
                        <span wire:loading.remove>Perbarui Password</span>
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
        Tiba-tiba ingat?
        <a href="/login" wire:navigate class="font-bold text-indigo-400 hover:text-indigo-300 transition-colors ml-1">Masuk di sini</a>
    </p>
</div>
