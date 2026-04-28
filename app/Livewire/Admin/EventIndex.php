<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class EventIndex extends Component
{
    use WithPagination;

    public $search = '';

    protected $updatesQueryString = ['search'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $events = Event::query()
            ->when($this->search, function ($query) {
                $query->where('event', 'like', '%' . $this->search . '%');
            })
            ->leftJoin('carts', function ($join) {
                $join->on('events.uid', '=', 'carts.event_uid')
                    ->where('carts.status', '=', 'SUCCESS');
            })
            ->leftJoin('harga_carts', 'carts.uid', '=', 'harga_carts.uid')
            ->select(
                'events.id',
                'events.uid',
                'events.event',
                'events.cover',
                'events.tanggal',
                'events.status',
                'events.created_at',
                DB::raw('COUNT(DISTINCT carts.uid) as total_transaksi'),
                DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_omset')
            )
            ->groupBy('events.id', 'events.uid', 'events.event', 'events.cover', 'events.tanggal', 'events.status', 'events.created_at')
            ->orderBy('events.created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.event-index', [
            'events' => $events
        ])->layout('admin.layout', ['title' => 'Manajemen Event']);
    }
}
