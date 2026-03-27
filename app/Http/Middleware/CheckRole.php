<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // 1. Cek apakah user sudah login
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // 2. Cek apakah role user ada dalam daftar yang diizinkan (...$roles)
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // 3. Jika tidak punya akses, lempar ke halaman 403 atau redirect
        abort(403, 'Maaf, akun Anda tidak memiliki akses ke halaman ini.');
    }
}
