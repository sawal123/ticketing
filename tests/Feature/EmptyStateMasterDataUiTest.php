<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Admin\SettingIndex;
use App\Models\Category;
use App\Models\Event;
use App\Models\Landing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class EmptyStateMasterDataUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share([
            'user' => null,
            'logo' => [(object) ['logo' => '', 'icon' => '']],
            'seo' => [(object) ['keyword' => 'Test', 'description' => 'Test']],
            'contact' => collect(),
            'contactus' => collect(),
        ]);
    }

    protected function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Admin Test',
            'email' => 'admin-'.Str::random(6).'@example.test',
            'password' => Hash::make('password'),
            'role' => User::ADMIN_ROLE,
        ], $overrides));
    }

    protected function makeEvent(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => (string) Str::uuid(),
            'event' => 'Art Jakarta 2026',
            'slug' => 'art-jakarta-2026',
            'cover' => 'cover.jpg',
            'deskripsi' => 'Deskripsi event test',
            'map' => 'https://maps.google.com',
            'start_sale' => '2026-01-01 10:00:00',
            'status' => 'active',
            'konfirmasi' => '1',
            'fee' => 0,
            'pajak' => 0,
            'tanggal' => '2026-10-10 10:00:00',
            'alamat' => 'JIExpo Kemayoran',
        ], $overrides));
    }

    protected function makeLanding(array $overrides = []): Landing
    {
        return Landing::create(array_merge([
            'description' => 'Gotik Test Description',
            'keyword' => 'gotik, test',
            'logo' => 'logo.webp',
            'icon' => 'icon.webp',
        ], $overrides));
    }

    public function test_admin_can_view_empty_state_setting_tab(): void
    {
        $admin = $this->makeUser();

        $this->actingAs($admin)
            ->get('/admin/setting?tab=empty-state')
            ->assertOk()
            ->assertSee('Tampilan Empty State', false)
            ->assertSee('Pengaturan Tampilan Event Tidak Ditemukan', false);
    }

    public function test_admin_can_update_empty_state_text_settings(): void
    {
        $admin = $this->makeUser();
        $this->makeLanding();

        Livewire::actingAs($admin)
            ->test(SettingIndex::class)
            ->set('activeTab', 'empty-state')
            ->set('nodata_title', 'Belum Ada Konser Seru')
            ->set('nodata_subtitle', 'Pantau terus info event terbaru kami di media sosial!')
            ->call('updateEmptyState')
            ->assertHasNoErrors()
            ->assertSee('Pengaturan tampilan empty state berhasil disimpan.');

        $landing = Landing::first();
        $this->assertNotNull($landing);
        $this->assertSame('Belum Ada Konser Seru', $landing->nodata_title);
        $this->assertSame('Pantau terus info event terbaru kami di media sosial!', $landing->nodata_subtitle);
    }

    public function test_empty_state_page_renders_custom_texts_and_master_categories_when_no_events_found(): void
    {
        $this->makeLanding([
            'nodata_title' => 'Event Tidak Ada Nih',
            'nodata_subtitle' => 'Silakan cari dengan nama band lain atau pilih kategori.',
        ]);

        Category::create([
            'name' => 'Festival Musik',
            'slug' => 'festival-musik',
        ]);

        $response = $this->get('/search/nonexistent-event-keyword-xyz');

        $response->assertOk()
            ->assertSee('Event Tidak Ada Nih', false)
            ->assertSee('Silakan cari dengan nama band lain atau pilih kategori.', false)
            ->assertSee('Festival Musik', false)
            ->assertSee('Kembali ke Beranda', false)
            ->assertSee('Jelajahi Berdasarkan Kategori Master', false);
    }

    public function test_search_matches_category_name_and_returns_active_events(): void
    {
        $category = Category::create([
            'name' => 'Pameran Seni',
            'slug' => 'pameran-seni',
        ]);

        $this->makeEvent([
            'category_id' => $category->id,
            'event' => 'Art Jakarta 2026',
            'alamat' => 'JIExpo Kemayoran',
        ]);

        $response = $this->get('/search/Pameran');

        $response->assertOk()
            ->assertSee('Art Jakarta 2026', false)
            ->assertSee('JIExpo Kemayoran', false);
    }

    public function test_landing_empty_state_image_url_helper_falls_back_to_default_svg(): void
    {
        $landing = new Landing([
            'nodata_image' => null,
        ]);

        $this->assertStringContainsString('nodata.svg', $landing->emptyStateImageUrl());

        $landing->nodata_image = 'custom-illustration.webp';
        $this->assertStringContainsString('custom-illustration.webp', $landing->emptyStateImageUrl());
    }
}
