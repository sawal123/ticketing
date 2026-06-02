<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Master Kategori</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Kelola kategori event untuk seluruh platform.</p>
        </div>
        <x-admin.button wire:click="openCreateModal" icon="plus" variant="primary">
            Tambah Kategori
        </x-admin.button>
    </div>

    <!-- Alert Session -->
    @if (session()->has('success'))
        <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl relative mb-6 flex items-center gap-3" role="alert">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span class="block sm:inline text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-rose-100 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl relative mb-6 flex items-center gap-3" role="alert">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <span class="block sm:inline text-sm font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Filter & Table Section -->
    <x-admin.card>
        <div class="mb-6">
            <div class="max-w-md">
                <x-admin.input wire:model.live.debounce.300ms="search" placeholder="Cari nama kategori..." icon="search" />
            </div>
        </div>

        <x-admin.table :headers="['Nama Kategori', 'Slug', 'Jumlah Event', 'Aksi']">
            @forelse($categories as $category)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $category->name }}</div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-500 font-mono">
                        {{ $category->slug }}
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                        <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg font-bold">
                            {{ $category->events_count }}
                        </span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center gap-2">
                            <button wire:click="openEditModal({{ $category->id }})"
                                wire:loading.attr="disabled"
                                wire:target="openEditModal({{ $category->id }})"
                                class="p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-xl transition-colors disabled:pointer-events-none disabled:opacity-60"
                                title="Edit">
                                <i wire:loading.remove wire:target="openEditModal({{ $category->id }})" data-lucide="edit-3" class="w-4 h-4"></i>
                                <svg wire:loading wire:target="openEditModal({{ $category->id }})" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                                </svg>
                            </button>
                            <button wire:click="confirmDelete({{ $category->id }})"
                                class="p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors"
                                title="Hapus">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col items-center gap-2">
                            <i data-lucide="search-x" class="w-10 h-10 text-slate-300 dark:text-slate-600"></i>
                            <p>Tidak ada kategori ditemukan.</p>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $categories->links() }}
            </x-slot>
        </x-admin.table>
    </x-admin.card>

    <!-- Create/Edit Modal -->
    <x-admin.modal name="category-modal" title="{{ $isEditMode ? 'Edit Kategori' : 'Tambah Kategori' }}" icon="tag">
        <form wire:submit.prevent="save" class="relative space-y-4">
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

            <x-admin.input label="Nama Kategori" wire:model="name" placeholder="Masukan nama kategori..."
                error="{{ $errors->first('name') }}" />

            <div class="flex justify-end gap-3 pt-4">
                <x-admin.button type="button" variant="secondary" x-on:click="show = false"
                    wire:loading.attr="disabled" wire:target="save"
                    class="disabled:pointer-events-none disabled:opacity-60">
                    Batal
                </x-admin.button>
                <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save"
                    class="disabled:pointer-events-none disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">
                        {{ $isEditMode ? 'Simpan Perubahan' : 'Tambah Kategori' }}
                    </span>
                    <span wire:loading.flex wire:target="save" class="items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                        </svg>
                        Memproses...
                    </span>
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Delete Confirmation Modal -->
    <x-admin.modal name="delete-modal" title="Hapus Kategori" icon="trash-2">
        <div class="text-center p-4">
            <p class="text-slate-600 dark:text-slate-400 mb-6">Apakah Anda yakin ingin menghapus kategori ini? Tindakan
                ini tidak dapat dibatalkan.</p>
            <div class="flex justify-center gap-3">
                <x-admin.button variant="secondary" x-on:click="show = false">
                    Batal
                </x-admin.button>
                <x-admin.button variant="danger" wire:click="delete">
                    Ya, Hapus
                </x-admin.button>
            </div>
        </div>
    </x-admin.modal>
</div>
