<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Shim for Livewire compatibility with Ignition
        if (! class_exists('Livewire\Mechanisms\ComponentRegistry')) {
            require_once app_path('Shims/ComponentRegistry.php');
            
            $this->app->singleton('Livewire\Mechanisms\ComponentRegistry', function () {
                return new \Livewire\Mechanisms\ComponentRegistry();
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Carbon::setLocale('id');
        date_default_timezone_set('Asia/Jakarta');

        view()->composer(['layouts.partials.sidebar', 'admin.sidebar'], function ($view) {
            $query = \App\Models\Event::where('status', 'active');
            
            if (auth()->check()) {
                $user = auth()->user();
                if ($user->role === 'penyewa') {
                    $query->where('user_uid', $user->uid);
                } elseif ($user->role === 'staff') {
                    $query->where('user_uid', $user->parent_uid);
                }
                // Admin role sees all active events
            }
            
            $activeEvents = $query->get();
            $view->with('activeEvents', $activeEvents);
        });
    }
}
