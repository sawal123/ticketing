<?php

namespace App\Livewire\Dashboard;

use App\Models\Partner;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;

class PartnerIndex extends Component
{
    use WithPagination;

    #[Layout('layouts.unified')]
    
    public $search = '';
    public $event_uid = '';
    
    // Form properties
    public $partner_id;
    public $name;
    public $email;
    public $city;
    public $alamat;
    public $hp;
    public $selected_event_uid;
    public $status = 'active';

    // Stats
    public $totalPartners = 0;
    public $activePartners = 0;
    public $totalCities = 0;

    public $isEditMode = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'city' => 'required|string|max:100',
        'alamat' => 'required|string',
        'hp' => 'required|string|max:20',
    ];

    public function resetForm()
    {
        $this->reset(['partner_id', 'name', 'email', 'city', 'alamat', 'hp', 'status', 'selected_event_uid', 'isEditMode']);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->dispatch('open-modal', name: 'partner-modal');
    }

    public function openEditModal($id)
    {
        $this->resetForm();
        $this->isEditMode = true;
        $partner = Partner::findOrFail($id);
        
        $this->partner_id = $partner->id;
        $this->name = $partner->name;
        $this->email = $partner->email;
        $this->city = $partner->city;
        $this->alamat = $partner->alamat;
        $this->hp = $partner->hp;
        $this->status = $partner->status;
        $this->selected_event_uid = $partner->event_uid;

        $this->dispatch('open-modal', name: 'partner-modal');
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'city' => $this->city,
            'alamat' => $this->alamat,
            'hp' => $this->hp,
            'status' => $this->status,
            'event_uid' => $this->selected_event_uid ?: null,
        ];

        if ($this->isEditMode) {
            Partner::find($this->partner_id)->update($data);
            session()->flash('success', 'Partner berhasil diperbarui.');
        } else {
            $data['uid'] = Str::uuid();
            $data['user_uid'] = auth()->user()->role === 'staff' ? auth()->user()->parent_uid : auth()->user()->uid;
            $data['referensi'] = 'PARTNER-' . strtoupper(Str::random(6));
            Partner::create($data);
            session()->flash('success', 'Partner berhasil ditambahkan.');
        }

        $this->dispatch('close-modal', name: 'partner-modal');
        $this->resetForm();
    }

    public function toggleStatus($id)
    {
        $partner = Partner::findOrFail($id);
        $partner->status = $partner->status === 'active' ? 'inactive' : 'active';
        $partner->save();
        session()->flash('success', 'Status partner berhasil diubah.');
    }

    public function confirmDelete($id)
    {
        $this->partner_id = $id;
        $this->dispatch('open-modal', name: 'delete-modal');
    }

    public function delete()
    {
        Partner::findOrFail($this->partner_id)->delete();
        $this->dispatch('close-modal', name: 'delete-modal');
        session()->flash('success', 'Partner berhasil dihapus.');
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        // Base Query for Table
        $query = Partner::query()->with('event');

        if (!$isAdmin) {
            $query->where('user_uid', $ownerId);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('referensi', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->event_uid) {
            $query->where('event_uid', $this->event_uid);
        }

        $partners = $query->latest()->paginate(10);

        // Stats Calculation
        $statsQuery = Partner::query();
        if (!$isAdmin) {
            $statsQuery->where('user_uid', $ownerId);
        }
        $this->totalPartners = (clone $statsQuery)->count();
        $this->activePartners = (clone $statsQuery)->where('status', 'active')->count();
        $this->totalCities = (clone $statsQuery)->distinct('city')->count();

        // Events for dropdown
        $eventsQuery = \App\Models\Event::query();
        if (!$isAdmin) {
            $eventsQuery->where('user_uid', $ownerId);
        }
        $events = $eventsQuery->orderBy('event', 'asc')->get();

        return view('livewire.dashboard.partner-index', [
            'partners' => $partners,
            'events' => $events
        ]);
    }
}
