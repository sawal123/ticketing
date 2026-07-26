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
        // API memakai auth:sanctum sebelum middleware ini. Web memakai session auth.
        if (!Auth::check()) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect('/login');
        }

        $userRole = strtolower((string) Auth::user()->role);

        // Akun staff hanya boleh digunakan oleh aplikasi scanner melalui API.
        if ($userRole === 'staff' && !$request->is('api/*')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->with(
                'error',
                'Akun staff hanya dapat digunakan untuk login ke aplikasi scan tiket.'
            );
        }

        $allowedRoles = array_map('strtolower', $roles);

        if (in_array($userRole, $allowedRoles, true)) {
            return $next($request);
        }

        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak memiliki akses ke API scanner.',
            ], 403);
        }

        abort(403, 'Maaf, akun Anda tidak memiliki akses ke halaman ini.');
    }
}
