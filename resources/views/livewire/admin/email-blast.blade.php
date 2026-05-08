<div>
    <!-- Load Quill Assets -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <style>
        .ql-container {
            border-bottom-left-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
            background-color: #f8fafc;
        }

        .dark .ql-container {
            background-color: #1e293b;
            color: white;
            border-color: #334155;
        }

        .ql-toolbar {
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
            background-color: #ffffff;
        }

        .dark .ql-toolbar {
            background-color: #0f172a;
            border-color: #334155;
        }

        .dark .ql-stroke {
            stroke: #94a3b8 !important;
        }

        .dark .ql-fill {
            fill: #94a3b8 !important;
        }

        .dark .ql-picker {
            color: #94a3b8 !important;
        }
    </style>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Email Blast</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Kirim email ke seluruh pengguna atau pembeli tiket
                spesifik.</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div
            class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-600 dark:text-emerald-400 flex items-start gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div>
                <h4 class="font-semibold text-sm">Berhasil!</h4>
                <p class="text-sm mt-1">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div
            class="mb-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-600 dark:text-rose-400 flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div>
                <h4 class="font-semibold text-sm">Gagal</h4>
                <p class="text-sm mt-1">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <div
        class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-6">
            <form wire:submit.prevent="sendBlast" class="space-y-6">

                <!-- Target Selection -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Target
                        Penerima</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <label
                            class="relative flex items-center p-4 border rounded-xl cursor-pointer transition-all duration-200 {{ $targetType === 'all' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-indigo-300' }}">
                            <input type="radio" wire:model.live="targetType" value="all" class="peer sr-only">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                    <i data-lucide="users" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h5 class="text-xs font-semibold text-slate-900 dark:text-white">Semua Pengguna</h5>
                                </div>
                            </div>
                            <div
                                class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 border-2 rounded-full flex items-center justify-center {{ $targetType === 'all' ? 'border-indigo-600' : 'border-slate-300 dark:border-slate-600' }}">
                                @if($targetType === 'all')
                                    <div class="w-2 h-2 bg-indigo-600 rounded-full"></div>
                                @endif
                            </div>
                        </label>

                        <label
                            class="relative flex items-center p-4 border rounded-xl cursor-pointer transition-all duration-200 {{ $targetType === 'event' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-indigo-300' }}">
                            <input type="radio" wire:model.live="targetType" value="event" class="peer sr-only">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                    <i data-lucide="ticket" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h5 class="text-xs font-semibold text-slate-900 dark:text-white">Pembeli Event</h5>
                                </div>
                            </div>
                            <div
                                class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 border-2 rounded-full flex items-center justify-center {{ $targetType === 'event' ? 'border-indigo-600' : 'border-slate-300 dark:border-slate-600' }}">
                                @if($targetType === 'event')
                                    <div class="w-2 h-2 bg-indigo-600 rounded-full"></div>
                                @endif
                            </div>
                        </label>

                        <label
                            class="relative flex items-center p-4 border rounded-xl cursor-pointer transition-all duration-200 {{ $targetType === 'users' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-indigo-300' }}">
                            <input type="radio" wire:model.live="targetType" value="users" class="peer sr-only">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                    <i data-lucide="user-check" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h5 class="text-xs font-semibold text-slate-900 dark:text-white">Pilih User</h5>
                                </div>
                            </div>
                            <div
                                class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 border-2 rounded-full flex items-center justify-center {{ $targetType === 'users' ? 'border-indigo-600' : 'border-slate-300 dark:border-slate-600' }}">
                                @if($targetType === 'users')
                                    <div class="w-2 h-2 bg-indigo-600 rounded-full"></div>
                                @endif
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Event Select (Conditional) -->
                @if($targetType === 'event')
                    <div class="animate-in fade-in slide-in-from-top-4 duration-300">
                        <label for="event_uid"
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Pilih Event</label>
                        <div class="relative">
                            <select wire:model="event_uid" id="event_uid"
                                class="w-full px-4 py-2.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 outline-none">
                                <option value="" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">-- Pilih
                                    Event --</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->uid }}"
                                        class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                                        {{ $event->event }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('event_uid') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                @endif

                <!-- User Selection (Conditional) -->
                @if($targetType === 'users')
                    <div class="animate-in fade-in slide-in-from-top-4 duration-300 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari & Pilih
                                Pengguna</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
                                </div>
                                <input type="text" wire:model.live="search_user" placeholder="Cari nama atau email..."
                                    class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 dark:text-white transition-all duration-200 outline-none">
                            </div>
                        </div>

                        <div
                            class="max-h-60 overflow-y-auto border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800/30 p-2 space-y-1">
                            @forelse($availableUsers as $user)
                                <label
                                    class="flex items-center gap-3 p-2 rounded-lg hover:bg-white dark:hover:bg-slate-800 transition-colors cursor-pointer border border-transparent hover:border-slate-200 dark:hover:border-slate-700">
                                    <input type="checkbox" wire:model="users_selected" value="{{ $user->uid }}"
                                        class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 bg-white dark:bg-slate-700">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm font-medium text-slate-900 dark:text-white">{{ $user->name }}</span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</span>
                                    </div>
                                </label>
                            @empty
                                <p class="text-center py-4 text-sm text-slate-500">Pengguna tidak ditemukan.</p>
                            @endforelse
                        </div>
                        @error('users_selected') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        <p class="text-[10px] text-slate-500 italic">*Menampilkan hingga 50 pengguna teratas berdasarkan
                            pencarian.</p>
                    </div>
                @endif

                <!-- Subject -->
                <div>
                    <label for="subject"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Subjek Email</label>
                    <input type="text" wire:model="subject" id="subject"
                        placeholder="Contoh: Promo Spesial Tiket Konser!"
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 dark:text-white transition-all duration-200 outline-none">
                    @error('subject') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <!-- Content with Quill -->
                <div wire:ignore>
                    <label for="content" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Isi
                        Email (Pesan)</label>
                    <div x-data="{ content: @entangle('content') }" x-init="
                            const quill = new Quill($refs.editor, {
                                theme: 'snow',
                                modules: {
                                    toolbar: [
                                        [{ 'header': [1, 2, 3, false] }],
                                        ['bold', 'italic', 'underline', 'strike'],
                                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                        [{ 'color': [] }, { 'background': [] }],
                                        ['link', 'clean']
                                    ]
                                }
                            });
                            quill.root.innerHTML = content;
                            quill.on('text-change', () => {
                                content = quill.root.innerHTML;
                            });
                        ">
                        <div x-ref="editor" class="h-64 rounded-b-xl"></div>
                    </div>
                </div>
                @error('content') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror

                <!-- Submit Button -->
                <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="submit" wire:loading.attr="disabled" wire:target="sendBlast"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-all duration-200 shadow-sm shadow-indigo-600/20 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="sendBlast" class="flex items-center gap-2">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Kirim Blast
                        </span>
                        <span wire:loading.flex wire:target="sendBlast" class="items-center gap-2" x-cloak>
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                            Memproses...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>