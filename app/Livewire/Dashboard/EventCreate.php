<?php

namespace App\Livewire\Dashboard;

use App\Models\Category;
use App\Models\Event;
use App\Models\Fasilitas;
use Carbon\Carbon;
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
    public $pajak = 0;
    public $start_sale;
    public $tanggal; // End Date / Event Date
    public $alamat;
    public $map;
    public $cover;
    public $existingCover = null;
    public $deskripsi;
    public $category_id;
    public $selectedFasilitas = [];

    public function mount($uid = null)
    {
        if ($uid) {
            $this->editingEventUid = $uid;
            $eventData = Event::where('uid', $uid)->firstOrFail();

            // Check ownership
            if ($eventData->user_uid !== auth()->user()->uid && auth()->user()->role !== 'admin') {
                abort(403);
            }

            $this->event = $eventData->event;
            $this->pajak = $eventData->fee;
            $this->start_sale = $eventData->start_sale ? Carbon::parse($eventData->start_sale)->format('Y-m-d H:i') : null;
            $this->tanggal = $eventData->tanggal ? Carbon::parse($eventData->tanggal)->format('Y-m-d H:i') : null;
            $this->alamat = $eventData->alamat;
            $this->map = $eventData->map;
            $this->existingCover = $eventData->cover;
            $this->deskripsi = $eventData->deskripsi;
            $this->category_id = $eventData->category_id;
            $this->selectedFasilitas = $eventData->fasilitas->pluck('id')->toArray();
        } else {
            $this->start_sale = Carbon::now()->format('Y-m-d H:i');
            $this->tanggal = Carbon::now()->addDays(7)->format('Y-m-d H:i');
        }
    }

    protected function rules()
    {
        return [
            'event' => 'required|string|max:255',
            'pajak' => 'nullable|numeric|min:0',
            'start_sale' => 'required|date',
            'tanggal' => 'required|date|after:start_sale',
            'alamat' => 'required|string',
            'map' => 'nullable|url',
            'cover' => $this->editingEventUid ? 'nullable|image|max:2048' : 'required|image|max:2048',
            'deskripsi' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'selectedFasilitas' => 'array',
        ];
    }

    public function save()
    {
        $this->validate();

        $event = null;
        if ($this->editingEventUid) {
            $event = Event::where('uid', $this->editingEventUid)->firstOrFail();
            $uid = $this->editingEventUid;
            $slug = $event->slug;
        } else {
            $uid = (string) Str::uuid();
            $slug = Str::slug($this->event) . '-' . Str::random(5);
        }

        // Handle Cover Upload
        $coverName = $this->existingCover;
        if ($this->cover) {
            $coverName = $uid . '.' . $this->cover->getClientOriginalExtension();
            $this->cover->storeAs('public/cover', $coverName);
        }

        $data = [
            'category_id' => $this->category_id,
            'event' => $this->event,
            'alamat' => $this->alamat,
            'tanggal' => $this->tanggal,
            'pajak' => $this->pajak,
            'start_sale' => $this->start_sale,
            'deskripsi' => $this->deskripsi,
            'map' => $this->map,
            'cover' => $coverName,
        ];

        if (!$this->editingEventUid) {
            $data['uid'] = $uid;
            $data['user_uid'] = auth()->user()->uid;
            $data['status'] = 'inactive';
            $data['fee'] = 0;
            $data['slug'] = $slug;
            $data['konfirmasi'] = null;

            $event = Event::create($data);
        } else {
            $event->update($data);
        }

        // Sync Fasilitas
        $event->fasilitas()->sync($this->selectedFasilitas);

        session()->flash('message', $this->editingEventUid ? 'Event berhasil diperbarui.' : 'Event berhasil diajukan dan sedang menunggu persetujuan admin.');

        return redirect()->route('dashboard.demo.event');
    }

    public function render()
    {
        return view('livewire.dashboard.event-create', [
            'categories' => Category::all(),
            'fasilitasData' => Fasilitas::all(),
            'title' => $this->editingEventUid ? 'Edit Event' : 'Add New Event',
        ]);
    }
}
