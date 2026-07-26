<?php

namespace App\Http\Controllers\Penyewa\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function index(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return redirect()->back()->with('success', 'Email atau password salah.');
        }

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
