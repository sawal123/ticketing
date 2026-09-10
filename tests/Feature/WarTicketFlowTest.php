<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Jobs\sendEmailETransaksi;
use App\Models\Cart;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Harga;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class WarTicketFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');
        Config::set('services.midtrans.serverKey', 'test-server-key');
        Config::set('gate-tokens.key', 'base64:'.base64_encode(str_repeat('w', 32)));

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
    }

    public function test_checkout_uses_database_price_not_hidden_request_values(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['harga' => 150000, 'qty' => 5]);

        $this->actingAs($user)->post('/checkout', [
            'event_uid' => $event->uid,
            'harga0' => 1,
            'kategori0' => 'FAKE',
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('harga_carts', [
            'harga_id' => $harga->id,
            'quantity' => 2,
            'harga_ticket' => 150000,
            'kategori_harga' => $harga->kategori,
        ]);
        $this->assertSame(2, (int) $harga->fresh()->reserved_qty);
    }

    public function test_same_user_event_reuses_active_checkout_without_reserving_stock_twice(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['qty' => 5]);
        $payload = [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 2],
            ],
        ];

        $this->actingAs($user)->post('/checkout', $payload)->assertRedirect();
        $activeCart = Cart::where('user_uid', $user->uid)
            ->where('event_uid', $event->uid)
            ->whereIn('status', Cart::ACTIVE_RESERVATION_STATUSES)
            ->firstOrFail();

        $this->actingAs($user)
            ->post('/checkout', $payload)
            ->assertRedirect('/detail-ticket/'.$activeCart->uid.'/'.$user->uid);

        $this->assertSame(1, Cart::where('user_uid', $user->uid)
            ->where('event_uid', $event->uid)
            ->whereIn('status', Cart::ACTIVE_RESERVATION_STATUSES)
            ->count());
        $this->assertSame(2, (int) $harga->fresh()->reserved_qty);
        $this->assertSame(1, DB::table('harga_carts')->where('uid', $activeCart->uid)->count());
    }

    public function test_same_user_can_checkout_again_after_cancelled_or_expired_cart(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['qty' => 5]);
        $cancelled = $this->cart($user, $event, [
            'status' => Cart::STATUS_CANCELLED,
            'reservation_released_at' => now(),
        ]);
        $this->hargaCart($cancelled, $harga, 1);

        $this->actingAs($user)->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $harga->id, 'quantity' => 1]],
        ])->assertRedirect();

        $active = Cart::where('user_uid', $user->uid)
            ->where('event_uid', $event->uid)
            ->where('status', Cart::STATUS_RESERVED)
            ->latest('id')
            ->firstOrFail();
        $active->expires_at = now()->subMinute();
        $active->save();

        $this->actingAs($user)->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $harga->id, 'quantity' => 1]],
        ])->assertRedirect();

        $this->assertSame(Cart::STATUS_EXPIRED, $active->fresh()->status);
        $this->assertSame(1, Cart::where('user_uid', $user->uid)
            ->where('event_uid', $event->uid)
            ->whereIn('status', Cart::ACTIVE_RESERVATION_STATUSES)
            ->count());
        $this->assertSame(1, (int) $harga->fresh()->reserved_qty);
    }

    public function test_harga_id_from_different_event_is_rejected(): void
    {
        $user = $this->user();
        $event = $this->event();
        $otherHarga = $this->harga($this->event(['uid' => 'event-other']));

        $this->actingAs($user)->from('/ticket/demo')->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $otherHarga->id, 'quantity' => 1],
            ],
        ])->assertRedirect('/ticket/demo');

        $this->assertDatabaseCount('carts', 0);
        $this->assertSame(0, (int) $otherHarga->fresh()->reserved_qty);
    }

    public function test_invalid_quantities_and_total_limit_are_rejected(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event);

        $this->actingAs($user)->from('/ticket/demo')->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 0],
            ],
        ])->assertRedirect('/ticket/demo');

        $this->actingAs($user)->from('/ticket/demo')->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => -1],
            ],
        ])->assertRedirect('/ticket/demo');

        $this->actingAs($user)->from('/ticket/demo')->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 6],
            ],
        ])->assertRedirect('/ticket/demo');

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_reservation_cannot_make_stock_negative(): void
    {
        $user = $this->user();
        $otherUser = $this->user(['uid' => 'user-other', 'email' => 'other@example.test']);
        $event = $this->event();
        $harga = $this->harga($event, ['qty' => 5]);

        $this->actingAs($user)->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 4],
            ],
        ])->assertRedirect();

        $this->actingAs($otherUser)->from('/ticket/demo')->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 2],
            ],
        ])->assertRedirect('/ticket/demo');

        $harga->refresh();
        $this->assertSame(4, (int) $harga->reserved_qty);
        $this->assertSame(0, (int) $harga->sold_qty);
        $this->assertGreaterThanOrEqual(0, $harga->remainingQty());
    }

    public function test_expired_reservation_returns_stock(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['reserved_qty' => 2]);
        $cart = $this->cart($user, $event, ['status' => Cart::STATUS_RESERVED, 'expires_at' => now()->subMinute()]);
        $this->hargaCart($cart, $harga, 2);

        $this->artisan('tickets:release-expired')->assertExitCode(0);

        $this->assertSame(0, (int) $harga->fresh()->reserved_qty);
        $this->assertSame(Cart::STATUS_EXPIRED, $cart->fresh()->status);
    }

    public function test_user_can_cancel_owned_reserved_cart_without_deleting_transaction_data(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['reserved_qty' => 2]);
        $cart = $this->cart($user, $event);
        $this->hargaCart($cart, $harga, 2);
        $transaction = $this->transaction($cart);

        $this->actingAs($user)
            ->deleteJson(route('transactions.cancel', $cart->uid))
            ->assertOk();

        $this->assertSame(Cart::STATUS_CANCELLED, $cart->fresh()->status);
        $this->assertNotNull($cart->fresh()->reservation_released_at);
        $this->assertSame(0, (int) $harga->fresh()->reserved_qty);
        $this->assertSame(Cart::STATUS_CANCELLED, $transaction->fresh()->status_transaksi);
        $this->assertDatabaseHas('carts', ['id' => $cart->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('harga_carts', ['uid' => $cart->uid, 'deleted_at' => null]);
    }

    public function test_user_cannot_cancel_another_users_cart_and_legacy_get_does_not_mutate(): void
    {
        $owner = $this->user();
        $intruder = $this->user(['uid' => 'cancel-intruder', 'email' => 'cancel-intruder@example.test']);
        $event = $this->event();
        $harga = $this->harga($event, ['reserved_qty' => 1]);
        $cart = $this->cart($owner, $event);
        $this->hargaCart($cart, $harga, 1);

        $this->actingAs($intruder)
            ->deleteJson(route('transactions.cancel', $cart->uid))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get('/detail-ticket/delete/'.$cart->uid.'/'.$owner->uid)
            ->assertNotFound();

        $this->assertSame(Cart::STATUS_RESERVED, $cart->fresh()->status);
        $this->assertSame(1, (int) $harga->fresh()->reserved_qty);
        $this->assertNull($cart->fresh()->deleted_at);
    }

    public function test_pending_cart_with_active_payment_link_cannot_be_cancelled(): void
    {
        Queue::fake();

        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['reserved_qty' => 1]);
        $cart = $this->cart($user, $event, [
            'status' => Cart::STATUS_PENDING,
            'link' => 'https://pay.example.test/active',
            'payment_link_expires_at' => now()->addMinutes(10),
        ]);
        $this->hargaCart($cart, $harga, 1);
        $transaction = $this->transaction($cart, Cart::STATUS_PENDING);

        $this->actingAs($user)
            ->deleteJson(route('transactions.cancel', $cart->uid))
            ->assertUnprocessable();

        $this->assertSame(Cart::STATUS_PENDING, $cart->fresh()->status);
        $this->assertSame(1, (int) $harga->fresh()->reserved_qty);
        $this->assertNull($cart->fresh()->deleted_at);
        $this->assertNull($transaction->fresh()->deleted_at);

        $this->postJson('/api/callback', $this->midtransPayload($cart, 'settlement', '150000.00'))->assertOk();

        $this->assertSame(Cart::STATUS_SUCCESS, $cart->fresh()->status);
        $this->assertSame(Cart::STATUS_SUCCESS, $transaction->fresh()->status_transaksi);
        $this->assertNull($transaction->fresh()->deleted_at);
    }

    public function test_success_cart_cannot_be_cancelled(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['sold_qty' => 1]);
        $cart = $this->cart($user, $event, [
            'status' => Cart::STATUS_SUCCESS,
            'reservation_released_at' => now(),
        ]);
        $this->hargaCart($cart, $harga, 1);
        $transaction = $this->transaction($cart, Cart::STATUS_SUCCESS);

        $this->actingAs($user)
            ->deleteJson(route('transactions.cancel', $cart->uid))
            ->assertUnprocessable();

        $this->assertSame(Cart::STATUS_SUCCESS, $cart->fresh()->status);
        $this->assertSame(1, (int) $harga->fresh()->sold_qty);
        $this->assertNull($cart->fresh()->deleted_at);
        $this->assertNull($transaction->fresh()->deleted_at);
    }

    public function test_payment_review_cart_cannot_be_cancelled(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event);
        $cart = $this->cart($user, $event, [
            'status' => Cart::STATUS_PAYMENT_REVIEW,
            'reservation_released_at' => now(),
        ]);
        $this->hargaCart($cart, $harga, 1);
        $transaction = $this->transaction($cart, Cart::STATUS_PAYMENT_REVIEW);

        $this->actingAs($user)
            ->deleteJson(route('transactions.cancel', $cart->uid))
            ->assertUnprocessable();

        $this->assertSame(Cart::STATUS_PAYMENT_REVIEW, $cart->fresh()->status);
        $this->assertNull($cart->fresh()->deleted_at);
        $this->assertNull($transaction->fresh()->deleted_at);
        $this->assertDatabaseHas('harga_carts', ['uid' => $cart->uid, 'deleted_at' => null]);
    }

    public function test_repeated_cancellation_does_not_release_stock_twice_or_delete_history(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['reserved_qty' => 1]);
        $cart = $this->cart($user, $event);
        $this->hargaCart($cart, $harga, 1);

        $this->actingAs($user)->deleteJson(route('transactions.cancel', $cart->uid))->assertOk();
        $this->actingAs($user)->deleteJson(route('transactions.cancel', $cart->uid))->assertUnprocessable();

        $this->assertSame(0, (int) $harga->fresh()->reserved_qty);
        $this->assertSame(Cart::STATUS_CANCELLED, $cart->fresh()->status);
        $this->assertNull($cart->fresh()->deleted_at);
        $this->assertDatabaseHas('harga_carts', ['uid' => $cart->uid, 'deleted_at' => null]);
    }

    public function test_legacy_admin_status_action_rejects_arbitrary_status_and_direct_success(): void
    {
        $admin = $this->user(['role' => 'admin']);
        $buyer = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['reserved_qty' => 1]);
        $cart = $this->cart($buyer, $event, ['status' => Cart::STATUS_PENDING]);
        $this->hargaCart($cart, $harga, 1);
        $transaction = $this->transaction($cart, Cart::STATUS_PENDING);

        $this->actingAs($admin)->postJson(route('old.transactions.status.update'), [
            'uid' => $cart->uid,
            'status' => 'ADMIN_INJECTED',
        ])->assertUnprocessable();

        $this->actingAs($admin)->postJson(route('old.transactions.status.update'), [
            'uid' => $cart->uid,
            'status' => Cart::STATUS_SUCCESS,
        ])->assertUnprocessable();

        $this->assertSame(Cart::STATUS_PENDING, $cart->fresh()->status);
        $this->assertSame(Cart::STATUS_PENDING, $transaction->fresh()->status_transaksi);
        $this->assertSame(1, (int) $harga->fresh()->reserved_qty);
        $this->assertSame(0, (int) $harga->fresh()->sold_qty);
    }

    public function test_admin_success_settlement_preserves_lifecycle_and_is_idempotent(): void
    {
        Queue::fake();

        $admin = $this->user(['role' => 'admin']);
        $buyer = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['reserved_qty' => 2]);
        $cart = $this->cart($buyer, $event, [
            'status' => Cart::STATUS_PENDING,
            'gross_amount' => 300000,
            'payment_type' => 'bca',
        ]);
        $this->hargaCart($cart, $harga, 2);
        $transaction = $this->transaction($cart, Cart::STATUS_PENDING);
        $voucher = $this->voucher($event);
        DB::table('cart_vouchers')->insert([
            'uid' => $cart->uid,
            'uid_vouchers' => 'voucher-1',
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'code' => 'PROMO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('event_registrations')->insert([
            'uid' => (string) Str::uuid(),
            'cart_uid' => $cart->uid,
            'invoice' => $cart->invoice,
            'event_uid' => $event->uid,
            'user_uid' => $buyer->uid,
            'registration_mode' => 'individual',
            'status' => EventRegistration::STATUS_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->postJson(route('old.transactions.settle'), ['uid' => $cart->uid])
            ->assertOk();
        $this->actingAs($admin)
            ->postJson(route('old.transactions.settle'), ['uid' => $cart->uid])
            ->assertOk();

        $cart->refresh();
        $this->assertSame(Cart::STATUS_SUCCESS, $cart->status);
        $this->assertNotNull($cart->paid_at);
        $this->assertNotNull($cart->reservation_released_at);
        $this->assertNotNull($cart->gate_token_hash);
        $this->assertNotNull($cart->gate_manual_code_hash);
        $this->assertSame(0, (int) $harga->fresh()->reserved_qty);
        $this->assertSame(2, (int) $harga->fresh()->sold_qty);
        $this->assertSame(Cart::STATUS_SUCCESS, $transaction->fresh()->status_transaksi);
        $this->assertNotNull($transaction->fresh()->paid_at);
        $this->assertSame(1, (int) $voucher->fresh()->digunakan);
        $this->assertDatabaseCount('voucher_usages', 1);
        $this->assertDatabaseHas('event_registrations', [
            'cart_uid' => $cart->uid,
            'status' => EventRegistration::STATUS_SUCCESS,
        ]);
        $this->assertDatabaseHas('carts', ['id' => $cart->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('harga_carts', ['uid' => $cart->uid, 'deleted_at' => null]);
        Queue::assertPushed(sendEmailETransaksi::class, 1);
    }

    public function test_success_and_payment_review_cannot_be_changed_by_legacy_admin_action(): void
    {
        $admin = $this->user(['role' => 'admin']);
        $buyer = $this->user();
        $event = $this->event();
        $success = $this->cart($buyer, $event, ['status' => Cart::STATUS_SUCCESS]);
        $review = $this->cart($buyer, $event, ['status' => Cart::STATUS_PAYMENT_REVIEW]);

        $this->actingAs($admin)->postJson(route('old.transactions.status.update'), [
            'uid' => $success->uid,
            'status' => Cart::STATUS_CANCELLED,
        ])->assertUnprocessable();
        $this->actingAs($admin)->postJson(route('old.transactions.status.update'), [
            'uid' => $review->uid,
            'status' => Cart::STATUS_SUCCESS,
        ])->assertUnprocessable();

        $this->assertSame(Cart::STATUS_SUCCESS, $success->fresh()->status);
        $this->assertSame(Cart::STATUS_PAYMENT_REVIEW, $review->fresh()->status);
    }

    public function test_legacy_admin_delete_route_cannot_delete_financial_records(): void
    {
        $admin = $this->user(['role' => 'admin']);
        $buyer = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['reserved_qty' => 1]);
        $cart = $this->cart($buyer, $event, ['status' => Cart::STATUS_PENDING]);
        $this->hargaCart($cart, $harga, 1);
        $transaction = $this->transaction($cart, Cart::STATUS_PENDING);

        $this->actingAs($admin)
            ->get('/admin/old/deleteTransksi/'.$cart->uid)
            ->assertNotFound();
        $this->actingAs($admin)
            ->get('/admin/old/cashes/delete/'.$cart->uid)
            ->assertNotFound();

        $this->assertDatabaseHas('carts', ['id' => $cart->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('harga_carts', ['uid' => $cart->uid, 'deleted_at' => null]);
        $this->assertSame(1, (int) $harga->fresh()->reserved_qty);
    }

    public function test_user_cannot_pay_another_users_cart(): void
    {
        $owner = $this->user();
        $intruder = $this->user(['uid' => 'intruder', 'email' => 'intruder@example.test']);
        $event = $this->event();
        $harga = $this->harga($event, ['reserved_qty' => 1]);
        $cart = $this->cart($owner, $event, ['status' => Cart::STATUS_RESERVED]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway();

        $this->actingAs($intruder)->from('/detail-ticket/'.$cart->uid.'/'.$intruder->uid)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
        ])->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$intruder->uid);
    }

    public function test_expired_cart_cannot_create_snap_transaction(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['reserved_qty' => 1]);
        $cart = $this->cart($user, $event, ['status' => Cart::STATUS_RESERVED, 'expires_at' => now()->subMinute()]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway();

        $this->actingAs($user)->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
        ])->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $this->assertSame(Cart::STATUS_EXPIRED, $cart->fresh()->status);
        $this->assertSame(0, (int) $harga->fresh()->reserved_qty);
    }

    public function test_paynow_ignores_event_user_invoice_values_from_request_when_link_exists(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['reserved_qty' => 1]);
        $cart = $this->cart($user, $event, [
            'status' => Cart::STATUS_PENDING,
            'link' => 'https://pay.example.test/snap',
            'payment_link_expires_at' => now()->addMinutes(10),
        ]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway();

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
            'event' => 'wrong-event',
            'person' => 'wrong-user',
            'invoice' => 'wrong-invoice',
            'gross_amount' => 1,
        ])->assertRedirect('https://pay.example.test/snap');
    }

    public function test_late_paid_expired_reservation_goes_to_payment_review_without_overselling(): void
    {
        Queue::fake();

        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['qty' => 1, 'sold_qty' => 1, 'reserved_qty' => 0]);
        $cart = $this->cart($user, $event, [
            'status' => Cart::STATUS_EXPIRED,
            'gross_amount' => 150000,
            'expires_at' => now()->subMinutes(5),
            'reservation_released_at' => now()->subMinutes(4),
        ]);
        $this->hargaCart($cart, $harga, 1);
        Transaction::create([
            'uid' => $cart->uid,
            'user_uid' => $user->uid,
            'event_uid' => $event->uid,
            'amount' => '150000',
            'gross_amount' => 150000,
            'invoice' => $cart->invoice,
            'status_transaksi' => Cart::STATUS_PENDING,
        ]);

        $this->postJson('/api/callback', $this->midtransPayload($cart, 'settlement', '150000.00'))->assertOk();

        $this->assertSame(Cart::STATUS_PAYMENT_REVIEW, $cart->fresh()->status);
        $this->assertSame(1, (int) $harga->fresh()->sold_qty);
        $this->assertLessThanOrEqual((int) $harga->fresh()->qty, (int) $harga->fresh()->sold_qty);
        Queue::assertNothingPushed();
    }

    public function test_midtrans_settlement_is_idempotent_for_stock_voucher_and_email(): void
    {
        Queue::fake();

        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['reserved_qty' => 2]);
        $cart = $this->cart($user, $event, [
            'status' => Cart::STATUS_PENDING,
            'gross_amount' => 300000,
        ]);
        $this->hargaCart($cart, $harga, 2);
        Transaction::create([
            'uid' => $cart->uid,
            'user_uid' => $user->uid,
            'event_uid' => $event->uid,
            'amount' => '300000',
            'gross_amount' => 300000,
            'invoice' => $cart->invoice,
            'status_transaksi' => Cart::STATUS_PENDING,
        ]);
        $voucher = $this->voucher($event);
        DB::table('cart_vouchers')->insert([
            'uid' => $cart->uid,
            'uid_vouchers' => $voucher->uid,
            'user_uid' => $user->uid,
            'event_uid' => $event->uid,
            'code' => $voucher->code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = $this->midtransPayload($cart, 'settlement', '300000.00');

        $this->postJson('/api/callback', $payload)->assertOk();
        $this->postJson('/api/callback', $payload)->assertOk();

        $harga->refresh();
        $this->assertSame(0, (int) $harga->reserved_qty);
        $this->assertSame(2, (int) $harga->sold_qty);
        $this->assertSame(Cart::STATUS_SUCCESS, $cart->fresh()->status);
        $this->assertNotNull($cart->fresh()->gate_token_hash);
        $this->assertNotNull($cart->fresh()->gate_manual_code_hash);
        $this->assertSame(1, (int) $voucher->fresh()->digunakan);
        $this->assertDatabaseCount('voucher_usages', 1);
        Queue::assertPushed(sendEmailETransaksi::class, 1);
    }

    public function test_success_cannot_be_demoted_and_invalid_callback_values_are_rejected(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['sold_qty' => 1]);
        $cart = $this->cart($user, $event, [
            'status' => Cart::STATUS_SUCCESS,
            'gross_amount' => 150000,
            'paid_at' => now(),
            'reservation_released_at' => now(),
        ]);
        $this->hargaCart($cart, $harga, 1);
        Transaction::create([
            'uid' => $cart->uid,
            'user_uid' => $user->uid,
            'event_uid' => $event->uid,
            'amount' => '150000',
            'gross_amount' => 150000,
            'invoice' => $cart->invoice,
            'status_transaksi' => Cart::STATUS_SUCCESS,
        ]);

        $this->postJson('/api/callback', $this->midtransPayload($cart, 'cancel', '150000.00'))->assertOk();
        $this->assertSame(Cart::STATUS_SUCCESS, $cart->fresh()->status);
        $this->assertSame(1, (int) $harga->fresh()->sold_qty);

        $badGross = $this->midtransPayload($cart, 'settlement', '1.00');
        $this->postJson('/api/callback', $badGross)->assertStatus(400);

        $badSignature = $this->midtransPayload($cart, 'settlement', '150000.00');
        $badSignature['signature_key'] = 'bad';
        $this->postJson('/api/callback', $badSignature)->assertStatus(403);
    }

    protected function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('role')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('event');
            $table->string('alamat');
            $table->string('tanggal');
            $table->string('status');
            $table->integer('fee')->default(0);
            $table->string('cover')->nullable();
            $table->string('slug')->nullable();
            $table->string('konfirmasi')->nullable();
            $table->text('deskripsi')->nullable();
            $table->text('map')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hargas', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('kategori')->nullable();
            $table->unsignedInteger('qty')->default(0);
            $table->unsignedInteger('sold_qty')->default(0);
            $table->unsignedInteger('reserved_qty')->default(0);
            $table->integer('harga')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('max_order_qty')->default(5);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('invoice')->nullable();
            $table->string('ticket_holder_name')->nullable();
            $table->string('ticket_recipient_email')->nullable();
            $table->char('gate_token_hash', 64)->nullable()->unique();
            $table->text('gate_token_encrypted')->nullable();
            $table->char('gate_manual_code_hash', 64)->nullable()->unique();
            $table->text('gate_manual_code_encrypted')->nullable();
            $table->timestamp('gate_token_issued_at')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->string('scanned_by')->nullable();
            $table->string('scan_device_id')->nullable();
            $table->unsignedInteger('gate_token_version')->default(1);
            $table->string('status');
            $table->string('konfirmasi')->nullable();
            $table->text('link')->nullable();
            $table->string('payment_type')->nullable();
            $table->integer('internet_fee')->default(0);
            $table->integer('pajak')->default(0);
            $table->integer('pajak_persen')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reservation_released_at')->nullable();
            $table->timestamp('payment_link_expires_at')->nullable();
            $table->unsignedBigInteger('gross_amount')->nullable();
            $table->unsignedBigInteger('payment_gateway_id')->nullable();
            $table->text('review_reason')->nullable();
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('midtrans_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->foreignId('harga_id')->nullable();
            $table->string('orderBy')->nullable();
            $table->string('uid');
            $table->string('event_uid');
            $table->unsignedInteger('quantity');
            $table->integer('harga_ticket');
            $table->string('kategori_harga');
            $table->string('voucher')->nullable();
            $table->integer('disc')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('transactions', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('amount');
            $table->unsignedBigInteger('gross_amount')->nullable();
            $table->string('invoice');
            $table->string('payment_type')->nullable();
            $table->string('status_transaksi');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('event_registrations', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('cart_uid');
            $table->string('invoice');
            $table->string('event_uid');
            $table->string('user_uid');
            $table->string('registration_mode');
            $table->string('status');
            $table->string('team_name')->nullable();
            $table->json('answers')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_gateways', function ($table) {
            $table->id();
            $table->string('payment');
            $table->string('category');
            $table->decimal('biaya', 15, 2);
            $table->string('biaya_type');
            $table->boolean('is_active')->default(true);
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('vouchers', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('code');
            $table->string('unit');
            $table->integer('nominal');
            $table->integer('min_beli');
            $table->integer('max_disc');
            $table->integer('digunakan')->default(0);
            $table->integer('limit');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('cart_vouchers', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('uid_vouchers')->nullable();
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('code');
            $table->timestamps();
        });

        Schema::create('voucher_usages', function ($table) {
            $table->id();
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->string('voucher_uid')->nullable();
            $table->string('cart_uid')->unique();
            $table->string('invoice')->nullable();
            $table->string('code');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    protected function user(array $attributes = []): User
    {
        return User::create(array_merge([
            'uid' => 'user-'.Str::random(6),
            'user_uid' => 'root',
            'name' => 'Test User',
            'email' => Str::random(6).'@example.test',
            'password' => bcrypt('password'),
            'role' => 'user',
        ], $attributes));
    }

    protected function event(array $attributes = []): Event
    {
        return Event::create(array_merge([
            'uid' => 'event-'.Str::random(6),
            'user_uid' => 'owner',
            'event' => 'Demo Event',
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->toDateTimeString(),
            'status' => 'active',
            'fee' => 0,
            'cover' => 'cover.jpg',
            'slug' => 'demo',
            'konfirmasi' => '1',
            'deskripsi' => 'Demo',
            'map' => '-',
        ], $attributes));
    }

    protected function harga(Event $event, array $attributes = []): Harga
    {
        return Harga::create(array_merge([
            'uid' => $event->uid,
            'kategori' => 'VIP',
            'qty' => 5,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => 150000,
            'status' => 'active',
        ], $attributes));
    }

    protected function cart(User $user, Event $event, array $attributes = []): Cart
    {
        return Cart::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $user->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.Str::upper(Str::random(8)),
            'ticket_holder_name' => 'Snapshot Holder',
            'ticket_recipient_email' => $user->email,
            'status' => Cart::STATUS_RESERVED,
            'expires_at' => now()->addMinutes(15),
        ], $attributes));
    }

    protected function hargaCart(Cart $cart, Harga $harga, int $quantity): void
    {
        DB::table('harga_carts')->insert([
            'harga_id' => $harga->id,
            'orderBy' => 1,
            'uid' => $cart->uid,
            'event_uid' => $cart->event_uid,
            'quantity' => $quantity,
            'harga_ticket' => $harga->harga,
            'kategori_harga' => $harga->kategori,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function gateway(): PaymentGateway
    {
        return PaymentGateway::create([
            'payment' => 'Bank Test',
            'category' => 'bank',
            'biaya' => 0,
            'biaya_type' => 'rupiah',
            'is_active' => true,
            'slug' => 'bca',
        ]);
    }

    protected function transaction(Cart $cart, string $status = Cart::STATUS_PENDING): Transaction
    {
        return Transaction::create([
            'uid' => $cart->uid,
            'user_uid' => $cart->user_uid,
            'event_uid' => $cart->event_uid,
            'amount' => '150000',
            'gross_amount' => 150000,
            'invoice' => $cart->invoice,
            'status_transaksi' => $status,
        ]);
    }

    protected function voucher(Event $event)
    {
        return DB::table('vouchers')->insertGetId([
            'uid' => 'voucher-1',
            'user_uid' => 'owner',
            'event_uid' => $event->uid,
            'code' => 'PROMO',
            'unit' => 'rupiah',
            'nominal' => 10000,
            'min_beli' => 0,
            'max_disc' => 0,
            'digunakan' => 0,
            'limit' => 10,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]) ? Voucher::where('code', 'PROMO')->first() : null;
    }

    protected function midtransPayload(Cart $cart, string $status, string $grossAmount): array
    {
        return [
            'transaction_status' => $status,
            'payment_type' => 'bank_transfer',
            'fraud_status' => 'accept',
            'order_id' => $cart->invoice,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'transaction_id' => 'midtrans-'.Str::random(8),
            'signature_key' => hash('sha512', $cart->invoice.'200'.$grossAmount.'test-server-key'),
        ];
    }
}
