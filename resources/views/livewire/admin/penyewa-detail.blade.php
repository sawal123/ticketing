<div>
    @php
        $display = fn ($value) => filled($value) ? $value : '-';
        $profileImage = filled($penyewa->gambar) ? asset('storage/user/' . $penyewa->gambar) : null;
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Detail Penyewa</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Profil, rekening, dan event milik penyewa.</p>
        </div>
        <a href="{{ route('admin.user', ['activeTab' => 'penyewa']) }}" wire:navigate>
            <x-admin.button variant="secondary" icon="arrow-left">Kembali</x-admin.button>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-admin.card class="xl:col-span-2" title="Profil Penyewa" icon="user" iconColor="indigo">
            <div class="flex flex-col gap-6 md:flex-row">
                <div class="flex-shrink-0">
                    @if($profileImage)
                        <img src="{{ $profileImage }}" alt="{{ $penyewa->name }}"
                            class="h-28 w-28 rounded-2xl border border-slate-200 object-cover dark:border-slate-700"
                            onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($penyewa->name) }}&color=4F46E5&background=EEF2FF'">
                    @else
                        <div class="flex h-28 w-28 items-center justify-center rounded-2xl border border-slate-200 bg-indigo-50 text-3xl font-bold text-indigo-600 dark:border-slate-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                            {{ strtoupper(substr($penyewa->name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                <dl class="grid flex-1 grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Nama</dt>
                        <dd class="mt-1 font-semibold text-slate-800 dark:text-white">{{ $display($penyewa->name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</dt>
                        <dd class="mt-1 font-semibold text-slate-800 dark:text-white">{{ $display($penyewa->email) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Nomor HP</dt>
                        <dd class="mt-1 text-slate-700 dark:text-slate-300">{{ $display($penyewa->nomor) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Tanggal Lahir</dt>
                        <dd class="mt-1 text-slate-700 dark:text-slate-300">{{ $display($penyewa->birthday) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Gender</dt>
                        <dd class="mt-1 text-slate-700 dark:text-slate-300">{{ $display($penyewa->gender) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Kota</dt>
                        <dd class="mt-1 text-slate-700 dark:text-slate-300">{{ $display($penyewa->kota) }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Alamat</dt>
                        <dd class="mt-1 text-slate-700 dark:text-slate-300">{{ $display($penyewa->alamat) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Tanggal Bergabung</dt>
                        <dd class="mt-1 text-slate-700 dark:text-slate-300">{{ optional($penyewa->created_at)->format('d M Y') ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </x-admin.card>

        <x-admin.card title="Rekening Bank" icon="landmark" iconColor="emerald">
            @if($bank)
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Bank</dt>
                        <dd class="mt-1 font-semibold text-slate-800 dark:text-white">{{ $display($bank->bank) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Nomor Rekening</dt>
                        <dd class="mt-1 font-semibold text-slate-800 dark:text-white">{{ $display($bank->norek) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Nama Pemilik</dt>
                        <dd class="mt-1 font-semibold text-slate-800 dark:text-white">{{ $display($bank->nama) }}</dd>
                    </div>
                </dl>
            @else
                <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm font-medium text-slate-500 dark:border-slate-600 dark:text-slate-400">
                    Belum menambahkan rekening
                </div>
            @endif
        </x-admin.card>
    </div>

    <div class="my-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-admin.card padding="p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Event</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-800 dark:text-white">{{ number_format($summary['total']) }}</p>
        </x-admin.card>
        <x-admin.card padding="p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Aktif</p>
            <p class="mt-2 text-2xl font-extrabold text-emerald-600">{{ number_format($summary['active']) }}</p>
        </x-admin.card>
        <x-admin.card padding="p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pending/Belum Aktif</p>
            <p class="mt-2 text-2xl font-extrabold text-amber-600">{{ number_format($summary['pending']) }}</p>
        </x-admin.card>
        <x-admin.card padding="p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ditutup</p>
            <p class="mt-2 text-2xl font-extrabold text-rose-600">{{ number_format($summary['closed']) }}</p>
        </x-admin.card>
    </div>

    <x-admin.table title="Event Milik Penyewa" :headers="['Nama Event', 'Tanggal', 'Status', 'Konfirmasi', 'Dibuat', 'Aksi']" :count="$events->total()">
        @forelse($events as $event)
            <tr class="table-row-hover transition-colors">
                <td class="px-5 py-4">
                    <span class="font-bold text-slate-800 dark:text-white">{{ $event->event }}</span>
                </td>
                <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-400">{{ $display($event->tanggal) }}</td>
                <td class="px-5 py-4">
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-semibold text-slate-600 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300">
                        {{ $display($event->status) }}
                    </span>
                </td>
                <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-400">
                    {{ (string) $event->konfirmasi === '1' ? 'Terkonfirmasi' : 'Belum dikonfirmasi' }}
                </td>
                <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-400">
                    {{ optional($event->created_at)->format('d M Y H:i') ?? '-' }}
                </td>
                <td class="px-5 py-4 text-center">
                    <a href="{{ route('admin.event.detail', $event->uid) }}" wire:navigate>
                        <x-admin.button variant="ghost" size="sm" icon="eye" class="text-indigo-600" title="Lihat Event">
                            Lihat Event
                        </x-admin.button>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                    <div class="flex flex-col items-center justify-center">
                        <i data-lucide="calendar-x" class="mb-2 h-12 w-12 opacity-20"></i>
                        <p>Belum ada event untuk penyewa ini.</p>
                    </div>
                </td>
            </tr>
        @endforelse

        <x-slot name="pagination">
            {{ $events->links('components.admin.pagination') }}
        </x-slot>
    </x-admin.table>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', () => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        });
    </script>
</div>
