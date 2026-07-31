<?php

namespace App\Livewire\Dashboard;

use App\Models\Cart;
use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class EventIndex extends Component
{
    use WithPagination;

    private const BLOCKING_TRANSACTION_STATUSES = [
        Cart::STATUS_SUCCESS,
        Cart::STATUS_PENDING,
        Cart::STATUS_RESERVED,
        Cart::STATUS_PAYMENT_REVIEW,
        Cart::STATUS_UNPAID,
    ];

    #[Layout('layouts.unified')]
    public $search = '';

    protected $updatesQueryString = ['search'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleStatus($uid)
    {
        $event = $this->ownedEventQuery($uid)->firstOrFail();
        $event->status = $event->status === 'active' ? 'close' : 'active';
        $event->save();
        $this->dispatch('event-status-updated');
    }

    public function deletePendingEvent(string $uid): void
    {
        $event = $this->ownedEventQuery($uid)->firstOrFail();

        if ($this->eventHasTransactions($event)) {
            session()->flash('error', 'Event tidak dapat dihapus karena sudah memiliki transaksi.');

            return;
        }

        if ($event->konfirmasi !== null) {
            session()->flash('error', 'Event yang sudah disetujui tidak dapat dihapus dari halaman ini.');

            return;
        }

        $event->delete();
        session()->flash('message', 'Event menunggu persetujuan berhasil dihapus.');
        $this->resetPage();
    }

    private function ownedEventQuery(string $uid)
    {
        $user = auth()->user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        return Event::where('uid', $uid)
            ->when($user->role !== 'admin', fn ($query) => $query->where('user_uid', $ownerId));
    }

    private function eventHasTransactions(Event $event): bool
    {
        return Cart::where('event_uid', $event->uid)
            ->whereIn('status', self::BLOCKING_TRANSACTION_STATUSES)
            ->exists();
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        $query = Event::query()
            ->when($this->search, function ($q) {
                $q->where('event', 'like', '%'.$this->search.'%');
            });

        if (! $isAdmin) {
            $query->where('user_uid', $ownerId);
        }

        $events = $query->orderBy('created_at', 'desc')->paginate(12);

        return view('livewire.dashboard.event-index', [
            'events' => $events,
        ]);
    }
}
