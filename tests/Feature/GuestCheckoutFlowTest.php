<?php

namespace Tests\Feature;

use App\Http\Controllers\TransactionController;
use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Mail\CheckoutPaymentOtpMail;
use App\Mail\MidtransPaymentNotification;
use App\Models\Cart;
use App\Models\CheckoutPaymentOtp;
use App\Models\Event;
use App\Models\EventPaymentGateway;
use App\Models\Harga;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Tickets\CartOwnershipService;
use App\Services\Tickets\GateTokenService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestCheckoutFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');
        Config::set('services.midtrans.serverKey', 'test-server-key');
        Config::set('gate-tokens.key', 'base64:'.base64_encode(str_repeat('g', 32)));

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        $defaultLanding = [(object) ['logo' => '', 'icon' => '', 'keyword' => '', 'description' => '']];
        View::share([
            'user' => null,
            'csrfToken' => 'test-csrf',
            'cari' => null,
            'logo' => $defaultLanding,
            'seo' => $defaultLanding,
            'contact' => collect(),
            'contactus' => collect(),
        ]);
    }

    public function test_guest_can_checkout_without_login_and_view_checkout_page(): void
    {
        $event = $this->event();
        $harga = $this->harga($event, ['harga' => 135000, 'qty' => 10]);
        $gateway = $this->gateway($event);

        $response = $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 2],
            ],
        ]);

        $cart = Cart::firstOrFail();
        $response->assertRedirect(route('checkout.guest.show', ['uid' => $cart->uid]));

        $this->assertTrue($cart->isGuest());
        $this->assertNull($cart->user_uid);
        $this->assertNotEmpty($cart->guest_token_hash);
        $this->assertNotEmpty($cart->guest_session_key);
        $this->assertSame(2, (int) $harga->fresh()->reserved_qty);

        $page = $this->get(route('checkout.guest.show', ['uid' => $cart->uid]));
        $page->assertOk();
        $page->assertSee('Informasi Pembeli');
        $page->assertSee('Login atau daftar');
        $page->assertSee('ticket_recipient_email');
        $page->assertSee('ticket_holder_name');
        $page->assertSee('ticket_holder_phone');
        $page->assertSee('ticket_holder_gender');
        $page->assertSee($gateway->payment);
    }

    public function test_guest_paynow_validates_required_email_name_and_gender(): void
    {
        $event = $this->event();
        $harga = $this->harga($event, ['harga' => 100000, 'qty' => 5]);
        $gateway = $this->gateway($event);

        $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $harga->id, 'quantity' => 1]],
        ])->assertRedirect();

        $cart = Cart::firstOrFail();
        $checkoutUrl = route('checkout.guest.show', ['uid' => $cart->uid]);

        $this->from($checkoutUrl)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
            'ticket_holder_name' => 'Budi Santoso',
            'ticket_recipient_email' => 'not-an-email',
            'ticket_holder_phone' => '081234567890',
            'ticket_holder_gender' => 'pria',
        ])->assertRedirect($checkoutUrl)
            ->assertSessionHasErrors('msg');

        $this->from($checkoutUrl)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
            'ticket_holder_name' => 'Budi Santoso',
            'ticket_recipient_email' => 'budi@example.com',
            'ticket_holder_phone' => 'abc',
            'ticket_holder_gender' => 'pria',
        ])->assertRedirect($checkoutUrl)
            ->assertSessionHasErrors('msg');

        $this->from($checkoutUrl)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
            'ticket_holder_name' => 'Budi Santoso',
            'ticket_recipient_email' => 'budi@example.com',
            'ticket_holder_phone' => '081234567890',
            'ticket_holder_gender' => 'invalid',
        ])->assertRedirect($checkoutUrl)
            ->assertSessionHasErrors('msg');

        $this->assertSame(Cart::STATUS_RESERVED, $cart->fresh()->status);
    }

    public function test_guest_end_to_end_payment_callback_email_and_signed_barcode_access(): void
    {
        Mail::fake();

        $event = $this->event();
        $harga = $this->harga($event, ['harga' => 150000, 'qty' => 5]);
        $gateway = $this->gateway($event);

        $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $harga->id, 'quantity' => 1]],
        ])->assertRedirect();

        $cart = Cart::firstOrFail();

        // Simulate guest submitting buyer details and payment link being generated
        $cart->update([
            'ticket_holder_name' => 'Siti Rahma',
            'ticket_recipient_email' => 'siti.rahma@example.com',
            'ticket_holder_phone' => '081234567890',
            'ticket_holder_gender' => Cart::GENDER_FEMALE,
            'status' => Cart::STATUS_PENDING,
            'payment_gateway_id' => $gateway->id,
            'payment_type' => $gateway->slug,
            'gross_amount' => 155000,
            'internet_fee' => 5000,
            'pajak' => 0,
            'pajak_persen' => 0,
            'link' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/guest-test',
            'payment_link_expires_at' => now()->addMinutes(10),
        ]);

        Transaction::create([
            'uid' => $cart->uid,
            'user_uid' => null,
            'event_uid' => $event->uid,
            'invoice' => $cart->invoice,
            'amount' => '155000',
            'gross_amount' => 155000,
            'status_transaksi' => Cart::STATUS_PENDING,
            'payment_type' => $gateway->slug,
        ]);

        $callbackPayload = $this->signedCallbackPayload($cart->invoice, 'settlement', '200', '155000.00');
        $this->postJson('/api/callback', $callbackPayload)->assertOk();

        $cart->refresh();
        $this->assertSame(Cart::STATUS_SUCCESS, $cart->status);
        $this->assertNotNull($cart->gate_token_hash);

        Mail::assertSent(MidtransPaymentNotification::class, function (MidtransPaymentNotification $mail) {
            return $mail->hasTo('siti.rahma@example.com');
        });

        $signedTicketUrl = URL::temporarySignedRoute('online.ticket.show', now()->addDay(), [
            'uid' => $cart->uid,
            'gate_access' => app(GateTokenService::class)->ticketAccessProof($cart),
        ]);

        $ticketResponse = $this->get($signedTicketUrl);
        $ticketResponse->assertOk();
        $ticketResponse->assertSee('Siti Rahma');
        $ticketResponse->assertSee($cart->invoice);
    }

    public function test_guest_cannot_access_or_mutate_another_guests_or_users_cart(): void
    {
        $event = $this->event();
        $harga = $this->harga($event, ['harga' => 100000, 'qty' => 10]);
        $gateway = $this->gateway($event);
        $voucher = $this->voucher($event);

        // Guest A creates cart
        $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $harga->id, 'quantity' => 1]],
        ])->assertRedirect();

        $guestACart = Cart::firstOrFail();

        // Clear session to simulate Guest B
        $this->flushSession();

        $this->get(route('checkout.guest.show', ['uid' => $guestACart->uid]))
            ->assertRedirect('/');

        $this->post('/checkVoucer', [
            'cartUid' => $guestACart->uid,
            'code' => $voucher->code,
        ])->assertRedirect()->assertSessionHas('vError');

        $this->post('/paynow', [
            'cart_uid' => $guestACart->uid,
            'payment_gateway_id' => $gateway->id,
            'ticket_holder_name' => 'Intruder',
            'ticket_recipient_email' => 'intruder@example.com',
            'ticket_holder_gender' => 'pria',
        ])->assertSessionHasErrors('msg');

        $this->deleteJson(route('transactions.cancel', ['uid' => $guestACart->uid]))
            ->assertNotFound();

        $this->assertSame(Cart::STATUS_RESERVED, $guestACart->fresh()->status);

        // Also ensure guest cannot access a logged-in user's cart
        $user = $this->user();
        $userCart = Cart::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $user->uid,
            'is_guest' => false,
            'event_uid' => $event->uid,
            'invoice' => 'INV-USER-001',
            'status' => Cart::STATUS_RESERVED,
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->get('/detail-ticket/'.$userCart->uid.'/'.$user->uid)
            ->assertRedirect('/');
    }

    public function test_guest_using_existing_user_email_proceeds_as_guest_without_accessing_user_account(): void
    {
        $existingUser = $this->user(['email' => 'existing@example.com', 'name' => 'Pemilik Akun']);
        $event = $this->event();
        $harga = $this->harga($event, ['harga' => 100000, 'qty' => 5]);
        $gateway = $this->gateway($event);

        $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $harga->id, 'quantity' => 1]],
        ])->assertRedirect();

        $cart = Cart::firstOrFail();

        // Trigger paynow validation / snapshot binding without external Midtrans HTTP by pre-testing controller snapshot logic
        // via OTP send which resolves guest email independently of existing user
        $event->update(['payment_otp_enabled' => true]);
        Mail::fake();

        $this->postJson(route('checkout-payment-otp.send'), [
            'cart_uid' => $cart->uid,
            'ticket_recipient_email' => 'existing@example.com',
            'ticket_holder_name' => 'Guest Pembeli',
        ])->assertOk()->assertJsonPath('status', 'sent');

        $this->assertGuest();
        $this->assertNull($cart->fresh()->user_uid);
        $this->assertTrue($cart->fresh()->isGuest());
        $this->assertSame('Pemilik Akun', $existingUser->fresh()->name);
    }

    public function test_guest_can_apply_remove_voucher_and_cancel_own_reservation(): void
    {
        $event = $this->event();
        $harga = $this->harga($event, ['harga' => 100000, 'qty' => 5]);
        $voucher = $this->voucher($event, ['code' => 'GUESTHEMAT', 'nominal' => 20000]);

        $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $harga->id, 'quantity' => 1]],
        ])->assertRedirect();

        $cart = Cart::firstOrFail();
        $this->assertSame(1, (int) $harga->fresh()->reserved_qty);

        $this->post('/checkVoucer', [
            'cartUid' => $cart->uid,
            'code' => 'GUESTHEMAT',
        ])->assertRedirect()->assertSessionHas('voucher');

        $this->assertDatabaseHas('cart_vouchers', [
            'uid' => $cart->uid,
            'code' => 'GUESTHEMAT',
            'user_uid' => null,
        ]);

        $this->post('/closeVoucher', [
            'cartUid' => $cart->uid,
        ])->assertRedirect()->assertSessionHas('voucher');

        $this->deleteJson(route('transactions.cancel', ['uid' => $cart->uid]))
            ->assertOk();

        $this->assertSame(Cart::STATUS_CANCELLED, $cart->fresh()->status);
        $this->assertSame(0, (int) $harga->fresh()->reserved_qty);
    }

    public function test_guest_payment_otp_binds_to_recipient_email(): void
    {
        Mail::fake();

        $event = $this->event(['payment_otp_enabled' => true]);
        $harga = $this->harga($event, ['harga' => 100000, 'qty' => 5]);
        $gateway = $this->gateway($event);

        $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $harga->id, 'quantity' => 1]],
        ])->assertRedirect();

        $cart = Cart::firstOrFail();

        $this->postJson(route('checkout-payment-otp.send'), [
            'cart_uid' => $cart->uid,
            'ticket_recipient_email' => 'otp-guest@example.com',
            'ticket_holder_name' => 'Guest OTP',
        ])->assertOk()->assertJsonPath('status', 'sent');

        Mail::assertSent(CheckoutPaymentOtpMail::class, function (CheckoutPaymentOtpMail $mail) {
            return $mail->hasTo('otp-guest@example.com');
        });

        $otp = CheckoutPaymentOtp::where('cart_uid', $cart->uid)->latest('id')->firstOrFail();
        $otp->update(['code_hash' => Hash::make('123456')]);

        $this->postJson(route('checkout-payment-otp.verify'), [
            'cart_uid' => $cart->uid,
            'ticket_recipient_email' => 'otp-guest@example.com',
            'otp' => '123456',
        ])->assertOk()->assertJsonPath('verified', true);

        // Attempting paynow with a different email than the verified OTP email must fail
        $this->from(route('checkout.guest.show', ['uid' => $cart->uid]))->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
            'ticket_holder_name' => 'Guest OTP',
            'ticket_recipient_email' => 'different@example.com',
            'ticket_holder_phone' => '081234567890',
            'ticket_holder_gender' => 'pria',
        ])->assertSessionHasErrors('msg');

        $this->assertSame(Cart::STATUS_RESERVED, $cart->fresh()->status);
    }

    public function test_guest_paynow_sets_midtrans_finish_callback_to_guest_finish_page(): void
    {
        $event = $this->event();
        $harga = $this->harga($event, ['harga' => 100000, 'qty' => 5]);
        $gateway = $this->gateway($event);

        $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $harga->id, 'quantity' => 1]],
        ])->assertRedirect();

        $cart = Cart::firstOrFail();
        $capturedPayload = null;

        $this->app->bind(TransactionController::class, function () use (&$capturedPayload) {
            return new class($capturedPayload) extends TransactionController
            {
                public $captured;

                public function __construct(&$captured)
                {
                    $this->captured = &$captured;
                }

                protected function createMidtransPaymentUrl(array $context): string
                {
                    $this->captured = $this->buildMidtransSnapPayload($context);

                    return 'https://app.sandbox.midtrans.com/snap/v4/redirection/test-guest-finish';
                }
            };
        });

        $this->from(route('checkout.guest.show', ['uid' => $cart->uid]))->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
            'ticket_holder_name' => 'Tamu Finish',
            'ticket_recipient_email' => 'tamu.finish@example.com',
            'ticket_holder_phone' => '081234567890',
            'ticket_holder_gender' => 'pria',
        ])->assertRedirect('https://app.sandbox.midtrans.com/snap/v4/redirection/test-guest-finish');

        $this->assertIsArray($capturedPayload);
        $finishUrl = $capturedPayload['callbacks']['finish'] ?? null;
        $this->assertNotEmpty($finishUrl);
        $this->assertStringContainsString('/checkout/'.$cart->uid.'/selesai', $finishUrl);
        $this->assertStringContainsString('token=', $finishUrl);
    }

    public function test_guest_finish_page_works_without_session_using_valid_token_and_uses_db_status(): void
    {
        Mail::fake();

        $event = $this->event();
        $harga = $this->harga($event, ['harga' => 100000, 'qty' => 5]);
        $gateway = $this->gateway($event);

        $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $harga->id, 'quantity' => 1]],
        ])->assertRedirect();

        $cart = Cart::firstOrFail();
        $tokens = session(CartOwnershipService::SESSION_TOKENS_KEY, []);
        $plainToken = $tokens[$cart->uid] ?? null;
        $this->assertNotEmpty($plainToken);

        $this->app->bind(TransactionController::class, function () {
            return new class extends TransactionController
            {
                protected function createMidtransPaymentUrl(array $context): string
                {
                    return 'https://app.sandbox.midtrans.com/snap/v4/redirection/test-token';
                }
            };
        });

        $this->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
            'ticket_holder_name' => 'Tamu Token',
            'ticket_recipient_email' => 'tamu.token@example.com',
            'ticket_holder_phone' => '081234567890',
            'ticket_holder_gender' => 'wanita',
        ])->assertRedirect();

        $cart->refresh();

        // Simulate cross-site Midtrans redirect with lost session & spoofed settlement query string while DB is still PENDING
        $this->flushSession();

        $pendingPage = $this->get(route('checkout.guest.finish', [
            'uid' => $cart->uid,
            'token' => $plainToken,
            'order_id' => $cart->invoice,
            'status_code' => '200',
            'transaction_status' => 'settlement',
        ]));

        $pendingPage->assertOk();
        $pendingPage->assertSee('Menunggu Konfirmasi Pembayaran');
        $pendingPage->assertDontSee('Pembayaran Berhasil!');
        $pendingPage->assertSee($cart->invoice);

        // Settle via authoritative Midtrans callback
        $grossAmount = (string) $cart->gross_amount;
        $signature = hash('sha512', $cart->invoice.'200'.$grossAmount.'test-server-key');

        $this->postJson('/api/callback', [
            'order_id' => $cart->invoice,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'transaction_id' => 'midtrans-tx-guest-finish',
            'payment_type' => 'qris',
        ])->assertOk();

        $this->flushSession();

        $paidPage = $this->get(route('checkout.guest.finish', [
            'uid' => $cart->uid,
            'token' => $plainToken,
            'order_id' => $cart->invoice,
            'status_code' => '200',
            'transaction_status' => 'settlement',
        ]));

        $paidPage->assertOk();
        $paidPage->assertSee('Pembayaran Berhasil!');
        $paidPage->assertSee('Buka E-Ticket &amp; Barcode', false);
        $paidPage->assertSee('tamu.token@example.com');
    }

    public function test_guest_finish_page_rejects_missing_or_invalid_token_without_session(): void
    {
        $event = $this->event();
        $harga = $this->harga($event, ['harga' => 100000, 'qty' => 5]);

        $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $harga->id, 'quantity' => 1]],
        ])->assertRedirect();

        $cart = Cart::firstOrFail();

        $this->flushSession();

        $this->get(route('checkout.guest.finish', ['uid' => $cart->uid]))
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $this->get(route('checkout.guest.finish', [
            'uid' => $cart->uid,
            'token' => str_repeat('a', 64),
        ]))
            ->assertRedirect('/')
            ->assertSessionHas('error');
    }

    public function test_logged_in_user_finish_url_points_to_transaksi(): void
    {
        $user = $this->user();
        $event = $this->event();

        $cart = Cart::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $user->uid,
            'is_guest' => false,
            'event_uid' => $event->uid,
            'invoice' => 'INV-USER-001',
            'status' => Cart::STATUS_PENDING,
        ]);

        $finishUrl = app(CartOwnershipService::class)
            ->finishUrl(request(), $cart);

        $this->assertSame(url('/transaksi'), $finishUrl);
    }

    protected function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('nomor')->default('-');
            $table->string('birthday')->default('2000-01-01');
            $table->string('alamat')->default('-');
            $table->string('kota')->default('-');
            $table->string('gender')->default('pria');
            $table->string('role')->default('user');
            $table->string('password');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('user_uid')->nullable();
            $table->string('event');
            $table->string('slug')->unique();
            $table->string('cover')->default('cover.jpg');
            $table->string('tanggal')->default('2027-01-20');
            $table->string('alamat')->default('Jakarta');
            $table->string('status')->default('active');
            $table->string('konfirmasi')->default('1');
            $table->integer('pajak')->default(0);
            $table->boolean('payment_otp_enabled')->default(false);
            $table->string('registration_mode')->default('ticketing');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('hargas', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('kategori');
            $table->integer('harga');
            $table->integer('qty');
            $table->integer('sold_qty')->default(0);
            $table->integer('reserved_qty')->default(0);
            $table->integer('max_order')->default(5);
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('user_uid')->nullable();
            $table->boolean('is_guest')->default(false);
            $table->string('event_uid');
            $table->string('invoice')->nullable()->unique();
            $table->string('ticket_holder_name')->nullable();
            $table->string('ticket_recipient_email')->nullable();
            $table->string('ticket_holder_gender', 20)->nullable();
            $table->string('ticket_holder_phone', 30)->nullable();
            $table->string('guest_token_hash', 64)->nullable();
            $table->string('guest_session_key', 64)->nullable();
            $table->string('gate_token_hash')->nullable();
            $table->text('gate_token_encrypted')->nullable();
            $table->string('gate_manual_code_hash')->nullable();
            $table->text('gate_manual_code_encrypted')->nullable();
            $table->timestamp('gate_token_issued_at')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->string('scanned_by')->nullable();
            $table->string('scan_device_id')->nullable();
            $table->unsignedInteger('gate_token_version')->default(1);
            $table->string('status');
            $table->string('konfirmasi')->default('0');
            $table->text('link')->nullable();
            $table->string('payment_type')->nullable();
            $table->integer('internet_fee')->default(0);
            $table->integer('pajak')->default(0);
            $table->integer('pajak_persen')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reservation_released_at')->nullable();
            $table->timestamp('payment_link_expires_at')->nullable();
            $table->integer('gross_amount')->nullable();
            $table->unsignedBigInteger('payment_gateway_id')->nullable();
            $table->string('payment_fee_mode')->nullable();
            $table->decimal('payment_fee_fixed', 15, 2)->nullable();
            $table->decimal('payment_fee_percent', 8, 4)->nullable();
            $table->text('review_reason')->nullable();
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('midtrans_status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->integer('orderBy')->default(1);
            $table->string('event_uid');
            $table->unsignedBigInteger('harga_id');
            $table->integer('quantity');
            $table->integer('harga_ticket');
            $table->string('kategori_harga');
            $table->string('voucher')->nullable();
            $table->integer('disc')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('transactions', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('event_uid');
            $table->string('invoice')->unique();
            $table->string('amount');
            $table->integer('gross_amount')->nullable();
            $table->string('status_transaksi');
            $table->string('payment_type')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payment_gateways', function ($table) {
            $table->id();
            $table->string('payment');
            $table->string('slug');
            $table->string('midtrans_code')->nullable();
            $table->string('fee_type')->default('fixed');
            $table->decimal('fee_fixed', 15, 2)->default(5000);
            $table->decimal('fee_percent', 8, 4)->default(0);
            $table->integer('biaya')->default(5000);
            $table->string('gambar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('event_payment_gateways', function ($table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('payment_gateway_id');
            $table->boolean('is_active')->default(true);
            $table->string('fee_mode')->default('global');
            $table->decimal('custom_fee_fixed', 15, 2)->nullable();
            $table->decimal('custom_fee_percent', 8, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('vouchers', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('user_uid')->nullable();
            $table->string('event_uid');
            $table->string('code');
            $table->string('jenis')->default('nominal');
            $table->integer('nominal')->default(10000);
            $table->integer('min_beli')->default(0);
            $table->integer('limit')->default(10);
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('cart_vouchers', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('event_uid');
            $table->string('uid_vouchers')->nullable();
            $table->string('user_uid')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('voucher_reservations', function ($table) {
            $table->id();
            $table->string('cart_uid')->unique();
            $table->unsignedBigInteger('voucher_id');
            $table->string('voucher_uid')->nullable();
            $table->string('invoice')->nullable();
            $table->string('code');
            $table->string('status');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('voucher_usages', function ($table) {
            $table->id();
            $table->unsignedBigInteger('voucher_id');
            $table->string('cart_uid')->unique();
            $table->string('invoice')->nullable();
            $table->timestamps();
        });

        Schema::create('checkout_payment_otps', function ($table) {
            $table->id();
            $table->string('cart_uid');
            $table->string('user_uid')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('event_uid');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    protected function user(array $overrides = []): User
    {
        return User::create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'User Test',
            'email' => 'user-'.Str::random(6).'@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ], $overrides));
    }

    protected function event(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'uid' => (string) Str::uuid(),
            'event' => 'Festival Guest Test',
            'slug' => 'festival-guest-'.Str::lower(Str::random(6)),
            'status' => 'active',
            'konfirmasi' => '1',
            'pajak' => 0,
            'payment_otp_enabled' => false,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ], $overrides));
    }

    protected function harga(Event $event, array $overrides = []): Harga
    {
        return Harga::create(array_merge([
            'uid' => $event->uid,
            'kategori' => 'Festival',
            'harga' => 100000,
            'qty' => 10,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'max_order' => 5,
            'status' => 'active',
        ], $overrides));
    }

    protected function gateway(Event $event): PaymentGateway
    {
        $gateway = PaymentGateway::create([
            'payment' => 'BCA Virtual Account',
            'slug' => 'bca',
            'midtrans_code' => 'bca_va',
            'fee_type' => 'fixed',
            'fee_fixed' => 5000,
            'fee_percent' => 0,
            'biaya' => 5000,
            'is_active' => true,
        ]);

        EventPaymentGateway::create([
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => true,
            'fee_mode' => 'global',
        ]);

        return $gateway;
    }

    protected function voucher(Event $event, array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'code' => 'PROMO'.Str::upper(Str::random(4)),
            'jenis' => 'nominal',
            'nominal' => 10000,
            'min_beli' => 0,
            'limit' => 10,
            'status' => 'active',
        ], $overrides));
    }

    protected function signedCallbackPayload(string $orderId, string $status, string $statusCode, string $grossAmount): array
    {
        $serverKey = (string) config('services.midtrans.serverKey');

        return [
            'order_id' => $orderId,
            'transaction_status' => $status,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'payment_type' => 'bank_transfer',
            'fraud_status' => 'accept',
            'transaction_id' => 'midtrans-'.Str::random(8),
            'signature_key' => hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey),
        ];
    }
}
