<?php

namespace App\Livewire\Admin;

use App\Models\Fasilitas;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;

class FasilitasIndex extends Component
{
    use WithPagination, WithFileUploads;

    #[Layout('admin.layout', ['title' => 'Master Fasilitas'])]

    public $search = '';
    public $fasilitas_id;
    public $name;
    public $icon; // This will store the path in DB
    public $icon_file; // This will handle the upload
    public $isEditMode = false;

    protected $rules = [
        'name' => 'required|string|max:255|unique:fasilitas,name',
        'icon_file' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:1024',
    ];

    public function resetForm()
    {
        $this->reset(['fasilitas_id', 'name', 'icon', 'icon_file', 'isEditMode']);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->dispatch('open-modal', name: 'fasilitas-modal');
    }

    public function openEditModal($id)
    {
        $this->resetForm();
        $this->isEditMode = true;
        $fasilitas = Fasilitas::findOrFail($id);
        $this->fasilitas_id = $fasilitas->id;
        $this->name = $fasilitas->name;
        $this->icon = $fasilitas->icon;
        $this->dispatch('open-modal', name: 'fasilitas-modal');
    }

    public function save()
    {
        $rules = $this->rules;
        if ($this->isEditMode) {
            $rules['name'] = 'required|string|max:255|unique:fasilitas,name,' . $this->fasilitas_id;
        } else {
            $rules['icon_file'] = 'required|image|mimes:png,jpg,jpeg,svg|max:1024';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
        ];

        if ($this->icon_file) {
            // Delete old icon if exists
            if ($this->isEditMode && $this->icon && Storage::disk('public')->exists($this->icon)) {
                Storage::disk('public')->delete($this->icon);
            }
            
            $path = $this->icon_file->store('fasilitas', 'public');
            $data['icon'] = $path;
        }

        if ($this->isEditMode) {
            Fasilitas::find($this->fasilitas_id)->update($data);
            session()->flash('success', 'Fasilitas berhasil diperbarui.');
        } else {
            Fasilitas::create($data);
            session()->flash('success', 'Fasilitas berhasil ditambahkan.');
        }

        $this->dispatch('close-modal', name: 'fasilitas-modal');
        $this->resetForm();
    }

    public function confirmDelete($id)
    {
        // Check if being used by any event in event_fasilitas pivot table
        $count = DB::table('event_fasilitas')->where('fasilitas_id', $id)->count();

        if ($count > 0) {
            session()->flash('error', "Fasilitas tidak dapat dihapus karena sedang digunakan oleh $count event.");
            return;
        }

        $this->fasilitas_id = $id;
        $this->dispatch('open-modal', name: 'delete-modal');
    }

    public function delete()
    {
        $fasilitas = Fasilitas::findOrFail($this->fasilitas_id);
        
        // Delete file from storage
        if ($fasilitas->icon && Storage::disk('public')->exists($fasilitas->icon)) {
            Storage::disk('public')->delete($fasilitas->icon);
        }

        $fasilitas->delete();
        $this->dispatch('close-modal', name: 'delete-modal');
        session()->flash('success', 'Fasilitas berhasil dihapus.');
    }

    public function render()
    {
        $fasilitas = Fasilitas::where('name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.fasilitas-index', [
            'fasilitas' => $fasilitas
        ]);
    }
}
