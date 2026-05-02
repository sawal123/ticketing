<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use App\Jobs\SendStaffInvitationJob;

class StaffIndex extends Component
{
    use WithPagination;

    #[Layout('layouts.unified')]
    
    public $search = '';
    
    // Form properties
    public $staff_id;
    public $name;
    public $email;
    public $isEditMode = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
    ];

    public function resetForm()
    {
        $this->reset(['staff_id', 'name', 'email', 'isEditMode']);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->dispatch('open-modal', name: 'staff-modal');
    }

    public function save()
    {
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        if ($this->isEditMode) {
            $this->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $this->staff_id,
            ]);

            $staff = User::findOrFail($this->staff_id);
            $staff->update([
                'name' => $this->name,
                'email' => $this->email,
            ]);
            session()->flash('success', 'Data staff berhasil diperbarui.');
        } else {
            // Create or Convert staff user
            $existingUser = User::where('email', $this->email)->first();

            if ($existingUser) {
                if (in_array($existingUser->role, ['admin', 'penyewa'])) {
                    $this->addError('email', 'Email ini sudah terdaftar sebagai ' . $existingUser->role . ' dan tidak dapat dijadikan staff.');
                    return;
                }

                if ($existingUser->role === 'staff') {
                    $this->addError('email', 'Email ini sudah terdaftar sebagai staff.');
                    return;
                }

                // If role is 'user', convert to 'staff'
                $existingUser->update([
                    'role' => 'staff',
                    'parent_uid' => $ownerId,
                    'name' => $this->name, // Update name to the one provided in the form
                ]);
                $staff = $existingUser;
                $message = 'User dengan email ini telah berhasil diubah menjadi staff.';
            } else {
                $this->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email',
                ]);

                $staff = User::create([
                    'uid' => (string) Str::uuid(),
                    'parent_uid' => $ownerId,
                    'name' => $this->name,
                    'email' => $this->email,
                    'role' => 'staff',
                    'password' => bcrypt(Str::random(16)),
                    'birthday' => '2000-01-01',
                    'nomor' => '-',
                    'alamat' => '-',
                    'kota' => '-',
                    'gender' => 'pria',
                    'gambar' => 'default.png',
                ]);
                $message = 'Undangan staff berhasil dikirim ke ' . $staff->email;
            }

            // Create verification URL
            $verifyUrl = URL::temporarySignedRoute(
                'staff.verify',
                now()->addHours(24),
                ['uid' => $staff->uid]
            );

            // Send invitation email
            dispatch(new SendStaffInvitationJob($staff->email, $staff->name, $verifyUrl));

            session()->flash('success', $message);
        }

        $this->dispatch('close-modal', name: 'staff-modal');
        $this->resetForm();
    }

    public function confirmDelete($id)
    {
        $this->staff_id = $id;
        $this->dispatch('open-modal', name: 'delete-modal');
    }

    public function delete()
    {
        $staff = User::findOrFail($this->staff_id);
        if ($staff->role === 'staff') {
            $staff->delete();
            session()->flash('success', 'Staff berhasil dihapus.');
        }
        $this->dispatch('close-modal', name: 'delete-modal');
    }

    public function render()
    {
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        $staffs = User::where('role', 'staff')
            ->where('parent_uid', $ownerId)
            ->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.dashboard.staff-index', [
            'staffs' => $staffs
        ]);
    }
}
