<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Http;

class AuthEventSubscriber
{
    /**
     * Handle user login events.
     */
    public function handleUserLogin($event) {
        $user = $event->user;
        $ip = Request::ip();
        $deviceId = md5(Request::userAgent() . $ip);
        
        // Check for concurrent sessions (anomaly)
        $existingSession = ActivityLog::where('user_uid', $user->uid)
            ->where('activity', 'Login')
            ->where('login_status', 'Success')
            ->where('device_id', '!=', $deviceId)
            ->where('created_at', '>', now()->subHours(2)) // Assume 2h session window
            ->exists();

        if ($existingSession) {
            ActivityLog::create([
                'user_uid' => $user->uid,
                'activity' => 'Concurrent Session',
                'login_status' => 'Success',
                'description' => "Deteksi Sesi Ganda: Akun login dari perangkat lain dalam waktu bersamaan.",
                'impact_level' => 'Berisiko Tinggi',
                'ip_address' => $ip,
                'location' => $this->getLocation($ip),
                'user_agent' => Request::userAgent(),
                'device_id' => $deviceId,
                'session_id' => session()->getId(),
            ]);
        }

        ActivityLog::create([
            'user_uid' => $user->uid,
            'activity' => 'Login',
            'login_status' => 'Success',
            'description' => "User {$user->name} logged in successfully.",
            'impact_level' => 'Normal',
            'ip_address' => $ip,
            'location' => $this->getLocation($ip),
            'user_agent' => Request::userAgent(),
            'device_id' => $deviceId,
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * Handle user logout events.
     */
    public function handleUserLogout($event) {
        $user = $event->user;
        if (!$user) return;
        
        $ip = Request::ip();
        
        ActivityLog::create([
            'user_uid' => $user->uid,
            'activity' => 'Logout',
            'login_status' => 'Success',
            'description' => "User {$user->name} logged out.",
            'impact_level' => 'Normal',
            'ip_address' => $ip,
            'location' => $this->getLocation($ip),
            'user_agent' => Request::userAgent(),
            'device_id' => md5(Request::userAgent() . $ip),
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * Handle user login failure events.
     */
    public function handleUserLoginFailed($event) {
        $ip = Request::ip();
        
        ActivityLog::create([
            'user_uid' => null, // No user for failed login
            'activity' => 'Login Attempt Failed',
            'login_status' => 'Failed',
            'description' => "Failed login attempt for email: " . ($event->credentials['email'] ?? 'Unknown'),
            'impact_level' => 'Berisiko Tinggi',
            'ip_address' => $ip,
            'location' => $this->getLocation($ip),
            'user_agent' => Request::userAgent(),
            'device_id' => md5(Request::userAgent() . $ip),
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return void
     */
    public function subscribe($events)
    {
        $events->listen(
            Login::class,
            [AuthEventSubscriber::class, 'handleUserLogin']
        );

        $events->listen(
            Logout::class,
            [AuthEventSubscriber::class, 'handleUserLogout']
        );

        $events->listen(
            Failed::class,
            [AuthEventSubscriber::class, 'handleUserLoginFailed']
        );
    }

    protected function getLocation($ip)
    {
        if ($ip === '127.0.0.1' || $ip === '::1') return 'Localhost';
        
        try {
            $response = Http::get("http://ip-api.com/json/{$ip}?fields=city,country");
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
