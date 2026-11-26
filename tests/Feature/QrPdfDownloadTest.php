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

class QrPdfDownloadTest extends TestCase
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

    public function test_owner_can_download_qr_as_pdf(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['konfirmasi' => '1']);

        $response = $this->actingAs($tenant)
            ->postJson(route('qr.scan.download.pdf', ['uid' => $event->uid]), [
                'image' => $this->pngDataUrl(),
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename=qr-'.$event->slug.'.pdf');
    }

    public function test_staff_of_owner_can_download_qr_as_pdf(): void
    {
        $owner = $this->tenant();
        $staff = $this->user([
            'role' => 'staff',
            'parent_uid' => $owner->uid,
        ]);
        $event = $this->event($owner, ['konfirmasi' => '1']);

        $response = $this->actingAs($staff)
            ->postJson(route('qr.scan.download.pdf', ['uid' => $event->uid]), [
                'image' => $this->pngDataUrl(),
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_cannot_download_pdf_for_other_tenant_event(): void
    {
        $tenant = $this->tenant();
        $otherTenant = $this->tenant();
        $otherEvent = $this->event($otherTenant, ['konfirmasi' => '1']);

        $this->actingAs($tenant)
            ->postJson(route('qr.scan.download.pdf', ['uid' => $otherEvent->uid]), [
                'image' => $this->pngDataUrl(),
            ])
            ->assertNotFound();
    }

    public function test_cannot_download_pdf_for_unconfirmed_event(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['konfirmasi' => null]);

        $this->actingAs($tenant)
            ->postJson(route('qr.scan.download.pdf', ['uid' => $event->uid]), [
                'image' => $this->pngDataUrl(),
            ])
            ->assertNotFound();
    }

    public function test_rejects_image_that_is_not_a_png(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['konfirmasi' => '1']);

        $this->actingAs($tenant)
            ->postJson(route('qr.scan.download.pdf', ['uid' => $event->uid]), [
                'image' => 'data:image/png;base64,'.base64_encode('not a png at all'),
            ])
            ->assertStatus(422);
    }

    public function test_rejects_image_with_invalid_base64(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['konfirmasi' => '1']);

        $this->actingAs($tenant)
            ->postJson(route('qr.scan.download.pdf', ['uid' => $event->uid]), [
                'image' => 'data:image/png;base64,@@@@invalid',
            ])
            ->assertStatus(422);
    }

    public function test_guest_is_rejected(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['konfirmasi' => '1']);

        $this->postJson(route('qr.scan.download.pdf', ['uid' => $event->uid]), [
            'image' => $this->pngDataUrl(),
        ])
            ->assertUnauthorized();
    }

    private function pngDataUrl(): string
    {
        $image = imagecreatetruecolor(20, 20);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    private function tenant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Tenant Qr Pdf',
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
            'name' => 'Generic Qr Pdf User',
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
            'event' => 'Festival Qr Pdf '.$uid,
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
            'deskripsi' => 'Deskripsi event qr pdf',
            'map' => 'https://maps.google.com/?q=istora',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'festival-qr-pdf-'.$uid,
            'konfirmasi' => null,
        ], $overrides));
    }
}
