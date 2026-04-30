<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Manajemen Event</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Daftar semua event yang terdaftar di platform.
            </p>
        </div>
        <a href="{{ route('dashboard.demo.event.create') }}" wire:navigate>
            <x-admin.button variant="primary" size="lg" icon="plus">
                Tambah Event
            </x-admin.button>
        </a>
    </div>

    <!-- Filter & Search -->
    <div class="mb-6">
        <div
            class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-slate-800 p-3 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="w-full md:w-1/3">
                <x-admin.input wire:model.live.debounce.300ms="search" placeholder="Cari nama event..." icon="search"
                    class="!border-transparent !bg-slate-50 dark:!bg-slate-900" />
            </div>

        </div>
    </div>

    <!-- Event Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6">
        @forelse($events as $event)
            <x-admin.card padding="p-2"
                class="flex flex-col overflow-hidden border-slate-200 dark:border-slate-700 hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300 group">
                <!-- Cover Image -->
                <div class="w-full aspect-[4/3] relative overflow-hidden bg-slate-100 dark:bg-slate-900">
                    <img src="{{ asset('storage/cover/' . $event->cover) }}" alt="{{ $event->event }}"
                        class="w-full rounded-2xl h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        onerror="this.src='https://placehold.co/800x600?text=No+Cover'">

                    <!-- Toggle Switch Overlay -->
                    @if ($event->konfirmasi !== null)
                        <div
                            class="absolute top-3 right-3 bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm p-2 rounded-xl shadow-sm border border-white/20 flex items-center gap-2">
                            <span
                                class="text-[10px] font-bold uppercase tracking-wider {{ $event->status === 'active' ? 'text-emerald-600' : 'text-slate-400' }}">
                                {{ $event->status === 'active' ? 'Active' : 'Closed' }}
                            </span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:click="toggleStatus('{{ $event->uid }}')" class="sr-only peer" {{ $event->status === 'active' ? 'checked' : '' }}>
                                <div
                                    class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-emerald-500">
                                </div>
                            </label>
                        </div>
                    @else
                        <div
                            class="absolute top-3 right-3 bg-amber-500/90 backdrop-blur-sm px-3 py-1.5 rounded-xl shadow-sm border border-white/20">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-white">Menunggu Persetujuan</span>
                        </div>
                    @endif
                </div>

                <!-- Content -->
                <div class="p-5 flex flex-col flex-1">
                    <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight mb-4 line-clamp-2"
                        title="{{ $event->event }}">
                        {{ $event->event }}
                    </h3>

                    <div class="mt-auto space-y-3">
                        @if ($event->konfirmasi !== null)
                            <div class="grid grid-cols-5 gap-3">
                                <a href="{{ route('dashboard.demo.event.detail', $event->uid) }}" wire:navigate
                                    class="col-span-4">
                                    <x-admin.button variant="secondary"
                                        class="w-full uppercase text-[11px] font-black tracking-widest border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-600 hover:text-indigo-600 transition-colors">
                                        Detail Event
                                    </x-admin.button>
                                </a>
                                <a href="{{ route('dashboard.demo.event.edit', $event->uid) }}" wire:navigate
                                    class="col-span-1">
                                    <x-admin.button variant="secondary"
                                        class="w-full !px-0 flex items-center justify-center border-slate-200 dark:border-slate-700 hover:border-amber-300 dark:hover:border-amber-600 hover:text-amber-600 transition-colors"
                                        title="Edit Event">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </x-admin.button>
                                </a>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <a href="{{ route('dashboard.demo.event.detail', $event->uid) }}?activeTab=transaksi"
                                    wire:navigate>
                                    <x-admin.button variant="primary"
                                        class="w-full !px-0 uppercase text-[10px] font-black tracking-widest shadow-indigo-200 dark:shadow-none">
                                        Trx Online
                                    </x-admin.button>
                                </a>
                                <a href="{{ route('dashboard.demo.event.detail', $event->uid) }}?activeTab=transaksi&filterPayment=cash"
                                    wire:navigate>
                                    <x-admin.button variant="primary"
                                        class="w-full !px-0 uppercase text-[10px] font-black tracking-widest shadow-indigo-200 dark:shadow-none">
                                        Trx Cash
                                    </x-admin.button>
                                </a>
                            </div>
                        @else
                            <x-admin.button variant="secondary" class="w-full uppercase text-[11px] font-black tracking-widest"
                                disabled>
                                Belum Tersedia
                            </x-admin.button>
                        @endif
                    </div>
                </div>
            </x-admin.card>
        @empty
            <div
                class="col-span-full py-12 text-center bg-white dark:bg-slate-800 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700">
                <i data-lucide="calendar-x" class="w-16 h-16 text-slate-300 mx-auto mb-4"></i>
                <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300 mb-1">Belum ada event</h3>
                <p class="text-slate-500 text-sm">Tambahkan event baru untuk mulai menjual tiket.</p>
                <a href="{{ url('/dashboard/event/addEvent') }}">
                    <x-admin.button variant="primary" icon="plus" class="mt-4 mx-auto">
                        Buat Event Baru
                    </x-admin.button>
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $events->links('components.admin.pagination') }}
    </div>
</div>