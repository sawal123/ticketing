<?php

namespace App\Livewire\Dashboard;

use App\Models\Penarikan;
use App\Models\User;
use App\Services\Withdrawals\WithdrawalBalanceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        'amount' => 'required|integer|min:10000|max:100000000',
        'note' => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->calculateStats();
    }

    public function calculateStats()
    {
        $ownerId = $this->ownerUid();
        $balances = app(WithdrawalBalanceService::class);

        $this->totalSaldo = $balances->grossEarningsFor($ownerId);
        $this->pendingWithdrawal = $balances->deductedWithdrawalsFor($ownerId, ['PENDING', 'PROCESSING']);
        $this->successWithdrawal = $balances->deductedWithdrawalsFor($ownerId, ['SUCCESS']);
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
        $penarikan = $this->ownedPenarikanQuery()
            ->where('id', $id)
            ->firstOrFail();

        if (strtoupper((string) $penarikan->status) !== 'PENDING') {
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
        $ownerId = $this->ownerUid();
        $amount = (int) $this->amount;
        $note = $this->note;

        try {
            DB::transaction(function () use ($ownerId, $amount, $note) {
                User::where('uid', $ownerId)->lockForUpdate()->firstOrFail();

                $balances = app(WithdrawalBalanceService::class);
                $availableBalance = $balances->availableBalanceFor($ownerId);

                if ($this->isEditMode) {
                    $penarikan = $this->ownedPenarikanQuery()
                        ->where('id', $this->penarikan_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (strtoupper((string) $penarikan->status) !== 'PENDING') {
                        throw ValidationException::withMessages([
                            'amount' => 'Hanya penarikan pending yang dapat diedit.',
                        ]);
                    }

                    if ($amount > ($availableBalance + (int) $penarikan->amount)) {
                        throw ValidationException::withMessages([
                            'amount' => 'Saldo tidak mencukupi.',
                        ]);
                    }

                    $penarikan->update([
                        'amount' => $amount,
                        'note' => $note,
                        'kwitansi' => $availableBalance + (int) $penarikan->amount,
                    ]);

                    return;
                }

                if ($availableBalance < 1 || $amount > $availableBalance) {
                    throw ValidationException::withMessages([
                        'amount' => 'Saldo tidak mencukupi.',
                    ]);
                }

                Penarikan::create([
                    'uid' => (string) Str::uuid(),
                    'uid_user' => $ownerId,
                    'amount' => $amount,
                    'note' => $note,
                    'kwitansi' => $availableBalance,
                    'status' => 'PENDING',
                ]);
            }, 3);
        } catch (ValidationException $e) {
            throw $e;
        }

        session()->flash('success', $this->isEditMode
            ? 'Permintaan penarikan diperbarui.'
            : 'Permintaan penarikan berhasil dikirim.');

        $this->dispatch('close-modal', name: 'penarikan-modal');
        $this->resetForm();
        $this->calculateStats();
    }

    public function confirmDelete($id)
    {
        $penarikan = $this->ownedPenarikanQuery()
            ->where('id', $id)
            ->firstOrFail();

        if (strtoupper((string) $penarikan->status) !== 'PENDING') {
            session()->flash('error', 'Hanya penarikan pending yang dapat dihapus.');

            return;
        }
        $this->penarikan_id = $id;
        $this->dispatch('open-modal', name: 'delete-modal');
    }

    public function delete()
    {
        if (! $this->penarikan_id) {
            return;
        }

        $ownerId = $this->ownerUid();

        try {
            DB::transaction(function () use ($ownerId) {
                $penarikan = Penarikan::query()
                    ->whereKey($this->penarikan_id)
                    ->where('uid_user', $ownerId)
                    ->where('status', Penarikan::STATUS_PENDING)
                    ->lockForUpdate()
                    ->first();

                if (! $penarikan) {
                    throw ValidationException::withMessages([
                        'withdrawal' => 'Penarikan tidak dapat dibatalkan karena status sudah berubah.',
                    ]);
                }

                $penarikan->delete();
            }, 3);
        } catch (ValidationException $e) {
            $this->addError('withdrawal', $e->errors()['withdrawal'][0] ?? 'Penarikan tidak dapat dibatalkan.');
            $this->penarikan_id = null;
            $this->dispatch('close-modal', name: 'delete-modal');
            $this->calculateStats();

            return;
        }

        $this->penarikan_id = null;
        $this->dispatch('close-modal', name: 'delete-modal');
        session()->flash('success', 'Permintaan penarikan dibatalkan.');
        $this->calculateStats();
    }

    public function render()
    {
        $ownerId = $this->ownerUid();

        $penarikans = Penarikan::where('uid_user', $ownerId)
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('note', 'like', '%'.$this->search.'%')
                        ->orWhere('amount', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.dashboard.penarikan-index', [
            'penarikans' => $penarikans,
        ]);
    }

    private function ownerUid(): string
    {
        $user = Auth::user();

        return ($user->role === 'staff') ? $user->parent_uid : $user->uid;
    }

    private function ownedPenarikanQuery()
    {
        return Penarikan::where('uid_user', $this->ownerUid());
    }
}
