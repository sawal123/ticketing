<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight">{{ $title }}</h1>
            <p class="text-slate-500 dark:text-slate-400">Configure your event details and publishing settings below.</p>
        </div>
        <div>
            <x-admin.button href="{{ auth()->user()->role === 'admin' ? route('admin.event') : route('dashboard.event') }}" wire:navigate variant="secondary" icon="arrow-left" class="!py-2 !px-4">
                Kembali
            </x-admin.button>
        </div>
    </div>

    @if (session()->has('message'))
        <div
            class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-500/20 flex items-center gap-3 text-emerald-600 dark:text-emerald-400 animate-in fade-in slide-in-from-top-4 duration-500">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span class="text-sm font-semibold">{{ session('message') }}</span>
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">
        <x-admin.card padding="p-8">
            <div class="space-y-10">
                <section class="space-y-6">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 dark:text-white">Informasi Event</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Isi informasi dasar event dan kategori yang sesuai.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-1">
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Nama Event</label>
                            <x-admin.input wire:model="event" placeholder="Contoh: Festival Musik Akhir Tahun" required />
                            @error('event') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Kategori Event</label>
                            <select wire:model="category_id"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all"
                                required>
                                <option value="">Pilih kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Fasilitas</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($fasilitasData as $f)
                                    <label class="cursor-pointer">
                                        <input type="checkbox" wire:model="selectedFasilitas" value="{{ $f->id }}"
                                            class="sr-only peer">
                                        <div
                                            class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-500 dark:text-slate-400 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition-all">
                                            {{ $f->name }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-6 border-t border-slate-100 dark:border-slate-700 pt-8">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 dark:text-white">Informasi Penyelenggara</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Data penyelenggara ini disimpan per event dan tidak mengikuti perubahan profil akun.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Nama Penyelenggara</label>
                            <x-admin.input wire:model="organizer_name" placeholder="Contoh: PT Event Nusantara" required />
                            @error('organizer_name') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Nama Penanggung Jawab</label>
                            <x-admin.input wire:model="responsible_name" placeholder="Contoh: Sawalinto" required />
                            @error('responsible_name') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Jabatan Penanggung Jawab</label>
                            <x-admin.input wire:model="responsible_position" placeholder="Contoh: Project Manager" required />
                            @error('responsible_position') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Nomor HP</label>
                            <x-admin.input wire:model="phone" placeholder="08xxxxxxxxxx" required />
                            @error('phone') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Email</label>
                            <x-admin.input type="email" wire:model="email" placeholder="penyelenggara@example.com" required />
                            @error('email') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Alamat Penyelenggara</label>
                            <x-admin.input wire:model="address" placeholder="Alamat lengkap penyelenggara" required />
                            @error('address') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </section>

                <section class="space-y-6 border-t border-slate-100 dark:border-slate-700 pt-8">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 dark:text-white">Jadwal Event</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Pastikan urutan waktu penjualan dan pelaksanaan event sudah benar.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div wire:ignore>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Mulai Penjualan Tiket</label>
                            <div class="relative" x-data
                                x-init="flatpickr($refs.startSale, { enableTime: true, dateFormat: 'Y-m-d H:i', time_24hr: true })">
                                <x-admin.input x-ref="startSale" wire:model="start_sale" placeholder="YYYY-MM-DD HH:MM"
                                    icon="calendar" required />
                            </div>
                            @error('start_sale') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div wire:ignore>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Tanggal &amp; Waktu Mulai Event</label>
                            <div class="relative" x-data
                                x-init="flatpickr($refs.eventStart, { enableTime: true, dateFormat: 'Y-m-d H:i', time_24hr: true })">
                                <x-admin.input x-ref="eventStart" wire:model="event_start" placeholder="YYYY-MM-DD HH:MM"
                                    icon="calendar" required />
                            </div>
                            @error('event_start') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div wire:ignore>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Tanggal &amp; Waktu Selesai Event</label>
                            <div class="relative" x-data
                                x-init="flatpickr($refs.eventEnd, { enableTime: true, dateFormat: 'Y-m-d H:i', time_24hr: true })">
                                <x-admin.input x-ref="eventEnd" wire:model="event_end" placeholder="YYYY-MM-DD HH:MM"
                                    icon="calendar" required />
                            </div>
                            @error('event_end') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </section>

                <section class="space-y-6 border-t border-slate-100 dark:border-slate-700 pt-8">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 dark:text-white">Rekening Pencairan Event</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Rekening ini disimpan khusus untuk event yang sedang Anda buat atau edit.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Nama Bank</label>
                            <x-admin.input wire:model="bank_name" placeholder="Contoh: Bank Central Asia" required />
                            @error('bank_name') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Nomor Rekening</label>
                            <x-admin.input wire:model="account_number" placeholder="Contoh: 1234567890" required />
                            @error('account_number') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Nama Pemilik Rekening</label>
                            <x-admin.input wire:model="account_holder_name" placeholder="Contoh: PT Event Nusantara" required />
                            @error('account_holder_name') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Buku Rekening</label>
                            <input type="file" wire:model="bank_book" accept=".pdf,image/jpeg,image/png"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                            @if ($existingBankBookOriginalName)
                                <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                    File saat ini: {{ $existingBankBookOriginalName }}
                                </div>
                            @endif
                            <div wire:loading wire:target="bank_book" class="text-indigo-600 text-xs font-semibold mt-2">
                                Mengupload buku rekening...
                            </div>
                            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">Format yang diterima: PDF, JPG, JPEG, PNG. Maksimal 5 MB.</div>
                            @error('bank_book') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </section>

                <section class="space-y-6 border-t border-slate-100 dark:border-slate-700 pt-8">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 dark:text-white">Dokumen Penyelenggara</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Surat penyelenggara event atau seminar disimpan per event dan tetap private.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Nomor Surat</label>
                            <x-admin.input wire:model="document_number" placeholder="Contoh: 001/SP-EVENT/VIII/2026" required />
                            @error('document_number') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div wire:ignore>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Tanggal Surat</label>
                            <div class="relative" x-data
                                x-init="flatpickr($refs.documentDate, { dateFormat: 'Y-m-d' })">
                                <x-admin.input x-ref="documentDate" wire:model="document_date" placeholder="YYYY-MM-DD"
                                    icon="calendar" required />
                            </div>
                            @error('document_date') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Surat Penyelenggara Event/Seminar</label>
                            <input type="file" wire:model="organizer_letter" accept=".pdf,image/jpeg,image/png"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                            @if ($existingOrganizerLetterOriginalName)
                                <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                    File saat ini: {{ $existingOrganizerLetterOriginalName }}
                                </div>
                            @endif
                            <div wire:loading wire:target="organizer_letter" class="text-indigo-600 text-xs font-semibold mt-2">
                                Mengupload surat penyelenggara...
                            </div>
                            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">Format yang diterima: PDF, JPG, JPEG, PNG. Maksimal 5 MB.</div>
                            @error('organizer_letter') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">KTP / Identitas Penanggung Jawab</label>
                            <input type="file" wire:model="responsible_identity" accept=".pdf,image/jpeg,image/png"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                            @if ($existingResponsibleIdentityOriginalName)
                                <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                    File saat ini: {{ $existingResponsibleIdentityOriginalName }}
                                </div>
                            @endif
                            <div wire:loading wire:target="responsible_identity" class="text-indigo-600 text-xs font-semibold mt-2">
                                Mengupload identitas penanggung jawab...
                            </div>
                            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">Format yang diterima: PDF, JPG, JPEG, PNG. Maksimal 5 MB.</div>
                            @error('responsible_identity') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </section>

                <section class="space-y-6 border-t border-slate-100 dark:border-slate-700 pt-8">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 dark:text-white">Lokasi Event</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Gunakan detail venue yang lengkap agar menjadi sumber data event yang rapi.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Nama Venue</label>
                            <x-admin.input wire:model="venue_name" placeholder="Contoh: Istora Senayan" icon="map-pin" required />
                            @error('venue_name') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Alamat Venue</label>
                            <x-admin.input wire:model="venue_address" placeholder="Contoh: Jl. Pintu Satu Senayan" icon="map-pin" required />
                            @error('venue_address') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Kota/Kabupaten</label>
                            <x-admin.input wire:model="venue_city" placeholder="Contoh: Jakarta Pusat" required />
                            @error('venue_city') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Provinsi</label>
                            <x-admin.input wire:model="venue_province" placeholder="Contoh: DKI Jakarta" required />
                            @error('venue_province') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Link Google Maps</label>
                            <x-admin.input wire:model="map" placeholder="https://maps.google.com/..." icon="link" />
                            @error('map') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </section>

                <section class="space-y-6 border-t border-slate-100 dark:border-slate-700 pt-8">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 dark:text-white">Pajak</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Pajak ini dibebankan ke pembeli tiket dan tetap disimpan pada field pajak event existing.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Pajak Tiket (%)</label>
                            <div class="relative">
                                <x-admin.input type="number" wire:model="fee" min="0" max="100" placeholder="15" />
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <span class="text-slate-400 font-bold">%</span>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">Pajak yang dibebankan ke pembeli.</div>
                            @error('fee') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </section>

                <section class="space-y-6 border-t border-slate-100 dark:border-slate-700 pt-8">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 dark:text-white">Cover &amp; Deskripsi</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Lengkapi visual event dan deskripsi utama untuk halaman event.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Cover Event</label>
                            <div x-data="{ isDragging: false }" @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                                class="relative w-full aspect-[16/5] border-2 border-dashed rounded-3xl flex flex-col items-center justify-center transition-all duration-300"
                                :class="isDragging ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50'">
                                @if ($cover)
                                    <img src="{{ $cover->temporaryUrl() }}"
                                        class="absolute inset-0 w-full h-full object-cover rounded-3xl">
                                    <div
                                        class="absolute inset-0 bg-black/40 rounded-3xl flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                        <x-admin.button type="button" @click="$refs.fileInput.click()" variant="secondary"
                                            size="sm">Change Image</x-admin.button>
                                    </div>
                                @elseif ($existingCover)
                                    <img src="{{ asset('storage/cover/' . $existingCover) }}"
                                        class="absolute inset-0 w-full h-full object-cover rounded-3xl">
                                    <div
                                        class="absolute inset-0 bg-black/40 rounded-3xl flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                        <x-admin.button type="button" @click="$refs.fileInput.click()" variant="secondary"
                                            size="sm">Change Image</x-admin.button>
                                    </div>
                                @else
                                    <div class="text-center p-8">
                                        <div
                                            class="w-16 h-16 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-center mx-auto mb-4">
                                            <i data-lucide="upload-cloud" class="w-8 h-8 text-indigo-600"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-1">Click or drag and drop to upload</h3>
                                        <p class="text-sm text-slate-500">PNG, JPG or WEBP (Recommended 1600x900px)</p>
                                    </div>
                                @endif
                                <input type="file" x-ref="fileInput" wire:model="cover" accept="image/png,image/jpeg,image/webp"
                                    class="absolute inset-0 opacity-0 cursor-pointer">
                            </div>
                            <div wire:loading wire:target="cover" class="text-indigo-600 text-xs font-semibold mt-2">
                                Mengupload cover...
                            </div>
                            @error('cover') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Deskripsi Event</label>
                            <div wire:ignore x-data="{
                                init() {
                                    const trixEditor = this.$refs.trix;
                                    trixEditor.addEventListener('trix-change', () => {
                                        @this.set('deskripsi', trixEditor.value);
                                    });
                                }
                            }">
                                <input id="deskripsi" type="hidden" name="content" value="{{ $deskripsi }}">
                                <trix-editor input="deskripsi" x-ref="trix"
                                    class="trix-content min-h-[300px] rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 focus:ring-2 focus:ring-indigo-500 transition-all"></trix-editor>
                            </div>
                            @error('deskripsi') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </section>
            </div>

            <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-100 dark:border-slate-700">
                <x-admin.button type="button" onclick="history.back()" variant="ghost">Cancel</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="check-circle" loadingTarget="cover,save"
                    class="shadow-lg shadow-indigo-200 dark:shadow-none">
                    {{ $editingEventUid ? 'Update Event' : 'Submit Event For Approval' }}
                </x-admin.button>
            </div>
        </x-admin.card>
    </form>

    <!-- Trix Editor Assets -->
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.8/dist/trix.css">
    <script type="text/javascript" src="https://unpkg.com/trix@2.0.8/dist/trix.umd.min.js"></script>
    <style>
        .trix-button-group--file-tools {
            display: none !important;
        }

        .trix-content {
            font-family: inherit;
        }
    </style>
</div>
