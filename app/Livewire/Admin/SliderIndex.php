<?php

namespace App\Livewire\Admin;

use App\Models\Slider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class SliderIndex extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $title;
    public $url;
    public $sort = 1;
    public $gambar;
    public $new_gambar;
    public $selectedUid;
    public $isEdit = false;

    protected $rules = [
        'title' => 'required|string|max:255',
        'url' => 'nullable|string|max:255',
        'sort' => 'required|integer|min:1',
        'new_gambar' => 'nullable|image|max:2048', // 2MB Max
    ];

    public function resetFields()
    {
        $this->title = '';
        $this->url = '';
        $this->sort = (Slider::max('sort') ?? 0) + 1;
        $this->gambar = null;
        $this->new_gambar = null;
        $this->selectedUid = null;
        $this->isEdit = false;
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetFields();
        $this->dispatch('open-modal', name: 'slider-modal');
    }

    public function save()
    {
        $this->validate();

        if (!$this->isEdit && !$this->new_gambar) {
            $this->addError('new_gambar', 'Gambar slider wajib diunggah.');
            return;
        }

        $data = [
            'title' => $this->title,
            'url' => trim((string) $this->url) ?: '#',
            'sort' => $this->sort,
        ];

        if ($this->new_gambar) {
            // Delete old image if editing
            if ($this->isEdit && $this->gambar) {
                Storage::delete('public/slider/' . $this->gambar);
            }

            $fileName = 'slider_' . time() . '.' . $this->new_gambar->getClientOriginalExtension();
            $this->new_gambar->storeAs('public/slider/', $fileName);
            $data['gambar'] = $fileName;
        }

        if ($this->isEdit) {
            $item = Slider::where('uid', $this->selectedUid)->firstOrFail();
            
            // Swap logic if sort changed
            if ($item->sort != $this->sort) {
                $existing = Slider::where('sort', $this->sort)->first();
                if ($existing) {
                    // Update existing one to take current item's old position
                    $existing->update(['sort' => $item->sort]);
                }
            }
            
            $item->update($data);
            $message = 'Slider berhasil diperbarui.';
        } else {
            // Check if sort already exists for new item
            $existing = Slider::where('sort', $this->sort)->first();
            if ($existing) {
                // Shift all subsequent sliders up to make room
                Slider::where('sort', '>=', $this->sort)->increment('sort');
            }
            
            $data['uid'] = Str::uuid();
            Slider::create($data);
            $message = 'Slider berhasil ditambahkan.';
        }

        $this->dispatch('close-modal', name: 'slider-modal');
        $this->resetFields();
        session()->flash('success', $message);
    }

    public function edit($uid)
    {
        $this->resetFields();
        $item = Slider::where('uid', $uid)->firstOrFail();
        $this->selectedUid = $uid;
        $this->title = $item->title;
        $this->url = $item->url;
        $this->sort = $item->sort;
        $this->gambar = $item->gambar;
        $this->isEdit = true;
        $this->dispatch('open-modal', name: 'slider-modal');
    }

    public function confirmDelete($uid)
    {
        $this->selectedUid = $uid;
        $this->dispatch('open-modal', name: 'delete-modal');
    }

    public function moveUp($uid)
    {
        $current = Slider::where('uid', $uid)->firstOrFail();
        $previous = Slider::where('sort', '<', $current->sort)->orderBy('sort', 'desc')->first();

        if ($previous) {
            $tempSort = $previous->sort;
            $previous->update(['sort' => $current->sort]);
            $current->update(['sort' => $tempSort]);
            session()->flash('success', 'Urutan slider berhasil dipindahkan ke atas.');
        }
    }

    public function moveDown($uid)
    {
        $current = Slider::where('uid', $uid)->firstOrFail();
        $next = Slider::where('sort', '>', $current->sort)->orderBy('sort', 'asc')->first();

        if ($next) {
            $tempSort = $next->sort;
            $next->update(['sort' => $current->sort]);
            $current->update(['sort' => $tempSort]);
            session()->flash('success', 'Urutan slider berhasil dipindahkan ke bawah.');
        }
    }

    public function delete()
    {
        $item = Slider::where('uid', $this->selectedUid)->first();
        if ($item) {
            if ($item->gambar) {
                Storage::delete('public/slider/' . $item->gambar);
            }
            $item->delete();
        }
        $this->dispatch('close-modal', name: 'delete-modal');
        session()->flash('success', 'Slider berhasil dihapus.');
    }

    public function render()
    {
        $sliders = Slider::where('title', 'like', '%' . $this->search . '%')
            ->orderBy('sort', 'asc')
            ->paginate(10);

        return view('livewire.admin.slider-index', [
            'sliders' => $sliders
        ])->layout('admin.layout', ['title' => 'Manajemen Slider']);
    }
}
