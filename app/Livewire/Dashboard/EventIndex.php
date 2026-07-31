<?php

namespace App\Livewire\Dashboard;

use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class EventIndex extends Component
{
    use WithPagination;

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
