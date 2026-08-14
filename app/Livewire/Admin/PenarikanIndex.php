<?php

namespace App\Livewire\Admin;

use App\Models\Bank;
use App\Models\Penarikan;
use App\Services\PrivateTransferProofStorage;
use App\Services\SecureImageStorage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class PenarikanIndex extends Component
{
    use WithFileUploads, WithPagination;

    private const ALLOWED_STATUS_FILTERS = ['all', 'pending', 'processing', 'success'];

    public $search = '';

    public $statusFilter = 'all'; // all, pending, processing, success

    public $selectedPenarikan = null;

    public array $selectedBank = [
        'bank_name' => null,
        'bank_account_name' => null,
        'bank_account_number' => null,
    ];

    public $editingTransferProofPenarikan = null;

    public $transferProof = null;

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

    public function setStatusFilter(string $status): void
    {
        if (! in_array($status, self::ALLOWED_STATUS_FILTERS, true)) {
            return;
        }

        if ($this->statusFilter === $status) {
            return;
        }

        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function render()
    {
        $penarikans = Penarikan::query()
            ->with(['user'])
            ->when($this->search, function ($query) {
                $query->where(function ($searchQuery) {
                    $searchQuery->whereHas('user', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    })->orWhere('note', 'like', '%' . $this->search . '%');
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

    public function process($uid)
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);

        $processed = DB::transaction(function () use ($uid) {
            $penarikan = Penarikan::where('uid', $uid)->lockForUpdate()->firstOrFail();

            if (strtoupper((string) $penarikan->status) !== Penarikan::STATUS_PENDING) {
                return false;
            }

            $penarikan->update([
                'status' => Penarikan::STATUS_PROCESSING,
                'processing_at' => now(),
            ]);

            return true;
        }, 3);

        if (! $processed) {
            session()->flash('error', 'Penarikan hanya dapat diproses jika masih berstatus pending.');

            return;
        }

        session()->flash('message', 'Penarikan mulai diproses.');
    }

    public function complete($uid)
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);

        $completed = DB::transaction(function () use ($uid) {
            $penarikan = Penarikan::where('uid', $uid)->lockForUpdate()->firstOrFail();

            if (strtoupper((string) $penarikan->status) !== Penarikan::STATUS_PROCESSING) {
                return false;
            }

            $penarikan->update([
                'status' => Penarikan::STATUS_SUCCESS,
                'approved_at' => now(),
            ]);

            return true;
        }, 3);

        if (! $completed) {
            session()->flash('error', 'Penarikan hanya dapat diselesaikan jika sedang diproses.');

            return;
        }

        session()->flash('message', 'Penarikan berhasil diselesaikan!');
    }

    public function approve($uid)
    {
        return $this->complete($uid);
    }

    public function openDetail(string $uid): void
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);

        $penarikan = Penarikan::with('user')
            ->where('uid', $uid)
            ->firstOrFail();

        $this->selectedPenarikan = $penarikan;
        $this->selectedBank = $this->bankDetailsFor($penarikan);
        $this->selectedPenarikan->load('transferProofUploader');

        $this->dispatch('open-modal', name: 'penarikan-detail-modal');
    }

    public function openTransferProofModal(string $uid): void
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);

        $this->resetValidation();
        $this->transferProof = null;
        $this->editingTransferProofPenarikan = Penarikan::with(['user', 'transferProofUploader'])
            ->where('uid', $uid)
            ->firstOrFail();

        $this->dispatch('open-modal', name: 'transfer-proof-modal');
    }

    public function saveTransferProof(PrivateTransferProofStorage $storage): void
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);

        $this->validate([
            'transferProof' => SecureImageStorage::rules(true),
        ]);

        $editingUid = $this->editingTransferProofPenarikan?->uid;
        abort_unless(is_string($editingUid) && $editingUid !== '', 404);

        $storedProof = $storage->storeBasename($this->transferProof);

        try {
            $oldProof = DB::transaction(function () use ($editingUid, $storedProof) {
                $penarikan = Penarikan::query()
                    ->where('uid', $editingUid)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array(strtoupper((string) $penarikan->status), [
                    Penarikan::STATUS_PENDING,
                    Penarikan::STATUS_PROCESSING,
                    Penarikan::STATUS_SUCCESS,
                ], true)) {
                    throw ValidationException::withMessages([
                        'transferProof' => 'Bukti transfer hanya dapat diunggah saat penarikan berstatus pending, processing, atau success.',
                    ]);
                }

                $oldProof = $penarikan->transfer_proof;

                $penarikan->update([
                    'transfer_proof' => $storedProof,
                    'transfer_proof_uploaded_at' => now(),
                    'transfer_proof_uploaded_by' => Auth::user()->uid,
                ]);

                return $oldProof;
            }, 3);
        } catch (\Throwable $e) {
            $storage->delete($storedProof);

            throw $e;
        }

        $storage->delete($oldProof);

        $this->transferProof = null;
        $this->editingTransferProofPenarikan = Penarikan::with(['user', 'transferProofUploader'])
            ->where('uid', $editingUid)
            ->firstOrFail();

        if (($this->selectedPenarikan?->uid ?? null) === $editingUid) {
            $this->selectedPenarikan = Penarikan::with(['user', 'transferProofUploader'])
                ->where('uid', $editingUid)
                ->firstOrFail();
        }

        session()->flash('message', 'Bukti transfer berhasil disimpan.');
        $this->dispatch('close-modal', name: 'transfer-proof-modal');
    }

    private function bankDetailsFor(Penarikan $penarikan): array
    {
        $fallbackBank = null;

        if (
            ! filled($penarikan->bank_name)
            || ! filled($penarikan->bank_account_name)
            || ! filled($penarikan->bank_account_number)
        ) {
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
