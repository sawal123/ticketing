<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Admin\SettingIndex;
use App\Models\PlatformLegalProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformLegalProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '', 'icon' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_admin_can_open_admin_setting_page_and_existing_tabs_render(): void
    {
        $admin = $this->user(['role' => User::ADMIN_ROLE, 'email' => 'admin-settings@example.test']);

        $this->actingAs($admin)
            ->get('/admin/setting')
            ->assertOk()
            ->assertSee('Logo & Icon', false)
            ->assertSee('SEO Meta', false)
            ->assertSee('Identitas Legal', false);
    }

    public function test_admin_can_save_platform_legal_profile(): void
    {
        $admin = $this->user(['role' => User::ADMIN_ROLE, 'email' => 'admin-save@example.test']);

        Livewire::actingAs($admin)
            ->test(SettingIndex::class)
            ->set('activeTab', 'legal')
            ->set('company_name', 'PT Gotik Indonesia')
            ->set('legal_id', 'NIB-001')
            ->set('address', 'Jl. Sudirman No. 1, Jakarta')
            ->set('representative_name', 'Sawala')
            ->set('representative_position', 'Direktur')
            ->set('email', 'legal@gotik.test')
            ->set('phone', '08123456789')
            ->set('website', 'https://gotik.test')
            ->call('updateLegalProfile')
            ->assertHasNoErrors()
            ->assertSee('Identitas legal berhasil diperbarui.');

        $this->assertDatabaseHas('platform_legal_profiles', [
            'profile_key' => PlatformLegalProfile::DEFAULT_KEY,
            'company_name' => 'PT Gotik Indonesia',
            'legal_id' => 'NIB-001',
            'address' => 'Jl. Sudirman No. 1, Jakarta',
            'representative_name' => 'Sawala',
            'representative_position' => 'Direktur',
            'email' => 'legal@gotik.test',
            'phone' => '08123456789',
            'website' => 'https://gotik.test',
        ]);
    }

    public function test_second_save_updates_same_row_without_creating_duplicate_profile(): void
    {
        $admin = $this->user(['role' => User::ADMIN_ROLE, 'email' => 'admin-update@example.test']);

        Livewire::actingAs($admin)
            ->test(SettingIndex::class)
            ->set('company_name', 'PT Gotik Indonesia')
            ->set('email', 'first@gotik.test')
            ->call('updateLegalProfile')
            ->assertHasNoErrors();

        $profile = PlatformLegalProfile::query()->sole();
        $profileId = $profile->id;

        Livewire::actingAs($admin)
            ->test(SettingIndex::class)
            ->set('company_name', 'PT Gotik Baru')
            ->set('email', 'second@gotik.test')
            ->call('updateLegalProfile')
            ->assertHasNoErrors()
            ->assertSee('Identitas legal berhasil diperbarui.');

        $this->assertDatabaseCount('platform_legal_profiles', 1);
        $this->assertDatabaseHas('platform_legal_profiles', [
            'id' => $profileId,
            'profile_key' => PlatformLegalProfile::DEFAULT_KEY,
            'company_name' => 'PT Gotik Baru',
            'email' => 'second@gotik.test',
        ]);
    }

    public function test_tampered_livewire_state_cannot_create_second_profile(): void
    {
        $admin = $this->user(['role' => User::ADMIN_ROLE, 'email' => 'admin-tamper@example.test']);

        Livewire::actingAs($admin)
            ->test(SettingIndex::class)
            ->set('company_name', 'PT Gotik Indonesia')
            ->set('email', 'first@gotik.test')
            ->call('updateLegalProfile')
            ->assertHasNoErrors();

        $profileId = PlatformLegalProfile::query()->sole()->id;

        Livewire::actingAs($admin)
            ->test(SettingIndex::class)
            ->set('legalProfileId', 999999)
            ->set('company_name', 'PT Gotik Aman')
            ->set('email', 'safe@gotik.test')
            ->call('updateLegalProfile')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('platform_legal_profiles', 1);
        $this->assertDatabaseHas('platform_legal_profiles', [
            'id' => $profileId,
            'profile_key' => PlatformLegalProfile::DEFAULT_KEY,
            'company_name' => 'PT Gotik Aman',
            'email' => 'safe@gotik.test',
        ]);
    }

    public function test_invalid_email_is_rejected(): void
    {
        $admin = $this->user(['role' => User::ADMIN_ROLE, 'email' => 'admin-invalid-email@example.test']);

        Livewire::actingAs($admin)
            ->test(SettingIndex::class)
            ->set('email', 'not-an-email')
            ->call('updateLegalProfile')
            ->assertHasErrors(['email' => 'email']);

        $this->assertDatabaseCount('platform_legal_profiles', 0);
    }

    public function test_invalid_website_is_rejected(): void
    {
        $admin = $this->user(['role' => User::ADMIN_ROLE, 'email' => 'admin-invalid-website@example.test']);

        Livewire::actingAs($admin)
            ->test(SettingIndex::class)
            ->set('website', 'not-a-url')
            ->call('updateLegalProfile')
            ->assertHasErrors(['website' => 'url']);

        $this->assertDatabaseCount('platform_legal_profiles', 0);
    }

    public function test_non_admin_cannot_access_admin_setting_page_or_component(): void
    {
        $user = $this->user(['role' => User::USER_ROLE, 'email' => 'user-settings@example.test']);

        $this->actingAs($user)
            ->get('/admin/setting')
            ->assertRedirect('/')
            ->assertSessionHas('error', 'Halaman tidak tersedia.');

        Livewire::actingAs($user)
            ->test(SettingIndex::class)
            ->assertForbidden();
    }

    private function user(array $overrides = []): User
    {
        return User::query()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Test User',
            'email' => 'user-'.Str::uuid().'@example.test',
            'role' => User::USER_ROLE,
            'password' => Hash::make('Secret123'),
        ], $overrides));
    }
}
