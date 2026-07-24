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
                
                $livewireActivity = $this->getLivewireActivity($request);
                $routeMutationActivity = $this->getRouteMutationActivity($request);
                $detectedActivity = $livewireActivity ?? $routeMutationActivity;
                $impactLevel = $detectedActivity['impact_level'] ?? 'Normal';
                if ($impactLevel !== 'Berisiko Tinggi' && ($method === 'DELETE' || str_contains($path, 'payment-gateway') || str_contains($path, 'setting') || str_contains($path, 'user'))) {
                    $impactLevel = 'Sensitif';
                }
                if (str_contains($path, 'delete') && $method === 'POST') {
                    $impactLevel = 'Berisiko Tinggi';
                }

                // Lightweight anomaly signal; enforcement happens through named route limiters.
                if (in_array($path, ['checkout', 'paynow'])) {
                    $deviceId = md5($request->userAgent() . session()->getId());
                    $recentRequests = ActivityLog::where('user_uid', $user->uid)
                        ->where('device_id', $deviceId)
                        ->where('created_at', '>', now()->subSeconds(30))
                        ->count();
                    
                    if ($recentRequests > 10) {
                        $impactLevel = 'Berisiko Tinggi';
                        ActivityLog::safeCreate([
                            'user_uid' => $user->uid,
                            'activity' => 'Scalper Anomaly',
                            'login_status' => 'Success',
                            'description' => "Anomali Kecepatan: Terdeteksi {$recentRequests} request dalam 30 detik (Potensi Bot/Calo).",
                            'impact_level' => 'Berisiko Tinggi',
                            'ip_address' => $request->ip(),
                            'location' => $this->getLocation($request->ip()),
                            'user_agent' => $request->userAgent(),
                            'device_id' => $deviceId,
                            'session_id' => session()->getId(),
                        ]);
                    }
                }

                ActivityLog::safeCreate([
                    'user_uid' => $user->uid,
                    'activity' => $detectedActivity['activity'] ?? $this->getActivityName($request),
                    'login_status' => 'Success',
                    'description' => $detectedActivity['description'] ?? "User {$user->name} performed {$method} on {$path}",
                    'impact_level' => $impactLevel,
                    'ip_address' => $request->ip(),
                    'location' => $this->getLocation($request->ip()),
                    'user_agent' => $request->userAgent(),
                    'device_id' => md5($request->userAgent() . session()->getId()),
                    'session_id' => session()->getId(),
                ]);
            }
        }

        return $response;
    }

    protected function getRouteMutationActivity(Request $request): ?array
    {
        $method = strtoupper($request->method());
        $path = strtolower($request->path());

        if ($method === 'GET' || str_contains($path, 'livewire')) {
            return null;
        }

        $action = null;
        $impactLevel = 'Sensitif';

        if ($method === 'DELETE' || str_contains($path, 'delete') || str_contains($path, 'destroy')) {
            $action = 'Menghapus data';
            $impactLevel = 'Berisiko Tinggi';
        } elseif (str_contains($path, 'edit') || str_contains($path, 'update') || str_contains($path, 'ubah')) {
            $action = 'Mengubah data';
        } elseif ($method === 'POST' || str_contains($path, 'add') || str_contains($path, 'create') || str_contains($path, 'store')) {
            $action = 'Menambah/Menyimpan data';
        }

        if (! $action) {
            return null;
        }

        $user = Auth::user();

        return [
            'activity' => 'Data Mutation',
            'description' => "User {$user->name} menjalankan {$action}: {$request->method()} {$request->path()}",
            'impact_level' => $impactLevel,
        ];
    }

    protected function getLivewireActivity(Request $request): ?array
    {
        if (! $this->isLivewireRequest($request)) {
            return null;
        }

        $components = $request->input('components', []);
        $activities = [];
        $impactLevel = 'Normal';

        foreach ($components as $component) {
            $componentName = $this->getLivewireComponentName($component['snapshot'] ?? null);

            foreach (($component['calls'] ?? []) as $call) {
                $method = $call['method'] ?? null;
                if (! $method) {
                    continue;
                }

                $action = $this->getMutationAction($method);
                if (! $action) {
                    continue;
                }

                $target = $this->getMutationTarget($componentName, $method);
                $activities[] = trim("{$action} {$target} ({$componentName}.{$method})");

                if (str_contains(strtolower($method), 'delete')) {
                    $impactLevel = 'Berisiko Tinggi';
                } elseif ($action) {
                    $impactLevel = $impactLevel === 'Berisiko Tinggi' ? $impactLevel : 'Sensitif';
                }
            }
        }

        if ($activities === []) {
            return null;
        }

        $user = Auth::user();

        return [
            'activity' => 'Livewire Action',
            'description' => "User {$user->name} menjalankan " . implode(', ', $activities),
            'impact_level' => $impactLevel,
        ];
    }

    protected function isLivewireRequest(Request $request): bool
    {
        $path = strtolower($request->path());

        return str_contains($path, 'livewire') && str_contains($path, 'update');
    }

    protected function getLivewireComponentName($snapshot): string
    {
        if (! is_string($snapshot)) {
            return 'unknown-component';
        }

        $decoded = json_decode($snapshot, true);

        return $decoded['memo']['name'] ?? 'unknown-component';
    }

    protected function getMutationAction(string $method): ?string
    {
        $method = strtolower($method);

        if (str_contains($method, 'open') || str_contains($method, 'confirm') || str_contains($method, 'show')) return null;
        if (str_contains($method, 'delete') || str_contains($method, 'destroy')) return 'Menghapus data';
        if (str_contains($method, 'update') || str_contains($method, 'toggle') || str_starts_with($method, 'edit')) return 'Mengubah data';
        if (str_contains($method, 'save') || str_contains($method, 'store') || str_contains($method, 'create')) return 'Menambah/Menyimpan data';

        return null;
    }

    protected function getMutationTarget(string $componentName, string $method): string
    {
        $haystack = strtolower($componentName . ' ' . $method);

        $targets = [
            'Tiket' => ['ticket', 'tiket', 'harga'],
            'Transaksi' => ['transaction', 'transaksi', 'resendemail'],
            'Event' => ['event'],
            'Talent' => ['talent'],
            'Voucher' => ['voucher'],
            'Partner' => ['partner'],
            'Staff' => ['staff'],
            'Penarikan' => ['penarikan'],
            'Pengaturan Akun' => ['settings', 'profile', 'password', 'bank'],
            'Kategori' => ['category'],
            'Fasilitas' => ['fasilitas'],
            'Payment Gateway' => ['payment'],
            'Slider' => ['slider'],
            'Terms & Conditions' => ['term'],
            'Pengguna' => ['user'],
        ];

        foreach ($targets as $target => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    return "data {$target}";
                }
            }
        }

        return 'data';
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

        return 'Unknown';
    }
}
