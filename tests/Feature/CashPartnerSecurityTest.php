<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Jobs\sendEmailTrnsaksi;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashPartnerSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');
        Config::set('gate-tokens.key', 'base64:'.base64_encode(str_repeat('p', 32)));
        Config::set('gate-tokens.active_event_uids', []);

        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_cash_dropdown_only_shows_logged_in_tenant_partners(): void
    {
        $tenantA = $this->user(['email' => 'tenant-a@example.test']);
        $tenantB = $this->user(['email' => 'tenant-b@example.test']);
        $this->event($tenantA);
        $partnerA = $this->partner($tenantA, ['name' => 'Partner A']);
        $partnerB = $this->partner($tenantB, ['name' => 'Partner B']);
        View::share('user', $tenantA);

        $this->actingAs($tenantA)
            ->get(route('dashboard.old'))
            ->assertOk()
            ->assertSee($partnerA->uid)
            ->assertSee('Partner A')
            ->assertDontSee($partnerB->uid)
            ->assertDontSee('Partner B');
    }

    public function test_create_cash_succeeds_without_partner(): void
    {
        Queue::fake();
        [$tenant, $event, $harga] = $this->ownedTicket();

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($event, $harga, ['partner' => null]))
            ->assertRedirect('/dashboard/old')
            ->assertSessionHas('success');

        $cart = Cart::firstOrFail();

        $this->assertDatabaseHas('cashes', [
            'uid' => $cart->uid,
            'uid_user' => $tenant->uid,
            'uid_event' => $event->uid,
            'uid_partner' => null,
        ]);
        Queue::assertPushed(sendEmailTrnsaksi::class);
    }

    public function test_create_cash_succeeds_with_owned_partner_uid(): void
    {
        Queue::fake();
        [$tenant, $event, $harga] = $this->ownedTicket();
        $partner = $this->partner($tenant);

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($event, $harga, [
                'partner' => $partner->uid,
            ]))
            ->assertRedirect('/dashboard/old')
            ->assertSessionHas('success');

        $cart = Cart::firstOrFail();

        $this->assertDatabaseHas('cashes', [
            'uid' => $cart->uid,
            'uid_user' => $tenant->uid,
            'uid_event' => $event->uid,
            'uid_partner' => $partner->uid,
        ]);
        Queue::assertPushed(sendEmailTrnsaksi::class);
    }

    public function test_create_cash_rejects_partner_owned_by_another_tenant_without_side_effects(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $eventA = $this->event($tenantA);
        $hargaA = $this->harga($eventA);
        $partnerB = $this->partner($tenantB);

        $this->actingAs($tenantA)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($eventA, $hargaA, [
                'partner' => $partnerB->uid,
            ]))
            ->assertRedirect('/dashboard/old')
            ->assertSessionHasErrors('partner');

        $this->assertRejectedWithoutSideEffects($hargaA);
    }

    public function test_request_cannot_spoof_uid_partner_owner_or_partner_identity_fields(): void
    {
        Queue::fake();
        [$tenantA, $tenantB] = $this->tenants();
        $eventA = $this->event($tenantA);
        $hargaA = $this->harga($eventA);
        $partnerA = $this->partner($tenantA, [
            'name' => 'Owned Partner',
            'referensi' => 'OWNED-REF',
        ]);
        $partnerB = $this->partner($tenantB, [
            'name' => 'Other Partner',
            'referensi' => 'OTHER-REF',
        ]);

        $this->actingAs($tenantA)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($eventA, $hargaA, [
                'partner' => $partnerA->uid,
                'uid_partner' => $partnerB->uid,
                'partner_user_uid' => $tenantB->uid,
                'owner' => $tenantB->uid,
                'partner_name' => 'Other Partner',
                'referensi' => 'OTHER-REF',
            ]))
            ->assertRedirect('/dashboard/old')
            ->assertSessionHas('success');

        $cart = Cart::firstOrFail();
        $cash = Cash::where('uid', $cart->uid)->firstOrFail();

        $this->assertSame($partnerA->uid, $cash->uid_partner);
        $this->assertNotSame($partnerB->uid, $cash->uid_partner);
        $this->assertSame($tenantA->uid, $cash->uid_user);
        $this->assertSame($eventA->uid, $cash->uid_event);
    }

    public function test_inactive_owned_partner_is_rejected(): void
    {
        [$tenant, $event, $harga] = $this->ownedTicket();
        $partner = $this->partner($tenant, ['status' => 'inactive']);

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($event, $harga, [
                'partner' => $partner->uid,
            ]))
            ->assertRedirect('/dashboard/old')
            ->assertSessionHasErrors('partner');

        $this->assertRejectedWithoutSideEffects($harga);
    }

    private function tenants(): array
    {
        return [
            $this->user(['email' => 'tenant-a@example.test']),
            $this->user(['email' => 'tenant-b@example.test']),
        ];
    }

    private function ownedTicket(): array
    {
        $tenant = $this->user();
        $event = $this->event($tenant);
        $harga = $this->harga($event);

        return [$tenant, $event, $harga];
    }

    private function payload(Event $event, Harga $harga, array $overrides = []): array
    {
        return array_merge([
            'event_uid' => $event->uid,
            'harga_id' => $harga->id,
            'qty' => 1,
            'name' => 'Cash Buyer',
            'email' => 'buyer@example.test',
            'alamat' => 'Jakarta',
            'ttl' => '2000-01-01',
            'gender' => 'pria',
            'nomor' => '08123456789',
            'partner' => null,
        ], $overrides);
    }

    private function assertRejectedWithoutSideEffects(Harga $harga, int $expectedSoldQty = 0): void
    {
        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('harga_carts', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('cashes', 0);
        $this->assertSame($expectedSoldQty, (int) $harga->fresh()->sold_qty);
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
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => 'https://example.test/map',
            'pajak' => 0,
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'event-'.$uid,
            'konfirmasi' => '1',
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

    private function partner(User $tenant, array $overrides = []): Partner
    {
        return Partner::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => null,
            'referensi' => 'PARTNER-'.Str::upper(Str::random(6)),
            'name' => 'Partner '.$tenant->uid,
            'email' => fake()->unique()->safeEmail(),
            'hp' => '08123456789',
            'city' => 'Jakarta',
            'alamat' => 'Alamat partner',
            'status' => 'active',
        ], $overrides));
    }
}
