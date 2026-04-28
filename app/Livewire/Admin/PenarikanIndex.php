<?php

namespace App\Livewire\Admin;

use App\Models\Penarikan;
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
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })->orWhere('note', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.penarikan-index', [
            'penarikans' => $penarikans
        ])->layout('admin.layout', ['title' => 'Manajemen Penarikan']);
    }

    public function approve($uid)
    {
        $penarikan = Penarikan::where('uid', $uid)->firstOrFail();
        $penarikan->update(['status' => 'success']);
        
        session()->flash('message', 'Penarikan berhasil disetujui!');
    }
}
