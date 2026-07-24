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
        $event = Event::where('uid', $uid)->first();
        if ($event) {
            $event->status = $event->status === 'active' ? 'close' : 'active';
            $event->save();
            $this->dispatch('event-status-updated');
        }
    }

    public function deletePendingEvent(string $uid): void
    {
        $user = auth()->user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        $event = Event::where('uid', $uid)->first();
        if (! $event) {
            session()->flash('error', 'Event tidak ditemukan.');

            return;
        }

        if ($event->user_uid !== $ownerId) {
            abort(403);
        }

        if ($event->konfirmasi !== null) {
            session()->flash('error', 'Event yang sudah disetujui tidak dapat dihapus dari halaman ini.');

            return;
        }

        $event->delete();
        session()->flash('message', 'Event menunggu persetujuan berhasil dihapus.');
        $this->resetPage();
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
