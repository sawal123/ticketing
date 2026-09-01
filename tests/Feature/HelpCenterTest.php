<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\HelpCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('logo', [(object) ['logo' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_tenant_and_staff_can_open_help_center_while_admin_is_blocked(): void
    {
        $tenant = $this->user('penyewa');
        $staff = $this->user('staff', ['parent_uid' => $tenant->uid]);
        $admin = $this->user('admin');

        $this->actingAs($tenant)->get(route('dashboard.help'))->assertOk()->assertSee('Pusat Panduan');
        $this->actingAs($staff)->get(route('dashboard.help'))->assertOk()->assertSee('Pusat Panduan');
        $this->actingAs($admin)->get(route('dashboard.help'))->assertForbidden();
    }

    public function test_help_center_has_five_categories_search_and_stable_article_anchors(): void
    {
        $tenant = $this->user('penyewa');

        Livewire::actingAs($tenant)
            ->test(HelpCenter::class)
            ->assertSee('Memulai')
            ->assertSee('Penjualan Tiket')
            ->assertSee('Operasional Event')
            ->assertSee('Keuangan')
            ->assertSee('Dokumen')
            ->assertSee('id="memulai-dashboard"', false)
            ->assertSee('id="penjualan-tiket"', false)
            ->assertSee('id="operasional-transaksi"', false)
            ->assertSee('id="keuangan-penarikan"', false)
            ->assertSee('id="dokumen-mou"', false)
            ->set('search', 'MOU')
            ->assertSee('MOU Event')
            ->set('search', 'Penarikan')
            ->assertSee('Penarikan Saldo')
            ->set('search', 'tidak-ada-panduan')
            ->assertSee('Panduan tidak ditemukan.');
    }

    public function test_sidebar_and_contextual_help_keep_support_and_article_mappings(): void
    {
        $tenant = $this->user('penyewa');
        $this->actingAs($tenant);

        $sidebar = view('layouts.partials.sidebar', ['sidebarActiveEvents' => collect()])->render();
        $help = file_get_contents(resource_path('views/components/dashboard/contextual-help.blade.php'));

        $this->assertStringContainsString('Panduan', $sidebar);
        $this->assertStringContainsString('book-open', $sidebar);
        $this->assertStringContainsString('Support', $sidebar);
        foreach (['memulai-dashboard', 'penjualan-tiket', 'dokumen-mou', 'operasional-transaksi', 'keuangan-penarikan'] as $anchor) {
            $this->assertStringContainsString($anchor, $help);
        }
        $this->assertStringNotContainsString('TutorialProgressService', file_get_contents(app_path('Livewire/Dashboard/HelpCenter.php')));
    }

    private function user(string $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Help Center User',
            'email' => fake()->unique()->safeEmail(),
            'role' => $role,
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Jl. Sudirman No. 1',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }
}
