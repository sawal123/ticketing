<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Manajemen Partner</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Kelola data partner dan kolaborator event Anda.</p>
        </div>
        <x-admin.button wire:click="openCreateModal" icon="plus" variant="primary">
            Tambah Partner
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
        <x-admin.card title="Total Partner" icon="users" iconColor="indigo">
            <div class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ $totalPartners }}
            </div>
        </x-admin.card>
        <x-admin.card title="Partner Aktif" icon="check-circle" iconColor="emerald">
            <div class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ $activePartners }}
            </div>
        </x-admin.card>
        <x-admin.card title="Kota Terjangkau" icon="map-pin" iconColor="amber">
            <div class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ $totalCities }}
            </div>
        </x-admin.card>
    </div>

    <!-- Filter & Table Section -->
    <x-admin.card>
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <x-admin.input wire:model.live.debounce.300ms="search" placeholder="Cari nama, email, atau referensi..."
                    icon="search" />
            </div>
            <div class="w-full md:w-64">
                <select wire:model.live="event_uid"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                    <option value="">Semua Event</option>
                    @foreach($events as $event)
                        <option value="{{ $event->uid }}">{{ $event->event }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <x-admin.table :headers="['Referensi', 'Nama Partner', 'Event', 'Kontak', 'Lokasi', 'Status', 'Aksi']">
            @forelse($partners as $partner)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span
                            class="font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded text-xs">
                            {{ $partner->referensi }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $partner->name }}</div>
                        <div class="text-xs text-slate-500">{{ $partner->email }}</div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded inline-block">
                            {{ $partner->event->event ?? 'Global' }}
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                            <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                            {{ $partner->hp }}
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="text-sm text-slate-800 dark:text-slate-200">{{ $partner->city }}</div>
                        <div class="text-[10px] text-slate-500 truncate max-w-[150px]">{{ $partner->alamat }}</div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <x-admin.toggle 
                            wire:click="toggleStatus({{ $partner->id }})"
                            :active="$partner->status === 'active'"
                            :label="$partner->status === 'active' ? 'Aktif' : 'Nonaktif'"
                        />
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center gap-2">
                            <button wire:click="openEditModal({{ $partner->id }})"
                                class="p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-xl transition-colors"
                                title="Edit">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button wire:click="confirmDelete({{ $partner->id }})"
                                class="p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors"
                                title="Hapus">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col items-center gap-2">
                            <i data-lucide="search-x" class="w-10 h-10 text-slate-300 dark:text-slate-600"></i>
                            <p>Tidak ada partner ditemukan.</p>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $partners->links() }}
            </x-slot>
        </x-admin.table>
    </x-admin.card>

    <!-- Create/Edit Modal -->
    <x-admin.modal name="partner-modal" title="{{ $isEditMode ? 'Edit Partner' : 'Tambah Partner' }}" icon="users" maxWidth="xl">
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-admin.input label="Nama Partner" wire:model="name" placeholder="Masukan nama.."
                    error="{{ $errors->first('name') }}" />
                
                <x-admin.input label="Email" type="email" wire:model="email" placeholder="Masukan email.."
                    error="{{ $errors->first('email') }}" />
            </div>

            <div class="w-full">
                <label
                    class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
                    Event (Opsional)
                </label>
                <select wire:model="selected_event_uid"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200">
                    <option value="">Global / Semua Event</option>
                    @foreach($events as $event)
                        <option value="{{ $event->uid }}">{{ $event->event }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="w-full">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
                        Kota
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 border-r border-slate-200 pr-3 dark:border-slate-600">
                            <i data-lucide="building-2" class="w-4 h-4"></i>
                        </div>
                        <input 
                            wire:model="city"
                            type="text"
                            placeholder="Choose City.."
                            class="w-full pl-12 pr-4 py-2.5 rounded-xl border {{ $errors->has('city') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-200 dark:border-slate-600' }} bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                        >
                    </div>
                    @error('city') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <x-admin.input label="Nomor HP" wire:model="hp" placeholder="Masukan nomor.."
                    error="{{ $errors->first('hp') }}" />
            </div>

            <x-admin.input label="Alamat" wire:model="alamat" placeholder="Masukan alamat.."
                error="{{ $errors->first('alamat') }}" />

            <div class="flex justify-end gap-3 pt-4">
                <x-admin.button type="button" variant="secondary" x-on:click="show = false">
                    Close
                </x-admin.button>
                <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        Simpan
                    </span>
                    <span wire:loading class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        Memproses...
                    </span>
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Delete Confirmation Modal -->
    <x-admin.modal name="delete-modal" title="Hapus Partner" icon="trash-2">
        <div class="text-center p-4">
            <p class="text-slate-600 dark:text-slate-400 mb-6">Apakah Anda yakin ingin menghapus partner ini? Tindakan
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
