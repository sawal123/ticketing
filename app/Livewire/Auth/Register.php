<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

class Register extends Component
{
    public $name;
    public $email;
    public $password;
    public $password_confirmation;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/',
        'password_confirmation' => 'required|same:password',
    ];

    protected $messages = [
        'password.regex' => 'Password harus mengandung huruf dan angka.',
        'password_confirmation.same' => 'Konfirmasi password tidak cocok.',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function register()
    {
        $this->validate();

        $user = User::create([
            'uid' => Str::uuid(),
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => User::USER_ROLE,
            'birthday' => now()->format('Y-m-d'), // Default for simplified register
            'gender' => 'Other', // Default
            'gambar' => 'default.png', // Fallback for non-nullable column
            'kota' => '-', // Fallback
            'alamat' => '-', // Fallback
        ]);

        Auth::login($user);

        return redirect('/');
    }

    public function render()
    {
        return view('livewire.auth.register')->layout('layouts.auth', ['title' => 'Sign Up']);
    }
}
