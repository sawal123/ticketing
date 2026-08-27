<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventIndex;
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

class DashboardEventDetailAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('logo', [(object) ['logo' => '']]);
        Storage::fake('local');
        Storage::fake('public');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
    }

    public function test_tenant_sees_detail_event_button_for_pending_event(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'event' => 'Pending Event Access',
            'konfirmasi' => null,
            'status' => 'inactive',
        ]);

        Agreement::createDraftForEvent($event, $tenant->uid);

        $response = $this->actingAs($tenant)->get(route('dashboard.event'));

        $response->assertOk();
        $response->assertSee(route('dashboard.event.detail', $event->uid), false);
        $response->assertSee(route('dashboard.event.edit', $event->uid), false);
        $response->assertSee('Yakin ingin menghapus event yang masih menunggu persetujuan ini?', false);
        $response->assertDontSee('Trx Online', false);
        $response->assertDontSee('Trx Cash', false);
        $response->assertDontSee("toggleStatus('{$event->uid}')", false);
    }

    public function test_tenant_can_open_detail_route_for_own_pending_event(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'event' => 'Pending Detail Route',
            'konfirmasi' => null,
            'status' => 'inactive',
        ]);

        Agreement::createDraftForEvent($event, $tenant->uid);

        $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid))
            ->assertOk()
            ->assertSee($event->event, false);
    }

    public function test_mou_tab_is_accessible_for_pending_event(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'event' => 'Pending MOU Access',
            'konfirmasi' => null,
            'status' => 'inactive',
        ]);

        Agreement::createDraftForEvent($event, $tenant->uid);

        $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid).'?activeTab=mou')
            ->assertOk()
            ->assertSee('Riwayat Agreement & Addendum', false)
            ->assertSee('Preview MOU pada tahap ini bersifat read-only', false);
    }

    public function test_other_tenant_cannot_open_pending_event_detail(): void
    {
        $owner = $this->tenant(['email' => 'owner-pending-detail@example.test']);
        $otherTenant = $this->tenant(['email' => 'other-pending-detail@example.test']);
        $event = $this->event($owner, [
            'event' => 'Pending Private Event',
            'konfirmasi' => null,
            'status' => 'inactive',
        ]);

        Agreement::createDraftForEvent($event, $owner->uid);

        $this->actingAs($otherTenant)
            ->get(route('dashboard.event.detail', $event->uid))
            ->assertNotFound();
    }

    public function test_pending_event_cannot_be_activated_by_tenant(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'event' => 'Pending Activation Guard',
            'konfirmasi' => null,
            'status' => 'inactive',
        ]);

        Agreement::createDraftForEvent($event, $tenant->uid);

        Livewire::actingAs($tenant)
            ->test(EventIndex::class)
            ->call('toggleStatus', $event->uid)
            ->assertForbidden();
    }

    public function test_confirmed_event_keeps_existing_detail_and_transaction_actions(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'event' => 'Confirmed Event Actions',
            'konfirmasi' => 'approved',
            'status' => 'active',
        ]);

        Agreement::createDraftForEvent($event, $tenant->uid);

        $response = $this->actingAs($tenant)->get(route('dashboard.event'));

        $response->assertOk();
        $response->assertSee(route('dashboard.event.detail', $event->uid), false);
        $response->assertSee(route('dashboard.event.edit', $event->uid), false);
        $response->assertSee(route('dashboard.event.detail', $event->uid).'?activeTab=transaksi', false);
        $response->assertSee(route('dashboard.event.detail', $event->uid).'?activeTab=transaksi&filterPayment=cash', false);
        $response->assertSee('Trx Online', false);
        $response->assertSee('Trx Cash', false);
        $response->assertSee("toggleStatus('{$event->uid}')", false);
    }

    private function tenant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Tenant Event Detail',
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
            'event' => 'Event Detail '.$uid,
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
            'deskripsi' => 'Deskripsi event detail',
            'map' => 'https://maps.google.com/?q=istora',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'event-detail-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
        ], $overrides));
    }
}
