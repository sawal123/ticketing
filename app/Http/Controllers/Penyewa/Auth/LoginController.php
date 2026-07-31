<?php

namespace App\Http\Controllers\Penyewa\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function index(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $email = Str::lower(trim((string) $request->email));
        $rateLimitKey = 'web-login:'.sha1($email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return redirect()->back()->with('success', 'Terlalu banyak percobaan login. Silakan coba lagi beberapa saat.');
        }

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($rateLimitKey, 60);

            return redirect()->back()->with('success', 'Email atau password salah.');
        }

        RateLimiter::clear($rateLimitKey);
        $role = strtolower((string) Auth::user()->role);

        if ($role === 'staff') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->back()->with(
                'success',
                'Akun staff hanya dapat digunakan untuk login ke aplikasi scan tiket.'
            );
        }

        if ($role === 'penyewa') {
            $request->session()->regenerate();

            return redirect('/dashboard');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->back()->with('success', 'Akun ini tidak memiliki akses ke dashboard penyewa.');
    }
}
