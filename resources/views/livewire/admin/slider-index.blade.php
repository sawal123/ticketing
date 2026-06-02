<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Home Slider</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Kelola banner promosi dan pengumuman di halaman depan.</p>
        </div>
        <div>
            <x-admin.button wire:click="create" variant="primary" icon="plus">
                Tambah Slide
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
    <x-admin.card title="Daftar Slider" icon="image">
        <div class="mb-4">
            <x-admin.input wire:model.live="search" placeholder="Cari judul slide..." icon="search" />
        </div>

        <x-admin.table :headers="['No', 'Preview', 'Judul & Link', 'Urutan', 'Aksi']">
            @forelse($sliders as $item)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-500 font-medium">
                        {{ ($sliders->currentPage() - 1) * $sliders->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="w-32 aspect-[21/9] rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600">
                            <img src="{{ asset('storage/slide/' . $item->gambar) }}" class="w-full h-full object-cover" alt="{{ $item->title }}">
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $item->title }}</span>
                            @if($item->url)
                                <a href="{{ $item->url }}" target="_blank" class="text-[10px] text-indigo-500 hover:underline flex items-center gap-1 mt-0.5">
                                    <i data-lucide="external-link" class="w-2.5 h-2.5"></i>
                                    {{ Str::limit($item->url, 40) }}
                                </a>
                            @else
                                <span class="text-[10px] text-slate-400 mt-0.5 italic">Tidak ada link</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded text-xs font-bold border border-slate-200 dark:border-slate-600">
                            #{{ $item->sort }}
                        </span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium space-x-1">
                        <div class="flex items-center justify-end gap-1">
                            <div class="flex flex-col gap-1">
                                <button wire:click="moveUp('{{ $item->uid }}')" class="p-1 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="Naikkan">
                                    <i data-lucide="chevron-up" class="w-3.5 h-3.5"></i>
                                </button>
                                <button wire:click="moveDown('{{ $item->uid }}')" class="p-1 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="Turunkan">
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                            <div class="h-8 w-px bg-slate-100 dark:bg-slate-700 mx-1"></div>
                            <button wire:click="edit('{{ $item->uid }}')"
                                wire:loading.attr="disabled"
                                wire:target="edit('{{ $item->uid }}')"
                                class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors disabled:pointer-events-none disabled:opacity-60" title="Edit">
                                <i wire:loading.remove wire:target="edit('{{ $item->uid }}')" data-lucide="edit-3" class="w-4 h-4"></i>
                                <svg wire:loading wire:target="edit('{{ $item->uid }}')" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                                </svg>
                            </button>
                            <button wire:click="confirmDelete('{{ $item->uid }}')" class="p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="Hapus">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-10 text-center">
                        <div class="flex flex-col items-center justify-center text-slate-400">
                            <i data-lucide="image-off" class="w-12 h-12 mb-2 opacity-20"></i>
                            <p class="text-sm">Belum ada slide yang ditambahkan.</p>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $sliders->links() }}
            </x-slot>
        </x-admin.table>
    </x-admin.card>

    <!-- Modal Form -->
    <x-admin.modal name="slider-modal" title="{{ $isEdit ? 'Edit Slide' : 'Tambah Slide Baru' }}">
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Judul Slide</label>
                    <x-admin.input wire:model="title" placeholder="Masukkan judul..." />
                    @error('title') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Urutan (Sort)</label>
                    <x-admin.input type="number" wire:model="sort" placeholder="1, 2, 3..." />
                    @error('sort') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">URL Tujuan (Opsional)</label>
                    <x-admin.input wire:model="url" placeholder="https://..." />
                    @error('url') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Gambar Slider</label>
                    <div class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 transition-colors relative">
                        @if ($new_gambar)
                            <img src="{{ $new_gambar->temporaryUrl() }}" class="max-h-48 rounded-lg shadow-md mb-4 object-cover">
                        @elseif ($gambar)
                            <img src="{{ asset('storage/slider/' . $this->gambar) }}" class="max-h-48 rounded-lg shadow-md mb-4 object-cover">
                        @else
                            <i data-lucide="upload-cloud" class="w-12 h-12 text-slate-300 mb-2"></i>
                        @endif
                        
                        <input type="file" wire:model="new_gambar" id="slider-upload" class="hidden" accept="image/*">
                        <label for="slider-upload" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold cursor-pointer hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-200 dark:shadow-none">
                            {{ ($new_gambar || $gambar) ? 'Ganti Gambar' : 'Pilih Gambar' }}
                        </label>
                        <p class="text-[10px] text-slate-400 mt-2 italic">Rekomendasi ukuran: 1920x800 px (Max 2MB)</p>
                        
                        <div wire:loading wire:target="new_gambar" class="absolute inset-0 bg-white/80 dark:bg-slate-900/80 rounded-2xl flex items-center justify-center">
                            <i data-lucide="loader-2" class="w-8 h-8 animate-spin text-indigo-600"></i>
                        </div>
                    </div>
                    @error('new_gambar') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <x-admin.button type="button" x-on:click="$dispatch('close-modal', {name: 'slider-modal'})" variant="secondary"
                    wire:loading.attr="disabled" wire:target="save"
                    class="disabled:pointer-events-none disabled:opacity-60">
                    Batal
                </x-admin.button>
                <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save"
                    class="disabled:pointer-events-none disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">{{ $isEdit ? 'Simpan Perubahan' : 'Tambah Baru' }}</span>
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
    <x-admin.modal-delete name="delete-modal" title="Hapus Slide?" description="Apakah Anda yakin ingin menghapus slide ini? Gambar yang tersimpan juga akan dihapus dari server." />

</div>
