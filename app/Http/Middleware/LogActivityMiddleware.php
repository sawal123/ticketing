<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogActivityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log if authenticated and it's a non-GET request (to avoid logging every page view)
        // Or if you want to log everything, just remove the method check.
        // For now, let's log important things like login, post, put, delete.
        
        if (Auth::check()) {
            $user = Auth::user();
            $method = $request->method();
            $path = $request->path();
            
            // Define activities to log
            if ($method !== 'GET' || str_contains($path, 'login') || str_contains($path, 'logout')) {
                ActivityLog::create([
                    'user_uid' => $user->uid,
                    'activity' => $this->getActivityName($request),
                    'description' => "User {$user->name} performed {$method} on {$path}",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        }

        return $response;
    }

    protected function getActivityName(Request $request)
    {
        $path = $request->path();
        if (str_contains($path, 'login')) return 'Login';
        if (str_contains($path, 'logout')) return 'Logout';
        
        return $request->method() . ' Request';
    }
}
