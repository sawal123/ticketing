<div>
    <!-- Page Title -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Dashboard Overview</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Ringkasan aktivitas dan metrik utama platform Anda.</p>
    </div>

    <!-- Top Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Total Users Card -->
        <x-admin.card icon="users" iconColor="indigo" padding="p-6">
            <div class="flex flex-col">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Total Users</p>
                <div class="flex items-end justify-between" x-data="{
                    init() {
                        new Chart(this.$refs.spark, {
                            type: 'line',
                            data: {
                                labels: ['', '', '', '', '', '', ''],
                                datasets: [{ data: [1, 5, 2, 8, 4, 9, 3], borderColor: '#6366f1' }]
                            },
                            options: sparklineOptions
                        });
                    }
                }">
                    <h3 class="text-3xl font-extrabold text-slate-800 dark:text-white">{{ number_format($totalUsers) }}</h3>
                    <div class="w-24 h-10">
                        <canvas x-ref="spark"></canvas>
                    </div>
                </div>
            </div>
        </x-admin.card>

        <!-- Total Omset Card -->
        <x-admin.card icon="dollar-sign" iconColor="emerald" padding="p-6">
            <div class="flex flex-col">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Total Omset</p>
                <div class="flex items-end justify-between" x-data="{
                    revenue: @js($revenueTrend),
                    init() {
                        new Chart(this.$refs.spark, {
                            type: 'line',
                            data: {
                                labels: ['', '', '', '', '', '', ''],
                                datasets: [{ data: this.revenue, borderColor: '#10b981' }]
                            },
                            options: sparklineOptions
                        });
                    }
                }">
                    <h3 class="text-3xl font-extrabold text-slate-800 dark:text-white">Rp {{ number_format($totalOmset, 0, ',', '.') }}</h3>
                    <div class="w-24 h-10">
                        <canvas x-ref="spark"></canvas>
                    </div>
                </div>
            </div>
        </x-admin.card>

        <!-- Total Transactions Card -->
        <x-admin.card icon="shopping-cart" iconColor="amber" padding="p-6">
            <div class="flex flex-col">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Total Transaction</p>
                <div class="flex items-end justify-between" x-data="{
                    init() {
                        new Chart(this.$refs.spark, {
                            type: 'line',
                            data: {
                                labels: ['', '', '', '', '', '', ''],
                                datasets: [{ data: [10, 15, 8, 20, 12, 25, 18], borderColor: '#f59e0b' }]
                            },
                            options: sparklineOptions
                        });
                    }
                }">
                    <h3 class="text-3xl font-extrabold text-slate-800 dark:text-white">{{ number_format($totalTransactions) }}</h3>
                    <div class="w-24 h-10">
                        <canvas x-ref="spark"></canvas>
                    </div>
                </div>
            </div>
        </x-admin.card>
    </div>

    <script>
        window.sparklineOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: { x: { display: false }, y: { display: false } },
            elements: { 
                line: { tension: 0.4, borderWidth: 2 },
                point: { radius: 0 }
            }
        };
    </script>

    <!-- Main Trend Chart -->
    <div class="mb-8" x-data="{
        labels: @js($chartLabels),
        revenue: @js($revenueTrend),
        cash: @js($cashTrend),
        nonCash: @js($nonCashTrend),
        init() {
            this.renderChart();
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

    <!-- Demographics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Gender Distribution -->
        <x-admin.card title="Demografi Gender" icon="pie-chart" iconColor="indigo">
            <div class="flex flex-col md:flex-row items-center justify-around py-4" x-data="{
                genderData: @js(array_values($genderData)),
                init() {
                    new Chart(this.$refs.genderChart, {
                        type: 'doughnut',
                        data: {
                            labels: ['Pria', 'Wanita'],
                            datasets: [{
                                data: this.genderData,
                                backgroundColor: ['#6366f1', '#f472b6'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: { legend: { display: false } }
                        }
                    });
                }
            }">
                <div class="w-48 h-48 mb-4 md:mb-0">
                    <canvas x-ref="genderChart"></canvas>
                </div>
                <div class="space-y-4 w-full md:w-auto">
                    <div class="flex items-center justify-between gap-8">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-indigo-500"></span>
                            <span class="text-sm text-slate-600 dark:text-slate-400">Pria</span>
                        </div>
                        <span class="text-sm font-bold">{{ $genderData['pria'] }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-8">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-rose-400"></span>
                            <span class="text-sm text-slate-600 dark:text-slate-400">Wanita</span>
                        </div>
                        <span class="text-sm font-bold">{{ $genderData['wanita'] }}</span>
                    </div>
                </div>
            </div>
        </x-admin.card>

        <!-- Age Distribution -->
        <x-admin.card title="Demografi Usia" icon="bar-chart-2" iconColor="emerald">
            <div class="py-4 space-y-6">
                @php
                    $totalAge = array_sum($ageData);
                @endphp
                @foreach($ageData as $label => $count)
                    @php
                        $percentage = $totalAge > 0 ? round(($count / $totalAge) * 100) : 0;
                        $color = $loop->first ? 'bg-indigo-500' : ($loop->remaining == 0 ? 'bg-rose-400' : 'bg-emerald-400');
                    @endphp
                    <div>
                        <div class="flex justify-between mb-1.5">
                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase">{{ $label }} thn</span>
                            <span class="text-xs font-bold text-indigo-600">{{ $percentage }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2">
                            <div class="{{ $color }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.card>
    </div>

    <!-- Recent Transactions Table -->
    <x-admin.table 
        title="Transaksi Terakhir" 
        subtitle="Data transaksi sukses terbaru"
        :headers="['User', 'Event', 'Amount', 'Invoice', 'Tanggal', 'Aksi']"
    >
        @foreach($recentTransactions as $transaction)
            <tr class="table-row-hover transition-colors">
                <td class="px-5 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600">
                            {{ substr($transaction->user->name ?? '?', 0, 1) }}
                        </div>
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $transaction->user->name ?? 'N/A' }}</span>
                    </div>
                </td>
                <td class="px-5 py-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                    {{ Str::limit($transaction->event->event ?? 'N/A', 25) }}
                </td>
                <td class="px-5 py-4 font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                    Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                </td>
                <td class="px-5 py-4 text-xs font-mono text-slate-500 dark:text-slate-400 whitespace-nowrap">
                    {{ Str::limit($transaction->invoice, 10, '...') }}
                </td>
                <td class="px-5 py-4 text-slate-600 dark:text-slate-400 whitespace-nowrap text-sm">
                    {{ $transaction->created_at->format('d M Y, H:i') }}
                </td>
                <td class="px-5 py-4 text-center">
                    <x-admin.button variant="ghost" size="sm" icon="external-link">
                        Detail
                    </x-admin.button>
                </td>
            </tr>
        @endforeach
    </x-admin.table>
</div>
