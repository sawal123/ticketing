<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Models\Cart;
use App\Models\Event;
use App\Models\Harga;
use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketCategoryPurchaseRulesTest extends TestCase
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

    /**
     * REG0: Kategori baru default max_order_qty = 5.
     */
    public function test_new_ticket_category_defaults_to_max_order_qty_of_5(): void
    {
        $event = $this->event();
        $harga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'VIP',
            'qty' => 10,
            'harga' => 150000,
            'status' => 'active',
        ]);

        // Refresh from database to get the default value
        $harga->refresh();

        $this->assertSame(5, (int) $harga->max_order_qty);
        $this->assertSame(5, $harga->maxOrderQty());
    }

    /**
     * REG0: create ticket menyimpan max_order_qty dan description.
     */
    public function test_create_ticket_saves_max_order_qty_and_description(): void
    {
        $event = $this->event();
        $harga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Regular',
            'qty' => 20,
            'harga' => 50000,
            'max_order_qty' => 4,
            'description' => 'Tiket regular untuk umum',
            'status' => 'active',
        ]);

        $this->assertSame(4, (int) $harga->max_order_qty);
        $this->assertSame('Tiket regular untuk umum', $harga->description);
        $this->assertSame(4, $harga->maxOrderQty());
    }

    /**
     * REG0: edit ticket memperbarui max_order_qty dan description.
     */
    public function test_edit_ticket_updates_max_order_qty_and_description(): void
    {
        $event = $this->event();
        $harga = $this->harga($event, [
            'max_order_qty' => 3,
            'description' => 'Old description',
        ]);

        $harga->update([
            'max_order_qty' => 8,
            'description' => 'Updated description',
        ]);

        $this->assertSame(8, (int) $harga->fresh()->max_order_qty);
        $this->assertSame('Updated description', $harga->fresh()->description);
    }

    /**
     * REG0: kategori A max 3 menolak quantity 4.
     */
    public function test_reject_quantity_exceeding_category_max_order_qty(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['max_order_qty' => 3, 'qty' => 10]);

        $this->actingAs($user)->from('/ticket/demo')->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 4],
            ],
        ])->assertRedirect('/ticket/demo');

        $this->assertDatabaseCount('carts', 0);
    }

    /**
     * REG0: request manual tidak dapat bypass max per kategori.
     */
    public function test_manual_request_cannot_bypass_max_order_qty(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['max_order_qty' => 2, 'qty' => 10]);

        // Try to bypass via hidden field manipulation
        $this->actingAs($user)->from('/ticket/demo')->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 5, 'orderBy' => 1],
            ],
        ])->assertRedirect('/ticket/demo');

        $this->assertDatabaseCount('carts', 0);
    }

    /**
     * REG0: kategori A x3 + kategori B x2 valid walaupun total > 5.
     */
    public function test_multiple_categories_each_respecting_own_limit_totaling_more_than_5(): void
    {
        $user = $this->user();
        $event = $this->event();
        $categoryA = $this->harga($event, ['kategori' => 'Regular', 'max_order_qty' => 3, 'qty' => 5]);
        $categoryB = $this->harga($event, ['kategori' => 'VIP', 'max_order_qty' => 2, 'qty' => 5]);

        $this->actingAs($user)->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $categoryA->id, 'quantity' => 3],
                ['harga_id' => $categoryB->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('carts', ['user_uid' => $user->uid]);
        $cart = Cart::where('user_uid', $user->uid)->first();

        $this->assertDatabaseHas('harga_carts', [
            'uid' => $cart->uid,
            'harga_id' => $categoryA->id,
            'quantity' => 3,
        ]);

        $this->assertDatabaseHas('harga_carts', [
            'uid' => $cart->uid,
            'harga_id' => $categoryB->id,
            'quantity' => 2,
        ]);
    }

    /**
     * REG0: limit masing-masing kategori independen.
     */
    public function test_category_limits_are_independent(): void
    {
        $user = $this->user();
        $event = $this->event();
        $categoryA = $this->harga($event, ['kategori' => 'A', 'max_order_qty' => 5, 'qty' => 10]);
        $categoryB = $this->harga($event, ['kategori' => 'B', 'max_order_qty' => 3, 'qty' => 10]);

        // A can take 5, but request 6
        $this->actingAs($user)->from('/ticket/demo')->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $categoryA->id, 'quantity' => 6],
                ['harga_id' => $categoryB->id, 'quantity' => 1],
            ],
        ])->assertRedirect('/ticket/demo');

        $this->assertDatabaseCount('carts', 0);

        // But A can take exactly 5
        $this->actingAs($user)->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $categoryA->id, 'quantity' => 5],
                ['harga_id' => $categoryB->id, 'quantity' => 3],
            ],
        ])->assertRedirect();

        $this->assertDatabaseCount('carts', 1);
    }

    /**
     * REG0: remaining stock lebih kecil dari max tetap membatasi pembelian.
     */
    public function test_remaining_stock_smaller_than_max_order_qty_limits_purchase(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, [
            'max_order_qty' => 5,
            'qty' => 3,
            'reserved_qty' => 0,
            'sold_qty' => 0,
        ]);

        // Try to buy 5 but only 3 in stock
        $this->actingAs($user)->from('/ticket/demo')->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 5],
            ],
        ])->assertRedirect('/ticket/demo');

        $this->assertDatabaseCount('carts', 0);

        // But 3 should work (matches stock)
        $this->actingAs($user)->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 3],
            ],
        ])->assertRedirect();

        $this->assertDatabaseCount('carts', 1);
    }

    /**
     * REG0: ticket lama/default tetap berperilaku max 5.
     */
    public function test_old_ticket_without_explicit_max_order_qty_defaults_to_5(): void
    {
        $event = $this->event();
        $harga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Legacy',
            'qty' => 100,
            'harga' => 100000,
            'status' => 'active',
            // No max_order_qty or null
        ]);

        $this->assertSame(5, $harga->maxOrderQty());
    }

    /**
     * REG0: description tampil pada halaman pembelian.
     * Note: This is tested by visual inspection; blade template includes
     * `@if (!empty($hargaItem->description))` with escaped output.
     */
    public function test_ticket_description_field_exists_and_is_nullable(): void
    {
        $event = $this->event();
        $hargaWithDesc = $this->harga($event, ['description' => 'VIP dengan akses backstage']);
        $hargaNoDesc = $this->harga($event, ['kategori' => 'Regular', 'description' => null]);

        $this->assertSame('VIP dengan akses backstage', $hargaWithDesc->description);
        $this->assertNull($hargaNoDesc->description);
    }

    /**
     * REG0: desktop/mobile menggunakan limit kategori, bukan hardcode global.
     * Test verifies that the maxOrderQty() helper returns per-category limits.
     */
    public function test_category_max_order_qty_is_per_category_not_global(): void
    {
        $event = $this->event();
        $categoryA = $this->harga($event, ['kategori' => 'Regular', 'max_order_qty' => 4, 'qty' => 10]);
        $categoryB = $this->harga($event, ['kategori' => 'VIP', 'max_order_qty' => 2, 'qty' => 10]);
        $categoryC = $this->harga($event, ['kategori' => 'Standard', 'qty' => 10]); // defaults to 5

        $this->assertSame(4, $categoryA->maxOrderQty());
        $this->assertSame(2, $categoryB->maxOrderQty());
        $this->assertSame(5, $categoryC->maxOrderQty());
    }

    /**
     * REG0: existing reservation/stock security tetap PASS.
     */
    public function test_reservation_security_after_category_max_implementation(): void
    {
        $user1 = $this->user(['email' => 'user1@test.com']);
        $user2 = $this->user(['email' => 'user2@test.com']);
        $event = $this->event();
        $harga = $this->harga($event, ['max_order_qty' => 5, 'qty' => 8]);

        // User 1 reserves 5
        $this->actingAs($user1)->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 5],
            ],
        ])->assertRedirect();

        $harga->refresh();
        $this->assertSame(5, (int) $harga->reserved_qty);

        // User 2 tries to take remaining 3 (only 3 left in stock after user1)
        $this->actingAs($user2)->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 3],
            ],
        ])->assertRedirect();

        $this->assertDatabaseCount('carts', 2); // Both users have carts

        // But if user2 tries to take 4 when only 3 remain, it should fail
        $user3 = $this->user(['email' => 'user3@test.com']);
        $this->actingAs($user3)->from('/ticket/demo')->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 4],
            ],
        ])->assertRedirect('/ticket/demo');

        $this->assertDatabaseCount('carts', 2); // user3 cart not created
    }

    /**
     * REG0: kategori A max 5 → request 6 = reject.
     */
    public function test_category_max_5_rejects_6(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['max_order_qty' => 5, 'qty' => 10]);

        $this->actingAs($user)->from('/ticket/demo')->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 6],
            ],
        ])->assertRedirect('/ticket/demo');

        $this->assertDatabaseCount('carts', 0);
    }

    /**
     * REG0: kategori B max 2 → request 3 = reject.
     */
    public function test_category_max_2_rejects_3(): void
    {
        $user = $this->user();
        $event = $this->event();
        $harga = $this->harga($event, ['max_order_qty' => 2, 'qty' => 10]);

        $this->actingAs($user)->from('/ticket/demo')->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [
                ['harga_id' => $harga->id, 'quantity' => 3],
            ],
        ])->assertRedirect('/ticket/demo');

        $this->assertDatabaseCount('carts', 0);
    }

    /**
     * REG0: Validasi minimal 1 tiket tetap berlaku.
     */
    public function test_checkout_requires_at_least_one_ticket(): void
    {
        $user = $this->user();
        $event = $this->event();
        $this->harga($event);

        $this->actingAs($user)->from('/ticket/demo')->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [],
        ])->assertRedirect('/ticket/demo');

        $this->assertDatabaseCount('carts', 0);
    }

    /**
     * REG0: max_order_qty persisten di database (tidak hilang pada edit).
     */
    public function test_max_order_qty_persists_on_edit(): void
    {
        $event = $this->event();
        $harga = $this->harga($event, ['max_order_qty' => 7]);

        $harga->update(['kategori' => 'Updated Name']);
        $harga->refresh();

        $this->assertSame(7, (int) $harga->max_order_qty);
    }

    /**
     * REG0: description nullable dan dapat disimpan kosong.
     */
    public function test_description_nullable_and_can_be_empty(): void
    {
        $event = $this->event();
        $harga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'No Desc',
            'qty' => 10,
            'harga' => 50000,
            'max_order_qty' => 3,
            'description' => null,
            'status' => 'active',
        ]);

        $this->assertNull($harga->description);
        $this->assertSame(3, $harga->maxOrderQty());
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
            'max_order_qty' => 5,
        ], $attributes));
    }
}
