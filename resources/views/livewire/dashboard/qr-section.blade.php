<div class="mb-8">
    <style>
        .qr-container svg {
            width: 180px !important;
            height: 180px !important;
            display: block;
        }
    </style>
    <x-admin.card title="QR Code Event" icon="qr-code" iconColor="indigo">
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
            Scan QR untuk mengarahkan pengunjung ke halaman tiket event. Setiap scan tercatat sebagai analitik.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            @forelse($events as $event)
                <div
                    class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex flex-col items-center text-center"
                    x-data="qrDownloader('{{ $event->uid }}', 'qr-{{ $event->slug }}', '{{ route('qr.scan.download.pdf', ['uid' => $event->uid]) }}')"
                >
                    <p class="text-sm font-bold text-slate-800 dark:text-white mb-3 line-clamp-2">{{ $event->event }}</p>
                    <div x-ref="qr" class="qr-container bg-white p-3 rounded-xl border border-slate-100 dark:border-slate-600 mb-3">
                        {!! $qrCodes[$event->uid] !!}
                    </div>
                    <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 mb-3">
                        {{ (int) ($scansPerEvent[$event->uid] ?? 0) }} scan
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="downloadPng()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition-colors"
                        >
                            <i data-lucide="image-down" class="w-4 h-4"></i>
                            PNG
                        </button>
                        <button
                            type="button"
                            @click="downloadPdf()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-rose-500 text-white hover:bg-rose-600 transition-colors"
                        >
                            <i data-lucide="file-down" class="w-4 h-4"></i>
                            PDF
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center bg-white dark:bg-slate-800 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700 col-span-full">
                    <p class="text-slate-400 text-sm font-medium">Belum ada event aktif.</p>
                </div>
            @endforelse
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800">
                <p class="text-[10px] font-bold text-indigo-500 dark:text-indigo-300 uppercase tracking-widest">Total Scan</p>
                <p class="text-2xl font-black text-indigo-700 dark:text-indigo-300">{{ $totalScans }}</p>
            </div>
        </div>

        <div class="h-56 w-full" x-data="{
            labels: @js($chart['labels']),
            scans: @js($chart['scans']),
            init() {
                this.labels = Object.values(this.labels || {});
                this.scans = Object.values(this.scans || {});
                this.$nextTick(() => this.renderChart());
            },
            renderChart() {
                new Chart(this.$refs.qrScansChart, {
                    type: 'bar',
                    data: {
                        labels: this.labels,
                        datasets: [{
                            label: 'Scan',
                            data: this.scans,
                            backgroundColor: 'rgba(99, 102, 241, 0.8)',
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            }
        }">
            <canvas x-ref="qrScansChart"></canvas>
        </div>
    </x-admin.card>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('qrDownloader', (uid, filename, pdfUrl) => ({
                uid: uid,
                filename: filename,
                pdfUrl: pdfUrl,
                downloading: false,

                toPngDataUrl() {
                    const svg = this.$refs.qr.querySelector('svg');

                    if (! svg) {
                        return Promise.reject(new Error('QR SVG tidak ditemukan.'));
                    }

                    const source = new XMLSerializer().serializeToString(svg);
                    const blob = new Blob([source], { type: 'image/svg+xml;charset=utf-8' });
                    const url = URL.createObjectURL(blob);

                    return new Promise((resolve, reject) => {
                        const img = new Image();

                        img.onload = () => {
                            const canvas = document.createElement('canvas');
                            canvas.width = 440;
                            canvas.height = 440;

                            const ctx = canvas.getContext('2d');
                            ctx.fillStyle = '#ffffff';
                            ctx.fillRect(0, 0, canvas.width, canvas.height);
                            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                            URL.revokeObjectURL(url);
                            resolve(canvas.toDataURL('image/png'));
                        };

                        img.onerror = () => {
                            URL.revokeObjectURL(url);
                            reject(new Error('Gagal memuat QR SVG.'));
                        };

                        img.src = url;
                    });
                },

                saveBlob(url, name) {
                    const anchor = document.createElement('a');
                    anchor.href = url;
                    anchor.download = name;
                    document.body.appendChild(anchor);
                    anchor.click();
                    anchor.remove();
                },

                async downloadPng() {
                    const dataUrl = await this.toPngDataUrl();
                    this.saveBlob(dataUrl, this.filename + '.png');
                },

                async downloadPdf() {
                    if (this.downloading) {
                        return;
                    }

                    this.downloading = true;

                    try {
                        const dataUrl = await this.toPngDataUrl();
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

                        const response = await fetch(this.pdfUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Content-Type': 'application/json',
                                'Accept': 'application/pdf',
                            },
                            body: JSON.stringify({ image: dataUrl }),
                        });

                        if (! response.ok) {
                            throw new Error('Gagal membuat PDF.');
                        }

                        const blob = await response.blob();
                        const url = URL.createObjectURL(blob);
                        this.saveBlob(url, this.filename + '.pdf');
                        URL.revokeObjectURL(url);
                    } finally {
                        this.downloading = false;
                    }
                },
            }));
        });
    </script>
</div>
