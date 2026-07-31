<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class Login extends Component
{
    private const RATE_LIMIT_MESSAGE = 'Terlalu banyak percobaan login. Silakan coba lagi beberapa saat.';

    public $email;

    public $password;

    public $remember = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
    ];

    public function login()
    {
        $this->validate();

        $rateLimitKey = $this->rateLimitKey();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            session()->flash('error', self::RATE_LIMIT_MESSAGE);

            return;
        }

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::clear($rateLimitKey);
            $role = strtolower((string) Auth::user()->role);

            if ($role === 'staff') {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();
                session()->flash('error', 'Akun staff hanya dapat digunakan untuk login ke aplikasi scan tiket.');

                return;
            }

            session()->regenerate();

            if ($role === 'admin') {
                return redirect('/admin');
            }

            if ($role === 'penyewa') {
                return redirect('/dashboard');
            }

            return redirect('/');
        }

        RateLimiter::hit($rateLimitKey, 60);
        session()->flash('error', 'Email atau password yang Anda masukkan salah.');
    }

    private function rateLimitKey(): string
    {
        return 'web-login:'.sha1(Str::lower(trim((string) $this->email)).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.auth', ['title' => 'Login']);
    }
}
