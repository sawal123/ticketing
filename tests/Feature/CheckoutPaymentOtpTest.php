<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Mail\CheckoutPaymentOtpMail;
use App\Models\Cart;
use App\Models\CheckoutPaymentOtp;
use App\Models\Event;
use App\Models\EventPaymentGateway;
use App\Models\Harga;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payments\CheckoutPaymentOtpService;
use App\Services\Tickets\TicketPricingService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CheckoutPaymentOtpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');
        Config::set('services.midtrans.serverKey', 'test-server-key');
        Config::set('services.midtrans.clientKey', 'test-client-key');
        Config::set('services.midtrans.isProduction', false);
        Config::set('services.midtrans.isSanitized', true);
        Config::set('services.midtrans.is3ds', true);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
    }

    public function test_existing_event_default_payment_otp_is_off(): void
    {
        $event = $this->event();

        $this->assertFalse((bool) $event->payment_otp_enabled);
    }

    public function test_paynow_without_otp_setting_still_works(): void
    {
        $this->fakeMidtransRedirect();

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => false]);
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway(['midtrans_code' => 'bca_va']);
        $this->eventGateway($event, $gateway);

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
        ])->assertRedirect('https://pay.example.test/snap');

        $this->assertSame(Cart::STATUS_PENDING, $cart->fresh()->status);
    }

    public function test_paynow_requires_verified_otp_when_event_setting_is_on(): void
    {
        Mockery::mock('alias:Midtrans\Snap')->shouldNotReceive('createTransaction');

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway(['midtrans_code' => 'bca_va']);
        $this->eventGateway($event, $gateway);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/paynow', [
                'cart_uid' => $cart->uid,
                'payment_gateway_id' => $gateway->id,
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $cart->refresh();

        $this->assertSame(Cart::STATUS_RESERVED, $cart->status);
        $this->assertNull($cart->payment_gateway_id);
        $this->assertNull($cart->gross_amount);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_send_otp_requires_cart_owned_by_authenticated_user(): void
    {
        Mail::fake();

        $owner = $this->user();
        $otherUser = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($owner, $event);

        $this->actingAs($otherUser)
            ->postJson(route('checkout-payment-otp.send'), [
                'cart_uid' => $cart->uid,
            ])
            ->assertStatus(422);

        Mail::assertNothingSent();
        $this->assertDatabaseCount('checkout_payment_otps', 0);
    }

    public function test_send_otp_creates_six_digit_code_and_does_not_store_plaintext(): void
    {
        Mail::fake();

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);
        $sentCode = null;

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.send'), [
                'cart_uid' => $cart->uid,
            ])
            ->assertOk();

        Mail::assertSent(CheckoutPaymentOtpMail::class, function (CheckoutPaymentOtpMail $mail) use (&$sentCode) {
            preg_match('/\b(\d{6})\b/', $mail->render(), $matches);
            $sentCode = $matches[1] ?? null;

            return $sentCode !== null;
        });

        $otp = CheckoutPaymentOtp::firstOrFail();

        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $sentCode);
        $this->assertNotSame($sentCode, $otp->code_hash);
        $this->assertSame(hash('sha256', $sentCode), $otp->code_hash);
    }

    public function test_wrong_otp_increments_attempts(): void
    {
        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);
        $this->otp($cart, $user, $event, '123456');

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.verify'), [
                'cart_uid' => $cart->uid,
                'otp' => '654321',
            ])
            ->assertStatus(422);

        $this->assertSame(1, (int) CheckoutPaymentOtp::first()->attempts);
    }

    public function test_after_five_wrong_attempts_otp_cannot_be_used(): void
    {
        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);
        $this->otp($cart, $user, $event, '123456', ['attempts' => 4]);

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.verify'), [
                'cart_uid' => $cart->uid,
                'otp' => '999999',
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.verify'), [
                'cart_uid' => $cart->uid,
                'otp' => '123456',
            ])
            ->assertStatus(422);

        $this->assertSame(5, (int) CheckoutPaymentOtp::first()->attempts);
        $this->assertNull(CheckoutPaymentOtp::first()->verified_at);
    }

    public function test_correct_otp_sets_verified_at(): void
    {
        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);
        $this->otp($cart, $user, $event, '123456');

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.verify'), [
                'cart_uid' => $cart->uid,
                'otp' => '123456',
            ])
            ->assertOk();

        $this->assertNotNull(CheckoutPaymentOtp::first()->verified_at);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);
        $this->otp($cart, $user, $event, '123456', ['expires_at' => now()->subMinute()]);

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.verify'), [
                'cart_uid' => $cart->uid,
                'otp' => '123456',
            ])
            ->assertStatus(422);
    }

    public function test_cart_expired_otp_is_rejected_even_if_otp_not_expired(): void
    {
        Mail::fake();

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event, ['expires_at' => now()->subSecond()]);
        $this->otp($cart, $user, $event, '123456');

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.send'), ['cart_uid' => $cart->uid])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.verify'), [
                'cart_uid' => $cart->uid,
                'otp' => '123456',
            ])
            ->assertStatus(422);
    }

    public function test_otp_event_a_cannot_be_used_for_event_b(): void
    {
        $user = $this->user();
        $eventA = $this->event(['uid' => 'event-a', 'payment_otp_enabled' => true]);
        $eventB = $this->event(['uid' => 'event-b', 'payment_otp_enabled' => true]);
        $cartA = $this->cart($user, $eventA);
        $cartB = $this->cart($user, $eventB);
        $this->otp($cartA, $user, $eventA, '123456');

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.verify'), [
                'cart_uid' => $cartB->uid,
                'otp' => '123456',
            ])
            ->assertStatus(422);
    }

    public function test_otp_user_a_cannot_be_used_for_user_b(): void
    {
        $userA = $this->user();
        $userB = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cartA = $this->cart($userA, $event);
        $this->otp($cartA, $userA, $event, '123456');

        $this->actingAs($userB)
            ->postJson(route('checkout-payment-otp.verify'), [
                'cart_uid' => $cartA->uid,
                'otp' => '123456',
            ])
            ->assertStatus(422);
    }

    public function test_otp_cart_a_cannot_be_used_for_cart_b(): void
    {
        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cartA = $this->cart($user, $event);
        $cartB = $this->cart($user, $event);
        $this->otp($cartA, $user, $event, '123456');

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.verify'), [
                'cart_uid' => $cartB->uid,
                'otp' => '123456',
            ])
            ->assertStatus(422);
    }

    public function test_resend_before_sixty_seconds_is_rejected(): void
    {
        Mail::fake();

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);
        $this->otp($cart, $user, $event, '123456', ['sent_at' => now()->subSeconds(30)]);

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.resend'), ['cart_uid' => $cart->uid])
            ->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_resend_after_cooldown_is_successful(): void
    {
        Mail::fake();

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);
        $this->otp($cart, $user, $event, '123456', ['sent_at' => now()->subSeconds(61)]);

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.resend'), ['cart_uid' => $cart->uid])
            ->assertOk();

        Mail::assertSent(CheckoutPaymentOtpMail::class);
        $this->assertDatabaseCount('checkout_payment_otps', 2);
    }

    public function test_resend_invalidates_previous_otp_and_only_latest_is_valid(): void
    {
        Mail::fake();

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);
        $oldOtp = $this->otp($cart, $user, $event, '123456', ['sent_at' => now()->subSeconds(61)]);

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.resend'), ['cart_uid' => $cart->uid])
            ->assertOk();

        $latest = CheckoutPaymentOtp::latest('id')->first();
        $latest->update(['code_hash' => hash('sha256', '654321')]);

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.verify'), [
                'cart_uid' => $cart->uid,
                'otp' => '123456',
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.verify'), [
                'cart_uid' => $cart->uid,
                'otp' => '654321',
            ])
            ->assertOk();

        $this->assertTrue($oldOtp->fresh()->expires_at->isPast());
    }

    public function test_send_again_while_active_does_not_send_new_otp(): void
    {
        Mail::fake();

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);

        $this->actingAs($user)->postJson(route('checkout-payment-otp.send'), ['cart_uid' => $cart->uid])->assertOk();
        $this->actingAs($user)->postJson(route('checkout-payment-otp.send'), ['cart_uid' => $cart->uid])->assertOk();

        Mail::assertSent(CheckoutPaymentOtpMail::class, 1);
        $this->assertDatabaseCount('checkout_payment_otps', 1);
    }

    public function test_send_again_after_verification_reuses_same_verified_otp(): void
    {
        Mail::fake();

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);
        $otp = $this->otp($cart, $user, $event, '123456', ['verified_at' => now()]);

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.send'), ['cart_uid' => $cart->uid])
            ->assertOk()
            ->assertJson([
                'verified' => true,
                'status' => 'verified',
            ]);

        $this->assertSame($otp->id, CheckoutPaymentOtp::first()->id);
    }

    public function test_payment_gateway_change_after_otp_verify_still_allows_paynow(): void
    {
        $this->fakeMidtransRedirect();

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $this->hargaCart($cart, $harga, 1);
        $gatewayA = $this->gateway(['slug' => 'bca', 'midtrans_code' => 'bca_va']);
        $gatewayB = $this->gateway(['slug' => 'qris', 'midtrans_code' => 'other_qris']);
        $this->eventGateway($event, $gatewayA);
        $this->eventGateway($event, $gatewayB);
        $this->otp($cart, $user, $event, '123456', ['verified_at' => now()]);

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gatewayB->id,
        ])->assertRedirect('https://pay.example.test/snap');

        $this->assertSame($gatewayB->id, $cart->fresh()->payment_gateway_id);
    }

    public function test_paynow_after_verified_otp_can_continue_to_midtrans(): void
    {
        $this->fakeMidtransRedirect();

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway(['midtrans_code' => 'bca_va']);
        $this->eventGateway($event, $gateway);
        $this->otp($cart, $user, $event, '123456', ['verified_at' => now()]);

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
        ])->assertRedirect('https://pay.example.test/snap');

        $this->assertSame(Cart::STATUS_PENDING, $cart->fresh()->status);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertNotNull(CheckoutPaymentOtp::latest('id')->first()->consumed_at);
    }

    public function test_verified_otp_with_invalid_tampered_gateway_is_still_rejected(): void
    {
        Mockery::mock('alias:Midtrans\Snap')->shouldNotReceive('createTransaction');

        $user = $this->user();
        $eventA = $this->event(['uid' => 'event-a', 'payment_otp_enabled' => true]);
        $eventB = $this->event(['uid' => 'event-b', 'payment_otp_enabled' => true]);
        $cart = $this->cart($user, $eventA);
        $harga = $this->harga($eventA);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway(['midtrans_code' => 'bca_va']);
        $this->eventGateway($eventB, $gateway);
        $this->otp($cart, $user, $eventA, '123456', ['verified_at' => now()]);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/paynow', [
                'cart_uid' => $cart->uid,
                'payment_gateway_id' => $gateway->id,
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $cart->refresh();

        $this->assertSame(Cart::STATUS_RESERVED, $cart->status);
        $this->assertNull($cart->payment_gateway_id);
        $this->assertNull($cart->gross_amount);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_send_and_verify_otp_do_not_write_payment_snapshots(): void
    {
        Mail::fake();

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true, 'fee' => 10]);
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $pricingBefore = app(TicketPricingService::class)->calculateCart($cart->fresh());

        $this->actingAs($user)->postJson(route('checkout-payment-otp.send'), ['cart_uid' => $cart->uid])->assertOk();
        $latest = CheckoutPaymentOtp::latest('id')->first();
        $latest->update(['code_hash' => hash('sha256', '123456')]);

        $this->actingAs($user)->postJson(route('checkout-payment-otp.verify'), [
            'cart_uid' => $cart->uid,
            'otp' => '123456',
        ])->assertOk();

        $cart->refresh();
        $pricingAfter = app(TicketPricingService::class)->calculateCart($cart->fresh());

        $this->assertSame(Cart::STATUS_RESERVED, $cart->status);
        $this->assertNull($cart->payment_gateway_id);
        $this->assertNull($cart->gross_amount);
        $this->assertSame($pricingBefore['subtotal'], $pricingAfter['subtotal']);
        $this->assertSame($pricingBefore['tax_amount'], $pricingAfter['tax_amount']);
    }

    public function test_financial_snapshot_remains_immutable_after_transaction_is_created_with_otp(): void
    {
        $this->fakeMidtransRedirect();

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => true, 'fee' => 10]);
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'midtrans_code' => 'bca_va',
            'default_fee_fixed' => 1000,
            'default_fee_percent' => 1,
        ]);
        $eventGateway = $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 2000,
            'fee_percent' => 3,
        ]);
        $this->otp($cart, $user, $event, '123456', ['verified_at' => now()]);

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
        ])->assertRedirect('https://pay.example.test/snap');

        $gateway->update([
            'default_fee_fixed' => 9000,
            'default_fee_percent' => 9,
        ]);
        $eventGateway->update([
            'fee_fixed' => 8000,
            'fee_percent' => 8,
        ]);
        $event->update(['fee' => 25]);

        $pricing = app(TicketPricingService::class)->calculateCart($cart->fresh());

        $this->assertSame(10, $pricing['tax_percent']);
        $this->assertSame(10000, $pricing['tax_amount']);
        $this->assertSame(5000, $pricing['internet_fee']);
        $this->assertSame(115000, $pricing['gross_amount']);
    }

    public function test_send_otp_rejects_when_event_setting_is_off(): void
    {
        Mail::fake();

        $user = $this->user();
        $event = $this->event(['payment_otp_enabled' => false]);
        $cart = $this->cart($user, $event);

        $this->actingAs($user)
            ->postJson(route('checkout-payment-otp.send'), ['cart_uid' => $cart->uid])
            ->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_otp_must_match_same_cart_user_and_event_even_after_manual_insert(): void
    {
        $userA = $this->user();
        $userB = $this->user();
        $eventA = $this->event(['uid' => 'event-one', 'payment_otp_enabled' => true]);
        $eventB = $this->event(['uid' => 'event-two', 'payment_otp_enabled' => true]);
        $cartA = $this->cart($userA, $eventA);
        $cartB = $this->cart($userB, $eventB);

        CheckoutPaymentOtp::create([
            'cart_uid' => $cartA->uid,
            'user_uid' => $userA->uid,
            'event_uid' => $eventA->uid,
            'code_hash' => hash('sha256', '123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'sent_at' => now(),
        ]);

        $this->actingAs($userB)
            ->postJson(route('checkout-payment-otp.verify'), [
                'cart_uid' => $cartB->uid,
                'otp' => '123456',
            ])
            ->assertStatus(422);
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
            $table->boolean('payment_otp_enabled')->default(false);
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
            $table->timestamps();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('invoice')->nullable();
            $table->string('status');
            $table->text('link')->nullable();
            $table->string('payment_type')->nullable();
            $table->integer('internet_fee')->default(0);
            $table->integer('pajak')->default(0);
            $table->integer('pajak_persen')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('payment_link_expires_at')->nullable();
            $table->unsignedBigInteger('gross_amount')->nullable();
            $table->unsignedBigInteger('payment_gateway_id')->nullable();
            $table->string('payment_fee_mode')->nullable();
            $table->decimal('payment_fee_fixed', 15, 2)->nullable();
            $table->decimal('payment_fee_percent', 8, 4)->nullable();
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
            $table->string('kategori_harga')->nullable();
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

        Schema::create('payment_gateways', function ($table) {
            $table->id();
            $table->string('payment');
            $table->string('category');
            $table->decimal('biaya', 15, 2)->default(0);
            $table->string('biaya_type');
            $table->decimal('default_fee_fixed', 15, 2)->default(0);
            $table->decimal('default_fee_percent', 8, 4)->default(0);
            $table->string('midtrans_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('event_payment_gateways', function ($table) {
            $table->id();
            $table->foreignId('event_id');
            $table->foreignId('payment_gateway_id');
            $table->boolean('is_active')->default(false);
            $table->string('fee_mode')->default('global');
            $table->decimal('fee_fixed', 15, 2)->nullable();
            $table->decimal('fee_percent', 8, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('cart_vouchers', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('uid_vouchers')->nullable();
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('code')->nullable();
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

        Schema::create('checkout_payment_otps', function ($table) {
            $table->id();
            $table->string('cart_uid')->index();
            $table->string('user_uid')->index();
            $table->string('event_uid')->index();
            $table->string('code_hash', 64);
            $table->timestamp('expires_at')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
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
            'slug' => 'demo-'.Str::lower(Str::random(5)),
            'konfirmasi' => '1',
            'deskripsi' => 'Demo',
            'map' => '-',
            'payment_otp_enabled' => false,
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

    protected function gateway(array $attributes = []): PaymentGateway
    {
        return PaymentGateway::create(array_merge([
            'payment' => 'Bank Test',
            'category' => 'bank',
            'biaya' => 0,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => 0,
            'default_fee_percent' => 0,
            'midtrans_code' => 'bca_va',
            'is_active' => true,
            'slug' => 'bca',
        ], $attributes));
    }

    protected function eventGateway(Event $event, PaymentGateway $gateway, array $attributes = []): EventPaymentGateway
    {
        return EventPaymentGateway::create(array_merge([
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => true,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
            'fee_fixed' => null,
            'fee_percent' => null,
        ], $attributes));
    }

    protected function otp(Cart $cart, User $user, Event $event, string $plainOtp, array $attributes = []): CheckoutPaymentOtp
    {
        return CheckoutPaymentOtp::create(array_merge([
            'cart_uid' => $cart->uid,
            'user_uid' => $user->uid,
            'event_uid' => $event->uid,
            'code_hash' => hash('sha256', $plainOtp),
            'expires_at' => now()->addMinutes(CheckoutPaymentOtpService::OTP_TTL_MINUTES),
            'attempts' => 0,
            'sent_at' => now(),
            'verified_at' => null,
            'consumed_at' => null,
        ], $attributes));
    }

    protected function fakeMidtransRedirect(string $url = 'https://pay.example.test/snap'): void
    {
        Mockery::mock('alias:Midtrans\Snap')
            ->shouldReceive('createTransaction')
            ->once()
            ->andReturn((object) ['redirect_url' => $url]);
    }
}
