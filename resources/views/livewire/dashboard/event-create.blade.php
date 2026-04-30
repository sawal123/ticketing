<div>
    <div class="mb-6">
        <h1 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight">{{ $title }}</h1>
        <p class="text-slate-500 dark:text-slate-400">Configure your event details and publishing settings below.</p>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        <x-admin.card padding="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <!-- Event Name -->
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Event Name</label>
                    <x-admin.input wire:model="event" placeholder="e.g. Annual Tech Leadership Summit 2024" required />
                </div>
                
                <!-- Event Tax -->
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Event Tax (%)</label>
                    <div class="relative">
                        <x-admin.input type="number" wire:model="pajak" placeholder="15" />
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <span class="text-slate-400 font-bold">%</span>
                        </div>
                    </div>
                </div>

                <!-- Start Date & Time -->
                <div class="md:col-span-1" wire:ignore>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Start Date & Time (Sale Start)</label>
                    <div class="relative" x-data x-init="flatpickr($refs.startSale, { enableTime: true, dateFormat: 'Y-m-d H:i', time_24hr: true })">
                        <x-admin.input x-ref="startSale" wire:model="start_sale" placeholder="YYYY-MM-DD HH:MM" icon="calendar" required />
                    </div>
                    @error('start_sale') <span class="text-rose-500 text-[10px] mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- End Date & Time -->
                <div class="md:col-span-1" wire:ignore>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">End Date & Time (Event Start)</label>
                    <div class="relative" x-data x-init="flatpickr($refs.tanggal, { enableTime: true, dateFormat: 'Y-m-d H:i', time_24hr: true })">
                        <x-admin.input x-ref="tanggal" wire:model="tanggal" placeholder="YYYY-MM-DD HH:MM" icon="calendar" required />
                    </div>
                    @error('tanggal') <span class="text-rose-500 text-[10px] mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Category -->
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Category</label>
                    <select wire:model="category_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Facilities -->
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Facilities</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($fasilitasData as $f)
                            <label class="cursor-pointer">
                                <input type="checkbox" wire:model="selectedFasilitas" value="{{ $f->id }}" class="sr-only peer">
                                <div class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-500 dark:text-slate-400 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition-all">
                                    {{ $f->name }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Venue Address -->
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Venue Address</label>
                    <x-admin.input wire:model="alamat" placeholder="123 Convention Plaza, San Francisco, CA" icon="map-pin" required />
                </div>

                <!-- Map Link -->
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Map Link (Google Maps)</label>
                    <x-admin.input wire:model="map" placeholder="https://goo.gl/maps/..." icon="link" />
                </div>

                <!-- Cover Thumbnail -->
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Cover Thumbnail</label>
                    <div 
                        x-data="{ isDragging: false }"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                        class="relative w-full aspect-[16/5] border-2 border-dashed rounded-3xl flex flex-col items-center justify-center transition-all duration-300"
                        :class="isDragging ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50'"
                    >
                        @if ($cover)
                            <img src="{{ $cover->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover rounded-3xl">
                            <div class="absolute inset-0 bg-black/40 rounded-3xl flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                <x-admin.button type="button" @click="$refs.fileInput.click()" variant="secondary" size="sm">Change Image</x-admin.button>
                            </div>
                        @elseif ($existingCover)
                            <img src="{{ asset('storage/cover/' . $existingCover) }}" class="absolute inset-0 w-full h-full object-cover rounded-3xl">
                            <div class="absolute inset-0 bg-black/40 rounded-3xl flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                <x-admin.button type="button" @click="$refs.fileInput.click()" variant="secondary" size="sm">Change Image</x-admin.button>
                            </div>
                        @else
                            <div class="text-center p-8">
                                <div class="w-16 h-16 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-center mx-auto mb-4">
                                    <i data-lucide="upload-cloud" class="w-8 h-8 text-indigo-600"></i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-1">Click or drag and drop to upload</h3>
                                <p class="text-sm text-slate-500">PNG, JPG or WEBP (Recommended 1600x900px)</p>
                            </div>
                        @endif
                        <input type="file" x-ref="fileInput" wire:model="cover" class="absolute inset-0 opacity-0 cursor-pointer">
                    </div>
                    @error('cover') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Event Description -->
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-2">Event Description</label>
                    <div wire:ignore x-data="{
                        init() {
                            const trixEditor = this.$refs.trix;
                            trixEditor.addEventListener('trix-change', () => {
                                @this.set('deskripsi', trixEditor.value);
                            });
                        }
                    }">
                        <input id="deskripsi" type="hidden" name="content" value="{{ $deskripsi }}">
                        <trix-editor input="deskripsi" x-ref="trix" class="trix-content min-h-[300px] rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 focus:ring-2 focus:ring-indigo-500 transition-all"></trix-editor>
                    </div>
                    @error('deskripsi') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-100 dark:border-slate-700">
                <x-admin.button type="button" onclick="history.back()" variant="ghost">Cancel</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="check-circle" class="shadow-lg shadow-indigo-200 dark:shadow-none">
                    {{ $editingEventUid ? 'Update Event' : 'Submit Event For Approval' }}
                </x-admin.button>
            </div>
        </x-admin.card>
    </form>

    <!-- Trix Editor Assets -->
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.8/dist/trix.css">
    <script type="text/javascript" src="https://unpkg.com/trix@2.0.8/dist/trix.umd.min.js"></script>
    <style>
        .trix-button-group--file-tools { display: none !important; }
        .trix-content { font-family: inherit; }
    </style>
</div>
