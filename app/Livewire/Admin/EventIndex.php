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
    public ?string $confirmingEventUid = null;
    public ?string $confirmingEventName = null;

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

    public function confirmEvent(string $uid): void
    {
        $event = Event::where('uid', $uid)->firstOrFail();

        if ((string) $event->konfirmasi === '1' && $event->status === 'active') {
            session()->flash('message', 'Event sudah aktif.');

            return;
        }

        $event->update([
            'konfirmasi' => '1',
            'status' => 'active',
        ]);

        session()->flash('message', 'Event berhasil dikonfirmasi dan diaktifkan.');
    }

    public function openConfirmEventModal(string $uid): void
    {
        $event = Event::where('uid', $uid)->firstOrFail();

        $this->confirmingEventUid = $event->uid;
        $this->confirmingEventName = $event->event;
        $this->dispatch('open-modal', name: 'confirm-event-modal');
    }

    public function confirmSelectedEvent(): void
    {
        if (! $this->confirmingEventUid) {
            session()->flash('message', 'Event tidak ditemukan.');
            $this->dispatch('close-modal', name: 'confirm-event-modal');

            return;
        }

        $this->confirmEvent($this->confirmingEventUid);
        $this->confirmingEventUid = null;
        $this->confirmingEventName = null;
        $this->dispatch('close-modal', name: 'confirm-event-modal');
    }

    public function render()
    {
        $events = Event::query()
            ->when($this->search, function ($query) {
                $query->where('event', 'like', '%' . $this->search . '%');
            })
            ->leftJoin('carts', function ($join) {
                $join->on('events.uid', '=', 'carts.event_uid')
                    ->where('carts.status', '=', 'SUCCESS')
                    ->whereNull('carts.deleted_at');
            })
            ->leftJoin('harga_carts', function ($join) {
                $join->on('carts.uid', '=', 'harga_carts.uid')
                    ->whereNull('harga_carts.deleted_at');
            })
            ->select(
                'events.id',
                'events.uid',
                'events.event',
                'events.cover',
                'events.tanggal',
                'events.status',
                'events.konfirmasi',
                'events.created_at',
                DB::raw('COUNT(DISTINCT carts.uid) as total_transaksi'),
                DB::raw('SUM(harga_carts.quantity) as total_tiket_terjual'),
                DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_omset')
            )
            ->groupBy('events.id', 'events.uid', 'events.event', 'events.cover', 'events.tanggal', 'events.status', 'events.konfirmasi', 'events.created_at')
            ->orderBy('events.created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.event-index', [
            'events' => $events
        ])->layout('admin.layout', ['title' => 'Manajemen Event']);
    }
}
