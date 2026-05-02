<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;
use Livewire\Component;

class StaffVerify extends Component
{
    public $uid;
    public $staff;
    
    // Form fields
    public $password;
    public $password_confirmation;
    public $nomor;
    public $birthday;
    public $alamat;

    public $isSuccess = false;

    public function mount($uid)
    {
        // Check signature from request
        if (!request()->hasValidSignature()) {
            abort(401, 'Link verifikasi sudah kadaluarsa atau tidak valid.');
        }

        $this->uid = $uid;
        $this->staff = User::where('uid', $uid)->firstOrFail();

        if ($this->staff->email_verified_at) {
            return redirect('/login')->with('success', 'Akun sudah diverifikasi. Silakan login.');
        }
    }

    public function save()
    {
        $this->validate([
            'password' => 'required|min:8|confirmed',
            'nomor' => 'required|numeric',
            'birthday' => 'required|date',
            'alamat' => 'required|string|min:10',
        ]);

        $this->staff->password = Hash::make($this->password);
        $this->staff->nomor = $this->nomor;
        $this->staff->birthday = $this->birthday;
        $this->staff->alamat = $this->alamat;
        $this->staff->email_verified_at = now();
        $this->staff->save();

        // Logout if any user is logged in
        if (Auth::check()) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
        }

        $this->isSuccess = true;
    }

    public function render()
    {
        return view('livewire.auth.staff-verify')->layout('layouts.auth');
    }
}
