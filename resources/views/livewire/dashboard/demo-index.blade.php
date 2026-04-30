<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white uppercase tracking-tight">Dashboard</h2>
        <x-admin.button variant="primary" size="lg" icon="plus-circle" x-on:click="$dispatch('open-modal', { name: 'sell-modal' })">
            Jual Tiket
        </x-admin.button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT COLUMN: STATISTICS (7/12) -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Total Omset (Large Card) -->
            <x-admin.card class="p-8 relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Total Omset (Seluruh)</p>
                    <h3 class="text-4xl font-black text-slate-800 dark:text-white tracking-tight">
                        Rp {{ number_format($stats['omset'], 0, ',', '.') }}
                    </h3>
                </div>
                <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:scale-110 transition-transform">
                    <i data-lucide="banknote" class="w-24 h-24 text-slate-400"></i>
                </div>
            </x-admin.card>

            <!-- Triple Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-admin.card class="p-6">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Total Transaksi</p>
                    <h4 class="text-3xl font-black text-slate-800 dark:text-white">
                        {{ number_format($stats['transaksi']) }}
                    </h4>
                </x-admin.card>

                <x-admin.card class="p-6">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Total Tiket (Sell)</p>
                    <h4 class="text-3xl font-black text-slate-800 dark:text-white">
                        {{ number_format($stats['tiket']) }}
                    </h4>
                </x-admin.card>

                <x-admin.card class="p-6">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Total Event</p>
                    <h4 class="text-3xl font-black text-slate-800 dark:text-white">
                        {{ $stats['total_event'] }}
                    </h4>
                </x-admin.card>
            </div>

            <!-- Gender Demographics -->
            <x-admin.card class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Data gender semua transaksi</h4>
                    <button x-on:click="$dispatch('open-modal', { name: 'gender-modal' })" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                        Lihat detail
                    </button>
                </div>
                
                @php
                    $totalGender = $gender['pria'] + $gender['wanita'];
                    $persenPria = $totalGender > 0 ? round(($gender['pria'] / $totalGender) * 100) : 0;
                    $persenWanita = $totalGender > 0 ? round(($gender['wanita'] / $totalGender) * 100) : 0;
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-5 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-2 text-center">Presentase Pria</p>
                        <h5 class="text-3xl font-black text-indigo-600 dark:text-indigo-400 text-center">{{ $persenPria }}%</h5>
                    </div>
                    <div class="p-5 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-2 text-center">Presentase Wanita</p>
                        <h5 class="text-3xl font-black text-indigo-600 dark:text-indigo-400 text-center">{{ $persenWanita }}%</h5>
                    </div>
                </div>
            </x-admin.card>
        </div>

        <!-- RIGHT COLUMN: ACTIVE EVENTS (5/12) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-bold text-slate-800 dark:text-white">Event aktif :</h4>
                <a href="{{ auth()->user()->role === 'admin' ? route('admin.event') : url('/dashboard/event') }}" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Lihat semua</a>
            </div>

            @forelse($activeEvents->take(2) as $event)
                <x-admin.card class="p-4 rounded-[2rem] group">
                    <div class="aspect-[16/10] rounded-2xl overflow-hidden mb-4 relative">
                        <img src="{{ asset('storage/cover/' . $event->cover) }}" alt="{{ $event->event }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </div>

                    <h4 class="text-lg font-extrabold text-slate-800 dark:text-white mb-6 uppercase tracking-tight line-clamp-1 px-1">
                        {{ $event->event }}
                    </h4>

                    <div class="grid grid-cols-3 gap-2">
                        <a href="{{ route('dashboard.demo.event.detail', $event->uid) }}" wire:navigate>
                            <x-admin.button variant="secondary" class="w-full !px-1 !text-[10px] uppercase font-extrabold">
                                Detail Event
                            </x-admin.button>
                        </a>
                        <a href="{{ route('dashboard.demo.event.detail', $event->uid) }}?activeTab=transaksi" wire:navigate>
                            <x-admin.button variant="primary" class="w-full !px-1 !text-[10px] uppercase font-extrabold">
                                Trx Online
                            </x-admin.button>
                        </a>
                        <a href="{{ route('dashboard.demo.event.detail', $event->uid) }}?activeTab=transaksi&filterPayment=cash" wire:navigate>
                            <x-admin.button variant="primary" class="w-full !px-1 !text-[10px] uppercase font-extrabold">
                                Trx Cash
                            </x-admin.button>
                        </a>
                    </div>
                </x-admin.card>
            @empty
                <div class="p-12 text-center bg-white dark:bg-slate-800 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700">
                    <p class="text-slate-400 text-sm font-medium">Belum ada event aktif.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Analytic Section (Footer) -->
    <x-admin.card class="p-6 mt-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-slate-800 dark:text-white">Graphic Sales Analytic</h3>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-1.5">
                    <div class="w-2 h-2 rounded-full bg-indigo-600"></div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Online</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Cash</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Tickets</span>
                </div>
            </div>
        </div>
        <div class="h-[300px]">
            <canvas id="salesChart"></canvas>
        </div>
    </x-admin.card>

    <!-- MODAL JUAL TIKET (POS SYSTEM) -->
    <x-admin.modal name="sell-modal" title="{{ $selectedEventId ? 'Jual Tiket - ' . $selectedEvent->event : 'Pilih Event' }}" icon="shopping-cart">
        <div class="space-y-6">
            <!-- Flash Messages -->
            @if (session()->has('success'))
                <div class="p-4 mb-4 text-sm text-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl border border-emerald-100 dark:border-emerald-800 flex items-center gap-3 animate-bounce">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <span class="font-bold">{{ session('success') }}</span>
                </div>
            @endif
            @if (session()->has('error'))
                <div class="p-4 mb-4 text-sm text-rose-700 bg-rose-50 dark:bg-rose-900/30 rounded-2xl border border-rose-100 dark:border-rose-800 flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <span class="font-bold">{{ session('error') }}</span>
                </div>
            @endif

            @if(!$selectedEventId)
                <!-- STEP 1: PILIH EVENT -->
                <div class="space-y-4">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest px-1">Daftar Event Aktif</p>
                    <div class="grid grid-cols-1 gap-3 max-h-[50vh] overflow-y-auto pr-1 custom-scrollbar">
                        @forelse($activeEvents as $event)
                            <button wire:click="selectEvent('{{ $event->uid }}')" class="w-full p-4 flex items-center gap-4 bg-slate-50 dark:bg-slate-900 rounded-2xl hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-all border border-slate-200 dark:border-slate-700 hover:border-indigo-300 text-left group">
                                <div class="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0 ring-2 ring-slate-200 dark:ring-slate-700 group-hover:ring-indigo-300">
                                    <img src="{{ asset('storage/cover/'.$event->cover) }}" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-slate-800 dark:text-white truncate uppercase text-xs">{{ $event->event }}</h4>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-bold tracking-wider">Mulai Transaksi</p>
                                </div>
                                <i data-lucide="arrow-right" class="w-4 h-4 text-slate-300 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all"></i>
                            </button>
                        @empty
                            <div class="text-center py-8">
                                <i data-lucide="calendar-x" class="w-12 h-12 text-slate-300 mx-auto mb-3"></i>
                                <p class="text-slate-500 italic text-sm">Tidak ada event aktif.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @else
                <!-- STEP 2: FORM TRANSAKSI -->
                <div class="flex flex-col h-full">
                    <div class="space-y-5 max-h-[60vh] overflow-y-auto px-1 custom-scrollbar pb-6">
                        <!-- Ticket Selector -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Tambah Kategori Tiket</label>
                            <div class="relative">
                                <select wire:change="addTicket($event.target.value)" class="w-full p-3 pl-10 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 transition-all outline-none appearance-none">
                                    <option value="">-- Pilih Tiket --</option>
                                    @foreach($availableTickets as $ticket)
                                        <option value="{{ $ticket->id }}">{{ $ticket->kategori }} (Rp {{ number_format($ticket->harga) }})</option>
                                    @endforeach
                                </select>
                                <i data-lucide="tag" class="w-4 h-4 text-slate-400 absolute left-4 top-3.5"></i>
                            </div>

                            <!-- Cart Items -->
                            <div class="space-y-2">
                                @forelse($selectedTickets as $index => $item)
                                    <div class="flex items-center gap-4 p-4 bg-indigo-50/50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-800/50 rounded-2xl animate-in fade-in slide-in-from-left-2">
                                        <div class="flex-1">
                                            <h5 class="text-sm font-black text-slate-800 dark:text-white uppercase">{{ $item['name'] }}</h5>
                                            <p class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">Rp {{ number_format($item['price']) }} / tiket</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden h-9 shadow-sm">
                                                <button wire:click="$set('selectedTickets.{{ $index }}.qty', {{ max(1, $item['qty'] - 1) }})" class="px-2 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-400">-</button>
                                                <input type="number" wire:model.live="selectedTickets.{{ $index }}.qty" class="w-10 text-center text-xs font-black bg-transparent border-x border-slate-100 dark:border-slate-700 outline-none">
                                                <button wire:click="$set('selectedTickets.{{ $index }}.qty', {{ $item['qty'] + 1 }})" class="px-2 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-400">+</button>
                                            </div>
                                            <button wire:click="removeTicket({{ $index }})" class="w-9 h-9 flex items-center justify-center text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors border border-rose-100 dark:border-rose-900/50">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-4 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl text-center">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase">Keranjang Kosong</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="h-px bg-slate-100 dark:bg-slate-700 mx-2"></div>

                        <!-- Buyer Info Form -->
                        <div class="space-y-4">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Informasi Pembeli</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <input type="text" wire:model="buyerName" placeholder="Nama Lengkap" class="w-full p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                                <div class="space-y-1">
                                    <input type="email" wire:model="buyerEmail" placeholder="Email Pembeli" class="w-full p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                                <div class="space-y-1">
                                    <input type="date" wire:model="buyerBirthday" class="w-full p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                                <div class="space-y-1">
                                    <select wire:model="buyerGender" class="w-full p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                        <option value="">Jenis Kelamin</option>
                                        <option value="pria">Pria</option>
                                        <option value="wanita">Wanita</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Extra Options -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Partner (Opsional)</label>
                                <select wire:model="partnerId" class="w-full p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                    <option value="">-- Pilih Partner --</option>
                                    @foreach($availablePartners as $partner)
                                        <option value="{{ $partner->uid }}">{{ $partner->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col justify-end gap-2 pb-1 px-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="isPaidCash" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Sudah Bayar Cash</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="isDirectEntry" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Langsung Masuk</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- STICKY FOOTER -->
                    <div class="pt-6 border-t border-slate-100 dark:border-slate-700 space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Subtotal</p>
                                <p class="text-sm font-black text-slate-800 dark:text-white">Rp {{ number_format($this->subtotal) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Pajak ({{ $selectedEvent->fee ?? 0 }}%)</p>
                                <p class="text-sm font-black text-slate-800 dark:text-white">Rp {{ number_format($this->tax) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] font-bold text-emerald-500 uppercase mb-1">Layanan</p>
                                <p class="text-sm font-black text-emerald-600 uppercase italic">Free</p>
                            </div>
                        </div>

                        <x-admin.button wire:click="checkout" variant="primary" class="w-full py-4 text-lg font-black tracking-widest shadow-xl shadow-indigo-100 dark:shadow-none" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="checkout">BAYAR Rp {{ number_format($this->total) }}</span>
                            <span wire:loading wire:target="checkout" class="flex items-center gap-2 uppercase">
                                <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Memproses...
                            </span>
                        </x-admin.button>
                        
                        <button wire:click="resetCashForm" class="w-full text-center text-[10px] font-bold text-slate-400 hover:text-rose-500 transition-colors uppercase tracking-widest">Batalkan Transaksi</button>
                    </div>
                </div>
            @endif
        </div>
    </x-admin.modal>

    <!-- MODAL GENDER DETAIL -->
    <x-admin.modal name="gender-modal" title="Detail Data Gender" icon="users">
        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl border border-indigo-100 dark:border-indigo-800 text-center">
                    <p class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase mb-1">Pria</p>
                    <p class="text-3xl font-black text-indigo-800 dark:text-indigo-200">{{ $gender['pria'] }}</p>
                </div>
                <div class="p-4 bg-rose-50 dark:bg-rose-900/20 rounded-2xl border border-rose-100 dark:border-rose-800 text-center">
                    <p class="text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase mb-1">Wanita</p>
                    <p class="text-3xl font-black text-rose-800 dark:text-rose-200">{{ $gender['wanita'] }}</p>
                </div>
            </div>

            <div class="space-y-3">
                <h5 class="text-xs font-bold text-slate-400 uppercase tracking-widest px-1">Rincian Berdasarkan Usia</h5>
                <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">18 s/d 25 tahun</span>
                        <span class="px-3 py-1 bg-white dark:bg-slate-800 rounded-lg font-bold text-slate-800 dark:text-white shadow-sm">{{ $gender['age_18_25'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">25 tahun ke atas</span>
                        <span class="px-3 py-1 bg-white dark:bg-slate-800 rounded-lg font-bold text-slate-800 dark:text-white shadow-sm">{{ $gender['age_gt_25'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">18 thn ke bawah</span>
                        <span class="px-3 py-1 bg-white dark:bg-slate-800 rounded-lg font-bold text-slate-800 dark:text-white shadow-sm">{{ $gender['age_lt_18'] }}</span>
                    </div>
                </div>
            </div>
            
            <x-admin.button variant="secondary" class="w-full" x-on:click="show = false">Tutup</x-admin.button>
        </div>
    </x-admin.modal>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                const ctxSales = document.getElementById('salesChart');
                new Chart(ctxSales, {
                    type: 'line',
                    data: {
                        labels: @json($chart['labels']),
                        datasets: [
                            {
                                label: 'Online',
                                data: @json($chart['online']),
                                borderColor: '#4f46e5',
                                backgroundColor: 'transparent',
                                borderWidth: 3,
                                pointRadius: 0,
                                tension: 0.4
                            },
                            {
                                label: 'Cash',
                                data: @json($chart['cash']),
                                borderColor: '#10b981',
                                backgroundColor: 'transparent',
                                borderWidth: 3,
                                pointRadius: 0,
                                tension: 0.4
                            },
                            {
                                label: 'Tickets',
                                data: @json($chart['qty']),
                                type: 'bar',
                                backgroundColor: 'rgba(245, 158, 11, 0.2)',
                                borderRadius: 8,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,0.03)' },
                                ticks: { font: { size: 10, weight: '600' } }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                grid: { display: false },
                                ticks: { font: { size: 10, weight: '600' } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10, weight: '600' } }
                            }
                        }
                    }
                });
            });
        </script>
    @endpush
</div>