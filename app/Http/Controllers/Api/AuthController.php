<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Cari user berdasarkan email
        $user = User::where('email', $request->email)->first();

        // 3. Cek apakah user ada dan passwordnya cocok
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah!',
            ], 401);
        }

        // 4. (Opsional) Batasi akses hanya untuk role 'staff' atau 'penyewa'
        if (!in_array($user->role, ['staff', 'penyewa'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak! Aplikasi ini hanya untuk petugas scanner.',
            ], 403);
        }

        // 5. Buat Token Sanctum
        $token = $user->createToken('scanner-app-token')->plainTextToken;

        // 6. Kembalikan response sukses beserta token
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            'data' => [
                'user' => [
                    'uid' => $user->uid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'gambar' => $user->gambar,
                ],
                'token' => $token
            ]
        ], 200);
    }

    // Sekalian kita buatkan fungsi Logout
    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil!'
        ], 200);
    }
}
