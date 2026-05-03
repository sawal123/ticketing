<?php

namespace App\Livewire\Dashboard;

use App\Models\Cart;
use App\Models\Penarikan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class PenarikanIndex extends Component
{
    use WithPagination;

    #[Layout('layouts.unified')]
    public $search = '';

    // Form properties
    public $penarikan_id;

    public $amount;

    public $note;

    public $isEditMode = false;

    // Stats
    public $totalSaldo = 0;

    public $pendingWithdrawal = 0;

    public $successWithdrawal = 0;

    protected $rules = [
        'amount' => 'required|numeric|min:10000',
        'note' => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->calculateStats();
    }

    public function calculateStats()
    {
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        // Total Saldo (SUCCESS & NOT Cash)
        // We use the same formula as in PenyewaController for consistency
        $rumusDasar = "
            (
                (harga_carts.quantity * harga_carts.harga_ticket) - 
                COALESCE(
                    CASE 
                        WHEN LOWER(vouchers.unit) = '%' OR LOWER(vouchers.unit) = 'persen' 
                        THEN 
                            CASE 
                                WHEN vouchers.max_disc > 0 AND ((harga_carts.quantity * harga_carts.harga_ticket) * (vouchers.nominal / 100)) > vouchers.max_disc
                                THEN vouchers.max_disc
                                ELSE (harga_carts.quantity * harga_carts.harga_ticket) * (vouchers.nominal / 100)
                            END
                        ELSE vouchers.nominal 
                    END, 
                0)
            ) 
            * (1 + (COALESCE(events.fee, 0) / 100))
        ";

        $this->totalSaldo = \App\Models\HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->leftJoin('vouchers', function($join) {
                $join->on('vouchers.code', '=', 'harga_carts.voucher')
                     ->on('vouchers.event_uid', '=', 'events.uid');
            })
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', $ownerId)
            ->where('carts.payment_type', '!=', 'cash')
            ->sum(\Illuminate\Support\Facades\DB::raw($rumusDasar));

        // Pending Withdrawal
        $this->pendingWithdrawal = Penarikan::where('uid_user', $ownerId)
            ->where('status', 'pending')
            ->sum('amount');

        // Success Withdrawal
        $this->successWithdrawal = Penarikan::where('uid_user', $ownerId)
            ->where('status', 'success')
            ->sum('amount');
    }

    public function resetForm()
    {
        $this->reset(['penarikan_id', 'amount', 'note', 'isEditMode']);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->dispatch('open-modal', name: 'penarikan-modal');
    }

    public function openEditModal($id)
    {
        $penarikan = Penarikan::findOrFail($id);

        if ($penarikan->status !== 'pending') {
            session()->flash('error', 'Hanya penarikan pending yang dapat diedit.');

            return;
        }

        $this->penarikan_id = $penarikan->id;
        $this->amount = $penarikan->amount;
        $this->note = $penarikan->note;
        $this->isEditMode = true;

        $this->dispatch('open-modal', name: 'penarikan-modal');
    }

    public function save()
    {
        $this->validate();
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        // Check if balance is enough
        $availableBalance = $this->totalSaldo - $this->successWithdrawal - $this->pendingWithdrawal;

        if (! $this->isEditMode && $this->amount > $availableBalance) {
            $this->addError('amount', 'Saldo tidak mencukupi.');

            return;
        }

        if ($this->isEditMode) {
            $penarikan = Penarikan::findOrFail($this->penarikan_id);
            if ($penarikan->status !== 'pending') {
                return;
            }

            // Check balance again for update
            $oldAmount = $penarikan->amount;
            if ($this->amount > ($availableBalance + $oldAmount)) {
                $this->addError('amount', 'Saldo tidak mencukupi.');

                return;
            }

            $penarikan->update([
                'amount' => $this->amount,
                'note' => $this->note,
            ]);
            session()->flash('success', 'Permintaan penarikan diperbarui.');
        } else {
            Penarikan::create([
                'uid' => (string) Str::uuid(),
                'uid_user' => $ownerId,
                'amount' => $this->amount,
                'note' => $this->note,
                'status' => 'pending',
            ]);
            session()->flash('success', 'Permintaan penarikan berhasil dikirim.');
        }

        $this->dispatch('close-modal', name: 'penarikan-modal');
        $this->resetForm();
        $this->calculateStats();
    }

    public function confirmDelete($id)
    {
        $penarikan = Penarikan::findOrFail($id);
        if ($penarikan->status !== 'pending') {
            session()->flash('error', 'Hanya penarikan pending yang dapat dihapus.');

            return;
        }
        $this->penarikan_id = $id;
        $this->dispatch('open-modal', name: 'delete-modal');
    }

    public function delete()
    {
        $penarikan = Penarikan::findOrFail($this->penarikan_id);
        if ($penarikan->status === 'pending') {
            $penarikan->delete();
            session()->flash('success', 'Permintaan penarikan dibatalkan.');
        }
        $this->dispatch('close-modal', name: 'delete-modal');
        $this->calculateStats();
    }

    public function render()
    {
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        $penarikans = Penarikan::where('uid_user', $ownerId)
            ->when($this->search, function ($q) {
                $q->where('note', 'like', '%'.$this->search.'%')
                    ->orWhere('amount', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.dashboard.penarikan-index', [
            'penarikans' => $penarikans,
        ]);
    }
}
