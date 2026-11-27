<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Models\Cart;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payments\MidtransPaymentLinkService;
use App\Services\Tickets\CartOwnershipService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CancelUnpaidHangingTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');

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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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
    }

    protected function createUser(): User
    {
        return User::create([
            'uid' => (string) Str::uuid(),
            'name' => 'Tester User',
            'email' => 'tester-'.Str::random(6).'@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);
    }

    protected function createEvent(): Event
    {
        return Event::create([
            'uid' => (string) Str::uuid(),
            'event' => 'Konser Musik Keren',
            'slug' => 'konser-musik-'.Str::lower(Str::random(6)),
            'status' => 'active',
            'konfirmasi' => '1',
            'pajak' => 0,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);
    }

    protected function createHarga(Event $event, int $qty = 10, int $price = 50000): Harga
    {
        return Harga::create([
            'uid' => $event->uid,
            'kategori' => 'VIP',
            'harga' => $price,
            'qty' => $qty,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'max_order' => 5,
            'status' => 'active',
        ]);
    }

    public function test_user_can_cancel_unpaid_hanging_transaction_and_release_reserved_qty(): void
    {
        $user = $this->createUser();
        $event = $this->createEvent();
        $harga = $this->createHarga($event, 10, 50000);

        // Simulate reservation: 2 tickets reserved
        $harga->update(['reserved_qty' => 2]);

        $cart = Cart::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $user->uid,
            'is_guest' => false,
            'event_uid' => $event->uid,
            'invoice' => 'INV-TEST-001',
            'status' => Cart::STATUS_UNPAID,
            'expires_at' => now()->addMinutes(15),
        ]);

        HargaCart::create([
            'uid' => $cart->uid,
            'orderBy' => 1,
            'event_uid' => $event->uid,
            'harga_id' => $harga->id,
            'quantity' => 2,
            'harga_ticket' => 50000,
            'kategori_harga' => $harga->kategori,
        ]);

        Transaction::create([
            'uid' => $cart->uid,
            'user_uid' => $user->uid,
            'event_uid' => $event->uid,
            'invoice' => $cart->invoice,
            'amount' => '100000',
            'status_transaksi' => Cart::STATUS_UNPAID,
        ]);

        $this->actingAs($user);

        $response = $this->deleteJson("/transactions/{$cart->uid}/cancel");

        $response->assertOk()
            ->assertJson(['message' => 'Transaksi berhasil dibatalkan.']);

        $cart->refresh();
        $harga->refresh();

        $this->assertSame(Cart::STATUS_CANCELLED, $cart->status);
        $this->assertNotNull($cart->reservation_released_at);
        $this->assertSame(0, (int) $harga->reserved_qty);

        $tx = Transaction::where('invoice', $cart->invoice)->first();
        $this->assertSame(Cart::STATUS_CANCELLED, $tx->status_transaksi);
    }

    public function test_cancelling_already_cancelled_or_expired_cart_is_rejected(): void
    {
        $user = $this->createUser();
        $event = $this->createEvent();
        $harga = $this->createHarga($event, 10, 50000);

        $cart = Cart::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $user->uid,
            'is_guest' => false,
            'event_uid' => $event->uid,
            'invoice' => 'INV-TEST-002',
            'status' => Cart::STATUS_CANCELLED,
            'reservation_released_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->deleteJson("/transactions/{$cart->uid}/cancel");

        $response->assertUnprocessable();
    }

    public function test_guest_can_cancel_unpaid_transaction(): void
    {
        $event = $this->createEvent();
        $harga = $this->createHarga($event, 10, 50000);
        $harga->update(['reserved_qty' => 1]);

        $tokenPlain = Str::random(40);
        $tokenHash = hash('sha256', $tokenPlain);
        $rawSessionKey = str_repeat('a', 32);
        $sessionKeyHash = hash('sha256', $rawSessionKey);

        $cart = Cart::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => null,
            'is_guest' => true,
            'guest_session_key' => $sessionKeyHash,
            'guest_token_hash' => $tokenHash,
            'event_uid' => $event->uid,
            'invoice' => 'INV-TEST-003',
            'status' => Cart::STATUS_UNPAID,
            'expires_at' => now()->addMinutes(15),
        ]);

        HargaCart::create([
            'uid' => $cart->uid,
            'orderBy' => 1,
            'event_uid' => $event->uid,
            'harga_id' => $harga->id,
            'quantity' => 1,
            'harga_ticket' => 50000,
            'kategori_harga' => $harga->kategori,
        ]);

        $response = $this->withSession([
            CartOwnershipService::SESSION_KEY => $rawSessionKey,
            CartOwnershipService::SESSION_TOKENS_KEY => [$cart->uid => $tokenPlain],
        ])->deleteJson("/transactions/{$cart->uid}/cancel");

        $response->assertOk()
            ->assertJson(['message' => 'Transaksi berhasil dibatalkan.']);

        $cart->refresh();
        $harga->refresh();

        $this->assertSame(Cart::STATUS_CANCELLED, $cart->status);
        $this->assertSame(0, (int) $harga->reserved_qty);
    }

    public function test_user_can_immediately_checkout_new_transaction_after_cancelling_unpaid(): void
    {
        $user = $this->createUser();
        $event = $this->createEvent();
        $harga = $this->createHarga($event, 10, 50000);
        $harga->update(['reserved_qty' => 1]);

        // Existing UNPAID cart
        $cart = Cart::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $user->uid,
            'is_guest' => false,
            'event_uid' => $event->uid,
            'invoice' => 'INV-TEST-004',
            'status' => Cart::STATUS_UNPAID,
            'expires_at' => now()->addMinutes(15),
        ]);

        HargaCart::create([
            'uid' => $cart->uid,
            'orderBy' => 1,
            'event_uid' => $event->uid,
            'harga_id' => $harga->id,
            'quantity' => 1,
            'harga_ticket' => 50000,
            'kategori_harga' => $harga->kategori,
        ]);

        $this->actingAs($user);

        // Cancel the hanging cart
        $cancelResponse = $this->deleteJson("/transactions/{$cart->uid}/cancel");
        $cancelResponse->assertOk();

        // Immediate checkout of new tickets
        $checkoutResponse = $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 2],
            ],
        ]);

        $checkoutResponse->assertRedirect();

        // New cart should exist and have reserved status
        $newCart = Cart::where('event_uid', $event->uid)
            ->where('uid', '!=', $cart->uid)
            ->first();

        $this->assertNotNull($newCart);
        $this->assertSame(Cart::STATUS_RESERVED, $newCart->status);
        $this->assertSame(2, (int) $harga->fresh()->reserved_qty);
    }

    public function test_cancellation_is_rejected_when_midtrans_expire_fails(): void
    {
        $user = $this->createUser();
        $event = $this->createEvent();
        $harga = $this->createHarga($event, 10, 50000);

        $cart = Cart::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $user->uid,
            'is_guest' => false,
            'event_uid' => $event->uid,
            'invoice' => 'INV-TEST-005',
            'status' => Cart::STATUS_UNPAID,
            'link' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/12345',
            'payment_link_expires_at' => now()->addMinutes(10),
            'expires_at' => now()->addMinutes(15),
        ]);

        HargaCart::create([
            'uid' => $cart->uid,
            'orderBy' => 1,
            'event_uid' => $event->uid,
            'harga_id' => $harga->id,
            'quantity' => 1,
            'harga_ticket' => 50000,
            'kategori_harga' => $harga->kategori,
        ]);

        // Mock MidtransPaymentLinkService to throw an exception
        $mockMidtrans = Mockery::mock(MidtransPaymentLinkService::class);
        $mockMidtrans->shouldReceive('expire')
            ->once()
            ->andThrow(new \RuntimeException('Midtrans connection timeout or order already expired'));

        $this->app->instance(MidtransPaymentLinkService::class, $mockMidtrans);

        $this->actingAs($user);

        $response = $this->deleteJson("/transactions/{$cart->uid}/cancel");

        $response->assertUnprocessable();

        $cart->refresh();
        $this->assertSame(Cart::STATUS_UNPAID, $cart->status);
    }
}
