<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Manajemen Pengguna</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola hak akses dan data profil pengguna sistem.</p>
        </div>
        <x-admin.button type="button"
            variant="primary"
            icon="user-plus"
            x-on:click="$dispatch('open-modal', { name: 'user-modal' })"
            wire:click="openCreateModal"
            loadingTarget="openCreateModal">
            Tambah User
        </x-admin.button>
    </div>

    <!-- Tab Navigation -->
    <div class="mb-6 flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl w-fit">
        <button 
            type="button"
            wire:click="setTab('user')"
            wire:loading.attr="disabled"
            wire:target="setTab('user')"
            class="px-4 py-2 rounded-lg text-sm font-semibold transition-all disabled:pointer-events-none disabled:opacity-60 {{ $activeTab === 'user' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            <span wire:loading.remove wire:target="setTab('user')">User</span>
            <span wire:loading.flex wire:target="setTab('user')" class="items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                </svg>
                User
            </span>
        </button>
        <button 
            type="button"
            wire:click="setTab('admin')"
            wire:loading.attr="disabled"
            wire:target="setTab('admin')"
            class="px-4 py-2 rounded-lg text-sm font-semibold transition-all disabled:pointer-events-none disabled:opacity-60 {{ $activeTab === 'admin' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            <span wire:loading.remove wire:target="setTab('admin')">Admin</span>
            <span wire:loading.flex wire:target="setTab('admin')" class="items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                </svg>
                Admin
            </span>
        </button>
        <button 
            type="button"
            wire:click="setTab('penyewa')"
            wire:loading.attr="disabled"
            wire:target="setTab('penyewa')"
            class="px-4 py-2 rounded-lg text-sm font-semibold transition-all disabled:pointer-events-none disabled:opacity-60 {{ $activeTab === 'penyewa' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            <span wire:loading.remove wire:target="setTab('penyewa')">Penyewa</span>
            <span wire:loading.flex wire:target="setTab('penyewa')" class="items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                </svg>
                Penyewa
            </span>
        </button>
        <button 
            type="button"
            wire:click="setTab('cashes')"
            wire:loading.attr="disabled"
            wire:target="setTab('cashes')"
            class="px-4 py-2 rounded-lg text-sm font-semibold transition-all disabled:pointer-events-none disabled:opacity-60 {{ $activeTab === 'cashes' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            <span wire:loading.remove wire:target="setTab('cashes')">Cashes</span>
            <span wire:loading.flex wire:target="setTab('cashes')" class="items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                </svg>
                Cashes
            </span>
        </button>
    </div>

    <!-- Alert Message -->
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 rounded-xl text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-700 rounded-xl text-rose-700 dark:text-rose-400 flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            {{ session('error') }}
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
        <div wire:loading.class="opacity-60 pointer-events-none" wire:target="setTab('user'),setTab('admin'),setTab('penyewa'),setTab('cashes')" class="transition-opacity">
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
                                @if($activeTab === 'penyewa')
                                    <a href="{{ route('admin.user.penyewa.detail', $user->uid) }}" wire:navigate>
                                        <x-admin.button
                                            variant="ghost"
                                            size="sm"
                                            icon="eye"
                                            class="text-emerald-600"
                                            title="Detail Penyewa"
                                        />
                                    </a>
                                @else
                                    @php
                                        $rowHistoryPayload = $preloadedHistories[$user->id] ?? [
                                            'name' => $user->name,
                                            'email' => $user->email,
                                            'items' => [],
                                        ];
                                    @endphp
                                    <x-admin.button
                                        type="button"
                                        data-user-history="{{ base64_encode(json_encode($rowHistoryPayload)) }}"
                                        x-on:click="$dispatch('open-user-history-modal', $el.dataset.userHistory)"
                                        variant="ghost"
                                        size="sm"
                                        icon="history"
                                        class="text-emerald-600"
                                        title="Riwayat Transaksi"
                                    />
                                @endif
                                <x-admin.button 
                                    wire:click="edit({{ $user->id }})" 
                                    wire:loading.attr="disabled"
                                    wire:target="edit({{ $user->id }})"
                                    variant="ghost" 
                                    size="sm" 
                                    class="text-indigo-600 disabled:pointer-events-none disabled:opacity-60"
                                    title="Edit"
                                >
                                    <i wire:loading.remove wire:target="edit({{ $user->id }})" data-lucide="pencil" class="w-4 h-4"></i>
                                    <svg wire:loading wire:target="edit({{ $user->id }})" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                                    </svg>
                                </x-admin.button>
                                <x-admin.button
                                    type="button"
                                    x-on:click="$dispatch('open-modal', { name: 'delete-user-modal' })"
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
    </div>

    <!-- Create/Edit Modal -->
    <x-admin.modal name="user-modal" title="{{ $editingId ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}" icon="user">
        <form wire:submit="save" class="relative space-y-4">
            <div wire:loading.flex wire:target="save"
                class="absolute inset-0 z-20 items-center justify-center rounded-2xl bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm">
                <div class="flex flex-col items-center gap-3">
                    <svg class="h-10 w-10 animate-spin text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                    </svg>
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Menyimpan data...</p>
                </div>
            </div>

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
                <x-admin.button type="button" x-on:click="show = false" variant="ghost"
                    wire:loading.attr="disabled" wire:target="save"
                    class="disabled:pointer-events-none disabled:opacity-60">Batal</x-admin.button>
                <x-admin.button type="submit" variant="primary"
                    wire:loading.attr="disabled" wire:target="save"
                    class="disabled:pointer-events-none disabled:opacity-60">
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Simpan Data
                    </span>
                    <span wire:loading.flex wire:target="save" class="items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                        </svg>
                        Menyimpan...
                    </span>
                </x-admin.button>
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
            type="button"
            wire:click="delete"
            loadingTarget="delete,confirmDelete"
            variant="danger"
            class="w-full !py-3 shadow-lg shadow-rose-200 dark:shadow-none"
            icon="trash-2"
        >
            Ya, Hapus Sekarang
        </x-admin.button>
    </x-admin.modal-delete>

    <div
        x-data="{
            historyData: null,
            formatRp(val) {
                return 'Rp ' + (Number(val) || 0).toLocaleString('en-US');
            },
            statusClass(status) {
                if (status === 'SUCCESS') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
                if (status === 'PENDING') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                return 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400';
            },
            openHistoryModal(encoded) {
                if (!encoded) return;
                if (typeof encoded === 'string') {
                    const bytes = Uint8Array.from(atob(encoded), c => c.charCodeAt(0));
                    this.historyData = JSON.parse(new TextDecoder().decode(bytes));
                } else {
                    this.historyData = encoded;
                }
                $dispatch('open-modal', { name: 'history-modal' });
            },
        }"
        x-on:open-user-history-modal.window="openHistoryModal($event.detail)"
    >
        <x-admin.modal name="history-modal" title="Riwayat Transaksi" icon="history">
            <!-- Instant Alpine.js Preloaded View (0ms, no server roundtrip) -->
            <div x-show="historyData !== null" style="display: none;" class="space-y-6">
                <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700">
                    <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold"
                        x-text="(historyData?.name || '?').substring(0, 1)"></div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-slate-800 dark:text-white break-words" x-text="historyData?.name || '-'"></h4>
                        <p class="text-sm text-slate-500 break-all" x-text="historyData?.email || '-'"></p>
                    </div>
                </div>

                <div class="space-y-3 max-h-[450px] overflow-y-auto pr-2 custom-scrollbar">
                    <template x-if="(historyData?.items || []).length === 0">
                        <div class="py-12 text-center">
                            <p class="text-slate-500 italic">Belum ada riwayat transaksi.</p>
                        </div>
                    </template>
                    <template x-for="(cart, idx) in (historyData?.items || [])" :key="idx">
                        <div class="p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Event</span>
                                    <span class="font-bold text-slate-800 dark:text-white" x-text="cart.event"></span>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                        x-bind:class="statusClass(cart.status)"
                                        x-text="cart.status"></span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                        x-bind:class="cart.isVerified ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400'"
                                        x-text="cart.isVerified ? ('Ter' + 'verifikasi') : ('Belum ' + 'Diverifikasi')"></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-3 p-2 bg-slate-50 dark:bg-slate-900/50 rounded-lg">
                                <div class="flex flex-col">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold">Invoice</span>
                                    <span class="text-xs font-mono font-semibold" x-text="cart.invoice"></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold">Waktu</span>
                                    <span class="text-xs font-semibold" x-text="cart.time"></span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tiket</span>
                                <template x-for="(hc, hIdx) in (cart.items || [])" :key="hIdx">
                                    <div class="flex justify-between items-center text-xs p-1">
                                        <span class="text-slate-600 dark:text-slate-400" x-text="hc.name + ' x' + hc.qty"></span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200" x-text="formatRp(hc.total)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end">
                    <x-admin.button type="button" x-on:click="show = false" variant="primary">Tutup</x-admin.button>
                </div>
            </div>

            <!-- Server-rendered fallback (when opened programmatically via Livewire openHistory) -->
            <div x-show="historyData === null" class="space-y-6">
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
                                    $isVerified = filled($cart->scanned_at) || (string) $cart->konfirmasi === '1';
                                    $verificationColor = $isVerified
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                        : 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400';
                                @endphp
                                <div class="flex flex-col items-end gap-1">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $statusColor }}">
                                        {{ $cart->status }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $verificationColor }}">
                                        {{ $isVerified ? 'Terverifikasi' : 'Belum Diverifikasi' }}
                                    </span>
                                </div>
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
                                        <span class="font-bold text-slate-800 dark:text-slate-200">Rp {{ number_format($hc->harga_ticket * $hc->quantity) }}</span>
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
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', () => {
                if (typeof lucide === 'undefined' || window.__adminLucideScheduled) return;
                window.__adminLucideScheduled = true;
                requestAnimationFrame(() => {
                    window.__adminLucideScheduled = false;
                    lucide.createIcons();
                });
            });
        });
    </script>
</div>
