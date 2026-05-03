<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Terms & Conditions</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Kelola syarat dan ketentuan layanan Gotik.</p>
        </div>
        <div>
            <x-admin.button wire:click="create" variant="primary" icon="plus">
                Tambah Term
            </x-admin.button>
        </div>
    </div>

    <!-- Alert Session -->
    @if (session()->has('success'))
        <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl relative mb-6 flex items-center gap-3" role="alert">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span class="block sm:inline text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Table Section -->
    <x-admin.card title="Daftar Syarat & Ketentuan" icon="file-text">
        <div class="mb-4">
            <x-admin.input wire:model.live="search" placeholder="Cari judul..." icon="search" />
        </div>

        <x-admin.table :headers="['Judul', 'Isi Deskripsi', 'Terakhir Diubah', 'Aksi']">
            @forelse($terms as $item)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $item->title }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 max-w-md">
                            {{ strip_tags($item->term) }}
                        </p>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $item->updated_at->format('d M Y, H:i') }}</span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                        <button wire:click="edit('{{ $item->uid }}')" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <button wire:click="confirmDelete('{{ $item->uid }}')" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-5 py-10 text-center">
                        <div class="flex flex-col items-center justify-center text-slate-400">
                            <i data-lucide="file-warning" class="w-12 h-12 mb-2 opacity-20"></i>
                            <p class="text-sm">Tidak ada data ditemukan.</p>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $terms->links() }}
            </x-slot>
        </x-admin.table>
    </x-admin.card>

    <!-- Modal Form -->
    <x-admin.modal name="term-modal" title="{{ $isEdit ? 'Edit Term and Condition' : 'Tambah Term and Condition' }}">
        <form wire:submit.prevent="save" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Judul Term</label>
                <x-admin.input wire:model="title" placeholder="Masukkan judul..." />
                @error('title') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Isi Ketentuan</label>
                <textarea wire:model="term" rows="8" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 dark:text-white placeholder-slate-400 transition-all duration-200" placeholder="Tulis rincian ketentuan di sini..."></textarea>
                @error('term') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <x-admin.button type="button" x-on:click="$dispatch('close-modal', {name: 'term-modal'})" variant="secondary">
                    Batal
                </x-admin.button>
                <x-admin.button type="submit" variant="primary">
                    {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Baru' }}
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Delete Confirmation Modal -->
    <x-admin.modal-delete name="delete-modal" title="Hapus Ketentuan?" description="Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan." />

</div>
