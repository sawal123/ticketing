    <div class="space-y-6">
    <!-- Header Section -->
    <div class="mb-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white uppercase tracking-tight">Dashboard</h2>
        <div class="flex flex-wrap items-center gap-3">
            <div data-tour="dashboard-help">
                <x-dashboard.contextual-help context="dashboard" />
            </div>
            @if (auth()->user()?->role === 'penyewa')
                <x-admin.button type="button" variant="secondary" x-on:click="$dispatch('replay-tour', { tutorialKey: 'dashboard.overview' })" title="Mulai tur dashboard">
                    Tur Dashboard
                </x-admin.button>
            @endif
            <x-admin.button variant="primary" size="lg" icon="plus-circle" x-on:click="$dispatch('open-modal', { name: 'sell-modal' })">
                Jual Tiket
            </x-admin.button>
        </div>
    </div>

    @if ($gettingStarted['visible'] ?? false)
        <x-admin.card class="overflow-hidden p-6 sm:p-8">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="min-w-0 flex-1 space-y-5">
                    <div class="space-y-2">
                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-indigo-500 dark:text-indigo-300">Onboarding</p>
                        <div class="space-y-1">
                            <h3 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">Mulai Menggunakan Gotik</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Checklist ini membaca data dashboard Anda secara otomatis dan mengarahkan ke langkah berikutnya.</p>
                        </div>
                        @if (filled($gettingStarted['event_name'] ?? null))
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                Event onboarding: <span class="text-slate-700 dark:text-slate-200">{{ $gettingStarted['event_name'] }}</span>
                            </p>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($gettingStarted['steps'] as $step)
                            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/40">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border {{ $step['completed'] ? 'border-emerald-200 bg-emerald-100 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-300' : 'border-slate-200 bg-white text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500' }}">
                                    @if ($step['completed'])
                                        <i data-lucide="check" class="h-4 w-4"></i>
                                    @else
                                        <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold {{ $step['completed'] ? 'text-slate-700 dark:text-slate-200' : 'text-slate-800 dark:text-white' }}">
                                        {{ $step['label'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="w-full max-w-xl xl:w-[22rem]">
                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50/80 p-5 dark:border-slate-700 dark:bg-slate-900/40">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-slate-800 dark:text-white">{{ $gettingStarted['completed_count'] }} dari {{ $gettingStarted['total_steps'] }} selesai</p>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $gettingStarted['progress_percentage'] }}% progress</p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">
                                {{ $gettingStarted['progress_percentage'] }}%
                            </span>
                        </div>

                        <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $gettingStarted['progress_percentage'] }}">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-600 via-sky-500 to-emerald-500 transition-all duration-300" style="width: {{ $gettingStarted['progress_percentage'] }}%"></div>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                            <x-admin.button href="{{ $gettingStarted['primary_url'] }}" wire:navigate variant="primary" class="w-full justify-center">
                                Lanjutkan Setup
                            </x-admin.button>
                            <x-admin.button type="button" wire:click="dismissGettingStartedChecklist" variant="secondary" class="w-full justify-center">
                                Lewati
                            </x-admin.button>
                        </div>
                    </div>
                </div>
            </div>
        </x-admin.card>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT COLUMN: STATISTICS (7/12) -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Total Omset (Large Card) -->
            <x-admin.card data-tour="dashboard-revenue" class="p-8 relative overflow-hidden group">
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
                <x-admin.card data-tour="dashboard-transactions" class="p-6">
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
        <div data-tour="dashboard-active-events" class="lg:col-span-5 space-y-4">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-bold text-slate-800 dark:text-white">Event aktif :</h4>
                <a href="{{ auth()->user()->role === 'admin' ? route('admin.event') : url('/dashboard/event') }}" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Lihat semua</a>
            </div>

            @forelse($activeEvents->take(2) as $event)
                <x-admin.card class="p-4 rounded-[2rem] group">
                    <div class="aspect-[16/10] rounded-2xl overflow-hidden mb-4 relative">
                        <img src="{{ asset('storage/cover/' . $event->cover) }}" alt="{{ $event->event }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 {{ $event->status !== 'active' ? 'grayscale opacity-75' : '' }}">
                    </div>

                    <h4 class="text-lg font-extrabold text-slate-800 dark:text-white mb-6 uppercase tracking-tight line-clamp-1 px-1">
                        {{ $event->event }}
                    </h4>

                    <div class="grid grid-cols-3 gap-2">
                        <a href="{{ route('dashboard.event.detail', $event->uid) }}" wire:navigate>
                            <x-admin.button variant="secondary" class="w-full !px-1 !text-[10px] uppercase font-extrabold">
                                Detail Event
                            </x-admin.button>
                        </a>
                        <a href="{{ route('dashboard.event.detail', $event->uid) }}?activeTab=transaksi" wire:navigate>
                            <x-admin.button variant="primary" class="w-full !px-1 !text-[10px] uppercase font-extrabold">
                                Trx Online
                            </x-admin.button>
                        </a>
                        <a href="{{ route('dashboard.event.detail', $event->uid) }}?activeTab=transaksi&filterPayment=cash" wire:navigate>
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

    <!-- Main Trend Chart -->
    <div data-tour="dashboard-sales-trend" class="mb-8" x-data="{
        labels: @js($chart['labels']),
        revenue: @js($chart['revenue']),
        cash: @js($chart['cash']),
        nonCash: @js($chart['nonCash']),
        init() {
            let arrLabels = Object.values(this.labels || {});
            let arrRevenue = Object.values(this.revenue || {});
            let arrCash = Object.values(this.cash || {});
            let arrNonCash = Object.values(this.nonCash || {});

            this.labels = arrLabels;
            this.revenue = arrRevenue;
            this.cash = arrCash;
            this.nonCash = arrNonCash;

            this.$nextTick(() => {
                this.renderChart();
            });
        },
        renderChart() {
            new Chart(this.$refs.mainChart, {
                type: 'line',
                data: {
                    labels: this.labels,
                    datasets: [
                        {
                            label: 'Total Uang (Rp)',
                            data: this.revenue,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Tiket Cash',
                            data: this.cash,
                            borderColor: '#6366f1',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            tension: 0.4,
                            yAxisID: 'y1'
                        },
                        {
                            label: 'Tiket Non-Cash',
                            data: this.nonCash,
                            borderColor: '#f59e0b',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', align: 'end' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    if (context.datasetIndex === 0) {
                                        label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                    } else {
                                        label += context.parsed.y + ' Tiket';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            grid: { drawOnChartArea: false },
                            ticks: {
                                callback: (value) => 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(value)
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: {
                                callback: (value) => value + ' Tkt'
                            }
                        }
                    }
                }
            });
        }
    }">
        <x-admin.card title="Tren Penjualan (7 Hari Terakhir)" icon="trending-up" iconColor="indigo">
            <div class="h-80 w-full">
                <canvas x-ref="mainChart"></canvas>
            </div>
        </x-admin.card>
    </div>

    <livewire:dashboard.qr-section />

    @if (auth()->user()?->role === 'penyewa')
        <livewire:tutorials.interactive-tour tutorial-key="dashboard.overview" :steps="$dashboardTourSteps" />
    @endif

    @include('livewire.dashboard.partials.sell-ticket-modal')

    <!-- MODAL SUKSES TRANSAKSI CASH -->
    <x-admin.modal name="cash-transaction-success-modal" title="Transaksi Cash Berhasil" icon="check-circle">
        @if(!empty($cashTransactionResult))
            <div class="space-y-6">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center mb-3">
                        <i data-lucide="check-circle" class="w-9 h-9 text-emerald-600 dark:text-emerald-400"></i>
                    </div>
                    <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight">Transaksi Cash Berhasil</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $cashTransactionResult['event_name'] ?? '-' }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pembeli</p>
                        <p class="font-bold text-slate-800 dark:text-white">{{ $cashTransactionResult['buyer_name'] ?? '-' }}</p>
                        <p class="text-xs text-slate-500">{{ $cashTransactionResult['buyer_email'] ?? '-' }}</p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Invoice</p>
                        <p class="font-mono font-bold text-slate-800 dark:text-white">{{ $cashTransactionResult['invoice'] ?? '-' }}</p>
                        <p class="text-xs text-slate-500">{{ number_format($cashTransactionResult['quantity'] ?? 0) }} tiket</p>
                    </div>
                </div>

                <div class="space-y-2 rounded-xl border border-slate-100 dark:border-slate-700 p-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Subtotal</span>
                        <span class="font-bold text-slate-800 dark:text-white">Rp {{ number_format($cashTransactionResult['subtotal'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Pajak / Fee</span>
                        <span class="font-bold text-slate-800 dark:text-white">Rp {{ number_format($cashTransactionResult['tax'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between text-base border-t border-slate-100 dark:border-slate-700 pt-2">
                        <span class="font-bold text-slate-800 dark:text-white">Total Bayar</span>
                        <span class="font-black text-indigo-600">Rp {{ number_format($cashTransactionResult['total'] ?? 0) }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 text-center">
                        <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase">Pembayaran</p>
                        <p class="text-sm font-black text-emerald-700 dark:text-emerald-300">{{ $cashTransactionResult['payment_status'] ?? 'Lunas' }}</p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 text-center">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Kehadiran</p>
                        <p class="text-sm font-black text-slate-700 dark:text-slate-200">{{ $cashTransactionResult['attendance_status'] ?? 'Belum Hadir' }}</p>
                    </div>
                    <div class="p-3 rounded-xl {{ ($cashTransactionResult['email_status'] ?? '') === 'failed' ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-100 dark:border-amber-800' : 'bg-sky-50 dark:bg-sky-900/20 border-sky-100 dark:border-sky-800' }} border text-center">
                        <p class="text-[10px] font-bold uppercase {{ ($cashTransactionResult['email_status'] ?? '') === 'failed' ? 'text-amber-600 dark:text-amber-400' : 'text-sky-600 dark:text-sky-400' }}">Email Barcode</p>
                        <p class="text-sm font-black {{ ($cashTransactionResult['email_status'] ?? '') === 'failed' ? 'text-amber-700 dark:text-amber-300' : 'text-sky-700 dark:text-sky-300' }}">
                            {{ $cashTransactionResult['email_message'] ?? 'Email barcode telah dijadwalkan.' }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <x-admin.button wire:click="startAnotherCashTransaction" variant="secondary" icon="plus-circle" class="w-full">
                        Transaksi Baru
                    </x-admin.button>
                    <x-admin.button wire:click="viewLastCashTransaction" variant="primary" icon="external-link" class="w-full">
                        Lihat Transaksi
                    </x-admin.button>
                    <x-admin.button wire:click="closeCashTransactionSuccess" variant="ghost" icon="x-circle" class="w-full">
                        Tutup
                    </x-admin.button>
                </div>
            </div>
        @endif
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

</div>
