<?php

namespace App\Livewire\Admin;

use App\Models\Cart;
use Livewire\Component;
use Livewire\WithPagination;

class TransaksiIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $filter = 'all'; // all, cash, non-cash
    public $eventUid = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filter' => ['except' => 'all'],
        'eventUid' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $transactions = Cart::query()
            ->with(['users', 'event'])
            ->where('status', 'SUCCESS')
            ->when($this->eventUid, function ($query) {
                $query->where('event_uid', $this->eventUid);
            })
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('invoice', 'like', '%' . $this->search . '%')
                      ->orWhereHas('users', function ($u) {
                          $u->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->filter === 'cash', function ($query) {
                $query->where('payment_type', 'cash');
            })
            ->when($this->filter === 'non-cash', function ($query) {
                $query->where('payment_type', '!=', 'cash');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.transaksi-index', [
            'transactions' => $transactions
        ])->layout('admin.layout', ['title' => 'Daftar Transaksi']);
    }
}
