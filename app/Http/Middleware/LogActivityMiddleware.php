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

        if (Auth::check()) {
            $user = Auth::user();
            $method = $request->method();
            $path = $request->path();
            
            // Only log important activities
            if ($method !== 'GET' || str_contains($path, 'login') || str_contains($path, 'logout') || str_contains($path, 'payment-gateway')) {
                
                $impactLevel = 'Normal';
                if ($method === 'DELETE' || str_contains($path, 'payment-gateway') || str_contains($path, 'setting') || str_contains($path, 'user')) {
                    $impactLevel = 'Sensitif';
                }
                if (str_contains($path, 'delete') && $method === 'POST') {
                    $impactLevel = 'Berisiko Tinggi';
                }

                // Scalper / Bot Detection (Simple Velocity Check)
                if (in_array($path, ['checkout', 'paynow'])) {
                    $recentRequests = ActivityLog::where('ip_address', $request->ip())
                        ->where('created_at', '>', now()->subSeconds(30))
                        ->count();
                    
                    if ($recentRequests > 10) {
                        $impactLevel = 'Berisiko Tinggi';
                        ActivityLog::create([
                            'user_uid' => $user->uid,
                            'activity' => 'Scalper Anomaly',
                            'login_status' => 'Success',
                            'description' => "Anomali Kecepatan: Terdeteksi {$recentRequests} request dalam 30 detik (Potensi Bot/Calo).",
                            'impact_level' => 'Berisiko Tinggi',
                            'ip_address' => $request->ip(),
                            'location' => $this->getLocation($request->ip()),
                            'user_agent' => $request->userAgent(),
                            'device_id' => md5($request->userAgent() . $request->ip()),
                            'session_id' => session()->getId(),
                        ]);
                    }
                }

                ActivityLog::create([
                    'user_uid' => $user->uid,
                    'activity' => $this->getActivityName($request),
                    'login_status' => 'Success',
                    'description' => "User {$user->name} performed {$method} on {$path}",
                    'impact_level' => $impactLevel,
                    'ip_address' => $request->ip(),
                    'location' => $this->getLocation($request->ip()),
                    'user_agent' => $request->userAgent(),
                    'device_id' => md5($request->userAgent() . $request->ip()),
                    'session_id' => session()->getId(),
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

    protected function getLocation($ip)
    {
        if ($ip === '127.0.0.1' || $ip === '::1') return 'Localhost';
        
        try {
            $response = \Illuminate\Support\Facades\Http::get("http://ip-api.com/json/{$ip}?fields=city,country");
            if ($response->successful()) {
                $data = $response->json();
                return ($data['city'] ?? 'Unknown') . ', ' . ($data['country'] ?? 'Unknown');
            }
        } catch (\Exception $e) {
            // Fallback
        }
        
        return 'Unknown';
    }
}
