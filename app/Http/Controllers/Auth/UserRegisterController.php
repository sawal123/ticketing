<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserRegisterController extends Controller
{

    public function index()
    {
        if (Auth::check()) {
            return redirect('/');
        } else {
            $http = Http::get('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
            if ($http->successful()) {
                $provinsi = $http->json();
            }
            return view('frontend.page.auth.register', [
                'title' => 'Register',
                'provinsi' => $provinsi
            ]);
        }
    }

    public function create(Request $request)
    {
        $uid = Str::uuid();

        $validateUser = $request->validate([
            'user' => 'required|max:255',
            'email' => 'required|email',
            // 'nomor' => 'required|numeric',
            'birthday' => 'required|max:255',
            'gender' => 'required|max:10',
            // 'kota' => 'required|max:100',
            // 'alamat' => 'required|max:255',
            'password' => [
                'required',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/', // Memastikan password tidak berupa angka semua
            ],
        ], [
            'password.min' => 'Password harus memiliki minimal 8 karakter.',
            'password.regex' => 'Password harus mengandung huruf dan angka.',
        ]);
        if (User::where('email', $validateUser['email'])->exists()) {
            return redirect()->back()->with('error', 'Email Sudah Terdaftar!'); // Ganti 'email.exists.page' dengan nama route yang sesuai
        }

        $user = User::create([
            'uid' => $uid,
            'name' => $validateUser['user'],
            'email' => $validateUser['email'],
            'nomor' => '',
            'birthday' => $validateUser['birthday'],
            'alamat' => '',
            'kota' => '',
            'gender' => $validateUser['gender'],
            'gambar' => '',
            'role' => User::USER_ROLE,
            'password' => Hash::make($validateUser['password'])
        ]);
        $success['token'] = $user->createToken('auth_token')->plainTextToken;
        $success['name'] = $user->name;
        return redirect('/login')->with('success', 'Registrasi Berhasil');



    }
}
