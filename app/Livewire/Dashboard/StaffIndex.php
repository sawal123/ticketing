<?php

namespace App\Livewire\Dashboard;

use App\Jobs\SendStaffInvitationJob;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class StaffIndex extends Component
{
    use WithPagination;

    #[Layout('layouts.unified')]
    public $search = '';

    public $staff_id;

    public $name;

    public $email;

    public $isEditMode = false;

    public function mount(): void
    {
        abort_unless(Auth::user()?->role === 'penyewa', 403);
    }

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
        if ($this->isEditMode) {
            $this->validate([
                'name' => 'required|string|max:255',
            ]);

            $staff = $this->ownedStaffQuery()
                ->whereKey($this->staff_id)
                ->firstOrFail();

            $staff->update([
                'name' => $this->name,
            ]);

            session()->flash('success', 'Data staff berhasil diperbarui.');
        } else {
            $this->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
            ]);

            $email = Str::lower(trim((string) $this->email));
            $existingUser = User::where('email', $email)->first();

            if ($existingUser?->role === User::STAFF_ROLE && $existingUser->parent_uid === $this->ownerUid()) {
                $this->addError('email', 'Staff dengan email ini sudah terdaftar.');

                return;
            }

            if ($existingUser?->role === User::STAFF_ROLE) {
                $this->addError('email', 'Email tidak dapat digunakan sebagai staff.');

                return;
            }

            if ($existingUser) {
                $this->addError('email', 'Email sudah terdaftar. Pemilik akun harus menerima undangan staff terlebih dahulu.');

                return;
            }

            $staff = User::create([
                'uid' => (string) Str::uuid(),
                'parent_uid' => $this->ownerUid(),
                'name' => $this->name,
                'email' => $email,
                'role' => User::STAFF_ROLE,
                'password' => Hash::make(Str::random(40)),
                'birthday' => '2000-01-01',
                'nomor' => '-',
                'alamat' => '-',
                'kota' => '-',
                'gender' => 'pria',
                'gambar' => 'default.png',
            ]);

            $verifyUrl = URL::temporarySignedRoute(
                'staff.verify',
                now()->addHours(24),
                ['uid' => $staff->uid]
            );

            dispatch(new SendStaffInvitationJob($staff->email, $staff->name, $verifyUrl));

            session()->flash('success', 'Undangan staff berhasil dikirim ke '.$staff->email);
        }

        $this->dispatch('close-modal', name: 'staff-modal');
        $this->resetForm();
    }

    public function confirmDelete($id)
    {
        $staff = $this->ownedStaffQuery()
            ->whereKey($id)
            ->firstOrFail();

        $this->staff_id = $staff->id;
        $this->dispatch('open-modal', name: 'delete-modal');
    }

    public function delete()
    {
        $staff = $this->ownedStaffQuery()
            ->whereKey($this->staff_id)
            ->firstOrFail();

        $staff->delete();
        session()->flash('success', 'Staff berhasil dihapus.');

        $this->dispatch('close-modal', name: 'delete-modal');
    }

    public function render()
    {
        $staffs = $this->ownedStaffQuery()
            ->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.dashboard.staff-index', [
            'staffs' => $staffs,
        ]);
    }

    private function ownedStaffQuery()
    {
        return User::where('role', User::STAFF_ROLE)
            ->where('parent_uid', $this->ownerUid());
    }

    private function ownerUid(): string
    {
        return Auth::user()->uid;
    }
}
