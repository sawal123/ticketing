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
    public $filterRole = 'all';
    public $filterImpact = 'all';
    public $filterStatus = 'all';

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['search', 'filterUser', 'filterRole', 'filterImpact', 'filterStatus'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $activities = ActivityLog::with('user')
            ->when($this->search, function ($query) {
                $query->where('activity', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('ip_address', 'like', '%' . $this->search . '%')
                    ->orWhere('location', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterUser, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->filterUser . '%');
                });
            })
            ->when($this->filterRole !== 'all', function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('role', $this->filterRole);
                });
            })
            ->when($this->filterImpact !== 'all', function ($query) {
                $query->where('impact_level', $this->filterImpact);
            })
            ->when($this->filterStatus !== 'all', function ($query) {
                $query->where('login_status', $this->filterStatus);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.activity-index', [
            'activities' => $activities
        ])->layout('admin.layout', ['title' => 'Aktivitas Pengguna']);
    }
}
