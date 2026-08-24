<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Models\Cart;
use App\Models\CheckoutPaymentOtp;
use App\Models\Event;
use App\Models\EventPaymentGateway;
use App\Models\Harga;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\Payments\CheckoutPaymentOtpService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CheckoutRecipientSnapshotTest extends TestCase
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

    public function test_ticket_holder_name_is_required_and_only_holder_input_is_flashed(): void
    {
        $user = $this->user(['name' => 'Mononym']);
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $gateway = $this->gateway();

        $this->hargaCart($cart, $harga, 1);
        $this->eventGateway($event, $gateway);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/paynow', [
                'cart_uid' => $cart->uid,
                'payment_gateway_id' => $gateway->id,
                'ticket_holder_name' => '',
                'ticket_recipient_email_option' => 'other_email',
                'ticket_recipient_other_email' => 'forged@example.test',
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertSessionHasErrors(['msg']);

        $this->assertNull(session()->getOldInput('ticket_holder_name'));
        $this->assertNull(session()->getOldInput('ticket_recipient_email_option'));
        $this->assertNull(session()->getOldInput('ticket_recipient_other_email'));
        $this->assertNull(session()->getOldInput('cart_uid'));
        $this->assertNull($cart->fresh()->ticket_holder_name);
        $this->assertNull($cart->fresh()->ticket_recipient_email);
        $this->assertSame('Mononym', $user->fresh()->name);
    }

    public function test_paynow_stores_authenticated_email_snapshot_and_ignores_forged_recipient_fields(): void
    {
        $capturedPayload = null;
        $this->fakeMidtransRedirectWithPayloadCapture($capturedPayload);

        $user = $this->user(['name' => 'Pembeli Akun']);
        $originalName = $user->name;
        $originalEmail = $user->email;
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $gateway = $this->gateway();

        $this->hargaCart($cart, $harga, 1);
        $this->eventGateway($event, $gateway);

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
            'ticket_holder_name' => 'Nama Sesuai KTP',
            'ticket_recipient_email_option' => 'other_email',
            'ticket_recipient_other_email' => 'recipient@example.test',
        ])->assertRedirect('https://pay.example.test/snap');

        $cart->refresh();
        $user->refresh();

        $this->assertSame(Cart::STATUS_PENDING, $cart->status);
        $this->assertSame('Nama Sesuai KTP', $cart->ticket_holder_name);
        $this->assertSame($originalEmail, $cart->ticket_recipient_email);
        $this->assertSame($originalEmail, $capturedPayload['customer_details']['email']);
        $this->assertSame($originalName, $capturedPayload['customer_details']['first_name']);
        $this->assertSame($originalName, $user->name);
        $this->assertSame($originalEmail, $user->email);
    }

    public function test_checkout_page_only_shows_account_email_and_hides_removed_recipient_and_quantity_controls(): void
    {
        $user = $this->user(['email' => 'buyer@example.test']);
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $gateway = $this->gateway();

        $this->hargaCart($cart, $harga, 1);
        $this->eventGateway($event, $gateway);

        $this->actingAs($user)
            ->get('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertOk()
            ->assertSee('Gunakan email akun saat ini')
            ->assertSee('buyer@example.test')
            ->assertSee('name="ticket_holder_name"', false)
            ->assertSee('form="paynowForm"', false)
            ->assertSee("form.elements.namedItem('ticket_holder_name')", false)
            ->assertDontSee("form.querySelector('[name=\"ticket_holder_name\"]')", false)
            ->assertDontSee('Kirim ke email lain')
            ->assertDontSee('Perbarui Jumlah Tiket')
            ->assertDontSee('/checkout/update-quantity');
    }

    public function test_other_user_cannot_modify_foreign_cart_snapshot(): void
    {
        $owner = $this->user(['uid' => 'owner-user', 'email' => 'owner@example.test']);
        $intruder = $this->user(['uid' => 'intruder-user', 'email' => 'intruder@example.test']);
        $event = $this->event();
        $cart = $this->cart($owner, $event);
        $harga = $this->harga($event);
        $gateway = $this->gateway();

        $this->hargaCart($cart, $harga, 1);
        $this->eventGateway($event, $gateway);

        $this->actingAs($intruder)
            ->from('/detail-ticket/'.$cart->uid.'/'.$intruder->uid)
            ->post('/paynow', [
                'cart_uid' => $cart->uid,
                'payment_gateway_id' => $gateway->id,
                'ticket_holder_name' => 'Nama Penyerang',
                'ticket_recipient_email_option' => 'other_email',
                'ticket_recipient_other_email' => 'attacker@example.test',
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$intruder->uid)
            ->assertSessionHasErrors(['msg']);

        $cart->refresh();

        $this->assertSame($owner->uid, $cart->user_uid);
        $this->assertNull($cart->ticket_holder_name);
        $this->assertNull($cart->ticket_recipient_email);
    }

    public function test_pending_without_active_payment_link_allows_holder_update_and_retry_while_email_stays_account_email(): void
    {
        $capturedPayload = null;
        $this->fakeMidtransRedirectWithPayloadCapture($capturedPayload, 'https://pay.example.test/retry');

        $user = $this->user(['email' => 'buyer@example.test']);
        $event = $this->event();
        $cart = $this->cart($user, $event, [
            'status' => Cart::STATUS_PENDING,
            'link' => null,
            'payment_link_expires_at' => now()->subMinute(),
            'ticket_holder_name' => 'Snapshot Lama',
            'ticket_recipient_email' => 'legacy@example.test',
        ]);
        $harga = $this->harga($event);
        $gateway = $this->gateway();

        $this->hargaCart($cart, $harga, 1);
        $this->eventGateway($event, $gateway);

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
            'ticket_holder_name' => 'Snapshot Baru',
            'ticket_recipient_email_option' => 'other_email',
            'ticket_recipient_other_email' => 'baru@example.test',
        ])->assertRedirect('https://pay.example.test/retry');

        $cart->refresh();

        $this->assertSame(Cart::STATUS_PENDING, $cart->status);
        $this->assertSame('Snapshot Baru', $cart->ticket_holder_name);
        $this->assertSame('buyer@example.test', $cart->ticket_recipient_email);
        $this->assertSame('https://pay.example.test/retry', $cart->link);
        $this->assertSame('buyer@example.test', $capturedPayload['customer_details']['email']);
    }

    public function test_locked_statuses_keep_snapshot_locked(): void
    {
        $user = $this->user();
        $event = $this->event();
        $lockedStatuses = [
            Cart::STATUS_SUCCESS,
            Cart::STATUS_CANCELLED,
            Cart::STATUS_EXPIRED,
            Cart::STATUS_PAYMENT_REVIEW,
            Cart::STATUS_UNPAID,
        ];

        foreach ($lockedStatuses as $status) {
            $cart = $this->cart($user, $event, [
                'status' => $status,
                'ticket_holder_name' => 'Snapshot '.$status,
                'ticket_recipient_email' => Str::lower($status).'@example.test',
            ]);

            $this->actingAs($user)
                ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
                ->post('/paynow', [
                    'cart_uid' => $cart->uid,
                    'payment_gateway_id' => 999,
                    'ticket_holder_name' => 'Perubahan '.$status,
                    'ticket_recipient_email_option' => 'other_email',
                    'ticket_recipient_other_email' => 'change-'.$status.'@example.test',
                ])
                ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid)
                ->assertSessionHasErrors(['msg']);

            $cart->refresh();

            $this->assertSame('Snapshot '.$status, $cart->ticket_holder_name);
            $this->assertSame(Str::lower($status).'@example.test', $cart->ticket_recipient_email);
        }
    }

    public function test_midtrans_url_failure_keeps_cart_retryable_without_dead_end(): void
    {
        $user = $this->user(['email' => 'buyer@example.test']);
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $gateway = $this->gateway();

        $this->hargaCart($cart, $harga, 1);
        $this->eventGateway($event, $gateway);

        $midtrans = Mockery::mock('alias:Midtrans\Snap');
        $midtrans->shouldReceive('createTransaction')
            ->once()
            ->andThrow(new \RuntimeException('Midtrans unavailable'));
        $midtrans->shouldReceive('createTransaction')
            ->once()
            ->andReturn((object) ['redirect_url' => 'https://pay.example.test/recovered']);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/paynow', [
                'cart_uid' => $cart->uid,
                'payment_gateway_id' => $gateway->id,
                'ticket_holder_name' => 'Nama Pertama',
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertSessionHasErrors(['msg']);

        $cart->refresh();

        $this->assertSame(Cart::STATUS_PENDING, $cart->status);
        $this->assertNull($cart->link);
        $this->assertSame('Nama Pertama', $cart->ticket_holder_name);
        $this->assertSame('buyer@example.test', $cart->ticket_recipient_email);

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
            'ticket_holder_name' => 'Nama Kedua',
        ])->assertRedirect('https://pay.example.test/recovered');

        $cart->refresh();

        $this->assertSame('Nama Kedua', $cart->ticket_holder_name);
        $this->assertSame('buyer@example.test', $cart->ticket_recipient_email);
        $this->assertSame('https://pay.example.test/recovered', $cart->link);
    }

    public function test_verified_checkout_payment_otp_flow_still_works_with_account_email_snapshot(): void
    {
        $capturedPayload = null;
        $this->fakeMidtransRedirectWithPayloadCapture($capturedPayload);

        $user = $this->user(['email' => 'buyer@example.test']);
        $event = $this->event(['payment_otp_enabled' => true]);
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $gateway = $this->gateway();

        $this->hargaCart($cart, $harga, 1);
        $this->eventGateway($event, $gateway);

        $otp = $this->otp($cart, $user, $event, '123456', [
            'verified_at' => now(),
        ]);

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
            'ticket_holder_name' => 'Nama OTP',
            'ticket_recipient_email_option' => 'other_email',
            'ticket_recipient_other_email' => 'otp-recipient@example.test',
        ])->assertRedirect('https://pay.example.test/snap');

        $cart->refresh();
        $otp->refresh();

        $this->assertSame(Cart::STATUS_PENDING, $cart->status);
        $this->assertSame('Nama OTP', $cart->ticket_holder_name);
        $this->assertSame('buyer@example.test', $cart->ticket_recipient_email);
        $this->assertNotNull($otp->consumed_at);
        $this->assertSame('buyer@example.test', $capturedPayload['customer_details']['email']);
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
            $table->string('ticket_holder_name')->nullable();
            $table->string('ticket_recipient_email')->nullable();
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
            $table->string('code_hash', 255);
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

        $harga->reserved_qty += $quantity;
        $harga->save();
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
            'code_hash' => Hash::make($plainOtp),
            'expires_at' => now()->addMinutes(CheckoutPaymentOtpService::OTP_TTL_MINUTES),
            'attempts' => 0,
            'sent_at' => now(),
            'verified_at' => null,
            'consumed_at' => null,
        ], $attributes));
    }

    protected function fakeMidtransRedirectWithPayloadCapture(?array &$capturedPayload, string $url = 'https://pay.example.test/snap'): void
    {
        Mockery::mock('alias:Midtrans\Snap')
            ->shouldReceive('createTransaction')
            ->once()
            ->with(Mockery::on(function ($payload) use (&$capturedPayload) {
                $capturedPayload = $payload;

                return true;
            }))
            ->andReturn((object) ['redirect_url' => $url]);
    }
}
