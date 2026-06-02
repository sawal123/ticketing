<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class MonitoringIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $filterRole = 'all';
    public $filterAction = 'all';
    public $filterImpact = 'all';

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['search', 'filterRole', 'filterAction', 'filterImpact'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $baseQuery = ActivityLog::with('user')
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['user', 'penyewa', 'staff']);
            })
            ->where(function ($query) {
                $query->where('description', 'like', '%Mengubah data%')
                    ->orWhere('description', 'like', '%Menambah%')
                    ->orWhere('description', 'like', '%Menyimpan data%')
                    ->orWhere('description', 'like', '%Menghapus data%');
            });

        $logs = (clone $baseQuery)
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('activity', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhere('ip_address', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($userQuery) {
                            $userQuery->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->filterRole !== 'all', function ($query) {
                $query->whereHas('user', function ($userQuery) {
                    $userQuery->where('role', $this->filterRole);
                });
            })
            ->when($this->filterImpact !== 'all', function ($query) {
                $query->where('impact_level', $this->filterImpact);
            })
            ->when($this->filterAction !== 'all', function ($query) {
                $needle = match ($this->filterAction) {
                    'create' => 'Menambah',
                    'update' => 'Mengubah',
                    'delete' => 'Menghapus',
                    default => '',
                };

                if ($needle) {
                    $query->where('description', 'like', '%' . $needle . '%');
                }
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.monitoring-index', [
            'logs' => $logs,
            'totalChanges' => (clone $baseQuery)->count(),
            'todayChanges' => (clone $baseQuery)->whereDate('created_at', now())->count(),
            'highRiskChanges' => (clone $baseQuery)->where('impact_level', 'Berisiko Tinggi')->count(),
        ])->layout('admin.layout', ['title' => 'Monitoring Data']);
    }
}
