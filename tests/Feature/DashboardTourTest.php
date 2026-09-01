<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTourTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_tenant_dashboard_renders_a_six_step_tour_with_stable_targets_without_events(): void
    {
        $response = $this->actingAs($this->user('penyewa'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tur Dashboard', false)
            ->assertSee('dashboard.overview', false)
            ->assertSee('Panduan Singkat', false);

        foreach ([
            'dashboard-help',
            'dashboard-revenue',
            'dashboard-transactions',
            'dashboard-active-events',
            'dashboard-sales-trend',
        ] as $target) {
            $response->assertSee('data-tour="'.$target.'"', false);
        }

        $dashboardComponent = file_get_contents(app_path('Livewire/Dashboard/DemoIndex.php'));
        $this->assertSame(6, substr_count($dashboardComponent, "'target' =>"));
        $response->assertSee('Belum ada event aktif.', false);
    }

    public function test_admin_dashboard_does_not_render_the_tenant_dashboard_tour(): void
    {
        $this->actingAs($this->user('admin'))
            ->get(route('admin'))
            ->assertOk()
            ->assertDontSee('Tur Dashboard', false)
            ->assertDontSee('dashboard.overview', false);
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'uid' => (string) Str::uuid(),
            'email' => fake()->unique()->safeEmail(),
            'role' => $role,
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Jl. Sudirman No. 1',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ]);
    }
}
