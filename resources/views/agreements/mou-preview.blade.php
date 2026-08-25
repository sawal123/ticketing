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

<div class="rounded-[2rem] border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.3em] text-slate-400">Preview Dokumen</p>
                <h3 class="mt-2 text-2xl font-black text-slate-900">MOU Event</h3>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">
                    Preview ini membaca data live event saat ini dan belum membekukan snapshot agreement.
                </p>
            </div>
            <div class="space-y-2 text-sm text-slate-600">
                <div class="text-right">
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-slate-400">Status Agreement</p>
                    <span class="mt-1 inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold {{ $statusClasses }}">
                        {{ $preview['agreement']['status'] }}
                    </span>
                </div>
                <div class="text-right">
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-slate-400">Agreement UID</p>
                    <p class="font-mono text-xs text-slate-700">{{ $preview['agreement']['uid'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 p-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 p-5">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500">Informasi Agreement</h4>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Tipe</dt>
                    <dd class="font-semibold text-slate-900">{{ strtoupper($preview['agreement']['type']) }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Versi</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['agreement']['version'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Status</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['agreement']['status'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 p-5">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500">Status Dokumen</h4>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Rekening Event</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['bank_account']['verification_status'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Surat Penyelenggara</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['organizer_letter']['verification_status'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Payment OTP</dt>
                    <dd class="font-semibold text-slate-900">
                        {{ $preview['commercial']['payment_otp_enabled'] ? 'Aktif' : 'Nonaktif' }}
                    </dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 p-5">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500">Identitas Penyelenggara</h4>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Nama Penyelenggara</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['organizer']['organizer_name'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Penanggung Jawab</dt>
                    <dd class="font-semibold text-slate-900">
                        {{ $preview['organizer']['responsible_name'] }} <span class="font-normal text-slate-500">({{ $preview['organizer']['responsible_position'] }})</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Kontak</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['organizer']['phone'] }}</dd>
                    <dd class="text-slate-700">{{ $preview['organizer']['email'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Alamat</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['organizer']['address'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 p-5">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500">Informasi Event</h4>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Nama Event</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['event']['name'] }}</dd>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Mulai Penjualan</dt>
                        <dd class="font-semibold text-slate-900">{{ $preview['event']['start_sale'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Jenis Biaya Pembeli</dt>
                        <dd class="font-semibold text-slate-900">{{ $preview['event']['ticket_tax']['mode_label'] }}</dd>
                    </div>
                </div>
                <div>
                    <dt class="text-slate-500">Biaya Pembeli</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['event']['ticket_tax']['value'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Sumber Biaya Pembeli</dt>
                    <dd class="font-semibold text-slate-900">events.fee</dd>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Mulai Event</dt>
                        <dd class="font-semibold text-slate-900">{{ $preview['event']['start'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Selesai Event</dt>
                        <dd class="font-semibold text-slate-900">{{ $preview['event']['end'] }}</dd>
                    </div>
                </div>
                <div>
                    <dt class="text-slate-500">Venue</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['event']['venue_name'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Alamat Venue</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['event']['venue_address'] }}</dd>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Kota / Kabupaten</dt>
                        <dd class="font-semibold text-slate-900">{{ $preview['event']['venue_city'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Provinsi</dt>
                        <dd class="font-semibold text-slate-900">{{ $preview['event']['venue_province'] }}</dd>
                    </div>
                </div>
                <div>
                    <dt class="text-slate-500">Alamat Legacy</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['event']['legacy_address'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 p-5">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500">Rekening Pencairan</h4>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Nama Bank</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['bank_account']['bank_name'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Nomor Rekening</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['bank_account']['account_number'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Atas Nama</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['bank_account']['account_holder_name'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Status Verifikasi</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['bank_account']['verification_status'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 p-5">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500">Dokumen Penyelenggara</h4>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Nomor Surat</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['organizer_letter']['document_number'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Tanggal Surat</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['organizer_letter']['document_date'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Nama File</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['organizer_letter']['original_name'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Status Verifikasi</dt>
                    <dd class="font-semibold text-slate-900">{{ $preview['organizer_letter']['verification_status'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 p-5 lg:col-span-2">
            <h4 class="text-sm font-black uppercase tracking-[0.25em] text-slate-500">Konfigurasi Komersial</h4>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-slate-400">Metode Pembayaran Aktif</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">
                        {{ count($preview['commercial']['active_payment_methods']) > 0 ? implode(', ', $preview['commercial']['active_payment_methods']) : 'Belum dikonfigurasi' }}
                    </p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-slate-400">Payment OTP</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">
                        {{ $preview['commercial']['payment_otp_enabled'] ? 'Aktif' : 'Nonaktif' }}
                    </p>
                </div>
            </div>

            <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.25em] text-slate-500">Gateway</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.25em] text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.25em] text-slate-500">Fee Mode</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.25em] text-slate-500">Fixed Fee</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.25em] text-slate-500">Percent Fee</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($preview['commercial']['payment_gateways'] as $gateway)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $gateway['payment'] }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $gateway['effective_is_active'] ? 'Aktif' : 'Nonaktif' }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ strtoupper($gateway['fee_mode']) }}</td>
                                <td class="px-4 py-3 text-slate-700">Rp {{ number_format((float) $gateway['resolved_fee_fixed'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $gateway['resolved_fee_percent'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum dikonfigurasi</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
