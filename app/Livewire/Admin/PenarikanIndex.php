<?php

namespace App\Livewire\Admin;

use App\Models\Bank;
use App\Models\Penarikan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PenarikanIndex extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = 'all'; // all, pending, success, failed

    public $selectedPenarikan = null;

    public array $selectedBank = [
        'bank_name' => null,
        'bank_account_name' => null,
        'bank_account_number' => null,
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $penarikans = Penarikan::query()
            ->with(['user'])
            ->when($this->search, function ($query) {
                $query->where(function ($searchQuery) {
                    $searchQuery->whereHas('user', function ($q) {
                        $q->where('name', 'like', '%'.$this->search.'%');
                    })->orWhere('note', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where(DB::raw('UPPER(status)'), strtoupper($this->statusFilter));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.penarikan-index', [
            'penarikans' => $penarikans,
        ])->layout('admin.layout', ['title' => 'Manajemen Penarikan']);
    }

    public function approve($uid)
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);

        $approved = DB::transaction(function () use ($uid) {
            $penarikan = Penarikan::where('uid', $uid)->lockForUpdate()->firstOrFail();

            if (! in_array(strtoupper((string) $penarikan->status), [
                Penarikan::STATUS_PENDING,
                Penarikan::STATUS_PROCESSING,
            ], true)) {
                return false;
            }

            $penarikan->update([
                'status' => Penarikan::STATUS_SUCCESS,
                'approved_at' => now(),
            ]);

            return true;
        }, 3);

        if (! $approved) {
            session()->flash('error', 'Penarikan hanya dapat disetujui jika masih pending atau processing.');

            return;
        }

        session()->flash('message', 'Penarikan berhasil disetujui!');
    }

    public function openDetail(string $uid): void
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);

        $penarikan = Penarikan::with('user')
            ->where('uid', $uid)
            ->firstOrFail();

        $this->selectedPenarikan = $penarikan;
        $this->selectedBank = $this->bankDetailsFor($penarikan);

        $this->dispatch('open-modal', name: 'penarikan-detail-modal');
    }

    private function bankDetailsFor(Penarikan $penarikan): array
    {
        $fallbackBank = null;

        if (! filled($penarikan->bank_name)
            || ! filled($penarikan->bank_account_name)
            || ! filled($penarikan->bank_account_number)) {
            $fallbackBank = Bank::where(function ($q) use ($penarikan) {
                $q->where('uid_user', $penarikan->uid_user)
                    ->orWhere('uid', $penarikan->uid_user);
            })->latest()->first();
        }

        return [
            'bank_name' => filled($penarikan->bank_name) ? $penarikan->bank_name : ($fallbackBank->bank ?? null),
            'bank_account_name' => filled($penarikan->bank_account_name) ? $penarikan->bank_account_name : ($fallbackBank->nama ?? null),
            'bank_account_number' => filled($penarikan->bank_account_number) ? $penarikan->bank_account_number : ($fallbackBank->norek ?? null),
        ];
    }
}
