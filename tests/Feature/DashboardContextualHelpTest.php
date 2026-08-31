<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Models\Agreement;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardContextualHelpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_contextual_help_component_renders_in_dashboard_header(): void
    {
        $tenant = $this->tenant();

        $this->actingAs($tenant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Panduan Dashboard', false)
            ->assertSee('Melihat ringkasan event, transaksi, tiket terjual, dan total omset.', false)
            ->assertSee('Panduan', false);
    }

    public function test_contextual_help_renders_on_event_pages_and_matches_active_tab(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        Agreement::createDraftForEvent($event, $tenant->uid);

        $this->actingAs($tenant)
            ->get(route('dashboard.event'))
            ->assertOk()
            ->assertSee('Panduan Manajemen Event', false)
            ->assertSee('Mengaktifkan atau menutup event yang sudah disetujui.', false);

        $this->actingAs($tenant)
            ->get(route('dashboard.event.create'))
            ->assertOk()
            ->assertSee('Panduan Pengaturan Event', false)
            ->assertSee('Menyiapkan rekening pencairan serta dokumen pendukung yang diminta sistem.', false);

        Livewire::actingAs($tenant)
            ->test(\App\Livewire\Dashboard\EventDetail::class, ['uid' => $event->uid])
            ->assertSee('Panduan Detail Event')
            ->assertSee('Meninjau informasi utama event, talent, jadwal, lokasi, dan status event.')
            ->set('activeTab', 'tiket')
            ->assertSee('Panduan Manajemen Tiket')
            ->assertSee('Menentukan harga dan kuota setiap kategori tiket.')
            ->set('activeTab', 'mou')
            ->assertSee('Panduan MOU')
            ->assertSee('Mengunduh file unsigned atau signed jika tombolnya tersedia.')
            ->set('activeTab', 'transaksi')
            ->assertSee('Panduan Transaksi Event')
            ->assertSee('Membuka detail transaksi untuk melihat status pembayaran dan rincian tiket.');
    }

    public function test_contextual_help_renders_on_penarikan_and_not_on_admin_event_detail(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        Agreement::createDraftForEvent($event, $tenant->uid);

        $this->actingAs($tenant)
            ->get(route('dashboard.penarikan'))
            ->assertOk()
            ->assertSee('Panduan Penarikan', false)
            ->assertSee('Mengubah atau membatalkan penarikan yang masih berstatus pending.', false);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.event.detail', $event->uid))
            ->assertOk()
            ->assertDontSee('Panduan Dashboard', false)
            ->assertDontSee('Panduan Manajemen Event', false)
            ->assertDontSee('Panduan Detail Event', false);
    }

    private function tenant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Tenant Help',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Jl. Sudirman No. 1',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function admin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Admin Help',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'admin',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Jl. Sudirman No. 2',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function event(User $tenant, array $overrides = []): Event
    {
        $uid = (string) Str::uuid();

        return Event::create(array_merge([
            'uid' => $uid,
            'user_uid' => $tenant->uid,
            'event' => 'Event Help '.$uid,
            'alamat' => 'Istora Senayan, Jl. Pintu Satu Senayan, Jakarta Pusat, DKI Jakarta',
            'tanggal' => '2026-09-10 19:00:00',
            'event_end' => '2026-09-10 22:00:00',
            'venue_name' => 'Istora Senayan',
            'venue_address' => 'Jl. Pintu Satu Senayan',
            'venue_city' => 'Jakarta Pusat',
            'venue_province' => 'DKI Jakarta',
            'status' => 'inactive',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'pajak' => 0,
            'deskripsi' => 'Deskripsi event contextual help',
            'map' => 'https://maps.google.com/?q=istora',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'event-help-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
        ], $overrides));
    }
}
