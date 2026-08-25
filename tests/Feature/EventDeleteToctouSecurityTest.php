<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventIndex;
use App\Models\Agreement;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class EventDeleteToctouSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_tenant_can_delete_own_pending_event_without_transactions_or_locked_stock(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->harga($event);

        Livewire::actingAs($tenant)
            ->test(EventIndex::class)
            ->call('deletePendingEvent', $event->uid);

        $this->assertSoftDeleted('events', ['uid' => $event->uid]);
    }

    public function test_legacy_route_can_delete_own_pending_event_without_transactions_or_locked_stock(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->harga($event);

        $this->actingAs($tenant)
            ->delete(route('dashboard.old.events.destroy', $event->uid));

        $this->assertDatabaseMissing('events', ['uid' => $event->uid]);
    }

    public function test_pending_event_with_completed_agreement_cannot_be_soft_deleted_from_livewire_flow(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->harga($event);
        $agreement = $this->agreement($tenant, $event, [
            'status' => Agreement::STATUS_COMPLETED,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventIndex::class)
            ->call('deletePendingEvent', $event->uid);

        $this->assertDatabaseHas('events', ['uid' => $event->uid, 'deleted_at' => null]);
        $this->assertDatabaseHas('agreements', [
            'id' => $agreement->id,
            'status' => Agreement::STATUS_COMPLETED,
        ]);
    }

    public function test_pending_event_with_completed_agreement_cannot_be_force_deleted_from_legacy_route(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->harga($event);
        $agreement = $this->agreement($tenant, $event, [
            'status' => Agreement::STATUS_COMPLETED,
        ]);

        $this->actingAs($tenant)
            ->delete(route('dashboard.old.events.destroy', $event->uid))
            ->assertSessionHas('error', 'Event tidak dapat dihapus karena memiliki agreement yang sudah selesai.');

        $this->assertDatabaseHas('events', ['uid' => $event->uid, 'deleted_at' => null]);
        $this->assertDatabaseHas('agreements', [
            'id' => $agreement->id,
            'status' => Agreement::STATUS_COMPLETED,
        ]);
    }

    public function test_pending_event_with_draft_agreement_keeps_existing_delete_behavior(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->harga($event);
        $this->agreement($tenant, $event, [
            'status' => Agreement::STATUS_DRAFT,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventIndex::class)
            ->call('deletePendingEvent', $event->uid);

        $this->assertSoftDeleted('events', ['uid' => $event->uid]);
    }

    public function test_tenant_cannot_delete_another_tenants_event(): void
    {
        [$tenantA] = $this->tenantWithEvent(['email' => 'tenant-a-event-delete@example.test']);
        [$tenantB, $eventB] = $this->tenantWithEvent(['email' => 'tenant-b-event-delete@example.test']);

        Livewire::actingAs($tenantA)
            ->test(EventIndex::class)
            ->call('deletePendingEvent', $eventB->uid);

        $this->assertDatabaseHas('events', [
            'uid' => $eventB->uid,
            'user_uid' => $tenantB->uid,
            'deleted_at' => null,
        ]);
    }

    public function test_active_or_confirmed_event_cannot_be_deleted(): void
    {
        [$tenant, $activeEvent] = $this->tenantWithEvent([
            'email' => 'tenant-active-event-delete@example.test',
        ], ['status' => 'active']);
        [, $confirmedEvent] = $this->tenantWithEvent([
            'email' => 'tenant-confirmed-event-delete@example.test',
        ], ['konfirmasi' => '1']);

        Livewire::actingAs($tenant)
            ->test(EventIndex::class)
            ->call('deletePendingEvent', $activeEvent->uid);

        $this->assertDatabaseHas('events', ['uid' => $activeEvent->uid, 'deleted_at' => null]);

        Livewire::actingAs($confirmedEvent->user)
            ->test(EventIndex::class)
            ->call('deletePendingEvent', $confirmedEvent->uid);

        $this->assertDatabaseHas('events', ['uid' => $confirmedEvent->uid, 'deleted_at' => null]);
    }

    public function test_event_with_active_cart_statuses_cannot_be_deleted(): void
    {
        foreach ([
            Cart::STATUS_SUCCESS,
            Cart::STATUS_PENDING,
            Cart::STATUS_UNPAID,
            Cart::STATUS_RESERVED,
            Cart::STATUS_PAYMENT_REVIEW,
        ] as $status) {
            [$tenant, $event] = $this->tenantWithEvent(['email' => Str::uuid().'@example.test']);
            $this->cart($tenant, $event, ['status' => $status]);

            Livewire::actingAs($tenant)
                ->test(EventIndex::class)
                ->call('deletePendingEvent', $event->uid);

            $this->assertDatabaseHas('events', ['uid' => $event->uid, 'deleted_at' => null]);
        }
    }

    public function test_event_with_historical_records_cannot_be_deleted_even_if_cart_is_soft_deleted(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $harga = $this->harga($event);
        $cart = $this->cart($tenant, $event, ['status' => Cart::STATUS_CANCELLED]);
        $this->hargaCart($cart, $event, $harga);
        $this->transaction($cart, $tenant, $event);
        $this->cash($cart, $tenant, $event);
        $cart->delete();

        Livewire::actingAs($tenant)
            ->test(EventIndex::class)
            ->call('deletePendingEvent', $event->uid);

        $this->assertDatabaseHas('events', ['uid' => $event->uid, 'deleted_at' => null]);
        $this->assertDatabaseHas('harga_carts', ['uid' => $cart->uid]);
        $this->assertDatabaseHas('transactions', ['uid' => $cart->uid]);
        $this->assertDatabaseHas('cashes', ['uid' => $cart->uid]);
    }

    public function test_event_with_sold_or_reserved_ticket_stock_cannot_be_deleted(): void
    {
        [$tenant, $soldEvent] = $this->tenantWithEvent(['email' => 'tenant-sold-event-delete@example.test']);
        $this->harga($soldEvent, ['sold_qty' => 1]);

        Livewire::actingAs($tenant)
            ->test(EventIndex::class)
            ->call('deletePendingEvent', $soldEvent->uid);

        $this->assertDatabaseHas('events', ['uid' => $soldEvent->uid, 'deleted_at' => null]);

        [$tenantReserved, $reservedEvent] = $this->tenantWithEvent(['email' => 'tenant-reserved-event-delete@example.test']);
        $this->harga($reservedEvent, ['reserved_qty' => 1]);

        Livewire::actingAs($tenantReserved)
            ->test(EventIndex::class)
            ->call('deletePendingEvent', $reservedEvent->uid);

        $this->assertDatabaseHas('events', ['uid' => $reservedEvent->uid, 'deleted_at' => null]);
    }

    public function test_toctou_cart_created_after_confirm_blocks_delete(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $harga = $this->harga($event);

        $component = Livewire::actingAs($tenant)->test(EventIndex::class);
        $cart = $this->cart($tenant, $event, ['status' => Cart::STATUS_SUCCESS]);
        $this->hargaCart($cart, $event, $harga);
        $this->transaction($cart, $tenant, $event);

        $component->call('deletePendingEvent', $event->uid);

        $this->assertDatabaseHas('events', ['uid' => $event->uid, 'deleted_at' => null]);
        $this->assertDatabaseHas('carts', ['uid' => $cart->uid, 'deleted_at' => null]);
        $this->assertDatabaseHas('harga_carts', ['uid' => $cart->uid]);
        $this->assertDatabaseHas('transactions', ['uid' => $cart->uid]);
    }

    public function test_toctou_status_change_after_confirm_blocks_delete(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $component = Livewire::actingAs($tenant)->test(EventIndex::class);

        $event->update(['status' => 'active', 'konfirmasi' => '1']);

        $component->call('deletePendingEvent', $event->uid);

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'status' => 'active',
            'konfirmasi' => '1',
            'deleted_at' => null,
        ]);
    }

    public function test_toctou_sold_qty_change_after_confirm_blocks_delete(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $harga = $this->harga($event);
        $component = Livewire::actingAs($tenant)->test(EventIndex::class);

        $harga->update(['sold_qty' => 1]);

        $component->call('deletePendingEvent', $event->uid);

        $this->assertDatabaseHas('events', ['uid' => $event->uid, 'deleted_at' => null]);
        $this->assertDatabaseHas('hargas', ['id' => $harga->id, 'sold_qty' => 1]);
    }

    public function test_legacy_route_rechecks_final_state_and_blocks_toctou_cart(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $harga = $this->harga($event);
        $cart = $this->cart($tenant, $event, ['status' => Cart::STATUS_SUCCESS]);
        $this->hargaCart($cart, $event, $harga);
        $this->transaction($cart, $tenant, $event);

        $this->actingAs($tenant)
            ->delete(route('dashboard.old.events.destroy', $event->uid));

        $this->assertDatabaseHas('events', ['uid' => $event->uid, 'deleted_at' => null]);
        $this->assertDatabaseHas('carts', ['uid' => $cart->uid, 'deleted_at' => null]);
        $this->assertDatabaseHas('harga_carts', ['uid' => $cart->uid]);
        $this->assertDatabaseHas('transactions', ['uid' => $cart->uid]);
    }

    private function tenantWithEvent(array $userOverrides = [], array $eventOverrides = []): array
    {
        $tenant = $this->user($userOverrides);
        $event = $this->event($tenant, $eventOverrides);

        return [$tenant, $event];
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Event Delete User',
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
            'event' => 'Pending Delete Event '.$uid,
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->format('Y-m-d H:i'),
            'status' => 'inactive',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Event',
            'map' => 'https://example.test/map',
            'pajak' => 0,
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'pending-delete-event-'.$uid,
            'konfirmasi' => null,
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

    private function cart(User $tenant, Event $event, array $overrides = []): Cart
    {
        return Cart::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.Str::upper(Str::random(8)),
            'status' => Cart::STATUS_SUCCESS,
            'payment_type' => 'bank_transfer',
            'gross_amount' => 100000,
            'paid_at' => now(),
        ], $overrides));
    }

    private function hargaCart(Cart $cart, Event $event, Harga $harga): HargaCart
    {
        return HargaCart::create([
            'uid' => $cart->uid,
            'harga_id' => $harga->id,
            'orderBy' => '1',
            'event_uid' => $event->uid,
            'quantity' => 1,
            'harga_ticket' => 100000,
            'kategori_harga' => $harga->kategori,
        ]);
    }

    private function transaction(Cart $cart, User $tenant, Event $event): Transaction
    {
        return Transaction::create([
            'uid' => $cart->uid,
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'amount' => (string) $cart->gross_amount,
            'gross_amount' => $cart->gross_amount,
            'invoice' => $cart->invoice,
            'payment_type' => $cart->payment_type,
            'status_transaksi' => Cart::STATUS_SUCCESS,
            'paid_at' => now(),
        ]);
    }

    private function cash(Cart $cart, User $tenant, Event $event): Cash
    {
        return Cash::create([
            'uid' => $cart->uid,
            'uid_partner' => null,
            'uid_user' => $tenant->uid,
            'uid_event' => $event->uid,
            'name' => 'Cash Buyer',
            'email' => 'cash@example.test',
            'nomor' => '08123456789',
            'alamat' => 'Jakarta',
            'lahir' => '2000-01-01',
            'gender' => 'pria',
        ]);
    }

    private function agreement(User $tenant, Event $event, array $overrides = []): Agreement
    {
        return Agreement::create(array_merge([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
            'status' => Agreement::STATUS_DRAFT,
        ], $overrides));
    }
}
