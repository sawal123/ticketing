@php
    use App\Models\Agreement;

    $statusClasses = match ($preview['agreement']['status']) {
        Agreement::STATUS_READY => 'bg-sky-50 text-sky-700 border-sky-200',
        Agreement::STATUS_COMPLETED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        Agreement::STATUS_REJECTED => 'bg-rose-50 text-rose-700 border-rose-200',
        Agreement::STATUS_CANCELLED => 'bg-slate-100 text-slate-700 border-slate-200',
        default => 'bg-indigo-50 text-indigo-700 border-indigo-200',
    };
@endphp

<div class="mb-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="border-b border-slate-200 bg-slate-50 px-6 py-5 dark:border-slate-700 dark:bg-slate-700/50">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.3em] text-indigo-500">Draft Dokumen Tambahan</p>
                <h3 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">
                    Addendum v{{ $preview['agreement']['version'] }}
                </h3>
                <p class="mt-2 max-w-2xl text-sm text-slate-500 dark:text-slate-300">
                    Dokumen ini merupakan addendum dari <strong>{{ strtoupper($preview['parent_agreement']['type'] ?? 'MOU') }} v{{ $preview['parent_agreement']['version'] ?? '1' }}</strong> (UID: {{ $preview['parent_agreement']['uid'] ?? '-' }}).
                </p>
            </div>
            <div class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                <div class="text-right">
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-slate-400">Status Addendum</p>
                    <span class="mt-1 inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold {{ $statusClasses }}">
                        {{ $preview['agreement']['status'] }}
                    </span>
                </div>
                <div class="text-right">
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-slate-400">Addendum UID</p>
                    <p class="font-mono text-xs text-slate-700 dark:text-slate-200">{{ $preview['agreement']['uid'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="p-6">
        <h4 class="mb-4 text-sm font-black uppercase tracking-[0.25em] text-slate-700 dark:text-white">
            Rincian Perubahan Kontraktual
        </h4>

        @if (!empty($preview['diffs']))
            <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:border-slate-700 dark:bg-slate-700/50 dark:text-slate-300">
                        <tr>
                            <th class="px-5 py-3 font-bold">Kategori</th>
                            <th class="px-5 py-3 font-bold">Field / Komponen</th>
                            <th class="px-5 py-3 font-bold text-rose-700">Data Sebelumnya</th>
                            <th class="px-5 py-3 font-bold text-emerald-700">Perubahan Baru</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/80 dark:bg-slate-800">
                        @foreach ($preview['diffs'] as $diff)
                            <tr class="transition hover:bg-slate-50/50 dark:hover:bg-slate-700/30">
                                <td class="px-5 py-3.5 font-bold text-slate-700 dark:text-slate-200">
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-700 dark:text-slate-200">
                                        {{ $diff['section'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 font-semibold text-slate-900 dark:text-white">{{ $diff['label'] }}</td>
                                <td class="px-5 py-3.5 text-rose-600 font-medium line-through decoration-rose-400">{{ $diff['before'] }}</td>
                                <td class="px-5 py-3.5 text-emerald-600 font-bold">{{ $diff['after'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-8 text-center text-sm text-slate-400 dark:text-slate-300">
                Tidak ada perbedaan nilai kontraktual yang terdeteksi.
            </div>
        @endif
    </div>
</div>
