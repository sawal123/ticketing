<?php

namespace App\Livewire\Admin;

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
}
