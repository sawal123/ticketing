<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Pengaturan Akun</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Kelola profil, keamanan, dan rekening bank Anda.</p>
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
            <button wire:click="setTab('profile')"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $activeTab === 'profile' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-none' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <i data-lucide="user" class="w-5 h-5"></i>
                <span class="font-medium text-sm">Profil</span>
            </button>
            <button wire:click="setTab('security')"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $activeTab === 'security' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-none' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <i data-lucide="lock" class="w-5 h-5"></i>
                <span class="font-medium text-sm">Keamanan</span>
            </button>
            <button wire:click="setTab('bank')"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $activeTab === 'bank' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-none' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <i data-lucide="credit-card" class="w-5 h-5"></i>
                <span class="font-medium text-sm">Rekening Bank</span>
            </button>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-3">
            @if ($activeTab === 'profile')
                <x-admin.card title="Informasi Profil" icon="user">
                    <form wire:submit.prevent="updateProfile" class="space-y-6">
                        <div class="flex flex-col md:flex-row items-center gap-6">
                            <div class="relative group">
                                <div class="w-24 h-24 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-700 border-2 border-slate-200 dark:border-slate-600">
                                    @if ($new_gambar)
                                        <img src="{{ $new_gambar->temporaryUrl() }}" class="w-full h-full object-cover">
                                    @elseif ($gambar)
                                        <img src="{{ asset('storage/user/' . $gambar) }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-slate-400">
                                            <i data-lucide="user" class="w-10 h-10"></i>
                                        </div>
                                    @endif
                                </div>
                                <label for="profile-photo" class="absolute bottom-0 right-0 w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center text-white cursor-pointer hover:bg-indigo-700 transition-colors shadow-lg">
                                    <i data-lucide="camera" class="w-4 h-4"></i>
                                    <input type="file" id="profile-photo" wire:model="new_gambar" class="hidden" accept="image/*">
                                </label>
                            </div>
                            <div class="flex-1 text-center md:text-left">
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white">{{ $name }}</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $email }}</p>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1 font-medium uppercase tracking-wider">{{ auth()->user()->role }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-admin.input label="Nama Lengkap" wire:model="name" placeholder="Nama Lengkap" error="{{ $errors->first('name') }}" />
                            <x-admin.input label="Alamat Email" wire:model="email" type="email" placeholder="email@example.com" error="{{ $errors->first('email') }}" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-admin.input label="Nomor WhatsApp" wire:model="nomor" placeholder="08123456789" error="{{ $errors->first('nomor') }}" />
                            <x-admin.input label="Tanggal Lahir" wire:model="birthday" type="date" error="{{ $errors->first('birthday') }}" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="w-full">
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
                                    Jenis Kelamin
                                </label>
                                <select wire:model="gender" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                                @error('gender') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                            </div>
                            <x-admin.input label="Kota/Provinsi" wire:model="kota" placeholder="Contoh: Jakarta" error="{{ $errors->first('kota') }}" />
                        </div>

                        <div class="w-full">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
                                Alamat Lengkap
                            </label>
                            <textarea wire:model="alamat" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200" placeholder="Alamat Lengkap..."></textarea>
                            @error('alamat') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end">
                            <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                                <span wire:loading.remove>Simpan Perubahan</span>
                                <span wire:loading class="flex items-center gap-2">
                                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                                    Memproses...
                                </span>
                            </x-admin.button>
                        </div>
                    </form>
                </x-admin.card>
            @endif

            @if ($activeTab === 'security')
                <x-admin.card title="Keamanan Akun" icon="lock">
                    <form wire:submit.prevent="updatePassword" class="space-y-6">
                        <div class="max-w-md space-y-4">
                            <x-admin.input label="Password Saat Ini" wire:model="current_password" type="password" placeholder="••••••••" error="{{ $errors->first('current_password') }}" />
                            <hr class="border-slate-100 dark:border-slate-700 my-4">
                            <x-admin.input label="Password Baru" wire:model="new_password" type="password" placeholder="••••••••" error="{{ $errors->first('new_password') }}" />
                            <x-admin.input label="Konfirmasi Password Baru" wire:model="new_password_confirmation" type="password" placeholder="••••••••" />
                        </div>

                        <div class="flex justify-end">
                            <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                                <span wire:loading.remove>Update Password</span>
                                <span wire:loading class="flex items-center gap-2">
                                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                                    Memproses...
                                </span>
                            </x-admin.button>
                        </div>
                    </form>
                </x-admin.card>
            @endif

            @if ($activeTab === 'bank')
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Rekening Bank</h3>
                        <x-admin.button wire:click="openBankModal" icon="plus" variant="primary">
                            Tambah Bank
                        </x-admin.button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @forelse($banks as $bank)
                            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 relative group overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-50 dark:bg-indigo-900/10 rounded-bl-full -mr-10 -mt-10 transition-transform duration-500 group-hover:scale-110"></div>
                                
                                <div class="flex justify-between items-start relative z-10">
                                    <div class="space-y-1">
                                        <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">{{ $bank->bank }}</p>
                                        <h4 class="text-xl font-mono font-bold text-slate-800 dark:text-white">{{ $bank->norek }}</h4>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">{{ $bank->nama }}</p>
                                    </div>
                                    <div class="flex gap-1">
                                        <button wire:click="openBankModal({{ $bank->id }})" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-colors">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                        <button onclick="confirm('Apakah Anda yakin?') || event.stopImmediatePropagation()" wire:click="deleteBank({{ $bank->id }})" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full py-12 flex flex-col items-center justify-center bg-slate-50 dark:bg-slate-800/50 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700">
                                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-300 dark:text-slate-600 mb-4">
                                    <i data-lucide="credit-card" class="w-8 h-8"></i>
                                </div>
                                <h4 class="text-slate-800 dark:text-white font-bold">Belum Ada Rekening</h4>
                                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Tambahkan rekening bank Anda untuk melakukan penarikan saldo.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Bank Modal -->
    <x-admin.modal name="bank-modal" title="{{ $isEditBank ? 'Edit Rekening Bank' : 'Tambah Rekening Bank' }}" icon="credit-card">
        <form wire:submit.prevent="saveBank" class="space-y-4">
            <div class="w-full">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
                    Nama Bank
                </label>
                <select wire:model="bank_name" class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('bank_name') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-200 dark:border-slate-600' }} bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200">
                    <option value="">Pilih Bank</option>
                    @foreach($available_banks as $b)
                        <option value="{{ $b->name }}">{{ $b->name }}</option>
                    @endforeach
                    @if($available_banks->isEmpty())
                        <option value="BCA">BCA</option>
                        <option value="BNI">BNI</option>
                        <option value="BRI">BRI</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="CIMB Niaga">CIMB Niaga</option>
                        <option value="Permata">Permata</option>
                    @endif
                </select>
                @error('bank_name') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <x-admin.input label="Nomor Rekening" wire:model="nomor_rekening" placeholder="Contoh: 1234567890" error="{{ $errors->first('nomor_rekening') }}" />
            <x-admin.input label="Nama Pemilik Rekening" wire:model="nama_rekening" placeholder="Contoh: Jhon Doe" error="{{ $errors->first('nama_rekening') }}" />

            <div class="flex justify-end gap-3 pt-4">
                <x-admin.button type="button" variant="secondary" x-on:click="show = false">
                    Batal
                </x-admin.button>
                <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        {{ $isEditBank ? 'Simpan Perubahan' : 'Tambah Rekening' }}
                    </span>
                    <span wire:loading class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        Memproses...
                    </span>
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
