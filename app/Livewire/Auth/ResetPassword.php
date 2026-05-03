<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ResetPassword extends Component
{
    public $data; // This is the user UID
    public $password;
    public $password_confirmation;

    protected $rules = [
        'password' => 'required|min:8|confirmed|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/',
    ];

    protected $messages = [
        'password.regex' => 'Password harus mengandung huruf dan angka.',
    ];

    public function mount($data)
    {
        $this->data = $data;
        $user = User::where('uid', $this->data)->first();
        
        if (!$user) {
            abort(404);
        }
    }

    public function resetPassword()
    {
        $this->validate();

        $user = User::where('uid', $this->data)->first();
        
        if ($user) {
            $user->update([
                'password' => Hash::make($this->password)
            ]);

            session()->flash('success', 'Password Anda telah berhasil diperbarui. Silakan login kembali.');
            return redirect()->route('login');
        }

        session()->flash('error', 'Gagal memperbarui password. Silakan coba lagi atau hubungi admin.');
    }

    public function render()
    {
        return view('livewire.auth.reset-password')->layout('layouts.auth', ['title' => 'Reset Password']);
    }
}
