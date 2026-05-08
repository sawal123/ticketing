<div class="sm:mx-auto sm:w-full sm:max-w-md p-2">
    <div class="flex justify-center mb-8">
        <a href="/" wire:navigate>
            <img src="{{ asset('storage/logo/' . ($logo[0]->logo ?? '')) }}" alt="Logo"
                class="h-16 w-auto object-contain">
        </a>
    </div>

    <div
        class="bg-[#16152a] border border-white/5 py-10 px-6 shadow-2xl rounded-[2.5rem] sm:px-10 relative overflow-hidden">
        <!-- Glow Effect -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-600/10 blur-[80px] rounded-full"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-purple-600/10 blur-[80px] rounded-full"></div>

        <div class="relative">
            <h2 class="text-3xl font-extrabold text-white text-center mb-2 tracking-tight">Buat Akun</h2>
            <p class="text-slate-400 text-center text-sm mb-10">Bergabunglah dengan Gotik dan mulai pengalaman seru.</p>

            <form wire:submit.prevent="register" class="space-y-6" x-data="{ showPassword: false, showConfirmPassword: false }">
                <div>
                    <label for="name"
                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Nama
                        Lengkap</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none" wire:ignore>
                            <i data-lucide="user" class="h-4 w-4 text-slate-500"></i>
                        </div>
                        <input wire:model.live="name" id="name" type="text" required
                            class="block w-full pl-11 pr-4 py-4 bg-[#1e1d35] border-0 ring-1 ring-white/5 focus:ring-2 focus:ring-indigo-500 rounded-2xl text-white text-sm placeholder-slate-500 transition-all duration-200"
                            placeholder="Nama Lengkap Anda">
                    </div>
                    @error('name') <p class="mt-2 text-xs text-rose-500 ml-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email"
                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Email
                        Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none" wire:ignore>
                            <i data-lucide="mail" class="h-4 w-4 text-slate-500"></i>
                        </div>
                        <input wire:model.live="email" id="email" type="email" required
                            class="block w-full pl-11 pr-4 py-4 bg-[#1e1d35] border-0 ring-1 ring-white/5 focus:ring-2 focus:ring-indigo-500 rounded-2xl text-white text-sm placeholder-slate-500 transition-all duration-200"
                            placeholder="nama@email.com">
                    </div>
                    @error('email') <p class="mt-2 text-xs text-rose-500 ml-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password"
                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none" wire:ignore>
                            <i data-lucide="lock" class="h-4 w-4 text-slate-500"></i>
                        </div>
                        <input wire:model.live="password" id="password" :type="showPassword ? 'text' : 'password'" required
                            class="block w-full pl-11 pr-12 py-4 bg-[#1e1d35] border-0 ring-1 ring-white/5 focus:ring-2 focus:ring-indigo-500 rounded-2xl text-white text-sm placeholder-slate-500 transition-all duration-200"
                            placeholder="Minimal 8 karakter">
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-indigo-400 transition-colors">
                            <svg x-show="!showPassword" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="showPassword" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-cloak><path d="M9.88 9.88 2 2m22 22-7.88-7.88m-2.83 0A7.26 7.26 0 0 1 2 12s3-7 10-7a7.06 7.06 0 0 1 5.12 2.12M12 7.14a3 3 0 0 1 4.76 4.76m-7.64 0A3 3 0 0 1 12 16.86m2.83 0a7.06 7.06 0 0 1-5.12-2.12L12 12m-2.83-2.83A3 3 0 0 1 12 12m0 0 2.83 2.83"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                        </button>
                    </div>
                    @error('password') <p class="mt-2 text-xs text-rose-500 ml-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation"
                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Konfirmasi
                        Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none" wire:ignore>
                            <i data-lucide="check-square" class="h-4 w-4 text-slate-500"></i>
                        </div>
                        <input wire:model.live="password_confirmation" id="password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'" required
                            class="block w-full pl-11 pr-12 py-4 bg-[#1e1d35] border-0 ring-1 ring-white/5 focus:ring-2 focus:ring-indigo-500 rounded-2xl text-white text-sm placeholder-slate-500 transition-all duration-200"
                            placeholder="Ulangi password">
                        <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-indigo-400 transition-colors">
                            <svg x-show="!showConfirmPassword" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="showConfirmPassword" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-cloak><path d="M9.88 9.88 2 2m22 22-7.88-7.88m-2.83 0A7.26 7.26 0 0 1 2 12s3-7 10-7a7.06 7.06 0 0 1 5.12 2.12M12 7.14a3 3 0 0 1 4.76 4.76m-7.64 0A3 3 0 0 1 12 16.86m2.83 0a7.06 7.06 0 0 1-5.12-2.12L12 12m-2.83-2.83A3 3 0 0 1 12 12m0 0 2.83 2.83"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                        </button>
                    </div>
                    @error('password_confirmation') <p class="mt-2 text-xs text-rose-500 ml-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <button type="submit" wire:loading.attr="disabled" wire:target="register"
                        class="w-full flex justify-center py-4 px-4 border border-transparent rounded-2xl shadow-lg text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 transform active:scale-[0.98] shadow-indigo-500/20 disabled:opacity-70 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="register">Daftar Akun</span>
                        <span wire:loading.flex wire:target="register" class="items-center gap-2" x-cloak>
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                            Memproses...
                        </span>
                    </button>
                </div>
            </form>

            <div class="mt-8">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-white/5"></div>
                    </div>
                    <div class="relative flex justify-center text-xs">
                        <span class="px-3 bg-[#16152a] text-slate-500 uppercase tracking-widest font-bold">Atau daftar
                            dengan</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="{{ route('auth.google') }}"
                        class="w-full inline-flex justify-center py-4 px-4 rounded-2xl bg-white/5 border border-white/5 text-white text-sm font-bold hover:bg-white/10 transition-all duration-200 gap-3 items-center">
                        <svg class="h-5 w-5" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                fill="#4285F4" />
                            <path fill="currentColor"
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                fill="#34A853" />
                            <path fill="currentColor"
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                fill="#FBBC05" />
                            <path fill="currentColor"
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                fill="#EA4335" />
                        </svg>
                        <span>Google</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <p class="mt-10 text-center text-sm text-slate-400">
        Sudah punya akun?
        <a href="/login" wire:navigate
            class="font-bold text-indigo-400 hover:text-indigo-300 transition-colors ml-1">Masuk di sini</a>
    </p>
</div>