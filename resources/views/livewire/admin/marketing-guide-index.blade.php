<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Marketing Guide</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Kelola temporary access link panduan marketing Gotik.</p>
        </div>
        <div>
            <x-admin.button wire:click="openCreateModal" variant="primary" icon="plus">
                Buat Link
            </x-admin.button>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl relative flex items-center gap-3" role="alert">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span class="block sm:inline text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if ($generatedUrl)
        <x-admin.card title="Link Baru (salin sekarang)" icon="link">
            <div class="space-y-3">
                <p class="text-xs text-amber-700 dark:text-amber-300">
                    Plain token hanya tersedia sekali setelah generate. Tidak dapat dipulihkan dari database.
                </p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="text" readonly value="{{ $generatedUrl }}"
                        class="flex-1 px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 rounded-2xl text-sm text-slate-900 dark:text-white"
                        data-generated-url="{{ $generatedUrl }}"
                        id="marketing-guide-generated-url" />
                    <x-admin.button type="button" variant="primary" icon="copy"
                        x-data
                        x-on:click="
                            const el = document.getElementById('marketing-guide-generated-url');
                            if (el) {
                                navigator.clipboard.writeText(el.value);
                            }
                        ">
                        Copy Link
                    </x-admin.button>
                    <x-admin.button type="button" variant="secondary" wire:click="clearGeneratedUrl">
                        Tutup
                    </x-admin.button>
                </div>
            </div>
        </x-admin.card>
    @endif

    <x-admin.card title="Daftar Link" icon="list">
        <x-admin.table :headers="['Penerima', 'Dibuat', 'Expired', 'Access Count', 'Last Accessed', 'Status', 'Action']">
            @forelse ($links as $item)
                @php
                    $status = $accessService->displayStatus($item);
                    $canCopyThis = $generatedAccessId === $item->id && filled($generatedUrl);
                @endphp
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors" wire:key="mg-link-{{ $item->id }}">
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            {{ $item->recipient_name ?: '—' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $item->created_at?->format('d M Y, H:i') }}</span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $item->expires_at?->format('d M Y, H:i') }}</span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="text-sm text-slate-700 dark:text-slate-200">{{ (int) $item->access_count }}</span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $item->last_accessed_at?->format('d M Y, H:i') ?: '—' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        @if ($status === 'Active')
                            <span class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-700 px-2.5 py-1 text-xs font-semibold">Active</span>
                        @elseif ($status === 'Expired')
                            <span class="inline-flex items-center rounded-full bg-amber-50 text-amber-700 px-2.5 py-1 text-xs font-semibold">Expired</span>
                        @elseif ($status === 'Revoked')
                            <span class="inline-flex items-center rounded-full bg-rose-50 text-rose-700 px-2.5 py-1 text-xs font-semibold">Revoked</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-600 px-2.5 py-1 text-xs font-semibold">{{ $status }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            @if ($canCopyThis)
                                <button type="button"
                                    class="px-2.5 py-1.5 text-xs font-semibold rounded-lg text-indigo-600 hover:bg-indigo-50"
                                    x-data
                                    x-on:click="navigator.clipboard.writeText(@js($generatedUrl))">
                                    Copy Link
                                </button>
                            @else
                                <button type="button"
                                    wire:click="regenerateLink({{ $item->id }})"
                                    wire:loading.attr="disabled"
                                    class="px-2.5 py-1.5 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100 disabled:opacity-60">
                                    Generate Ulang
                                </button>
                            @endif

                            <button type="button"
                                wire:click="openExtendModal({{ $item->id }})"
                                class="px-2.5 py-1.5 text-xs font-semibold rounded-lg text-sky-600 hover:bg-sky-50">
                                Perpanjang
                            </button>

                            @if ($status !== 'Revoked')
                                <button type="button"
                                    wire:click="revokeLink({{ $item->id }})"
                                    wire:confirm="Revoke link ini? Link tidak dapat digunakan lagi."
                                    class="px-2.5 py-1.5 text-xs font-semibold rounded-lg text-rose-600 hover:bg-rose-50">
                                    Revoke
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center">
                        <div class="flex flex-col items-center justify-center text-slate-400">
                            <i data-lucide="link-2-off" class="w-12 h-12 mb-2 opacity-20"></i>
                            <p class="text-sm">Belum ada link Marketing Guide.</p>
                        </div>
                    </td>
                </tr>
            @endforelse

            <x-slot name="pagination">
                {{ $links->links() }}
            </x-slot>
        </x-admin.table>
    </x-admin.card>

    <x-admin.modal name="marketing-guide-create-modal" title="Buat Link Marketing Guide">
        <form wire:submit.prevent="generateLink" class="relative space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Nama penerima / organisasi (opsional)</label>
                <x-admin.input wire:model="recipient_name" placeholder="Contoh: PT Acme Indonesia" />
                @error('recipient_name') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Masa berlaku</label>
                <select wire:model="duration_days"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 dark:text-white">
                    @foreach ($durationOptions as $days)
                        <option value="{{ $days }}">{{ $days }} hari</option>
                    @endforeach
                </select>
                @error('duration_days') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <x-admin.button type="button" x-on:click="$dispatch('close-modal', {name: 'marketing-guide-create-modal'})" variant="secondary">
                    Batal
                </x-admin.button>
                <x-admin.button type="submit" variant="primary">
                    Generate Link
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <x-admin.modal name="marketing-guide-extend-modal" title="Perpanjang Masa Berlaku">
        <form wire:submit.prevent="extendLink" class="relative space-y-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Hanya memperbarui tanggal kedaluwarsa. Link yang sudah di-revoke tidak akan aktif kembali.
            </p>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Tambahan masa berlaku</label>
                <select wire:model="extend_days"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-0 ring-1 ring-slate-200 dark:ring-slate-600 focus:ring-2 focus:ring-indigo-600 rounded-2xl text-slate-900 dark:text-white">
                    @foreach ($durationOptions as $days)
                        <option value="{{ $days }}">+{{ $days }} hari</option>
                    @endforeach
                </select>
                @error('extend_days') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <x-admin.button type="button" x-on:click="$dispatch('close-modal', {name: 'marketing-guide-extend-modal'})" variant="secondary">
                    Batal
                </x-admin.button>
                <x-admin.button type="submit" variant="primary">
                    Simpan Perpanjangan
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
