<?php

namespace App\Livewire\Admin;

use App\Models\Bank;
use App\Models\Event;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class PenyewaDetail extends Component
{
    use WithPagination;

    public string $uid;

    public function mount(string $uid): void
    {
        $this->uid = $uid;
    }

    private function penyewa(): User
    {
        return User::where('uid', $this->uid)
            ->where('role', 'penyewa')
            ->firstOrFail();
    }

    private function eventQuery()
    {
        return Event::where('user_uid', $this->uid);
    }

    public function render()
    {
        $penyewa = $this->penyewa();

        $bank = Bank::where(function ($q) {
            $q->where('uid_user', $this->uid)
                ->orWhere('uid', $this->uid);
        })->latest()->first();

        $summary = [
            'total' => (clone $this->eventQuery())->count(),
            'active' => (clone $this->eventQuery())->whereRaw('LOWER(status) = ?', ['active'])->count(),
            'closed' => (clone $this->eventQuery())->whereIn('status', ['close', 'closed', 'ditutup'])->count(),
        ];
        $summary['pending'] = max(0, $summary['total'] - $summary['active'] - $summary['closed']);

        $events = $this->eventQuery()
            ->select(['id', 'uid', 'event', 'tanggal', 'status', 'konfirmasi', 'created_at'])
            ->latest()
            ->paginate(10);

        return view('livewire.admin.penyewa-detail', [
            'penyewa' => $penyewa,
            'bank' => $bank,
            'summary' => $summary,
            'events' => $events,
        ])->layout('admin.layout', ['title' => 'Detail Penyewa: '.$penyewa->name]);
    }
}
