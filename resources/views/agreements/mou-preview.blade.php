@php
    use App\Models\Agreement;

    $statusClasses = match ($preview['agreement']['status']) {
        Agreement::STATUS_READY => 'bg-sky-50 text-sky-700 border-sky-200',
        Agreement::STATUS_SENT_TO_PRIVY => 'bg-violet-50 text-violet-700 border-violet-200',
        Agreement::STATUS_SIGNING => 'bg-amber-50 text-amber-700 border-amber-200',
        Agreement::STATUS_COMPLETED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        Agreement::STATUS_REJECTED => 'bg-rose-50 text-rose-700 border-rose-200',
        Agreement::STATUS_CANCELLED => 'bg-slate-100 text-slate-700 border-slate-200',
        default => 'bg-indigo-50 text-indigo-700 border-indigo-200',
    };
@endphp

<div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="border-b border-slate-200 bg-slate-50 px-6 py-5 dark:border-slate-700 dark:bg-slate-700/50">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.3em] text-slate-400">Preview Dokumen</p>
                <h3 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">MOU Event</h3>
                <p class="mt-2 max-w-2xl text-sm text-slate-500 dark:text-slate-300">
                    Preview ini membaca data live event saat ini dan belum membekukan snapshot agreement.
                </p>
            </div>
            <div class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                <div class="text-right">
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-slate-400">Status Agreement</p>
                    <span class="mt-1 inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold {{ $statusClasses }}">
                        {{ $preview['agreement']['status'] }}
                    </span>
                </div>
                <div class="text-right">
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-slate-400">Agreement UID</p>
                    <p class="font-mono text-xs text-slate-700 dark:text-slate-200">{{ $preview['agreement']['uid'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 p-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 p-5 dark:border-slate-700 dark:bg-slate-900/50">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Informasi Agreement</h4>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500 dark:text-slate-400">Tipe</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ strtoupper($preview['agreement']['type']) }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500 dark:text-slate-400">Versi</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['agreement']['version'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['agreement']['status'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 p-5 dark:border-slate-700 dark:bg-slate-900/50">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Status Dokumen</h4>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500 dark:text-slate-400">Rekening Event</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['bank_account']['verification_status'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500 dark:text-slate-400">Surat Penyelenggara</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['organizer_letter']['verification_status'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500 dark:text-slate-400">Payment OTP</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">
                        {{ $preview['commercial']['payment_otp_enabled'] ? 'Aktif' : 'Nonaktif' }}
                    </dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 p-5 dark:border-slate-700 dark:bg-slate-900/50">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Identitas Penyelenggara</h4>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Nama Penyelenggara</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['organizer']['organizer_name'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Penanggung Jawab</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">
                        {{ $preview['organizer']['responsible_name'] }} <span class="font-normal text-slate-500 dark:text-slate-400">({{ $preview['organizer']['responsible_position'] }})</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Kontak</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['organizer']['phone'] }}</dd>
                    <dd class="text-slate-700 dark:text-slate-300">{{ $preview['organizer']['email'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Alamat</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['organizer']['address'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 p-5 dark:border-slate-700 dark:bg-slate-900/50">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Informasi Event</h4>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Nama Event</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['event']['name'] }}</dd>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Mulai Penjualan</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['event']['start_sale'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Jenis Biaya Pembeli</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['event']['ticket_tax']['mode_label'] }}</dd>
                    </div>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Biaya Pembeli</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['event']['ticket_tax']['value'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Sumber Biaya Pembeli</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">events.fee</dd>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Mulai Event</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['event']['start'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Selesai Event</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['event']['end'] }}</dd>
                    </div>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Venue</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['event']['venue_name'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Alamat Venue</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['event']['venue_address'] }}</dd>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Kota / Kabupaten</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['event']['venue_city'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Provinsi</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['event']['venue_province'] }}</dd>
                    </div>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Alamat Legacy</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['event']['legacy_address'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 p-5 dark:border-slate-700 dark:bg-slate-900/50">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Rekening Pencairan</h4>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Nama Bank</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['bank_account']['bank_name'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Nomor Rekening</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['bank_account']['account_number'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Atas Nama</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['bank_account']['account_holder_name'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Status Verifikasi</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['bank_account']['verification_status'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 p-5 dark:border-slate-700 dark:bg-slate-900/50">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Dokumen Penyelenggara</h4>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Nomor Surat</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['organizer_letter']['document_number'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Tanggal Surat</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['organizer_letter']['document_date'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Nama File</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['organizer_letter']['original_name'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Status Verifikasi</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['organizer_letter']['verification_status'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 p-5 lg:col-span-2 dark:border-slate-700 dark:bg-slate-900/50">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Konfigurasi Komersial</h4>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-slate-400 dark:text-slate-300">Metode Pembayaran Aktif</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">
                        {{ count($preview['commercial']['active_payment_methods']) > 0 ? implode(', ', $preview['commercial']['active_payment_methods']) : 'Belum dikonfigurasi' }}
                    </p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-slate-400 dark:text-slate-300">Payment OTP</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">
                        {{ $preview['commercial']['payment_otp_enabled'] ? 'Aktif' : 'Nonaktif' }}
                    </p>
                </div>
            </div>

            <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Gateway</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Fee Mode</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Fixed Fee</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Percent Fee</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                        @forelse ($preview['commercial']['payment_gateways'] as $gateway)
                            <tr class="dark:hover:bg-slate-700/30">
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">{{ $gateway['payment'] }}</td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                                    {{ $gateway['effective_is_active'] ? 'Aktif' : 'Nonaktif' }}
                                </td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ strtoupper($gateway['fee_mode']) }}</td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">Rp {{ number_format((float) $gateway['resolved_fee_fixed'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $gateway['resolved_fee_percent'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500 dark:text-slate-400">Belum dikonfigurasi</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
