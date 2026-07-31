<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class isAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            if (strtolower(Auth::user()->role) === 'admin') {
                return $next($request);
            }

            return redirect('/')->with('error', 'Halaman tidak tersedia.');
        }

        return redirect('/login');
    }
}
