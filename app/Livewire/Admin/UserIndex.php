<?php

namespace App\Livewire\Admin;

use Livewire\Component;

use App\Models\User;
use App\Models\Cash;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $activeTab = 'user';
    public $editingId = null;
    public $isModalOpen = false;

    // Form fields
    public $name;
    public $email;
    public $nomor;
    public $role = 'user';
    public $password;

    // History properties
    public $historyItems = [];
    public $historyUser = null;

    protected $queryString = ['search', 'activeTab'];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetFields();
        $this->dispatch('open-modal', name: 'user-modal');
    }

    public function resetFields()
    {
        $this->name = '';
        $this->email = '';
        $this->nomor = '';
        $this->role = $this->activeTab === 'cashes' ? 'user' : $this->activeTab;
        $this->password = '';
        $this->editingId = null;
        $this->resetValidation();
    }

    public function edit($id)
    {
        $this->resetFields();
        $this->editingId = $id;

        if ($this->activeTab === 'cashes') {
            $user = Cash::findOrFail($id);
            $this->role = 'user'; // Cash record doesn't have role
        } else {
            $user = User::findOrFail($id);
            $this->role = $user->role;
        }

        $this->name = $user->name;
        $this->email = $user->email;
        $this->nomor = $user->nomor;
        
        $this->dispatch('open-modal', name: 'user-modal');
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'nomor' => 'nullable|string|max:20',
        ];

        if ($this->activeTab !== 'cashes') {
            $rules['role'] = 'required|in:user,admin,penyewa';
            $rules['email'] .= '|unique:users,email,' . $this->editingId . ',id';
            
            if (!$this->editingId) {
                $rules['password'] = 'required|min:6';
            } else {
                $rules['password'] = 'nullable|min:6';
            }
        }

        $this->validate($rules);

        if ($this->activeTab === 'cashes') {
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'nomor' => $this->nomor,
            ];

            if ($this->editingId) {
                Cash::find($this->editingId)->update($data);
            } else {
                $data['uid'] = (string) Str::uuid();
                Cash::create($data);
            }
        } else {
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'nomor' => $this->nomor,
                'role' => $this->role,
            ];

            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }

            if ($this->editingId) {
                User::find($this->editingId)->update($data);
            } else {
                $data['uid'] = (string) Str::uuid();
                User::create($data);
            }
        }

        session()->flash('message', 'Data berhasil disimpan.');
        $this->dispatch('close-modal', name: 'user-modal');
        $this->resetFields();
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'delete-user-modal');
    }

    public function openHistory($id)
    {
        $this->historyUser = null;
        $this->historyItems = [];

        if ($this->activeTab === 'cashes') {
            $cash = Cash::findOrFail($id);
            $this->historyUser = $cash;
            
            // For Cash, we link via Cart UID matching Cash UID
            // But user wants "all history for this email"
            $this->historyItems = \App\Models\Cart::with(['event', 'hargaCarts'])
                ->whereIn('uid', Cash::where('email', $cash->email)->pluck('uid'))
                ->latest()
                ->get();
        } else {
            $user = User::findOrFail($id);
            $this->historyUser = $user;
            
            $this->historyItems = \App\Models\Cart::with(['event', 'hargaCarts'])
                ->where('user_uid', $user->uid)
                ->latest()
                ->get();
        }
    }

    public function delete()
    {
        if ($this->deletingId) {
            if ($this->activeTab === 'cashes') {
                Cash::findOrFail($this->deletingId)->delete();
            } else {
                User::findOrFail($this->deletingId)->delete();
            }
            $this->dispatch('close-modal', name: 'delete-user-modal');
            $this->deletingId = null;
            session()->flash('message', 'Data berhasil dihapus.');
        }
    }

    public function render()
    {
        if ($this->activeTab === 'cashes') {
            // Group by email, take the latest record for each email
            $latestIds = Cash::selectRaw('MAX(id) as id')->groupBy('email')->pluck('id');
            $query = Cash::whereIn('id', $latestIds);
        } else {
            $query = User::where('role', $this->activeTab);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('nomor', 'like', '%' . $this->search . '%');
            });
        }

        $users = $query->latest()->paginate(10);

        return view('livewire.admin.user-index', [
            'users' => $users
        ])->layout('admin.layout', ['title' => 'Manajemen Pengguna']);
    }
}
