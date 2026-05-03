<?php

namespace App\Livewire\Admin;

use App\Models\Term;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class TermIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $title;
    public $term;
    public $selectedUid;
    public $isEdit = false;

    protected $rules = [
        'title' => 'required|string|max:255',
        'term' => 'required|string',
    ];

    public function resetFields()
    {
        $this->title = '';
        $this->term = '';
        $this->selectedUid = null;
        $this->isEdit = false;
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetFields();
        $this->dispatch('open-modal', name: 'term-modal');
    }

    public function save()
    {
        $this->validate();

        if ($this->isEdit) {
            $item = Term::where('uid', $this->selectedUid)->firstOrFail();
            $item->update([
                'title' => $this->title,
                'term' => $this->term,
            ]);
            $message = 'Term and Condition berhasil diperbarui.';
        } else {
            Term::create([
                'uid' => Str::uuid(),
                'title' => $this->title,
                'term' => $this->term,
            ]);
            $message = 'Term and Condition berhasil ditambahkan.';
        }

        $this->dispatch('close-modal', name: 'term-modal');
        $this->resetFields();
        session()->flash('success', $message);
    }

    public function edit($uid)
    {
        $this->resetFields();
        $item = Term::where('uid', $uid)->firstOrFail();
        $this->selectedUid = $uid;
        $this->title = $item->title;
        $this->term = $item->term;
        $this->isEdit = true;
        $this->dispatch('open-modal', name: 'term-modal');
    }

    public function confirmDelete($uid)
    {
        $this->selectedUid = $uid;
        $this->dispatch('open-modal', name: 'delete-modal');
    }

    public function delete()
    {
        Term::where('uid', $this->selectedUid)->delete();
        $this->dispatch('close-modal', name: 'delete-modal');
        session()->flash('success', 'Term and Condition berhasil dihapus.');
    }

    public function render()
    {
        $terms = Term::where('title', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.term-index', [
            'terms' => $terms
        ])->layout('admin.layout', ['title' => 'Terms & Conditions']);
    }
}
