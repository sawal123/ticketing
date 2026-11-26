<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class QrScanRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        $this->artisan('migrate:fresh', ['--database' => 'sqlite']);
    }

    public function test_scan_qr_records_scan_and_redirects_to_public_ticket_page(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'event' => 'Konser QR Redirect',
            'konfirmasi' => '1',
        ]);

        $this->get('/qr/'.$event->uid)
            ->assertRedirect(url('/ticket/'.$event->slug));

        $this->assertDatabaseHas('qr_scans', [
            'event_uid' => $event->uid,
        ]);
    }

    public function test_repeated_scans_are_each_recorded(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['konfirmasi' => '1']);

        $this->get('/qr/'.$event->uid);
        $this->get('/qr/'.$event->uid);

        $this->assertDatabaseCount('qr_scans', 2);
    }

    public function test_unconfirmed_event_returns_404_and_records_nothing(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['konfirmasi' => null]);

        $this->get('/qr/'.$event->uid)->assertNotFound();

        $this->assertDatabaseCount('qr_scans', 0);
    }

    public function test_unknown_uid_returns_404(): void
    {
        $this->get('/qr/'.(string) Str::uuid())->assertNotFound();

        $this->assertDatabaseCount('qr_scans', 0);
    }

    private function tenant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Tenant Qr Scan',
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
