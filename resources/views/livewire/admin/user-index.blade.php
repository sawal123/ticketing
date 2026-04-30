<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Manajemen Pengguna</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola hak akses dan data profil pengguna sistem.</p>
        </div>
        <x-admin.button variant="primary" icon="user-plus" wire:click="openCreateModal">
            Tambah User
        </x-admin.button>
    </div>

    <!-- Tab Navigation -->
    <div class="mb-6 flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl w-fit">
        <button 
            wire:click="setTab('user')" 
            class="px-4 py-2 rounded-lg text-sm font-semibold transition-all {{ $activeTab === 'user' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            User
        </button>
        <button 
            wire:click="setTab('admin')" 
            class="px-4 py-2 rounded-lg text-sm font-semibold transition-all {{ $activeTab === 'admin' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            Admin
        </button>
        <button 
            wire:click="setTab('penyewa')" 
            class="px-4 py-2 rounded-lg text-sm font-semibold transition-all {{ $activeTab === 'penyewa' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            Penyewa
        </button>
        <button 
            wire:click="setTab('cashes')" 
            class="px-4 py-2 rounded-lg text-sm font-semibold transition-all {{ $activeTab === 'cashes' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            Cashes
        </button>
    </div>

    <!-- Alert Message -->
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 rounded-xl text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            {{ session('message') }}
        </div>
    @endif

    <div class="space-y-4">
        <!-- Search & Filters -->
        <x-admin.card padding="p-4">
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <x-admin.input 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Cari nama, email, atau nomor HP..." 
                        icon="search" 
                    />
                </div>
            </div>
        </x-admin.card>

        <!-- Table -->
        <x-admin.table title="Daftar Pengguna ({{ ucfirst($activeTab) }})" :headers="['User', 'Kontak', 'Role', 'Bergabung', 'Aksi']" :count="$users->total()">
            @forelse($users as $user)
                <tr class="table-row-hover transition-colors">
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 font-bold border border-slate-200 dark:border-slate-600">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-800 dark:text-white">{{ $user->name }}</span>
                                <span class="text-xs text-slate-500">{{ $user->email }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-sm text-slate-600 dark:text-slate-400 font-medium">
                            {{ $user->nomor ?: '-' }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        @php
                            $roleName = $activeTab === 'cashes' ? 'Guest' : $user->role;
                            $roleColor = match($roleName) {
                                'admin' => 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800',
                                'penyewa' => 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
                                'Guest' => 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',
                                default => 'bg-indigo-50 text-indigo-600 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800',
                            };
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $roleColor }}">
                            {{ $roleName }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-sm text-slate-500 dark:text-slate-400">
                        {{ $user->created_at->format('d M Y') }}
                    </td>
                    <td class="px-5 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <x-admin.button 
                                x-on:click="$dispatch('open-modal', { name: 'history-modal' })"
                                wire:click="openHistory({{ $user->id }})" 
                                variant="ghost" 
                                size="sm" 
                                icon="history"
                                class="text-emerald-600"
                                title="Riwayat Transaksi"
                            />
                            <x-admin.button 
                                wire:click="edit({{ $user->id }})" 
                                variant="ghost" 
                                size="sm" 
                                icon="pencil"
                                class="text-indigo-600"
                                title="Edit"
                            />
                            <x-admin.button 
                                wire:click="confirmDelete({{ $user->id }})" 
                                variant="ghost" 
                                size="sm" 
                                icon="trash-2"
                                class="text-rose-600"
                                title="Hapus"
                            />
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                        <div class="flex flex-col items-center justify-center">
                            <i data-lucide="users" class="w-12 h-12 mb-2 opacity-20"></i>
                            <p>Tidak ada pengguna yang ditemukan dalam kategori ini.</p>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $users->links('components.admin.pagination') }}
            </x-slot>
        </x-admin.table>
    </div>

    <!-- Create/Edit Modal -->
    <x-admin.modal name="user-modal" title="{{ $editingId ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}" icon="user">
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-full">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Lengkap</label>
                    <x-admin.input wire:model="name" placeholder="Masukkan nama lengkap..." required />
                    @error('name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Email</label>
                    <x-admin.input type="email" wire:model="email" placeholder="contoh@mail.com" required />
                    @error('email') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nomor HP</label>
                    <x-admin.input wire:model="nomor" placeholder="0812..." />
                    @error('nomor') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                @if($activeTab !== 'cashes')
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Role Hak Akses</label>
                        <select wire:model="role" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                            <option value="user">User (Pembeli)</option>
                            <option value="penyewa">Penyewa (Organizer)</option>
                            <option value="admin">Administrator</option>
                        </select>
                        @error('role') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Password {{ $editingId ? '(Kosongkan jika tidak diubah)' : '' }}</label>
                        <x-admin.input type="password" wire:model="password" placeholder="Min. 6 karakter" />
                        @error('password') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <x-admin.button type="button" x-on:click="show = false" variant="ghost">Batal</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="save">Simpan Data</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Delete Confirmation Modal -->
    <x-admin.modal-delete 
        name="delete-user-modal" 
        title="Hapus Pengguna?" 
        message="Akun pengguna ini akan dihapus secara permanen. Pengguna tidak akan bisa login lagi ke sistem."
    >
        <x-admin.button 
            wire:click="delete" 
            variant="primary" 
            class="w-full !bg-rose-600 hover:!bg-rose-700 !py-3 shadow-lg shadow-rose-200"
            icon="trash-2"
        >
            Ya, Hapus Sekarang
        </x-admin.button>
    </x-admin.modal-delete>

    <x-admin.modal name="history-modal" title="Riwayat Transaksi" icon="history">
        <div wire:loading wire:target="openHistory" class="w-full py-12">
            <div class="flex flex-col items-center justify-center space-y-4">
                <div class="w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-sm text-slate-500 font-medium animate-pulse">Mengambil riwayat transaksi...</p>
            </div>
        </div>

        <div wire:loading.remove wire:target="openHistory" class="space-y-6">
            @if($historyUser)
                <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700">
                    <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold">
                        {{ substr($historyUser->name, 0, 1) }}
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 dark:text-white">{{ $historyUser->name }}</h4>
                        <p class="text-sm text-slate-500">{{ $historyUser->email }}</p>
                    </div>
                </div>
            @endif

            <div class="space-y-3 max-h-[450px] overflow-y-auto pr-2 custom-scrollbar">
                @forelse($historyItems as $cart)
                    <div class="p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Event</span>
                                <span class="font-bold text-slate-800 dark:text-white">{{ $cart->event->event ?? 'N/A' }}</span>
                            </div>
                            @php
                                $statusColor = match($cart->status) {
                                    'SUCCESS' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                    'PENDING' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                    default => 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $statusColor }}">
                                {{ $cart->status }}
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-3 p-2 bg-slate-50 dark:bg-slate-900/50 rounded-lg">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-slate-400 uppercase font-bold">Invoice</span>
                                <span class="text-xs font-mono font-semibold">{{ $cart->invoice }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-slate-400 uppercase font-bold">Waktu</span>
                                <span class="text-xs font-semibold">{{ $cart->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tiket</span>
                            @foreach($cart->hargaCarts as $hc)
                                <div class="flex justify-between items-center text-xs p-1">
                                    <span class="text-slate-600 dark:text-slate-400">{{ $hc->kategori_harga }} x{{ $hc->quantity }}</span>
                                    <span class="font-bold text-slate-800 dark:text-200">Rp {{ number_format($hc->harga_ticket * $hc->quantity) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <p class="text-slate-500 italic">Belum ada riwayat transaksi.</p>
                    </div>
                @endforelse
            </div>
            
            <div class="flex justify-end">
                <x-admin.button type="button" x-on:click="show = false" variant="primary">Tutup</x-admin.button>
            </div>
        </div>
    </x-admin.modal>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', (el, component) => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        });
    </script>
</div>

