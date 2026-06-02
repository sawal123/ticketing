<?php

namespace App\Livewire\Layout;

use App\Models\Event;
use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    #[On('event-status-updated')]
    public function refreshEvents(): void
    {
        //
    }

    public function render()
    {
        $query = Event::where('status', 'active')
            ->where('konfirmasi', '1')
            ->latest();

        if (auth()->check()) {
            $user = auth()->user();

            if ($user->role === 'penyewa') {
                $query->where('user_uid', $user->uid);
            } elseif ($user->role === 'staff') {
                $query->where('user_uid', $user->parent_uid);
            }
        }

        return view('livewire.layout.sidebar', [
            'sidebarActiveEvents' => $query->get(),
        ]);
    }
}
