<?php

namespace App\Livewire\Admin;

use App\Models\Fasilitas;
use App\Services\SecureImageStorage;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class FasilitasIndex extends Component
{
    use WithFileUploads, WithPagination;

    #[Layout('admin.layout', ['title' => 'Master Fasilitas'])]
    public $search = '';

    public $fasilitas_id;

    public $name;

    public $icon; // This will store the path in DB

    public $icon_file; // This will handle the upload

    public $isEditMode = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:fasilitas,name',
            'icon_file' => SecureImageStorage::rules(),
        ];
    }

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
        $rules = $this->rules();
        if ($this->isEditMode) {
            $rules['name'] = 'required|string|max:255|unique:fasilitas,name,'.$this->fasilitas_id;
        } else {
            $rules['icon_file'] = SecureImageStorage::rules(true);
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
        ];

        $oldIcon = null;
        if ($this->icon_file) {
            $oldIcon = $this->isEditMode ? $this->icon : null;
            $data['icon'] = app(SecureImageStorage::class)->store($this->icon_file, 'fasilitas');
        }

        if ($this->isEditMode) {
            Fasilitas::find($this->fasilitas_id)->update($data);
            session()->flash('success', 'Fasilitas berhasil diperbarui.');
        } else {
            Fasilitas::create($data);
            session()->flash('success', 'Fasilitas berhasil ditambahkan.');
        }

        app(SecureImageStorage::class)->delete('fasilitas', $oldIcon);

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

        app(SecureImageStorage::class)->delete('fasilitas', $fasilitas->icon);
        $fasilitas->delete();
        $this->dispatch('close-modal', name: 'delete-modal');
        session()->flash('success', 'Fasilitas berhasil dihapus.');
    }

    public function render()
    {
        $fasilitas = Fasilitas::where('name', 'like', '%'.$this->search.'%')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.fasilitas-index', [
            'fasilitas' => $fasilitas,
        ]);
    }
}
