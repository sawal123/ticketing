<?php

namespace App\Livewire\Dashboard;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventOrganizer;
use App\Models\Fasilitas;
use App\Services\SecureImageStorage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class EventCreate extends Component
{
    use WithFileUploads;

    #[Layout('layouts.unified')]
    public $editingEventUid = null;

    public $event;

    public $fee = 0;

    public $start_sale;

    public $event_start;

    public $event_end;

    public $venue_name;

    public $venue_address;

    public $venue_city;

    public $venue_province;

    public $map;

    public $cover;

    public $existingCover = null;

    public $deskripsi;

    public $category_id;

    public $selectedFasilitas = [];

    public $organizer_name;

    public $responsible_name;

    public $responsible_position;

    public $phone;

    public $email;

    public $address;

    public function mount($uid = null)
    {
        if ($uid) {
            $this->editingEventUid = $uid;
            $eventData = Event::where('uid', $uid)->firstOrFail();

            $user = auth()->user();
            $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

            // Check ownership
            if ($eventData->user_uid !== $ownerId && $user->role !== 'admin') {
                abort(403);
            }

            $this->event = $eventData->event;
            $this->fee = $eventData->fee;
            $eventStart = $eventData->tanggal ? Carbon::parse($eventData->tanggal) : null;
            $eventEnd = $eventData->event_end ? Carbon::parse($eventData->event_end) : null;
            $organizer = $eventData->organizer;

            $this->start_sale = $eventData->start_sale ? Carbon::parse($eventData->start_sale)->format('Y-m-d H:i') : null;
            $this->event_start = $eventStart?->format('Y-m-d H:i');
            $this->event_end = $eventEnd?->format('Y-m-d H:i');
            $this->venue_name = $eventData->venue_name;
            $this->venue_address = $eventData->venue_address ?: $eventData->alamat;
            $this->venue_city = $eventData->venue_city;
            $this->venue_province = $eventData->venue_province;
            $this->map = $eventData->map;
            $this->existingCover = $eventData->cover;
            $this->deskripsi = $eventData->deskripsi;
            $this->category_id = $eventData->category_id;
            $this->selectedFasilitas = $eventData->fasilitas->pluck('id')->toArray();
            $this->organizer_name = $organizer?->organizer_name;
            $this->responsible_name = $organizer?->responsible_name;
            $this->responsible_position = $organizer?->responsible_position;
            $this->phone = $organizer?->phone;
            $this->email = $organizer?->email;
            $this->address = $organizer?->address;
        } else {
            $user = auth()->user();
            $this->start_sale = Carbon::now()->format('Y-m-d H:i');
            $eventStart = Carbon::now()->addDays(7);
            $this->event_start = $eventStart->format('Y-m-d H:i');
            $this->event_end = $eventStart->copy()->addHours(2)->format('Y-m-d H:i');
            $this->responsible_name = $user?->name;
            $this->phone = $user?->nomor;
            $this->email = $user?->email;
            $this->address = $user?->alamat;
        }
    }

    protected function rules()
    {
        return [
            'event' => 'required|string|max:255',
            'fee' => 'required|numeric|min:0|max:100',
            'start_sale' => 'required|date',
            'event_start' => 'required|date|after:start_sale',
            'event_end' => 'required|date|after:event_start',
            'venue_name' => 'required|string|max:255',
            'venue_address' => 'required|string|max:500',
            'venue_city' => 'required|string|max:255',
            'venue_province' => 'required|string|max:255',
            'map' => 'nullable|url',
            'cover' => SecureImageStorage::rules(! $this->editingEventUid),
            'deskripsi' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'selectedFasilitas' => 'array',
            'organizer_name' => 'required|string|max:255',
            'responsible_name' => 'required|string|max:255',
            'responsible_position' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
        ];
    }

    public function save()
    {
        $this->validate();

        $eventStart = Carbon::parse($this->event_start);
        $eventEnd = Carbon::parse($this->event_end);
        $legacyAddress = collect([
            $this->venue_name,
            $this->venue_address,
            $this->venue_city,
            $this->venue_province,
        ])->filter(fn ($value) => filled($value))->implode(', ');

        $event = null;
        if ($this->editingEventUid) {
            $event = $this->ownedEventQuery($this->editingEventUid)->firstOrFail();
            $uid = $this->editingEventUid;
            $slug = $event->slug;
        } else {
            $uid = (string) Str::uuid();
            $slug = Str::slug($this->event).'-'.Str::random(5);
        }

        // Handle Cover Upload
        $coverName = $this->existingCover;
        $oldCover = null;
        if ($this->cover) {
            $oldCover = $this->editingEventUid ? $this->existingCover : null;
            $coverName = app(SecureImageStorage::class)->storeBasename($this->cover, 'cover');
        }

        if (! $this->editingEventUid && blank($coverName)) {
            $this->addError('cover', 'Cover event wajib diupload sebelum event disimpan.');

            return null;
        }

        $eventData = [
            'category_id' => $this->category_id,
            'event' => $this->event,
            'alamat' => $legacyAddress,
            'tanggal' => $eventStart->format('Y-m-d H:i:s'),
            'event_end' => $eventEnd->format('Y-m-d H:i:s'),
            'venue_name' => $this->venue_name,
            'venue_address' => $this->venue_address,
            'venue_city' => $this->venue_city,
            'venue_province' => $this->venue_province,
            'fee' => $this->fee,
            'start_sale' => Carbon::parse($this->start_sale)->format('Y-m-d H:i:s'),
            'deskripsi' => $this->deskripsi,
            'map' => $this->map,
            'cover' => $coverName,
        ];

        $organizerData = [
            'organizer_name' => $this->organizer_name,
            'responsible_name' => $this->responsible_name,
            'responsible_position' => $this->responsible_position,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
        ];

        $newCoverStored = $this->cover !== null;

        try {
            DB::transaction(function () use (&$event, $uid, $slug, $eventData, $organizerData) {
                if (! $this->editingEventUid) {
                    $user = auth()->user();
                    $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

                    $event = Event::create($eventData + [
                        'uid' => $uid,
                        'user_uid' => $ownerId,
                        'status' => 'inactive',
                        'slug' => $slug,
                        'konfirmasi' => null,
                    ]);
                } else {
                    $event->update($eventData);
                }

                EventOrganizer::updateOrCreate(
                    ['event_uid' => $event->uid],
                    $organizerData
                );
            });
        } catch (\Throwable $exception) {
            if ($newCoverStored && filled($coverName)) {
                app(SecureImageStorage::class)->delete('cover', $coverName);
            }

            throw $exception;
        }

        app(SecureImageStorage::class)->delete('cover', $oldCover);

        // Sync Fasilitas
        $event->fasilitas()->sync($this->selectedFasilitas);

        session()->flash('message', $this->editingEventUid ? 'Event berhasil diperbarui.' : 'Event berhasil diajukan dan sedang menunggu persetujuan admin.');

        return redirect()->route('dashboard.event.edit', $uid);
    }

    public function render()
    {
        return view('livewire.dashboard.event-create', [
            'categories' => Category::all(),
            'fasilitasData' => Fasilitas::all(),
            'title' => $this->editingEventUid ? 'Edit Event' : 'Add New Event',
        ]);
    }

    private function ownedEventQuery(string $uid)
    {
        $user = auth()->user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        return Event::where('uid', $uid)
            ->when($user->role !== 'admin', fn ($query) => $query->where('user_uid', $ownerId));
    }
}
