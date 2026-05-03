<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Event;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;

class CategoryIndex extends Component
{
    use WithPagination;

    #[Layout('admin.layout', ['title' => 'Master Kategori'])]

    public $search = '';
    public $category_id;
    public $name;
    public $isEditMode = false;

    protected $rules = [
        'name' => 'required|string|max:255|unique:categories,name',
    ];

    public function resetForm()
    {
        $this->reset(['category_id', 'name', 'isEditMode']);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->dispatch('open-modal', name: 'category-modal');
    }

    public function openEditModal($id)
    {
        $this->resetForm();
        $this->isEditMode = true;
        $category = Category::findOrFail($id);
        $this->category_id = $category->id;
        $this->name = $category->name;
        $this->dispatch('open-modal', name: 'category-modal');
    }

    public function save()
    {
        $rules = $this->rules;
        if ($this->isEditMode) {
            $rules['name'] = 'required|string|max:255|unique:categories,name,' . $this->category_id;
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'slug' => Str::slug($this->name),
        ];

        if ($this->isEditMode) {
            Category::find($this->category_id)->update($data);
            session()->flash('success', 'Kategori berhasil diperbarui.');
        } else {
            Category::create($data);
            session()->flash('success', 'Kategori berhasil ditambahkan.');
        }

        $this->dispatch('close-modal', name: 'category-modal');
        $this->resetForm();
    }

    public function confirmDelete($id)
    {
        // Check if being used by any event
        $count = Event::where('category_id', $id)->count();

        if ($count > 0) {
            session()->flash('error', "Kategori tidak dapat dihapus karena sedang digunakan oleh $count event.");
            return;
        }

        $this->category_id = $id;
        $this->dispatch('open-modal', name: 'delete-modal');
    }

    public function delete()
    {
        Category::findOrFail($this->category_id)->delete();
        $this->dispatch('close-modal', name: 'delete-modal');
        session()->flash('success', 'Kategori berhasil dihapus.');
    }

    public function render()
    {
        $categories = Category::where('name', 'like', '%' . $this->search . '%')
            ->withCount('events')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.category-index', [
            'categories' => $categories
        ]);
    }
}
