<div class="space-y-6">
    @if (!($modalOnly ?? false))
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Editor Marketing Guide</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">
                Perubahan disimpan ke <span class="font-semibold">draft</span>. Versi published tidak akan berubah sampai
                draft di-publish.
            </p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                <a href="{{ route('admin.marketing-guide') }}"
                    class="text-indigo-600 dark:text-indigo-400 hover:underline">Kembali ke halaman Marketing Guide</a>
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if ($published !== null)
                <span class="text-xs text-slate-500 dark:text-slate-400">Published v{{ $published->number }} #{{ $published->id }}</span>
            @endif
            @if ($draft === null)
                <x-admin.button wire:click="openEditor" variant="primary" icon="edit-3">
                    Siapkan Draft
                </x-admin.button>
            @else
                <span
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    <i data-lucide="check-circle" class="w-4 h-4"></i> Draft aktif v{{ $draft->number }} #{{ $draft->id }}
                </span>
                <x-admin.button :href="route('admin.marketing-guide.content.preview')" target="_blank" rel="noopener noreferrer"
                    variant="secondary" icon="eye">
                    Preview Draft
                </x-admin.button>
                <x-admin.button wire:click="publishDraft" wire:confirm="Publish draft ini? Versi published saat ini akan diarsipkan."
                    loadingTarget="publishDraft" variant="primary" icon="upload">
                    Publish
                </x-admin.button>
            @endif
        </div>
    </div>

    @if (session()->has('success') || $successMessage !== '')
        <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center gap-3"
            role="alert">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span class="text-sm font-medium">{{ $successMessage !== '' ? $successMessage : session('success') }}</span>
        </div>
    @endif

    @if ($errorMessage !== '')
        <div class="bg-rose-100 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl flex items-center gap-3"
            role="alert">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <span class="text-sm font-medium">{{ $errorMessage }}</span>
        </div>
    @endif

    @if ($draft === null)
        <x-admin.card title="Belum ada draft" icon="info">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Tekan tombol "Siapkan Draft" untuk meng-clone versi published ke draft baru. Versi published tidak akan
                berubah sampai draft di-publish.
            </p>
        </x-admin.card>
    @else
        @forelse ($grouped as $groupName => $groupSections)
            <x-admin.card>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                        {{ $groupName }}
                    </h2>
                    <span class="text-xs text-slate-400">{{ $groupSections->count() }} section</span>
                </div>
                <div class="space-y-4">
                    @foreach ($groupSections as $section)
                        @php
                            $blockCount = $section->blocks->count();
                            $activeBlocks = $section->blocks->where('is_active', true)->count();
                        @endphp
                        <div
                            class="border border-slate-200 dark:border-slate-700 rounded-2xl p-4 bg-slate-50/50 dark:bg-slate-800/40">
                            <div class="flex items-start justify-between gap-3 flex-wrap">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-bold dark:bg-indigo-900/40 dark:text-indigo-300">
                                            #{{ $section->position }}
                                        </span>
                                        <h3 class="text-base font-semibold text-slate-800 dark:text-white truncate">
                                            {{ $section->title }}
                                        </h3>
                                        <code class="text-[10px] text-slate-400 font-mono">{{ $section->key }}</code>
                                        @if (!$section->is_active)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">nonaktif</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        {{ $activeBlocks }} dari {{ $blockCount }} block aktif
                                    </p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button wire:click="moveSectionUp({{ $section->id }})"
                                        wire:loading.attr="disabled" wire:target="moveSectionUp({{ $section->id }})"
                                        class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                                        title="Naikkan urutan">
                                        <i data-lucide="chevron-up" class="w-4 h-4"></i>
                                    </button>
                                    <button wire:click="moveSectionDown({{ $section->id }})"
                                        wire:loading.attr="disabled" wire:target="moveSectionDown({{ $section->id }})"
                                        class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                                        title="Turunkan urutan">
                                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                    </button>
                                    <button wire:click="$dispatchTo('admin.marketing-guide-content-modal', 'mge-open-section-editor', { sectionId: {{ $section->id }} })"
                                        class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
                                        title="Edit section">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button wire:click="toggleSectionActive({{ $section->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleSectionActive({{ $section->id }})"
                                        class="p-1.5 rounded-lg transition-colors {{ $section->is_active ? 'text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20' : 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20' }}"
                                        title="{{ $section->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <i data-lucide="{{ $section->is_active ? 'eye-off' : 'eye' }}"
                                            class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-4 space-y-2">
                                @foreach ($section->blocks as $block)
                                    <div
                                        class="flex items-start justify-between gap-3 p-3 rounded-xl bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span
                                                    class="inline-flex items-center justify-center w-6 h-6 rounded text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                                    #{{ $block->position }}
                                                </span>
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 uppercase tracking-wide">
                                                    {{ $block->type }}
                                                </span>
                                                @if (!$block->is_active)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">nonaktif</span>
                                                @endif
                                            </div>
                                            <p
                                                class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-mono break-all line-clamp-2">
                                                {{ Str::limit(json_encode($block->data, JSON_UNESCAPED_UNICODE), 200) }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <button wire:click="moveBlockUp({{ $block->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="moveBlockUp({{ $block->id }})"
                                                class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                                title="Naikkan urutan">
                                                <i data-lucide="chevron-up" class="w-3.5 h-3.5"></i>
                                            </button>
                                            <button wire:click="moveBlockDown({{ $block->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="moveBlockDown({{ $block->id }})"
                                                class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                                title="Turunkan urutan">
                                                <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                                            </button>
                                            <button wire:click="$dispatchTo('admin.marketing-guide-content-modal', 'mge-open-block-editor', { blockId: {{ $block->id }} })"
                                                class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
                                                title="Edit block">
                                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                            </button>
                                            <button wire:click="removeBlock({{ $block->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="removeBlock({{ $block->id }})"
                                                onclick="return confirm('Nonaktifkan block ini di draft?')"
                                                class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors"
                                                title="Nonaktifkan block">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                                <button wire:click="$dispatchTo('admin.marketing-guide-content-modal', 'mge-open-add-block', { sectionId: {{ $section->id }} })"
                                    class="w-full flex items-center justify-center gap-2 p-2.5 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 text-slate-500 dark:text-slate-400 hover:border-indigo-400 hover:text-indigo-600 dark:hover:border-indigo-500 dark:hover:text-indigo-400 transition-colors text-xs font-semibold">
                                    <i data-lucide="plus" class="w-4 h-4"></i> Tambah block
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin.card>
        @empty
            <x-admin.card>
                <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-6">Belum ada section pada draft.
                </p>
            </x-admin.card>
        @endforelse
    @endif
        <livewire:admin.marketing-guide-content-modal />
    @else
    <x-admin.modal name="mge-section-modal" title="Edit Section">
        <form wire:submit.prevent="saveSection" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Title</label>
                <x-admin.input wire:model="sectionTitle" placeholder="Judul section" />
                @error('sectionTitle')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Nav
                    Group</label>
                <x-admin.input wire:model="sectionNavGroup" placeholder="Contoh: MENGENAL GOTIK" />
                <p class="mt-1 text-[10px] text-slate-400 ml-1">Grouping pada sidebar publik. Maks 64 karakter.</p>
                @error('sectionNavGroup')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label
                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Posisi</label>
                    <x-admin.input type="number" min="1" wire:model="sectionPosition" />
                    @error('sectionPosition')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-end pb-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="sectionIsActive"
                            class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm text-slate-700 dark:text-slate-300">Aktif di draft</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <x-admin.button type="button" x-on:click="$dispatch('close-modal', {name: 'mge-section-modal'})"
                    variant="secondary">Batal</x-admin.button>
                <x-admin.button type="submit" variant="primary">Simpan Section</x-admin.button>
            </div>
        </form>
    </x-admin.modal>
    <x-admin.modal name="mge-block-modal" title="{{ $editingBlockId ? 'Edit Block' : 'Tambah Block' }}">
        <form wire:submit.prevent="saveBlock" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Tipe
                        Block</label>
                    @if ($editingBlockId === null)
                        <select wire:change="switchBlockType($event.target.value)"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 dark:text-white">
                            @foreach ($blockTypes as $t)
                                <option value="{{ $t }}" @selected($blockType === $t)>{{ $t }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[10px] text-slate-400 ml-1">Tipe tidak dapat diubah setelah block dibuat.
                        </p>
                    @else
                        <div
                            class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-600 rounded-2xl text-slate-700 dark:text-slate-300 uppercase tracking-wide text-sm font-semibold">
                            {{ $blockType }}
                        </div>
                        <p class="mt-1 text-[10px] text-amber-600 dark:text-amber-400 ml-1">Tipe terkunci saat edit.
                            Hapus block lalu gunakan "Tambah Block" jika ingin tipe lain.</p>
                    @endif
                </div>
                <div class="flex items-end pb-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="blockIsActive"
                            class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm text-slate-700 dark:text-slate-300">Aktif di draft</span>
                    </label>
                </div>
            </div>
            <div x-data="mgeBlockEditor(@entangle('blockDataRaw'))" x-init="init($wire)" class="space-y-4">
                @if ($blockType === 'text')
                    <div class="space-y-3">
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Title</label>
                            <x-admin.input x-model="data.title" placeholder="Judul utama" />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Subtitle</label>
                            <x-admin.input x-model="data.subtitle" placeholder="Subtitle" />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Intro</label>
                            <textarea x-model="data.intro" rows="3"
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 dark:text-white"
                                placeholder="Teks intro section"></textarea>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Badge</label>
                            <x-admin.input x-model="data.badge" placeholder="Badge kecil di atas title" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="data.show_recipient"
                                    class="w-4 h-4 rounded border-slate-300 text-indigo-600" />
                                <span class="text-sm text-slate-700 dark:text-slate-300">Tampilkan nama penerima</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="data.show_expiry"
                                    class="w-4 h-4 rounded border-slate-300 text-indigo-600" />
                                <span class="text-sm text-slate-700 dark:text-slate-300">Tampilkan masa berlaku</span>
                            </label>
                        </div>
                        <div class="border-t border-slate-100 dark:border-slate-700 pt-3">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">
                                Call-to-Action</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] text-slate-400 uppercase mb-1">Label</label>
                                    <x-admin.input x-model="data.cta.label" placeholder="Mulai Sekarang" />
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 uppercase mb-1">Href</label>
                                    <x-admin.input x-model="data.cta.href" placeholder="#section atau https://..." />
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="block text-[10px] text-slate-400 uppercase mb-1">Icon</label>
                                <select x-model="data.cta.icon"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-xl text-slate-900 dark:text-white text-sm">
                                    <option value="">- tidak ada -</option>
                                    @foreach ($iconWhitelist as $icon)
                                        <option value="{{ $icon }}">{{ $icon }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @elseif ($blockType === 'placeholder')
                    <div class="space-y-3">
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Icon</label>
                            <select x-model="data.icon"
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 dark:text-white">
                                @foreach ($iconWhitelist as $icon)
                                    <option value="{{ $icon }}">{{ $icon }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Title</label>
                            <x-admin.input x-model="data.title" placeholder="Judul placeholder" />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Caption</label>
                            <textarea x-model="data.caption" rows="3"
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 dark:text-white"
                                placeholder="Caption"></textarea>
                        </div>
                    </div>
                @elseif ($blockType === 'stats')
                    <div class="space-y-3">
                        <p class="text-xs text-slate-500">Stat cards: nilai dan label pendek.</p>
                        <template x-for="(item, idx) in (data.stats || [])" :key="idx">
                            <div
                                class="flex items-center gap-2 p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/50">
                                <div class="flex items-center gap-0.5">
                                    <button type="button" @click="moveItemIn(data.stats, idx, -1)" title="Naikkan"
                                        class="p-1 rounded text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                        <i data-lucide="chevron-up" class="w-3 h-3"></i>
                                    </button>
                                    <button type="button" @click="moveItemIn(data.stats, idx, 1)" title="Turunkan"
                                        class="p-1 rounded text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                        <i data-lucide="chevron-down" class="w-3 h-3"></i>
                                    </button>
                                    <span class="text-xs font-mono text-slate-400 w-4 text-center"
                                        x-text="idx + 1"></span>
                                </div>
                                <div class="flex-1 grid grid-cols-2 gap-2">
                                    <x-admin.input x-model="item.value" placeholder="2.5 Juta" />
                                    <x-admin.input x-model="item.label" placeholder="Transaksi/Bulan" />
                                </div>
                                <button type="button" @click="data.stats.splice(idx, 1)"
                                    class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20"
                                    title="Hapus">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </template>
                        <button type="button"
                            @click="data.stats = data.stats || []; data.stats.push({value: \"\", label: \"\"})"
                            class="w-full flex items-center justify-center gap-2 p-2 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 text-slate-500 hover:border-indigo-400 hover:text-indigo-600 transition-colors text-xs font-semibold">
                            <i data-lucide="plus" class="w-4 h-4"></i> Tambah stat
                        </button>
                    </div>
                @elseif ($blockType === 'workflow' || $blockType === 'flow')
                    <div class="space-y-3">
                        <p class="text-xs text-slate-500">
                            {{ $blockType === 'workflow' ? 'Workflow step' : 'Flow box' }}: icon, judul, dan deskripsi.
                        </p>
                        <template x-for="(item, idx) in (data.steps || data.boxes || [])" :key="idx">
                            <div
                                class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/50 space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-0.5">
                                        <button type="button" @click="moveItemIn(data.steps || data.boxes, idx, -1)"
                                            title="Naikkan"
                                            class="p-1 rounded text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                            <i data-lucide="chevron-up" class="w-3 h-3"></i>
                                        </button>
                                        <button type="button" @click="moveItemIn(data.steps || data.boxes, idx, 1)"
                                            title="Turunkan"
                                            class="p-1 rounded text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                            <i data-lucide="chevron-down" class="w-3 h-3"></i>
                                        </button>
                                        <span class="text-xs font-mono text-slate-400" x-text="(idx + 1)"></span>
                                    </div>
                                    <button type="button" @click="(data.steps || data.boxes).splice(idx, 1)"
                                        class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20"
                                        title="Hapus">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-slate-400 uppercase mb-1">Icon</label>
                                        <select x-model="item.icon"
                                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-xl text-slate-900 dark:text-white text-sm">
                                            @foreach ($iconWhitelist as $icon)
                                                <option value="{{ $icon }}">{{ $icon }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] text-slate-400 uppercase mb-1">{{ $blockType === 'workflow' ? 'Title' : 'Label' }}</label>
                                        <x-admin.input
                                            x-model="{{ $blockType === 'workflow' ? 'item.title' : 'item.label' }}" />
                                    </div>
                                </div>
                                @if ($blockType === 'workflow')
                                    <div>
                                        <label
                                            class="block text-[10px] text-slate-400 uppercase mb-1">Description</label>
                                        <textarea x-model="item.description" rows="2"
                                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-xl text-slate-900 dark:text-white text-sm"></textarea>
                                    </div>
                                @endif
                            </div>
                        </template>
                        <button type="button" @click="addWorkflowItem()"
                            class="w-full flex items-center justify-center gap-2 p-2 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 text-slate-500 hover:border-indigo-400 hover:text-indigo-600 transition-colors text-xs font-semibold">
                            <i data-lucide="plus" class="w-4 h-4"></i> Tambah item
                        </button>
                    </div>
                @elseif ($blockType === 'cards')
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <label class="text-xs font-bold text-slate-500 uppercase">Kolom</label>
                            <select x-model.number="data.columns"
                                class="px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 rounded-xl text-slate-900 dark:text-white text-sm">
                                <option :value="2">2 kolom</option>
                                <option :value="3">3 kolom</option>
                            </select>
                        </div>
                        <p class="text-xs text-slate-500">Card: icon, judul, dan body.</p>
                        <template x-for="(item, idx) in (data.cards || [])" :key="idx">
                            <div
                                class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/50 space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-0.5">
                                        <button type="button" @click="moveItemIn(data.cards, idx, -1)"
                                            title="Naikkan"
                                            class="p-1 rounded text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                            <i data-lucide="chevron-up" class="w-3 h-3"></i>
                                        </button>
                                        <button type="button" @click="moveItemIn(data.cards, idx, 1)"
                                            title="Turunkan"
                                            class="p-1 rounded text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                            <i data-lucide="chevron-down" class="w-3 h-3"></i>
                                        </button>
                                        <span class="text-xs font-mono text-slate-400" x-text="(idx + 1)"></span>
                                    </div>
                                    <button type="button" @click="data.cards.splice(idx, 1)"
                                        class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20"
                                        title="Hapus">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-slate-400 uppercase mb-1">Icon</label>
                                        <select x-model="item.icon"
                                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-xl text-slate-900 dark:text-white text-sm">
                                            @foreach ($iconWhitelist as $icon)
                                                <option value="{{ $icon }}">{{ $icon }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-400 uppercase mb-1">Title</label>
                                        <x-admin.input x-model="item.title" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 uppercase mb-1">Body</label>
                                    <textarea x-model="item.body" rows="2"
                                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-xl text-slate-900 dark:text-white text-sm"></textarea>
                                </div>
                            </div>
                        </template>
                        <button type="button"
                            @click="data.cards = data.cards || []; data.cards.push({icon: "info-circle", title: "" ,
                            body: "" })"
                            class="w-full flex items-center justify-center gap-2 p-2 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 text-slate-500 hover:border-indigo-400 hover:text-indigo-600 transition-colors text-xs font-semibold">
                            <i data-lucide="plus" class="w-4 h-4"></i> Tambah card
                        </button>
                    </div>
                @elseif ($blockType === 'tickets')
                    <div class="space-y-3">
                        <p class="text-xs text-slate-500">Tipe tiket: nama, harga, kuota, dan deskripsi.</p>
                        <template x-for="(item, idx) in (data.tickets || [])" :key="idx">
                            <div
                                class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/50 space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-0.5">
                                        <button type="button" @click="moveItemIn(data.tickets, idx, -1)"
                                            title="Naikkan"
                                            class="p-1 rounded text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                            <i data-lucide="chevron-up" class="w-3 h-3"></i>
                                        </button>
                                        <button type="button" @click="moveItemIn(data.tickets, idx, 1)"
                                            title="Turunkan"
                                            class="p-1 rounded text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                            <i data-lucide="chevron-down" class="w-3 h-3"></i>
                                        </button>
                                        <span class="text-xs font-mono text-slate-400" x-text="(idx + 1)"></span>
                                    </div>
                                    <button type="button" @click="data.tickets.splice(idx, 1)"
                                        class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20"
                                        title="Hapus">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-slate-400 uppercase mb-1">Tipe</label>
                                        <x-admin.input x-model="item.type" placeholder="Presale" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-400 uppercase mb-1">Harga</label>
                                        <x-admin.input x-model="item.price" placeholder="Rp 150K" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 uppercase mb-1">Quantity</label>
                                    <x-admin.input x-model="item.quantity" placeholder="Kuota: 100 tiket" />
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 uppercase mb-1">Description</label>
                                    <textarea x-model="item.description" rows="2"
                                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-xl text-slate-900 dark:text-white text-sm"></textarea>
                                </div>
                            </div>
                        </template>
                        <button type="button" @click="data.tickets = data.tickets || []; data.tickets.push({type: "",
                            price: "" , quantity: "" , description: "" })"
                            class="w-full flex items-center justify-center gap-2 p-2 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 text-slate-500 hover:border-indigo-400 hover:text-indigo-600 transition-colors text-xs font-semibold">
                            <i data-lucide="plus" class="w-4 h-4"></i> Tambah tiket
                        </button>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Tip</label>
                            <textarea x-model="data.tip" rows="2"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-xl text-slate-900 dark:text-white text-sm"
                                placeholder="Tip tambahan"></textarea>
                        </div>
                    </div>
                @elseif ($blockType === 'faq')
                    <div class="space-y-3">
                        <p class="text-xs text-slate-500">Pertanyaan dan jawaban.</p>
                        <template x-for="(item, idx) in (data.items || [])" :key="idx">
                            <div
                                class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/50 space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-0.5">
                                        <button type="button" @click="moveItemIn(data.items, idx, -1)"
                                            title="Naikkan"
                                            class="p-1 rounded text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                            <i data-lucide="chevron-up" class="w-3 h-3"></i>
                                        </button>
                                        <button type="button" @click="moveItemIn(data.items, idx, 1)"
                                            title="Turunkan"
                                            class="p-1 rounded text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                            <i data-lucide="chevron-down" class="w-3 h-3"></i>
                                        </button>
                                        <span class="text-xs font-mono text-slate-400" x-text="(idx + 1)"></span>
                                    </div>
                                    <button type="button" @click="data.items.splice(idx, 1)"
                                        class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20"
                                        title="Hapus">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 uppercase mb-1">Question</label>
                                    <x-admin.input x-model="item.question" placeholder="Pertanyaan" />
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 uppercase mb-1">Answer</label>
                                    <textarea x-model="item.answer" rows="3"
                                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-xl text-slate-900 dark:text-white text-sm"
                                        placeholder="Jawaban"></textarea>
                                </div>
                            </div>
                        </template>
                        <button type="button" @click="data.items = data.items || []; data.items.push({question: "",
                            answer: "" })"
                            class="w-full flex items-center justify-center gap-2 p-2 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 text-slate-500 hover:border-indigo-400 hover:text-indigo-600 transition-colors text-xs font-semibold">
                            <i data-lucide="plus" class="w-4 h-4"></i> Tambah FAQ
                        </button>
                    </div>
                @elseif ($blockType === 'qr')
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Event
                                    Name</label>
                                <x-admin.input x-model="data.event_name" />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Event
                                    Date</label>
                                <x-admin.input x-model="data.event_date"
                                    placeholder="25 September 2026 - Jakarta Convention Center" />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Ticket
                                    Holder</label>
                                <x-admin.input x-model="data.ticket_holder" />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Ticket
                                    Type</label>
                                <x-admin.input x-model="data.ticket_type" placeholder="VIP" />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Ticket
                                    Number</label>
                                <x-admin.input x-model="data.ticket_number" />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Entry
                                    Window</label>
                                <x-admin.input x-model="data.entry_window" placeholder="08:00 - 23:59" />
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Icon</label>
                            <select x-model="data.icon"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-xl text-slate-900 dark:text-white text-sm">
                                @foreach ($iconWhitelist as $icon)
                                    <option value="{{ $icon }}">{{ $icon }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Footer</label>
                            <textarea x-model="data.footer" rows="2"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-xl text-slate-900 dark:text-white text-sm"></textarea>
                        </div>
                    </div>
                @elseif ($blockType === 'cta')
                    <div class="space-y-3">
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Title</label>
                            <x-admin.input x-model="data.title" placeholder="Siap Menjalankan Event Bersama Gotik?" />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Subtitle</label>
                            <textarea x-model="data.subtitle" rows="2"
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 dark:text-white"
                                placeholder="Deskripsi singkat untuk mengajak pembaca bertindak"></textarea>
                        </div>
                        <div class="border-t border-slate-100 dark:border-slate-700 pt-3">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">
                                Call-to-Action</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] text-slate-400 uppercase mb-1">Label</label>
                                    <x-admin.input x-model="data.cta.label" placeholder="Hubungi Tim Gotik" />
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 uppercase mb-1">Href</label>
                                    <x-admin.input x-model="data.cta.href"
                                        placeholder="mailto:hello@gotik.io atau #anchor atau https://..." />
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 uppercase mb-1">Icon</label>
                                    <select x-model="data.cta.icon"
                                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-xl text-slate-900 dark:text-white text-sm">
                                        <option value="">- tidak ada -</option>
                                        @foreach ($iconWhitelist as $icon)
                                            <option value="{{ $icon }}">{{ $icon }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 uppercase mb-1">Variant</label>
                                    <select x-model="data.cta.variant"
                                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-xl text-slate-900 dark:text-white text-sm">
                                        <option value="primary">Primary</option>
                                        <option value="secondary">Secondary</option>
                                        <option value="cta">CTA</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <x-admin.button type="button" x-on:click="$dispatch('close-modal', {name: 'mge-block-modal'})"
                    variant="secondary">Batal</x-admin.button>
                <x-admin.button type="submit" variant="primary">Simpan Block</x-admin.button>
            </div>
        </form>
    </x-admin.modal>
    @endif
</div>

@if ($modalOnly ?? false)
<script>
    function mgeBlockEditor(initialRaw) {
        return {
            raw: initialRaw || "{}",
            data: {},
            wire: null,
            init(wire) {
                this.wire = wire;
                this.parse();
                this.$watch("data", () => this.serialize(), {
                    deep: true
                });
                this.$watch("raw", (v) => this.parse());
            },
            parse() {
                try {
                    const parsed = JSON.parse(this.raw || "{}");
                    this.data = (parsed && typeof parsed === "object") ? parsed : {};
                } catch (e) {
                    this.data = {};
                }
                // Nested CTA fields (data.cta.*) are only safe to bind once
                // data.cta exists. Older/seed text blocks often only carry
                // `intro`, so we normalize the container up front. This only
                // touches block types whose schema actually has a nested cta
                // (text, cta) and never injects keys into other block types.
                this.ensureCtaDefaults();
            },
            ensureCtaDefaults() {
                const type = this.wire ? this.wire.get("blockType") : null;
                if (type !== "text" && type !== "cta") {
                    return;
                }
                if (!this.data || typeof this.data !== "object" || Array.isArray(this.data)) {
                    this.data = {};
                }
                const cta = this.data.cta;
                if (!cta || typeof cta !== "object" || Array.isArray(cta)) {
                    this.data.cta = {};
                }
                const defaults = type === "text" ?
                    {
                        label: "",
                        href: "",
                        icon: ""
                    } :
                    {
                        label: "",
                        href: "",
                        icon: "arrow-right",
                        variant: "primary"
                    };
                for (const key of Object.keys(defaults)) {
                    if (this.data.cta[key] === undefined) {
                        this.data.cta[key] = defaults[key];
                    }
                }
            },
            serialize() {
                try {
                    this.raw = JSON.stringify(this.data, null, 2);
                    if (this.wire) {
                        this.wire.set("blockDataRaw", this.raw);
                    }
                } catch (e) {
                    // ignore
                }
            },
            moveItemIn(arr, idx, delta) {
                if (!Array.isArray(arr)) {
                    return;
                }
                const target = idx + delta;
                if (target < 0 || target >= arr.length) {
                    return;
                }
                const [moved] = arr.splice(idx, 1);
                arr.splice(target, 0, moved);
            },
            addWorkflowItem() {
                const key = this.wire ? this.wire.get("blockType") : null;
                const target = (key === "flow") ? "boxes" : "steps";
                if (!this.data[target]) {
                    this.data[target] = [];
                }
                const item = (target === "boxes") ?
                    {
                        icon: "phone",
                        label: ""
                    } :
                    {
                        icon: "edit",
                        title: "",
                        description: ""
                    };
                this.data[target].push(item);
            },
        };
    }
</script>
@endif
