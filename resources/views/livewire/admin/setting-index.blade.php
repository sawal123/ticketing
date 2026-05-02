<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Pengaturan Sistem</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Kelola identitas visual dan optimasi mesin pencari (SEO).</p>
        </div>
    </div>

    <!-- Alert Session -->
    @if (session()->has('success'))
        <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl relative mb-6 flex items-center gap-3"
            role="alert">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span class="block sm:inline text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar Tabs -->
        <div class="lg:col-span-1 space-y-2">
            <button wire:click="setTab('logo')"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $activeTab === 'logo' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-none' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <i data-lucide="image" class="w-5 h-5"></i>
                <span class="font-medium text-sm">Logo & Icon</span>
            </button>
            <button wire:click="setTab('seo')"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $activeTab === 'seo' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-none' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <i data-lucide="search" class="w-5 h-5"></i>
                <span class="font-medium text-sm">SEO Meta</span>
            </button>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-3 space-y-6">
            @if ($activeTab === 'logo')
                <!-- Logo Settings -->
                <x-admin.card title="Pengaturan Logo" icon="image">
                    <form wire:submit.prevent="updateLogo" class="space-y-6">
                        <div class="flex flex-col md:flex-row items-start gap-8">
                            <div class="w-full md:w-1/3">
                                <div class="relative group aspect-video rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-600 flex items-center justify-center">
                                    @if ($new_logo)
                                        <img src="{{ $new_logo->temporaryUrl() }}" class="max-w-full max-h-full object-contain p-4">
                                    @elseif ($logo)
                                        <img src="{{ asset('storage/logo/' . $logo) }}" class="max-w-full max-h-full object-contain p-4">
                                    @else
                                        <div class="text-center p-6">
                                            <i data-lucide="image-plus" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                                            <p class="text-xs text-slate-400">Belum ada logo</p>
                                        </div>
                                    @endif
                                </div>
                                <p class="text-[10px] text-slate-400 mt-2 text-center uppercase tracking-widest font-bold">Preview Logo</p>
                            </div>
                            
                            <div class="flex-1 space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-2 uppercase tracking-wider">Unggah Logo Baru</label>
                                    <div class="flex items-center gap-3">
                                        <input type="file" wire:model="new_logo" id="logo-input" class="hidden" accept="image/*">
                                        <label for="logo-input" class="px-4 py-2.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl text-sm font-bold cursor-pointer hover:bg-indigo-100 transition-colors flex items-center gap-2">
                                            <i data-lucide="upload" class="w-4 h-4"></i>
                                            Pilih File
                                        </label>
                                        <span class="text-xs text-slate-400">Maks. 2MB (PNG, JPG, SVG)</span>
                                    </div>
                                    @error('new_logo') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                                </div>
                                
                                <div class="pt-4">
                                    <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                                        <span wire:loading.remove>Simpan Logo</span>
                                        <span wire:loading class="flex items-center gap-2">
                                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                                            Memproses...
                                        </span>
                                    </x-admin.button>
                                </div>
                            </div>
                        </div>
                    </form>
                </x-admin.card>

                <!-- Icon Settings -->
                <x-admin.card title="Pengaturan Icon (Favicon)" icon="smartphone">
                    <form wire:submit.prevent="updateIcon" class="space-y-6">
                        <div class="flex flex-col md:flex-row items-start gap-8">
                            <div class="w-24">
                                <div class="aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-600 flex items-center justify-center">
                                    @if ($new_icon)
                                        <img src="{{ $new_icon->temporaryUrl() }}" class="w-12 h-12 object-contain">
                                    @elseif ($icon)
                                        <img src="{{ asset('storage/icon/' . $icon) }}" class="w-12 h-12 object-contain">
                                    @else
                                        <i data-lucide="help-circle" class="w-8 h-8 text-slate-300"></i>
                                    @endif
                                </div>
                                <p class="text-[10px] text-slate-400 mt-2 text-center uppercase tracking-widest font-bold">Favicon</p>
                            </div>
                            
                            <div class="flex-1 space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-2 uppercase tracking-wider">Unggah Icon Baru</label>
                                    <div class="flex items-center gap-3">
                                        <input type="file" wire:model="new_icon" id="icon-input" class="hidden" accept="image/*">
                                        <label for="icon-input" class="px-4 py-2.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl text-sm font-bold cursor-pointer hover:bg-emerald-100 transition-colors flex items-center gap-2">
                                            <i data-lucide="upload" class="w-4 h-4"></i>
                                            Pilih File
                                        </label>
                                        <span class="text-xs text-slate-400">Maks. 1MB (Square PNG/ICO)</span>
                                    </div>
                                    @error('new_icon') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                                </div>
                                
                                <div class="pt-4">
                                    <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                                        <span wire:loading.remove>Simpan Icon</span>
                                        <span wire:loading class="flex items-center gap-2">
                                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                                            Memproses...
                                        </span>
                                    </x-admin.button>
                                </div>
                            </div>
                        </div>
                    </form>
                </x-admin.card>
            @endif

            @if ($activeTab === 'seo')
                <!-- SEO Meta Settings -->
                <x-admin.card title="Optimasi Mesin Pencari (SEO)" icon="search">
                    <form wire:submit.prevent="updateSEO" class="space-y-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-2 uppercase tracking-wider">Keywords (Kata Kunci)</label>
                                <input type="text" wire:model="keyword" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 dark:text-white placeholder-slate-400 transition-all duration-200" placeholder="tiket konser, gotik, festival musik...">
                                <p class="text-[10px] text-slate-400 mt-1.5 ml-1">Pisahkan dengan koma (,)</p>
                                @error('keyword') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-2 uppercase tracking-wider">Deskripsi Meta</label>
                                <textarea wire:model="description" rows="4" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 dark:text-white placeholder-slate-400 transition-all duration-200" placeholder="GOTIK adalah platform ticketing konser terpercaya..."></textarea>
                                <div class="flex justify-between mt-1.5 px-1">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">Rekomendasi: 150-160 karakter</p>
                                    <p class="text-[10px] {{ strlen($description) > 160 ? 'text-rose-500' : 'text-slate-400' }} font-bold">{{ strlen($description) }} Karakter</p>
                                </div>
                                @error('description') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                                <span wire:loading.remove>Simpan Pengaturan SEO</span>
                                <span wire:loading class="flex items-center gap-2">
                                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                                    Memproses...
                                </span>
                            </x-admin.button>
                        </div>
                    </form>
                </x-admin.card>

                <!-- SEO Preview -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                    <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-4 flex items-center gap-2">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        Preview di Google
                    </h4>
                    <div class="space-y-1">
                        <p class="text-[#1a0dab] dark:text-[#8ab4f8] text-xl hover:underline cursor-pointer">GOTIK - Tiket Konser & Festival Terpercaya</p>
                        <p class="text-[#006621] dark:text-[#34a853] text-sm">https://go-tik.com</p>
                        <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed max-w-2xl">
                            {{ $description ?: 'Tambahkan deskripsi meta untuk melihat pratinjau di sini...' }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
