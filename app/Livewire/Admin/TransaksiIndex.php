<?php

namespace App\Livewire\Admin;

use App\Models\Cart;
use Carbon\Carbon;
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

    private function allowedStatuses(): array
    {
        return [
            'all',
            Cart::STATUS_SUCCESS,
            Cart::STATUS_PENDING,
            Cart::STATUS_UNPAID,
            Cart::STATUS_EXPIRED,
            'FAILED',
            Cart::STATUS_CANCELLED,
            Cart::STATUS_RESERVED,
            Cart::STATUS_PAYMENT_REVIEW,
        ];
    }

    private function allowedPaymentTypes(): array
    {
        return ['all', 'cash', 'non-cash'];
    }

    private function sanitizeFilters(): void
    {
        $this->search = mb_substr(trim((string) $this->search), 0, 100);
        $this->eventUid = mb_substr(trim((string) $this->eventUid), 0, 100);

        if (! in_array($this->status, $this->allowedStatuses(), true)) {
            $this->status = 'SUCCESS';
        }

        if (! in_array($this->paymentType, $this->allowedPaymentTypes(), true)) {
            $this->paymentType = 'all';
        }

        $this->date = trim((string) $this->date);
        if ($this->date !== '' && ! $this->validDate($this->date)) {
            $this->date = '';
            session()->flash('error', 'Filter tanggal tidak valid.');
        }
    }

    private function validDate(string $date): bool
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d') === $date;
        } catch (\Throwable) {
            return false;
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingDate()
    {
        $this->resetPage();
    }

    public function updatingPaymentType()
    {
        $this->resetPage();
    }

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

        $hargaCartWithVoucher = $this->selectedTrx->hargaCarts->whereNotNull('voucher')->first();
        if ($hargaCartWithVoucher) {
            $this->voucherCode = $hargaCartWithVoucher->voucher;
        }
        $this->discount = $this->selectedTrx->hargaCarts->sum(fn ($item) => (int) ($item->disc ?? 0));

        $this->dispatch('open-modal', name: 'trx-detail-modal');
    }

    public function render()
    {
        $this->sanitizeFilters();

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
                $query->where(function ($q) {
                    $q->where('invoice', 'like', '%'.$this->search.'%')
                        ->orWhereHas('users', function ($u) {
                            $u->where('name', 'like', '%'.$this->search.'%')
                                ->orWhere('email', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.transaksi-index', [
            'transactions' => $transactions,
        ])->layout('admin.layout', ['title' => 'Daftar Transaksi']);
    }
}
