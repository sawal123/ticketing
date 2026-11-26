<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\QrSection;
use App\Models\Event;
use App\Models\QrScan;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardQrSectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        View::share('logo', [(object) ['logo' => '']]);
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        $this->artisan('migrate:fresh', ['--database' => 'sqlite']);
    }

    public function test_dashboard_qr_section_renders_qr_and_scan_stats_for_owned_events(): void
    {
        $tenant = $this->tenant();
        $eventA = $this->event($tenant, ['event' => 'Konser QR Utama', 'konfirmasi' => '1']);
        $eventB = $this->event($tenant, ['event' => 'Festival QR Kedua', 'konfirmasi' => '1']);

        QrScan::create(['event_uid' => $eventA->uid, 'ip_address' => '127.0.0.1']);
        QrScan::create(['event_uid' => $eventA->uid, 'ip_address' => '127.0.0.1']);
        QrScan::create(['event_uid' => $eventB->uid, 'ip_address' => '127.0.0.1']);

        Livewire::actingAs($tenant)
            ->test(QrSection::class)
            ->assertSee('Konser QR Utama')
            ->assertSee('Festival QR Kedua')
            ->assertSeeHtml('<svg')
            ->assertViewHas('totalScans', 3);
    }

    public function test_scan_stats_never_include_other_tenant_events(): void
    {
        $tenant = $this->tenant();
        $otherTenant = $this->tenant();
        $ownEvent = $this->event($tenant, ['event' => 'Event Milik Sendiri', 'konfirmasi' => '1']);
        $otherEvent = $this->event($otherTenant, ['event' => 'Event Penyewa Lain', 'konfirmasi' => '1']);

        QrScan::create(['event_uid' => $ownEvent->uid]);
        QrScan::create(['event_uid' => $ownEvent->uid]);
        QrScan::create(['event_uid' => $otherEvent->uid]);
        QrScan::create(['event_uid' => $otherEvent->uid]);
        QrScan::create(['event_uid' => $otherEvent->uid]);

        Livewire::actingAs($tenant)
            ->test(QrSection::class)
            ->assertSee('Event Milik Sendiri')
            ->assertDontSee('Event Penyewa Lain')
            ->assertViewHas('totalScans', 2);
    }

    public function test_staff_sees_events_of_parent_tenant(): void
    {
        $owner = $this->tenant();
        $staff = $this->user([
            'role' => 'staff',
            'parent_uid' => $owner->uid,
        ]);
        $event = $this->event($owner, ['event' => 'Event Parent Staff', 'konfirmasi' => '1']);

        QrScan::create(['event_uid' => $event->uid]);

        Livewire::actingAs($staff)
            ->test(QrSection::class)
            ->assertSee('Event Parent Staff')
            ->assertViewHas('totalScans', 1);
    }

    public function test_unconfirmed_events_are_not_listed(): void
    {
        $tenant = $this->tenant();
        $this->event($tenant, ['event' => 'Event Belum Dikonfirmasi', 'konfirmasi' => null]);

        Livewire::actingAs($tenant)
            ->test(QrSection::class)
            ->assertDontSee('Event Belum Dikonfirmasi')
            ->assertViewHas('totalScans', 0);
    }

    private function tenant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Tenant Qr Section',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat Tenant',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Generic Qr User',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat User',
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
            'event' => 'Festival Qr '.$uid,
            'alamat' => 'Istora Senayan, Jl. Pintu Satu Senayan, Jakarta Pusat, DKI Jakarta',
            'tanggal' => '2026-09-10 19:00:00',
            'event_end' => '2026-09-10 22:00:00',
            'venue_name' => 'Istora Senayan',
            'venue_address' => 'Jl. Pintu Satu Senayan',
            'venue_city' => 'Jakarta Pusat',
            'venue_province' => 'DKI Jakarta',
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'pajak' => 0,
            'deskripsi' => 'Deskripsi event qr',
            'map' => 'https://maps.google.com/?q=istora',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'festival-qr-'.$uid,
            'konfirmasi' => null,
        ], $overrides));
    }
}
