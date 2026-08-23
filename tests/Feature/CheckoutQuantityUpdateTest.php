<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Models\Cart;
use App\Models\Event;
use App\Models\EventPaymentGateway;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutQuantityUpdateTest extends TestCase
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
    }

    public function test_reserved_without_payment_link_can_increase_quantity(): void
    {
        [$user, $cart, $hargaCart, $masterHarga] = $this->cartWithSingleTier();

        $this->actingAs($user)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $hargaCart->id, 'quantity' => 3],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $cart->refresh();
        $hargaCart->refresh();
        $masterHarga->refresh();

        $this->assertSame(3, $hargaCart->quantity);
        $this->assertSame(3, $masterHarga->reserved_qty);
        $this->assertSame('Nama Snapshot', $cart->ticket_holder_name);
        $this->assertSame('recipient@example.test', $cart->ticket_recipient_email);
        $this->assertNull($cart->gross_amount);
        $this->assertNull($cart->link);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_reserved_can_decrease_quantity(): void
    {
        [$user, $cart, $hargaCart, $masterHarga] = $this->cartWithSingleTier(['quantity' => 3]);

        $this->actingAs($user)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $hargaCart->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $this->assertSame(1, $hargaCart->fresh()->quantity);
        $this->assertSame(1, $masterHarga->fresh()->reserved_qty);
    }

    public function test_recipient_input_is_preserved_after_quantity_update_success(): void
    {
        [$user, $cart, $hargaCart, $masterHarga] = $this->cartWithSingleTier();
        $originalName = $user->name;
        $originalEmail = $user->email;

        $this->actingAs($user)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'ticket_holder_name' => 'Nama Baru UI',
                'ticket_recipient_email_option' => 'other_email',
                'ticket_recipient_other_email' => 'ui-recipient@example.test',
                'items' => [
                    ['harga_cart_id' => $hargaCart->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $this->assertSame('Nama Baru UI', session()->getOldInput('ticket_holder_name'));
        $this->assertSame('other_email', session()->getOldInput('ticket_recipient_email_option'));
        $this->assertSame('ui-recipient@example.test', session()->getOldInput('ticket_recipient_other_email'));
        $this->assertNull(session()->getOldInput('items'));
        $this->assertSame('Nama Snapshot', $cart->fresh()->ticket_holder_name);
        $this->assertSame('recipient@example.test', $cart->fresh()->ticket_recipient_email);
        $this->assertSame($originalName, $user->fresh()->name);
        $this->assertSame($originalEmail, $user->fresh()->email);
        $this->assertSame(2, $hargaCart->fresh()->quantity);
        $this->assertSame(2, $masterHarga->fresh()->reserved_qty);
    }

    public function test_recipient_input_is_preserved_after_quantity_update_failure(): void
    {
        [$user, $cart, $hargaCart, $masterHarga] = $this->cartWithSingleTier();

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'ticket_holder_name' => 'Nama Gagal UI',
                'ticket_recipient_email_option' => 'other_email',
                'ticket_recipient_other_email' => 'gagal@example.test',
                'items' => [
                    ['harga_cart_id' => $hargaCart->id, 'quantity' => 0],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertSessionHas('error');

        $this->assertSame('Nama Gagal UI', session()->getOldInput('ticket_holder_name'));
        $this->assertSame('other_email', session()->getOldInput('ticket_recipient_email_option'));
        $this->assertSame('gagal@example.test', session()->getOldInput('ticket_recipient_other_email'));
        $this->assertNull(session()->getOldInput('items'));
        $this->assertSame('Nama Snapshot', $cart->fresh()->ticket_holder_name);
        $this->assertSame('recipient@example.test', $cart->fresh()->ticket_recipient_email);
        $this->assertSame(1, $hargaCart->fresh()->quantity);
        $this->assertSame(1, $masterHarga->fresh()->reserved_qty);
    }

    public function test_quantity_zero_removes_tier_and_releases_reserved_stock(): void
    {
        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $vip = $this->harga($event, ['kategori' => 'VIP']);
        $festival = $this->harga($event, ['kategori' => 'Festival']);
        $vipCart = $this->hargaCart($cart, $vip, 1, 1);
        $festivalCart = $this->hargaCart($cart, $festival, 2, 2);

        $this->actingAs($user)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $vipCart->id, 'quantity' => 0],
                    ['harga_cart_id' => $festivalCart->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $this->assertNotNull(DB::table('harga_carts')->where('id', $vipCart->id)->value('deleted_at'));
        $this->assertSame(0, $vip->fresh()->reserved_qty);
        $this->assertSame(2, $festival->fresh()->reserved_qty);
    }

    public function test_inactive_tier_can_decrease_quantity(): void
    {
        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $inactiveHarga = $this->harga($event, ['status' => 'inactive', 'qty' => 5]);
        $activeHarga = $this->harga($event, ['kategori' => 'Festival']);
        $inactiveHargaCart = $this->hargaCart($cart, $inactiveHarga, 3, 1);
        $activeHargaCart = $this->hargaCart($cart, $activeHarga, 1, 2);

        $this->actingAs($user)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $inactiveHargaCart->id, 'quantity' => 1],
                    ['harga_cart_id' => $activeHargaCart->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $this->assertSame(1, $inactiveHargaCart->fresh()->quantity);
        $this->assertSame(1, $inactiveHarga->fresh()->reserved_qty);
        $this->assertSame(1, $activeHargaCart->fresh()->quantity);
        $this->assertSame(1, $activeHarga->fresh()->reserved_qty);
    }

    public function test_inactive_tier_can_be_removed_and_release_reserved_stock(): void
    {
        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $inactiveHarga = $this->harga($event, ['status' => 'inactive']);
        $activeHarga = $this->harga($event, ['kategori' => 'Festival']);
        $inactiveHargaCart = $this->hargaCart($cart, $inactiveHarga, 1, 1);
        $activeHargaCart = $this->hargaCart($cart, $activeHarga, 1, 2);

        $this->actingAs($user)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $inactiveHargaCart->id, 'quantity' => 0],
                    ['harga_cart_id' => $activeHargaCart->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $this->assertNotNull(DB::table('harga_carts')->where('id', $inactiveHargaCart->id)->value('deleted_at'));
        $this->assertSame(0, $inactiveHarga->fresh()->reserved_qty);
        $this->assertSame(1, $activeHargaCart->fresh()->quantity);
    }

    public function test_inactive_tier_cannot_increase_quantity(): void
    {
        [$user, $cart, $hargaCart, $masterHarga] = $this->cartWithSingleTier([
            'harga' => ['status' => 'inactive'],
        ]);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $hargaCart->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertSessionHas('error');

        $this->assertSame(1, $hargaCart->fresh()->quantity);
        $this->assertSame(1, $masterHarga->fresh()->reserved_qty);
    }

    public function test_total_quantity_less_than_one_is_rejected(): void
    {
        [$user, $cart, $hargaCart, $masterHarga] = $this->cartWithSingleTier();

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $hargaCart->id, 'quantity' => 0],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertSessionHas('error');

        $this->assertSame(1, $hargaCart->fresh()->quantity);
        $this->assertSame(1, $masterHarga->fresh()->reserved_qty);
    }

    public function test_total_quantity_more_than_five_is_rejected(): void
    {
        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $vip = $this->harga($event, ['kategori' => 'VIP']);
        $festival = $this->harga($event, ['kategori' => 'Festival']);
        $vipCart = $this->hargaCart($cart, $vip, 3, 1);
        $festivalCart = $this->hargaCart($cart, $festival, 2, 2);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $vipCart->id, 'quantity' => 4],
                    ['harga_cart_id' => $festivalCart->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertSessionHas('error');

        $this->assertSame(3, $vipCart->fresh()->quantity);
        $this->assertSame(2, $festivalCart->fresh()->quantity);
    }

    public function test_quantity_cannot_exceed_remaining_stock(): void
    {
        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $masterHarga = $this->harga($event, ['qty' => 3, 'reserved_qty' => 1]);
        $hargaCart = $this->hargaCart($cart, $masterHarga, 1, 1);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $hargaCart->id, 'quantity' => 3],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertSessionHas('error');

        $this->assertSame(1, $hargaCart->fresh()->quantity);
        $this->assertSame(2, $masterHarga->fresh()->reserved_qty);
    }

    public function test_failed_tier_update_rolls_back_all_quantity_changes(): void
    {
        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $vip = $this->harga($event, ['kategori' => 'VIP', 'qty' => 5, 'reserved_qty' => 1]);
        $festival = $this->harga($event, ['kategori' => 'Festival', 'qty' => 2, 'reserved_qty' => 1]);
        $vipCart = $this->hargaCart($cart, $vip, 1, 1);
        $festivalCart = $this->hargaCart($cart, $festival, 1, 2);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $vipCart->id, 'quantity' => 2],
                    ['harga_cart_id' => $festivalCart->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertSessionHas('error');

        $this->assertSame(1, $vipCart->fresh()->quantity);
        $this->assertSame(1, $festivalCart->fresh()->quantity);
        $this->assertSame(2, $vip->fresh()->reserved_qty);
        $this->assertSame(2, $festival->fresh()->reserved_qty);
    }

    public function test_duplicate_master_harga_net_delta_rolls_back_when_stock_is_insufficient(): void
    {
        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $masterHarga = $this->harga($event, ['qty' => 3]);
        $firstHargaCart = $this->hargaCart($cart, $masterHarga, 1, 1);
        $secondHargaCart = $this->hargaCart($cart, $masterHarga, 1, 2);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $firstHargaCart->id, 'quantity' => 2],
                    ['harga_cart_id' => $secondHargaCart->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertSessionHas('error');

        $this->assertSame(1, $firstHargaCart->fresh()->quantity);
        $this->assertSame(1, $secondHargaCart->fresh()->quantity);
        $this->assertSame(2, $masterHarga->fresh()->reserved_qty);
    }

    public function test_duplicate_master_harga_uses_net_delta_when_stock_is_sufficient(): void
    {
        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $masterHarga = $this->harga($event, ['qty' => 4]);
        $firstHargaCart = $this->hargaCart($cart, $masterHarga, 1, 1);
        $secondHargaCart = $this->hargaCart($cart, $masterHarga, 1, 2);

        $this->actingAs($user)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $firstHargaCart->id, 'quantity' => 2],
                    ['harga_cart_id' => $secondHargaCart->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $this->assertSame(2, $firstHargaCart->fresh()->quantity);
        $this->assertSame(2, $secondHargaCart->fresh()->quantity);
        $this->assertSame(4, $masterHarga->fresh()->reserved_qty);
    }

    public function test_other_user_cannot_update_cart_quantity(): void
    {
        [$owner, $cart, $hargaCart] = $this->cartWithSingleTier();
        $intruder = $this->user(['uid' => 'user-intruder', 'email' => 'intruder@example.test']);

        $this->actingAs($intruder)
            ->from('/detail-ticket/'.$cart->uid.'/'.$intruder->uid)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $hargaCart->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$intruder->uid)
            ->assertSessionHas('error');

        $this->assertSame(1, $hargaCart->fresh()->quantity);
        $this->assertSame($owner->uid, $cart->fresh()->user_uid);
    }

    public function test_harga_cart_from_another_cart_is_rejected(): void
    {
        [$user, $cart, $hargaCart] = $this->cartWithSingleTier();
        $otherCart = $this->cart($user, $cart->event);
        $otherHarga = $this->harga($cart->event, ['kategori' => 'Festival']);
        $foreignHargaCart = $this->hargaCart($otherCart, $otherHarga, 1, 1);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $foreignHargaCart->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertSessionHas('error');

        $this->assertSame(1, $hargaCart->fresh()->quantity);
        $this->assertSame(1, $foreignHargaCart->fresh()->quantity);
    }

    public function test_expired_reservation_is_rejected(): void
    {
        [$user, $cart, $hargaCart] = $this->cartWithSingleTier([
            'cart' => ['expires_at' => now()->subMinute()],
        ]);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $hargaCart->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertSessionHas('error');

        $this->assertSame(1, $hargaCart->fresh()->quantity);
    }

    public function test_pending_active_link_and_payment_snapshot_states_are_rejected(): void
    {
        $user = $this->user();
        $event = $this->event();
        $cases = [
            'pending' => [
                'status' => Cart::STATUS_PENDING,
            ],
            'active_link' => [
                'status' => Cart::STATUS_RESERVED,
                'link' => 'https://pay.example.test/existing',
                'payment_link_expires_at' => now()->addMinutes(10),
            ],
            'gross_amount_snapshot' => [
                'status' => Cart::STATUS_RESERVED,
                'gross_amount' => 150000,
                'payment_gateway_id' => 1,
                'payment_type' => 'bca',
            ],
        ];

        foreach ($cases as $attributes) {
            $cart = $this->cart($user, $event, $attributes);
            $harga = $this->harga($event);
            $hargaCart = $this->hargaCart($cart, $harga, 1, 1);

            $this->actingAs($user)
                ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
                ->post('/checkout/update-quantity', [
                    'cart_uid' => $cart->uid,
                    'items' => [
                        ['harga_cart_id' => $hargaCart->id, 'quantity' => 2],
                    ],
                ])
                ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid)
                ->assertSessionHas('error');

            $this->assertSame(1, $hargaCart->fresh()->quantity);
        }
    }

    public function test_pricing_after_update_uses_latest_quantity(): void
    {
        $user = $this->user();
        $event = $this->event(['fee' => 10]);
        $cart = $this->cart($user, $event);
        $vip = $this->harga($event, ['kategori' => 'VIP']);
        $festival = $this->harga($event, ['kategori' => 'Festival']);
        $vipCart = $this->hargaCart($cart, $vip, 1, 1);
        $festivalCart = $this->hargaCart($cart, $festival, 1, 2);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway);

        $this->actingAs($user)
            ->post('/checkout/update-quantity', [
                'cart_uid' => $cart->uid,
                'items' => [
                    ['harga_cart_id' => $vipCart->id, 'quantity' => 2],
                    ['harga_cart_id' => $festivalCart->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $response = $this->actingAs($user)->get('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $response->assertOk();
        $response->assertSee('Total 4 Tiket');
        $response->assertSee('Rp 600.000');
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
            'qty' => 10,
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
            'ticket_holder_name' => 'Nama Snapshot',
            'ticket_recipient_email' => 'recipient@example.test',
            'status' => Cart::STATUS_RESERVED,
            'expires_at' => now()->addMinutes(15),
        ], $attributes));
    }

    protected function hargaCart(Cart $cart, Harga $harga, int $quantity, int $orderBy): HargaCart
    {
        $harga->reserved_qty += $quantity;
        $harga->save();

        return HargaCart::create([
            'harga_id' => $harga->id,
            'orderBy' => $orderBy,
            'uid' => $cart->uid,
            'event_uid' => $cart->event_uid,
            'quantity' => $quantity,
            'harga_ticket' => $harga->harga,
            'kategori_harga' => $harga->kategori,
            'voucher' => null,
            'disc' => 0,
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

    protected function cartWithSingleTier(array $overrides = []): array
    {
        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event, $overrides['cart'] ?? []);
        $harga = $this->harga($event, $overrides['harga'] ?? []);
        $hargaCart = $this->hargaCart($cart, $harga, $overrides['quantity'] ?? 1, 1);

        return [$user, $cart, $hargaCart, $harga];
    }
}
