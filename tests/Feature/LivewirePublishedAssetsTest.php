<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\TestCase;

class LivewirePublishedAssetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        cache()->flush();
        View::share('logo', [(object) ['logo' => '']]);
    }

    public function test_admin_page_uses_livewire_endpoint_instead_of_published_vendor_assets(): void
    {
        $admin = $this->user(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin'))
            ->assertOk()
            ->assertSee('/livewire/livewire.js', false)
            ->assertDontSee('/vendor/livewire/', false);
    }

    public function test_dashboard_page_uses_livewire_endpoint_instead_of_published_vendor_assets(): void
    {
        $tenant = $this->user(['role' => 'penyewa']);

        $this->actingAs($tenant)
            ->get(route('dashboard.settings'))
            ->assertOk()
            ->assertSee('/livewire/livewire.js', false)
            ->assertDontSee('/vendor/livewire/', false);
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Livewire Asset User',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'user',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat awal',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }
}
