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
        }
    }

    public function render()
    {
        $ownerId = auth()->user();
        $isAdmin = auth()->user()->role === 'admin';

        $query = Event::query()
            ->when($this->search, function ($q) {
                $q->where('event', 'like', '%'.$this->search.'%');
            });

        if (! $isAdmin) {
            $query->where('user_uid', $ownerId->uid);
        }

        $events = $query->orderBy('created_at', 'desc')->paginate(12);

        return view('livewire.dashboard.event-index', [
            'events' => $events,
        ]);
    }
}
