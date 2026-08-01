<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Jobs\sendEmailTrnsaksi;
use App\Livewire\Dashboard\DemoIndex;
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
use Livewire\Livewire;
use Tests\TestCase;

class DemoIndexCashPartnerSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');
        Config::set('gate-tokens.key', 'base64:'.base64_encode(str_repeat('d', 32)));
        Config::set('gate-tokens.active_event_uids', []);

        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_demoindex_partner_dropdown_only_shows_active_partners_owned_by_logged_in_tenant(): void
    {
        [$tenantA] = $this->tenants();
        $tenantB = $this->user(['email' => 'tenant-b-dropdown@example.test']);
        $this->event($tenantA);
        $partnerA = $this->partner($tenantA, ['name' => 'Partner A']);
        $partnerB = $this->partner($tenantB, ['name' => 'Partner B']);
        $inactivePartner = $this->partner($tenantA, [
            'name' => 'Partner Inactive',
            'status' => 'inactive',
        ]);

        Livewire::actingAs($tenantA)
            ->test(DemoIndex::class)
            ->call('selectEvent', Event::where('user_uid', $tenantA->uid)->firstOrFail()->uid)
            ->assertSee($partnerA->uid)
            ->assertSee('Partner A')
            ->assertDontSee($partnerB->uid)
            ->assertDontSee('Partner B')
            ->assertDontSee($inactivePartner->uid)
            ->assertDontSee('Partner Inactive');
    }

    public function test_demoindex_create_cash_succeeds_without_partner(): void
    {
        Queue::fake();
        [$tenant, $event, $harga] = $this->ownedTicket();

        $this->cashCheckout($tenant, $event, $harga)
            ->assertHasNoErrors();

        $cart = Cart::firstOrFail();

        $this->assertDatabaseHas('cashes', [
            'uid' => $cart->uid,
            'uid_user' => $tenant->uid,
            'uid_event' => $event->uid,
            'uid_partner' => null,
        ]);
        Queue::assertPushed(sendEmailTrnsaksi::class);
    }

    public function test_demoindex_create_cash_succeeds_with_owned_active_partner_uid(): void
    {
        Queue::fake();
        [$tenant, $event, $harga] = $this->ownedTicket();
        $partner = $this->partner($tenant);

        $this->cashCheckout($tenant, $event, $harga, [
            'partnerId' => $partner->uid,
        ])->assertHasNoErrors();

        $cart = Cart::firstOrFail();

        $this->assertDatabaseHas('cashes', [
            'uid' => $cart->uid,
            'uid_user' => $tenant->uid,
            'uid_event' => $event->uid,
            'uid_partner' => $partner->uid,
        ]);
        Queue::assertPushed(sendEmailTrnsaksi::class);
    }

    public function test_demoindex_rejects_partner_owned_by_another_tenant_without_side_effects(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $eventA = $this->event($tenantA);
        $hargaA = $this->harga($eventA);
        $partnerB = $this->partner($tenantB);

        $this->cashCheckout($tenantA, $eventA, $hargaA, [
            'partnerId' => $partnerB->uid,
        ])->assertHasErrors('partnerId');

        $this->assertRejectedWithoutSideEffects($hargaA);
    }

    public function test_demoindex_rejects_inactive_owned_partner_without_side_effects(): void
    {
        [$tenant, $event, $harga] = $this->ownedTicket();
        $partner = $this->partner($tenant, ['status' => 'inactive']);

        $this->cashCheckout($tenant, $event, $harga, [
            'partnerId' => $partner->uid,
        ])->assertHasErrors('partnerId');

        $this->assertRejectedWithoutSideEffects($harga);
    }

    public function test_demoindex_ignores_spoof_fields_and_uses_only_official_partner_id(): void
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

        Livewire::actingAs($tenantA)
            ->test(DemoIndex::class)
            ->call('selectEvent', $eventA->uid)
            ->call('addTicket', $hargaA->id)
            ->set('buyerName', 'Cash Buyer')
            ->set('buyerEmail', 'buyer@example.test')
            ->set('buyerBirthday', '2000-01-01')
            ->set('buyerGender', 'pria')
            ->set('partnerId', $partnerA->uid)
            ->set('cashTransactionResult', [
                'uid_partner' => $partnerB->uid,
                'partner_user_uid' => $tenantB->uid,
                'owner' => $tenantB->uid,
                'partner_name' => 'Other Partner',
                'referensi' => 'OTHER-REF',
            ])
            ->call('checkout')
            ->assertHasNoErrors();

        $cash = Cash::firstOrFail();

        $this->assertSame($partnerA->uid, $cash->uid_partner);
        $this->assertNotSame($partnerB->uid, $cash->uid_partner);
        $this->assertSame($tenantA->uid, $cash->uid_user);
        $this->assertSame($eventA->uid, $cash->uid_event);
    }

    public function test_demoindex_rejects_fake_partner_uid_without_side_effects(): void
    {
        [$tenant, $event, $harga] = $this->ownedTicket();

        $this->cashCheckout($tenant, $event, $harga, [
            'partnerId' => 'fake-partner-uid',
        ])->assertHasErrors('partnerId');

        $this->assertRejectedWithoutSideEffects($harga);
    }

    private function cashCheckout(User $tenant, Event $event, Harga $harga, array $overrides = [])
    {
        $component = Livewire::actingAs($tenant)
            ->test(DemoIndex::class)
            ->call('selectEvent', $event->uid)
            ->call('addTicket', $harga->id)
            ->set('buyerName', $overrides['buyerName'] ?? 'Cash Buyer')
            ->set('buyerEmail', $overrides['buyerEmail'] ?? 'buyer@example.test')
            ->set('buyerBirthday', $overrides['buyerBirthday'] ?? '2000-01-01')
            ->set('buyerGender', $overrides['buyerGender'] ?? 'pria');

        if (array_key_exists('partnerId', $overrides)) {
            $component->set('partnerId', $overrides['partnerId']);
        }

        return $component->call('checkout');
    }

    private function tenants(): array
    {
        return [
            $this->user(['email' => 'tenant-a-demo@example.test']),
            $this->user(['email' => 'tenant-b-demo@example.test']),
        ];
    }

    private function ownedTicket(): array
    {
        $tenant = $this->user();
        $event = $this->event($tenant);
        $harga = $this->harga($event);

        return [$tenant, $event, $harga];
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
            'event' => 'Demo Event '.$uid,
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->format('Y-m-d H:i'),
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => 'https://example.test/map',
            'pajak' => 0,
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'demo-event-'.$uid,
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
