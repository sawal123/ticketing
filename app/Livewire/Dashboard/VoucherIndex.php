<?php

namespace App\Livewire\Dashboard;

use App\Models\Event;
use App\Models\Voucher;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class VoucherIndex extends Component
{
    use WithPagination;

    #[Layout('layouts.unified')]
    
    public $search = '';
    public $event_uid = '';
    
    // Form properties
    public $voucher_id;
    public $code;
    public $selected_event_uid;
    public $unit = 'persen'; // persen or rupiah
    public $nominal;
    public $min_beli = 0;
    public $max_disc = 0;
    public $limit = 100;
    public $status = 'active';

    public $isEditMode = false;
    public $confirmingDeletion = false;
    
    // Properti untuk List Transaksi
    public $selectedVoucherCode;
    public $transactions = [];
    
    // Stats
    public $totalVouchers = 0;
    public $activeVouchers = 0;
    public $totalUsedVouchers = 0;

    public function mount()
    {
        // Initial setup if needed
    }

    public function resetForm()
    {
        $this->reset(['voucher_id', 'code', 'selected_event_uid', 'unit', 'nominal', 'min_beli', 'max_disc', 'limit', 'status', 'isEditMode']);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->dispatch('open-modal', name: 'voucher-modal');
    }

    public function updatedUnit($value)
    {
        if ($value === 'rupiah') {
            $this->max_disc = $this->nominal;
        }
    }

    public function updatedNominal($value)
    {
        if ($value < 0) $this->nominal = 0;
        
        if ($this->unit === 'rupiah') {
            $this->max_disc = $this->nominal;
        }
        
        if ($this->unit === 'persen' && $value > 100) {
            $this->nominal = 100;
        }
    }

    public function updatedMinBeli($value)
    {
        if ($value < 0) $this->min_beli = 0;
    }

    public function updatedMaxDisc($value)
    {
        if ($value < 0) $this->max_disc = 0;
        
        if ($this->unit === 'rupiah') {
            $this->max_disc = $this->nominal;
        }
    }

    public function updatedLimit($value)
    {
        if ($value < 1) $this->limit = 1;
    }

    public function openEditModal($id)
    {
        $this->resetForm();
        $this->isEditMode = true;
        $voucher = Voucher::findOrFail($id);
        
        $this->voucher_id = $voucher->id;
        $this->code = $voucher->code;
        $this->selected_event_uid = $voucher->event_uid;
        $this->unit = $voucher->unit == '%' ? 'persen' : ($voucher->unit == 'rupiah' ? 'rupiah' : $voucher->unit);
        $this->nominal = $voucher->nominal;
        $this->min_beli = $voucher->min_beli;
        $this->max_disc = $voucher->max_disc;
        $this->limit = $voucher->limit;
        $this->status = $voucher->status;

        $this->dispatch('open-modal', name: 'voucher-modal');
    }

    public function save()
    {
        $rules = [
            'code' => 'required|string|max:50',
            'selected_event_uid' => 'required',
            'unit' => 'required|in:persen,rupiah',
            'nominal' => 'required|numeric|min:0' . ($this->unit === 'persen' ? '|max:100' : ''),
            'min_beli' => 'required|numeric|min:0',
            'max_disc' => 'nullable|numeric|min:0',
            'limit' => 'required|numeric|min:1',
        ];

        $this->validate($rules);

        if ($this->unit === 'rupiah') {
            $this->max_disc = $this->nominal;
        }

        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        $data = [
            'user_uid' => $ownerId,
            'event_uid' => $this->selected_event_uid,
            'code' => $this->code,
            'unit' => $this->unit,
            'nominal' => $this->nominal,
            'min_beli' => $this->min_beli,
            'max_disc' => $this->max_disc ?? 0,
            'limit' => $this->limit,
            'status' => $this->status,
        ];

        if ($this->isEditMode) {
            Voucher::find($this->voucher_id)->update($data);
            session()->flash('success', 'Voucher berhasil diperbarui.');
        } else {
            $data['uid'] = Str::uuid();
            $data['digunakan'] = 0;
            Voucher::create($data);
            session()->flash('success', 'Voucher berhasil dibuat.');
        }

        $this->dispatch('close-modal', name: 'voucher-modal');
        $this->resetForm();
    }

    public function toggleStatus($id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->status = $voucher->status === 'active' ? 'inactive' : 'active';
        $voucher->save();
        session()->flash('success', 'Status voucher berhasil diubah.');
    }

    public function confirmDelete($id)
    {
        $this->voucher_id = $id;
        $this->dispatch('open-modal', name: 'delete-modal');
    }

    public function delete()
    {
        \App\Models\Voucher::findOrFail($this->voucher_id)->delete();
        $this->dispatch('close-modal', name: 'delete-modal');
        session()->flash('success', 'Voucher berhasil dihapus.');
    }

    public function viewTransactions($code)
    {
        $this->selectedVoucherCode = $code;
        $this->transactions = \App\Models\Cart::whereHas('hargaCarts', function($q) use ($code) {
                $q->where('voucher', $code);
            })
            ->where('status', 'SUCCESS')
            ->with(['users', 'hargaCarts' => function($q) use ($code) {
                $q->with('masterHarga');
            }])
            ->latest()
            ->get();

        $this->dispatch('open-modal', name: 'transaction-modal');
    }

    public function render()
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        // Calculate Stats
        $statsQuery = Voucher::query();
        if (!$isAdmin) {
            $statsQuery->where('user_uid', $ownerId);
        }
        $this->totalVouchers = (clone $statsQuery)->count();
        $this->activeVouchers = (clone $statsQuery)->where('status', 'active')->count();
        
        // Total Used (Success)
        $this->totalUsedVouchers = \App\Models\Cart::where('status', 'SUCCESS')
            ->whereHas('hargaCarts', function($q) use ($isAdmin, $ownerId) {
                $q->whereNotNull('voucher');
                if (!$isAdmin) {
                    $q->whereHas('event', fn($e) => $e->where('user_uid', $ownerId));
                }
            })->count();

        $vouchersQuery = Voucher::query();
        if (!$isAdmin) {
            $vouchersQuery->where('user_uid', $ownerId);
        }

        if ($this->search) {
            $vouchersQuery->where('code', 'like', '%' . $this->search . '%');
        }

        if ($this->event_uid) {
            $vouchersQuery->where('event_uid', $this->event_uid);
        }

        $vouchers = $vouchersQuery->with(['event'])->withCount([
            'cartVoucher',
            'hargaCart as success_count' => function($q) {
                $q->whereHas('cart', function($p) {
                    $p->where('status', 'SUCCESS');
                });
            }
        ])->latest()->paginate(10);

        $eventsQuery = Event::query();
        if (!$isAdmin) {
            $eventsQuery->where('user_uid', $ownerId);
        }
        $events = $eventsQuery->get();

        return view('livewire.dashboard.voucher-index', [
            'vouchers' => $vouchers,
            'events' => $events,
        ]);
    }
}
