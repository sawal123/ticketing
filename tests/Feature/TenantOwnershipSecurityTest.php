<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventDate;
use App\Models\Harga;
use App\Models\Partner;
use App\Models\Talent;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantOwnershipSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('logo', [(object) ['logo' => '']]);
    }

    public function test_tenant_cannot_delete_another_tenants_event(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $eventB = $this->event($tenantB);

        $this->actingAs($tenantA)
            ->delete(route('dashboard.old.events.destroy', $eventB->uid))
            ->assertNotFound();

        $this->assertDatabaseHas('events', ['uid' => $eventB->uid]);
    }

    public function test_tenant_cannot_delete_another_tenants_talent(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $talentB = $this->talent($this->event($tenantB));

        $this->actingAs($tenantA)
            ->delete(route('dashboard.old.talents.destroy', $talentB->id))
            ->assertNotFound();

        $this->assertDatabaseHas('talent', ['id' => $talentB->id]);
    }

    public function test_tenant_cannot_delete_another_tenants_harga(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $hargaB = $this->harga($this->event($tenantB));

        $this->actingAs($tenantA)
            ->delete(route('dashboard.old.hargas.destroy', $hargaB->id))
            ->assertNotFound();

        $this->assertDatabaseHas('hargas', ['id' => $hargaB->id]);
    }

    public function test_tenant_cannot_delete_another_tenants_voucher(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $voucherB = $this->voucher($tenantB, $this->event($tenantB));

        $this->actingAs($tenantA)
            ->delete(route('dashboard.old.vouchers.destroy', $voucherB->uid))
            ->assertNotFound();

        $this->assertDatabaseHas('vouchers', ['uid' => $voucherB->uid]);
    }

    public function test_tenant_cannot_delete_another_tenants_partner(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $partnerB = $this->partner($tenantB, $this->event($tenantB));

        $this->actingAs($tenantA)
            ->delete(route('dashboard.old.partners.destroy', $partnerB->uid))
            ->assertNotFound();

        $this->assertDatabaseHas('partners', ['uid' => $partnerB->uid]);
    }

    public function test_tenant_cannot_edit_another_tenants_event(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $eventB = $this->event($tenantB, ['event' => 'Original Event']);
        $this->eventDate($eventB);

        $this->actingAs($tenantA)
            ->post('/dashboard/old/editEventPenyewa', $this->eventPayload($eventB, ['event' => 'Hijacked Event']))
            ->assertNotFound();

        $this->assertDatabaseHas('events', [
            'uid' => $eventB->uid,
            'event' => 'Original Event',
        ]);
    }

    public function test_tenant_cannot_edit_another_tenants_talent(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $talentB = $this->talent($this->event($tenantB), ['talent' => 'Original Talent']);

        $this->actingAs($tenantA)
            ->post('/dashboard/old/editTalent', [
                'uid' => $talentB->id,
                'talent' => 'Hijacked Talent',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('talent', [
            'id' => $talentB->id,
            'talent' => 'Original Talent',
        ]);
    }

    public function test_tenant_cannot_edit_another_tenants_harga(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $hargaB = $this->harga($this->event($tenantB), ['kategori' => 'VIP']);

        $this->actingAs($tenantA)
            ->post('/dashboard/old/editHarga', [
                'id' => $hargaB->id,
                'kategori' => 'Hijacked',
                'qty' => 9,
                'harga' => 999,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('hargas', [
            'id' => $hargaB->id,
            'kategori' => 'VIP',
        ]);
    }

    public function test_tenant_cannot_edit_another_tenants_voucher(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $eventB = $this->event($tenantB);
        $voucherB = $this->voucher($tenantB, $eventB, ['code' => 'ORIGINAL']);

        $this->actingAs($tenantA)
            ->post('/dashboard/old/updateVoucher', $this->voucherPayload($voucherB, $eventB, ['code' => 'HIJACKED']))
            ->assertNotFound();

        $this->assertDatabaseHas('vouchers', [
            'id' => $voucherB->id,
            'code' => 'ORIGINAL',
        ]);
    }

    public function test_tenant_cannot_move_voucher_to_another_tenants_event(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $eventA = $this->event($tenantA);
        $eventB = $this->event($tenantB);
        $voucherA = $this->voucher($tenantA, $eventA);

        $this->actingAs($tenantA)
            ->post('/dashboard/old/updateVoucher', $this->voucherPayload($voucherA, $eventB))
            ->assertNotFound();

        $this->assertDatabaseHas('vouchers', [
            'id' => $voucherA->id,
            'event_uid' => $eventA->uid,
        ]);
    }

    public function test_legacy_get_delete_urls_do_not_delete(): void
    {
        [$tenantA] = $this->tenants();
        $eventA = $this->event($tenantA);
        $talentA = $this->talent($eventA);
        $hargaA = $this->harga($eventA);
        $voucherA = $this->voucher($tenantA, $eventA);
        $partnerA = $this->partner($tenantA, $eventA);

        $urls = [
            '/dashboard/old/events/delete/'.$eventA->uid,
            '/dashboard/old/delete/'.$talentA->id,
            '/dashboard/old/hargas/delete/'.$hargaA->id,
            '/dashboard/old/delete/voucher/'.$voucherA->uid,
            '/dashboard/old/delete/partner/'.$partnerA->uid,
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($tenantA)->get($url);

            $this->assertContains($response->getStatusCode(), [404, 405]);
        }

        $this->assertDatabaseHas('events', ['uid' => $eventA->uid]);
        $this->assertDatabaseHas('talent', ['id' => $talentA->id]);
        $this->assertDatabaseHas('hargas', ['id' => $hargaA->id]);
        $this->assertDatabaseHas('vouchers', ['uid' => $voucherA->uid]);
        $this->assertDatabaseHas('partners', ['uid' => $partnerA->uid]);
    }

    public function test_owner_can_update_and_delete_own_data(): void
    {
        [$tenantA] = $this->tenants();
        $eventA = $this->event($tenantA, ['event' => 'Owned Event']);
        $this->eventDate($eventA);
        $hargaA = $this->harga($eventA, ['kategori' => 'Regular']);

        $this->actingAs($tenantA)
            ->post('/dashboard/old/editHarga', [
                'id' => $hargaA->id,
                'kategori' => 'VIP',
                'qty' => 12,
                'harga' => 120000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('hargas', [
            'id' => $hargaA->id,
            'kategori' => 'VIP',
            'qty' => 12,
        ]);

        $this->actingAs($tenantA)
            ->delete(route('dashboard.old.events.destroy', $eventA->uid))
            ->assertRedirect();

        $this->assertDatabaseMissing('events', ['uid' => $eventA->uid]);
    }

    private function tenants(): array
    {
        return [
            $this->user(['email' => 'tenant-a@example.test']),
            $this->user(['email' => 'tenant-b@example.test']),
        ];
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Tenant User',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat',
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
            'event' => 'Event '.$uid,
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->format('Y-m-d H:i'),
            'status' => 'inactive',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => 'https://example.test/map',
            'pajak' => 0,
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'event-'.$uid,
            'konfirmasi' => null,
        ], $overrides));
    }

    private function eventDate(Event $event): EventDate
    {
        return EventDate::create([
            'uid' => $event->uid,
            'start' => now()->format('Y-m-d H:i'),
            'end' => now()->addDay()->format('Y-m-d H:i'),
        ]);
    }

    private function talent(Event $event, array $overrides = []): Talent
    {
        return Talent::create(array_merge([
            'uid' => $event->uid,
            'talent' => 'Talent',
            'gambar' => 'talent.jpg',
        ], $overrides));
    }

    private function harga(Event $event, array $overrides = []): Harga
    {
        return Harga::create(array_merge([
            'uid' => $event->uid,
            'kategori' => 'Regular',
            'qty' => 10,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => 100000,
            'status' => 'active',
        ], $overrides));
    }

    private function voucher(User $tenant, Event $event, array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'code' => 'DISC'.Str::upper(Str::random(6)),
            'unit' => 'rupiah',
            'nominal' => 10000,
            'min_beli' => 0,
            'max_disc' => 10000,
            'digunakan' => 0,
            'limit' => 10,
            'status' => 'active',
        ], $overrides));
    }

    private function partner(User $tenant, Event $event, array $overrides = []): Partner
    {
        return Partner::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'referensi' => 'PARTNER'.Str::upper(Str::random(6)),
            'name' => 'Partner',
            'email' => fake()->unique()->safeEmail(),
            'hp' => '08123456789',
            'alamat' => 'Alamat',
            'city' => 'Jakarta',
            'status' => 'active',
        ], $overrides));
    }

    private function eventPayload(Event $event, array $overrides = []): array
    {
        return array_merge([
            'uid' => $event->uid,
            'event' => 'Updated Event',
            'alamat' => 'Bandung',
            'tanggal' => now()->addDays(2)->format('Y-m-d H:i'),
            'fee' => 5,
            'start' => now()->format('Y-m-d H:i'),
            'end' => now()->addDays(2)->format('Y-m-d H:i'),
            'status' => 'active',
            'deskripsi' => 'Updated description',
            'map' => 'https://example.test/updated-map',
        ], $overrides);
    }

    private function voucherPayload(Voucher $voucher, Event $event, array $overrides = []): array
    {
        return array_merge([
            'id' => $voucher->id,
            'event' => $event->uid,
            'code' => 'UPDATED',
            'unit' => 'rupiah',
            'nominalRupiah' => 20000,
            'nominalPersen' => null,
            'min' => 0,
            'max' => 20000,
            'maxUse' => 5,
        ], $overrides);
    }
}
