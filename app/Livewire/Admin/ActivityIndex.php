<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $filterUser = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $activities = ActivityLog::with('user')
            ->when($this->search, function ($query) {
                $query->where('activity', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('ip_address', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterUser, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->filterUser . '%');
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.activity-index', [
            'activities' => $activities
        ])->layout('admin.layout', ['title' => 'Aktivitas Pengguna']);
    }
}
