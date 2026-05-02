<div class="p-6 space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Manajemen Tim (Staff)</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Kelola akses tim Anda untuk membantu operasional event.</p>
        </div>
        <x-admin.button wire:click="openCreateModal" icon="user-plus" variant="primary">
            Undang Staff
        </x-admin.button>
    </div>

    <!-- Alert Session -->
    @if (session()->has('success'))
        <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl relative mb-6 flex items-center gap-3"
            role="alert">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span class="block sm:inline text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Stats Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-admin.card title="Total Staff" icon="users" iconColor="indigo">
            <div class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ \App\Models\User::where('role', 'staff')->where('parent_uid', auth()->user()->role === 'staff' ? auth()->user()->parent_uid : auth()->user()->uid)->count() }}
            </div>
            <p class="text-[10px] text-slate-500 mt-1">Jumlah anggota tim terdaftar</p>
        </x-admin.card>
        <x-admin.card title="Akses Aktif" icon="shield-check" iconColor="emerald">
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                {{ \App\Models\User::where('role', 'staff')->where('parent_uid', auth()->user()->role === 'staff' ? auth()->user()->parent_uid : auth()->user()->uid)->whereNotNull('email_verified_at')->count() }}
            </div>
            <p class="text-[10px] text-slate-500 mt-1">Staff yang sudah verifikasi</p>
        </x-admin.card>
        <x-admin.card title="Menunggu Verifikasi" icon="mail" iconColor="amber">
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                {{ \App\Models\User::where('role', 'staff')->where('parent_uid', auth()->user()->role === 'staff' ? auth()->user()->parent_uid : auth()->user()->uid)->whereNull('email_verified_at')->count() }}
            </div>
            <p class="text-[10px] text-slate-500 mt-1">Undangan terkirim</p>
        </x-admin.card>
    </div>

    <!-- Filter & Table Section -->
    <x-admin.card>
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <x-admin.input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau email staff..."
                    icon="search" />
            </div>
        </div>

        <x-admin.table :headers="['Staff', 'Akses', 'Status', 'Bergabung', 'Aksi']">
            @forelse($staffs as $staff)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center overflow-hidden border border-slate-200 dark:border-slate-600">
                                <img src="{{ asset('storage/profile/' . $staff->gambar) }}" alt="{{ $staff->name }}" class="w-full h-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($staff->name) }}&color=7F9CF5&background=EBF4FF'">
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $staff->name }}</div>
                                <div class="text-xs text-slate-500">{{ $staff->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 rounded border border-indigo-100 dark:border-indigo-800 uppercase tracking-wider">
                            {{ strtoupper($staff->role) }}
                        </span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        @if($staff->email_verified_at)
                            <span class="flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                                <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                                Aktif
                            </span>
                        @else
                            <span class="flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400 font-medium">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                Pending Verifikasi
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-500">
                        {{ $staff->created_at->format('d M Y') }}
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            <button wire:click="confirmDelete({{ $staff->id }})"
                                class="p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors"
                                title="Hapus Akses">
                                <i data-lucide="user-minus" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col items-center gap-2">
                            <i data-lucide="users" class="w-10 h-10 text-slate-300 dark:text-slate-600"></i>
                            <p>Belum ada staff yang terdaftar.</p>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $staffs->links() }}
            </x-slot>
        </x-admin.table>
    </x-admin.card>

    <!-- Create Modal -->
    <x-admin.modal name="staff-modal" title="Undang Staff Baru" icon="user-plus" maxWidth="md">
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-800 mb-4">
                <p class="text-xs text-indigo-700 dark:text-indigo-400 leading-relaxed">
                    Undangan akan dikirim via email. Staff harus melakukan verifikasi dan melengkapi profil (termasuk password) sebelum dapat masuk ke dashboard.
                </p>
            </div>

            <x-admin.input label="Nama Lengkap" wire:model="name" placeholder="Masukan nama staff.."
                error="{{ $errors->first('name') }}" />
            
            <x-admin.input label="Alamat Email" type="email" wire:model="email" placeholder="email@example.com"
                error="{{ $errors->first('email') }}" />

            <div class="flex justify-end gap-3 pt-4">
                <x-admin.button type="button" variant="secondary" x-on:click="show = false">
                    Close
                </x-admin.button>
                <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        Kirim Undangan
                    </span>
                    <span wire:loading class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        Mengirim...
                    </span>
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Delete Confirmation Modal -->
    <x-admin.modal name="delete-modal" title="Hapus Akses Staff" icon="user-minus">
        <div class="text-center p-4">
            <p class="text-slate-600 dark:text-slate-400 mb-6">Apakah Anda yakin ingin menghapus akses staff ini? Mereka tidak akan bisa lagi masuk ke dashboard.</p>
            <div class="flex justify-center gap-3">
                <x-admin.button variant="secondary" x-on:click="show = false">
                    Batal
                </x-admin.button>
                <x-admin.button variant="danger" wire:click="delete">
                    Ya, Hapus Akses
                </x-admin.button>
            </div>
        </div>
    </x-admin.modal>
</div>
