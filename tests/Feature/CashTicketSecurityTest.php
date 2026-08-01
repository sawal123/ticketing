<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Jobs\sendEmailTrnsaksi;
use App\Models\Cart;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashTicketSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');
        Config::set('gate-tokens.key', 'base64:'.base64_encode(str_repeat('c', 32)));
        Config::set('gate-tokens.active_event_uids', []);

        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
    }

    public function test_tenant_cannot_create_cash_ticket_for_another_tenants_event(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $eventB = $this->event($tenantB);
        $hargaB = $this->harga($eventB);

        $this->actingAs($tenantA)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($eventB, $hargaB))
            ->assertRedirect('/dashboard/old');

        $this->assertRejectedWithoutSideEffects($hargaB);
    }

    public function test_tenant_can_create_cash_ticket_for_owned_confirmed_event(): void
    {
        Queue::fake();

        $tenant = $this->user();
        $event = $this->event($tenant, ['fee' => 10]);
        $harga = $this->harga($event, ['harga' => 100000, 'qty' => 10]);

        Config::set('gate-tokens.active_event_uids', [$event->uid]);

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($event, $harga, ['qty' => 2]))
            ->assertRedirect('/dashboard/old')
            ->assertSessionHas('success');

        $cart = Cart::first();

        $this->assertNotNull($cart);
        $this->assertSame($tenant->uid, $cart->user_uid);
        $this->assertSame($event->uid, $cart->event_uid);
        $this->assertSame(Cart::STATUS_SUCCESS, $cart->status);
        $this->assertSame('cash', $cart->payment_type);
        $this->assertNull($cart->konfirmasi);
        $this->assertNotNull($cart->paid_at);
        $this->assertNotNull($cart->gate_token_hash);
        $this->assertSame(2, (int) $harga->fresh()->sold_qty);
        $this->assertSame(8, $harga->fresh()->remainingQty());
        $this->assertDatabaseHas('harga_carts', [
            'uid' => $cart->uid,
            'harga_id' => $harga->id,
            'event_uid' => $event->uid,
            'quantity' => 2,
            'harga_ticket' => 100000,
            'kategori_harga' => $harga->kategori,
        ]);
        $this->assertDatabaseHas('transactions', [
            'uid' => $cart->uid,
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'invoice' => $cart->invoice,
            'payment_type' => 'cash',
            'status_transaksi' => Cart::STATUS_SUCCESS,
        ]);
        $this->assertSame(220000, (int) Transaction::first()->amount);
        $this->assertSame(220000, (int) Transaction::first()->gross_amount);
        $this->assertDatabaseHas('cashes', [
            'uid' => $cart->uid,
            'uid_user' => $tenant->uid,
            'uid_event' => $event->uid,
            'name' => 'Cash Buyer',
            'email' => 'buyer@example.test',
        ]);
        Queue::assertPushed(sendEmailTrnsaksi::class, function (sendEmailTrnsaksi $job) use ($cart) {
            return $job->recipientEmail === 'buyer@example.test'
                && $job->recipientName === 'Cash Buyer'
                && $job->cartUid === $cart->uid;
        });
    }

    public function test_request_cannot_spoof_owner_user_total_status_confirmation_or_ticket_fields(): void
    {
        $tenantA = $this->user(['email' => 'tenant-a@example.test']);
        $tenantB = $this->user(['email' => 'tenant-b@example.test']);
        $eventA = $this->event($tenantA, ['fee' => 5]);
        $eventB = $this->event($tenantB);
        $hargaA = $this->harga($eventA, ['kategori' => 'REGULAR', 'harga' => 50000]);
        $hargaB = $this->harga($eventB, ['kategori' => 'VIP', 'harga' => 999999]);

        $this->actingAs($tenantA)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($eventA, $hargaA, [
                'uid' => $tenantB->uid,
                'user_uid' => $tenantB->uid,
                'owner' => $tenantB->uid,
                'event' => $eventB->event,
                'ticket' => $hargaB->kategori,
                'harga' => $hargaB->harga,
                'total' => 1,
                'status' => Cart::STATUS_CANCELLED,
                'konfirmasi' => '1',
                'qty' => 2,
            ]))
            ->assertRedirect('/dashboard/old')
            ->assertSessionHas('success');

        $cart = Cart::firstOrFail();

        $this->assertSame($tenantA->uid, $cart->user_uid);
        $this->assertSame($eventA->uid, $cart->event_uid);
        $this->assertSame(Cart::STATUS_SUCCESS, $cart->status);
        $this->assertNull($cart->konfirmasi);
        $this->assertDatabaseHas('harga_carts', [
            'uid' => $cart->uid,
            'harga_id' => $hargaA->id,
            'harga_ticket' => 50000,
            'kategori_harga' => 'REGULAR',
        ]);
        $this->assertSame(105000, (int) Transaction::firstOrFail()->amount);
        $this->assertDatabaseHas('cashes', [
            'uid' => $cart->uid,
            'uid_user' => $tenantA->uid,
            'uid_event' => $eventA->uid,
        ]);
    }

    public function test_qty_must_be_an_integer(): void
    {
        [$tenant, $event, $harga] = $this->ownedTicket();

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($event, $harga, ['qty' => '1.5']))
            ->assertRedirect('/dashboard/old')
            ->assertSessionHasErrors('qty');

        $this->assertRejectedWithoutSideEffects($harga);
    }

    public function test_negative_qty_is_rejected(): void
    {
        [$tenant, $event, $harga] = $this->ownedTicket();

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($event, $harga, ['qty' => -1]))
            ->assertRedirect('/dashboard/old')
            ->assertSessionHasErrors('qty');

        $this->assertRejectedWithoutSideEffects($harga);
    }

    public function test_zero_qty_is_rejected(): void
    {
        [$tenant, $event, $harga] = $this->ownedTicket();

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($event, $harga, ['qty' => 0]))
            ->assertRedirect('/dashboard/old')
            ->assertSessionHasErrors('qty');

        $this->assertRejectedWithoutSideEffects($harga);
    }

    public function test_qty_greater_than_five_is_rejected(): void
    {
        [$tenant, $event, $harga] = $this->ownedTicket();

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($event, $harga, ['qty' => 6]))
            ->assertRedirect('/dashboard/old')
            ->assertSessionHasErrors('qty');

        $this->assertRejectedWithoutSideEffects($harga);
    }

    public function test_qty_cannot_exceed_remaining_stock(): void
    {
        [$tenant, $event, $harga] = $this->ownedTicket(['qty' => 3, 'sold_qty' => 1, 'reserved_qty' => 0]);

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($event, $harga, ['qty' => 3]))
            ->assertRedirect('/dashboard/old')
            ->assertSessionHas('error', 'Stok tiket tidak mencukupi.');

        $this->assertRejectedWithoutSideEffects($harga, 1);
    }

    public function test_sold_qty_increases_when_remaining_stock_is_enough(): void
    {
        [$tenant, $event, $harga] = $this->ownedTicket(['qty' => 5, 'sold_qty' => 1, 'reserved_qty' => 1]);

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($event, $harga, ['qty' => 3]))
            ->assertRedirect('/dashboard/old')
            ->assertSessionHas('success');

        $this->assertSame(4, (int) $harga->fresh()->sold_qty);
        $this->assertSame(0, $harga->fresh()->remainingQty());
    }

    public function test_harga_id_from_another_event_is_rejected(): void
    {
        $tenant = $this->user();
        $eventA = $this->event($tenant);
        $eventB = $this->event($tenant);
        $hargaA = $this->harga($eventA);
        $hargaB = $this->harga($eventB);

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($eventA, $hargaB))
            ->assertRedirect('/dashboard/old');

        $this->assertRejectedWithoutSideEffects($hargaA);
        $this->assertSame(0, (int) $hargaB->fresh()->sold_qty);
    }

    public function test_unconfirmed_event_is_rejected(): void
    {
        $tenant = $this->user();
        $event = $this->event($tenant, ['konfirmasi' => null]);
        $harga = $this->harga($event);

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($event, $harga))
            ->assertRedirect('/dashboard/old');

        $this->assertRejectedWithoutSideEffects($harga);
    }

    public function test_inactive_event_is_rejected(): void
    {
        $tenant = $this->user();
        $event = $this->event($tenant, ['status' => 'inactive']);
        $harga = $this->harga($event);

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($event, $harga))
            ->assertRedirect('/dashboard/old');

        $this->assertRejectedWithoutSideEffects($harga);
    }

    public function test_inactive_harga_is_rejected(): void
    {
        [$tenant, $event, $harga] = $this->ownedTicket(['status' => 'inactive']);

        $this->actingAs($tenant)
            ->from('/dashboard/old')
            ->post(route('old.add.cash'), $this->payload($event, $harga))
            ->assertRedirect('/dashboard/old');

        $this->assertRejectedWithoutSideEffects($harga);
    }

    private function tenants(): array
    {
        return [
            $this->user(['email' => 'tenant-a@example.test']),
            $this->user(['email' => 'tenant-b@example.test']),
        ];
    }

    private function ownedTicket(array $hargaOverrides = []): array
    {
        $tenant = $this->user();
        $event = $this->event($tenant);
        $harga = $this->harga($event, $hargaOverrides);

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
}
