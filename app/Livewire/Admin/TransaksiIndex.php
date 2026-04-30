<?php

namespace App\Livewire\Admin;

use App\Models\Cart;
use Livewire\Component;
use Livewire\WithPagination;

class TransaksiIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $status = 'SUCCESS'; // Default to SUCCESS
    public $date = '';
    public $paymentType = 'all'; // all, cash, non-cash
    public $eventUid = '';
    public $selectedTrx = null;
    public $discount = 0;
    public $voucherCode = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
        'date' => ['except' => ''],
        'paymentType' => ['except' => 'all'],
        'eventUid' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatus() { $this->resetPage(); }
    public function updatingDate() { $this->resetPage(); }
    public function updatingPaymentType() { $this->resetPage(); }

    public function resetFilters()
    {
        $this->search = '';
        $this->status = 'SUCCESS';
        $this->date = '';
        $this->paymentType = 'all';
        $this->resetPage();
    }

    public function openDetail($id)
    {
        $this->selectedTrx = Cart::with(['event', 'users', 'hargaCarts'])->findOrFail($id);
        
        $this->discount = 0;
        $this->voucherCode = null;
        
        $cartVoucher = \App\Models\CartVoucher::where('uid', $this->selectedTrx->uid)->first();
        if ($cartVoucher) {
            $this->voucherCode = $cartVoucher->code;
            $this->discount = $cartVoucher->nominal;
        }

        $this->dispatch('open-modal', name: 'trx-detail-modal');
    }

    public function render()
    {
        $transactions = Cart::query()
            ->with(['users', 'event', 'hargaCarts'])
            // Status Filter
            ->when($this->status !== 'all', function ($query) {
                $query->where('status', $this->status);
            })
            // Date Filter
            ->when($this->date, function ($query) {
                $query->whereDate('created_at', $this->date);
            })
            // Event Filter
            ->when($this->eventUid, function ($query) {
                $query->where('event_uid', $this->eventUid);
            })
            // Payment Type Filter
            ->when($this->paymentType === 'cash', function ($query) {
                $query->where('payment_type', 'cash');
            })
            ->when($this->paymentType === 'non-cash', function ($query) {
                $query->where('payment_type', '!=', 'cash');
            })
            // Search (Invoice, Email, Name)
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('invoice', 'like', '%' . $this->search . '%')
                      ->orWhereHas('users', function ($u) {
                          $u->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.transaksi-index', [
            'transactions' => $transactions
        ])->layout('admin.layout', ['title' => 'Daftar Transaksi']);
    }
}
