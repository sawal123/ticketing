<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Master Fasilitas</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Kelola daftar fasilitas yang tersedia untuk event.</p>
        </div>
        <x-admin.button wire:click="openCreateModal" icon="plus" variant="primary">
            Tambah Fasilitas
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
                <x-admin.input wire:model.live.debounce.300ms="search" placeholder="Cari nama fasilitas..." icon="search" />
            </div>
        </div>

        <x-admin.table :headers="['Ikon', 'Nama Fasilitas', 'Aksi']">
            @forelse($fasilitas as $item)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden">
                            @if($item->icon && Storage::disk('public')->exists($item->icon))
                                <img src="{{ asset('storage/' . $item->icon) }}" class="w-full h-full object-contain p-1" alt="{{ $item->name }}">
                            @else
                                <i data-lucide="zap" class="w-5 h-5 text-slate-400"></i>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $item->name }}</div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center gap-2">
                            <button wire:click="openEditModal({{ $item->id }})"
                                wire:loading.attr="disabled"
                                wire:target="openEditModal({{ $item->id }})"
                                class="p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-xl transition-colors disabled:pointer-events-none disabled:opacity-60"
                                title="Edit">
                                <i wire:loading.remove wire:target="openEditModal({{ $item->id }})" data-lucide="edit-3" class="w-4 h-4"></i>
                                <svg wire:loading wire:target="openEditModal({{ $item->id }})" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                                </svg>
                            </button>
                            <button wire:click="confirmDelete({{ $item->id }})"
                                class="p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors"
                                title="Hapus">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col items-center gap-2">
                            <i data-lucide="search-x" class="w-10 h-10 text-slate-300 dark:text-slate-600"></i>
                            <p>Tidak ada fasilitas ditemukan.</p>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $fasilitas->links() }}
            </x-slot>
        </x-admin.table>
    </x-admin.card>

    <!-- Create/Edit Modal -->
    <x-admin.modal name="fasilitas-modal" title="{{ $isEditMode ? 'Edit Fasilitas' : 'Tambah Fasilitas' }}" icon="sparkles">
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

            <x-admin.input label="Nama Fasilitas" wire:model="name" placeholder="Masukan nama fasilitas..."
                error="{{ $errors->first('name') }}" />
            
            <div class="w-full">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
                    Upload Ikon (SVG/PNG)
                </label>
                <div class="relative group">
                    <input type="file" wire:model="icon_file" id="icon_file" class="hidden" accept=".png,.jpg,.jpeg,.svg">
                    <label for="icon_file" class="flex flex-col items-center justify-center w-full h-32 px-4 transition bg-slate-50 dark:bg-slate-900/50 border-2 border-slate-200 dark:border-slate-700 border-dashed rounded-2xl cursor-pointer hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 hover:border-indigo-400 dark:hover:border-indigo-500 transition-all duration-200">
                        @if($icon_file)
                            <div class="flex flex-col items-center gap-2">
                                <img src="{{ $icon_file->temporaryUrl() }}" class="w-12 h-12 object-contain">
                                <span class="text-xs text-indigo-600 font-medium">{{ $icon_file->getClientOriginalName() }}</span>
                            </div>
                        @elseif($isEditMode && $icon)
                            <div class="flex flex-col items-center gap-2">
                                <img src="{{ asset('storage/' . $icon) }}" class="w-12 h-12 object-contain">
                                <span class="text-[10px] text-slate-500 italic">Klik untuk ganti ikon</span>
                            </div>
                        @else
                            <div class="flex flex-col items-center gap-2">
                                <i data-lucide="upload-cloud" class="w-8 h-8 text-slate-400 group-hover:text-indigo-500"></i>
                                <span class="text-xs text-slate-500 group-hover:text-indigo-600">Klik atau drop file SVG/PNG</span>
                                <span class="text-[10px] text-slate-400">Max size: 1MB</span>
                            </div>
                        @endif
                    </label>
                </div>
                @error('icon_file') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <x-admin.button type="button" variant="secondary" x-on:click="show = false"
                    wire:loading.attr="disabled" wire:target="save"
                    class="disabled:pointer-events-none disabled:opacity-60">
                    Batal
                </x-admin.button>
                <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save"
                    class="disabled:pointer-events-none disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">
                        {{ $isEditMode ? 'Simpan Perubahan' : 'Tambah Fasilitas' }}
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
    <x-admin.modal name="delete-modal" title="Hapus Fasilitas" icon="trash-2">
        <div class="text-center p-4">
            <p class="text-slate-600 dark:text-slate-400 mb-6">Apakah Anda yakin ingin menghapus fasilitas ini? Tindakan
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
