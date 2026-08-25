<?php

namespace App\Livewire\Dashboard;

use App\Models\Agreement;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        try {
            DB::transaction(function () use ($uid) {
                $event = $this->ownedEventQuery($uid)
                    ->lockForUpdate()
                    ->first();

                if (! $event) {
                    throw ValidationException::withMessages([
                        'event' => 'Event tidak ditemukan atau bukan milik Anda.',
                    ]);
                }

                if ($event->konfirmasi !== null || strtolower((string) $event->status) === 'active') {
                    throw ValidationException::withMessages([
                        'event' => 'Event yang sudah aktif/terkonfirmasi tidak dapat dihapus.',
                    ]);
                }

                $tickets = Harga::query()
                    ->where('uid', $event->uid)
                    ->lockForUpdate()
                    ->get();

                if ($tickets->contains(fn (Harga $ticket) => (int) ($ticket->sold_qty ?? 0) > 0
                    || (int) ($ticket->reserved_qty ?? 0) > 0)) {
                    throw ValidationException::withMessages([
                        'event' => 'Event tidak dapat dihapus karena sudah memiliki tiket terjual atau reserved.',
                    ]);
                }

                $relatedCart = Cart::query()
                    ->where('event_uid', $event->uid)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if ($relatedCart) {
                    throw ValidationException::withMessages([
                        'event' => 'Event tidak dapat dihapus karena sudah memiliki transaksi.',
                    ]);
                }

                if ($this->eventHasHistoricalRecords($event->uid)) {
                    throw ValidationException::withMessages([
                        'event' => 'Event tidak dapat dihapus karena sudah memiliki riwayat transaksi.',
                    ]);
                }

                if ($this->eventHasCompletedAgreement($event->uid)) {
                    throw ValidationException::withMessages([
                        'event' => 'Event tidak dapat dihapus karena memiliki agreement yang sudah selesai.',
                    ]);
                }

                $event->delete();
            }, 3);
        } catch (ValidationException $e) {
            session()->flash('error', $e->errors()['event'][0] ?? 'Event tidak dapat dihapus.');

            return;
        }

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

    private function eventHasHistoricalRecords(string $eventUid): bool
    {
        return HargaCart::query()->where('event_uid', $eventUid)->exists()
            || Transaction::query()->where('event_uid', $eventUid)->exists()
            || Cash::query()->where('uid_event', $eventUid)->exists();
    }

    private function eventHasCompletedAgreement(string $eventUid): bool
    {
        return Agreement::query()
            ->where('event_uid', $eventUid)
            ->where('status', Agreement::STATUS_COMPLETED)
            ->lockForUpdate()
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
