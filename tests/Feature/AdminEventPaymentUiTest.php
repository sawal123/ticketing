<?php

namespace Tests\Feature;

use App\Livewire\Admin\EventDetail;
use App\Models\Cart;
use App\Models\Event;
use App\Models\EventPaymentGateway;
use App\Models\Harga;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\Tickets\TicketPricingService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEventPaymentUiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('name');
            $table->string('email');
            $table->string('nomor')->nullable();
            $table->string('birthday')->nullable();
            $table->string('gender')->nullable();
            $table->string('kota')->nullable();
            $table->string('alamat')->nullable();
            $table->string('role');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->string('category_id')->nullable();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event');
            $table->string('alamat');
            $table->string('tanggal');
            $table->string('status');
            $table->string('cover')->nullable();
            $table->unsignedBigInteger('fee')->default(0);
            $table->text('deskripsi');
            $table->text('map')->nullable();
            $table->unsignedBigInteger('pajak')->default(0);
            $table->string('start_sale')->nullable();
            $table->string('slug')->nullable();
            $table->string('konfirmasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('talent', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('talent');
            $table->string('gambar')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });

        Schema::create('hargas', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('kategori');
            $table->unsignedInteger('qty')->default(0);
            $table->unsignedInteger('sold_qty')->default(0);
            $table->unsignedInteger('reserved_qty')->default(0);
            $table->unsignedBigInteger('harga')->default(0);
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
            $table->string('payment_type')->nullable();
            $table->unsignedBigInteger('internet_fee')->default(0);
            $table->unsignedBigInteger('pajak')->default(0);
            $table->unsignedInteger('pajak_persen')->default(0);
            $table->unsignedBigInteger('gross_amount')->nullable();
            $table->unsignedBigInteger('payment_gateway_id')->nullable();
            $table->string('payment_fee_mode')->nullable();
            $table->decimal('payment_fee_fixed', 15, 2)->nullable();
            $table->decimal('payment_fee_percent', 8, 4)->nullable();
            $table->string('konfirmasi')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->unsignedBigInteger('harga_id')->nullable();
            $table->string('event_uid')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedBigInteger('harga_ticket')->default(0);
            $table->string('voucher')->nullable();
            $table->unsignedBigInteger('disc')->default(0);
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
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('event_payment_gateways', function ($table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('payment_gateway_id');
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

    public function test_admin_can_see_payment_tab(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $this->makeGateway();

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->assertSee('Pembayaran')
            ->set('activeTab', 'pembayaran')
            ->assertSet('activeTab', 'pembayaran')
            ->assertSee('Konfigurasi Payment Gateway Event');
    }

    public function test_non_admin_does_not_see_payment_tab(): void
    {
        $user = $this->makeUser('user');
        $event = $this->makeEvent();
        $this->makeGateway();

        Livewire::actingAs($user)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->assertDontSee('Pembayaran')
            ->assertDontSee('Konfigurasi Payment Gateway Event')
            ->set('activeTab', 'pembayaran')
            ->assertSet('activeTab', 'umum');
    }

    public function test_non_admin_cannot_update_event_payment_configuration(): void
    {
        $user = $this->makeUser('user');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        Livewire::actingAs($user)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('saveEventPaymentGateway', $gateway->id)
            ->assertForbidden();

        $this->assertDatabaseCount('event_payment_gateways', 0);
    }

    public function test_non_admin_cannot_toggle_event_payment_configuration(): void
    {
        $user = $this->makeUser('user');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        Livewire::actingAs($user)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('toggleEventPaymentGateway', $gateway->id)
            ->assertForbidden();

        $this->assertDatabaseCount('event_payment_gateways', 0);
    }

    public function test_admin_can_toggle_event_gateway_active_status(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        $component = Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran');

        $component->call('toggleEventPaymentGateway', $gateway->id);

        $this->assertDatabaseHas('event_payment_gateways', [
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => 1,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
        ]);

        $component->call('toggleEventPaymentGateway', $gateway->id);

        $this->assertDatabaseHas('event_payment_gateways', [
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => 0,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
        ]);
    }

    public function test_admin_can_choose_global_fee_mode(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        EventPaymentGateway::create([
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => true,
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 4000,
            'fee_percent' => 3,
        ]);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_mode', EventPaymentGateway::FEE_MODE_GLOBAL)
            ->call('saveEventPaymentGateway', $gateway->id);

        $this->assertDatabaseHas('event_payment_gateways', [
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
        ]);

        $this->assertNull(EventPaymentGateway::where('event_id', $event->id)->where('payment_gateway_id', $gateway->id)->first()->fee_fixed);
        $this->assertNull(EventPaymentGateway::where('event_id', $event->id)->where('payment_gateway_id', $gateway->id)->first()->fee_percent);
    }

    public function test_admin_can_save_manual_fixed_and_percent_fee(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->set('paymentGatewayConfigs.'.$gateway->id.'.is_active', true)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_mode', EventPaymentGateway::FEE_MODE_MANUAL)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_fixed', 4000)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_percent', 3)
            ->call('saveEventPaymentGateway', $gateway->id);

        $this->assertDatabaseHas('event_payment_gateways', [
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => 1,
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 4000,
            'fee_percent' => 3,
        ]);
    }

    public function test_zero_manual_fee_values_are_valid(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_mode', EventPaymentGateway::FEE_MODE_MANUAL)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_fixed', 0)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_percent', 0)
            ->call('saveEventPaymentGateway', $gateway->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_payment_gateways', [
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 0,
            'fee_percent' => 0,
        ]);
    }

    public function test_negative_fee_values_are_rejected(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_mode', EventPaymentGateway::FEE_MODE_MANUAL)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_fixed', -1)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_percent', -3)
            ->call('saveEventPaymentGateway', $gateway->id)
            ->assertHasErrors([
                'paymentGatewayConfigs.'.$gateway->id.'.fee_fixed',
                'paymentGatewayConfigs.'.$gateway->id.'.fee_percent',
            ]);

        $this->assertDatabaseCount('event_payment_gateways', 0);
    }

    public function test_fee_values_with_too_many_decimals_are_rejected(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_mode', EventPaymentGateway::FEE_MODE_MANUAL)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_fixed', '4000.123')
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_percent', '3.12345')
            ->call('saveEventPaymentGateway', $gateway->id)
            ->assertHasErrors([
                'paymentGatewayConfigs.'.$gateway->id.'.fee_fixed',
                'paymentGatewayConfigs.'.$gateway->id.'.fee_percent',
            ]);
    }

    public function test_fee_values_exceeding_database_capacity_are_rejected(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_mode', EventPaymentGateway::FEE_MODE_MANUAL)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_fixed', '10000000000000.00')
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_percent', '10000.0000')
            ->call('saveEventPaymentGateway', $gateway->id)
            ->assertHasErrors([
                'paymentGatewayConfigs.'.$gateway->id.'.fee_fixed',
                'paymentGatewayConfigs.'.$gateway->id.'.fee_percent',
            ]);
    }

    public function test_payment_configuration_changes_only_affect_the_open_event(): void
    {
        $admin = $this->makeUser('admin');
        $eventA = $this->makeEvent(['uid' => 'event-a', 'slug' => 'event-a']);
        $eventB = $this->makeEvent(['uid' => 'event-b', 'slug' => 'event-b']);
        $gateway = $this->makeGateway();

        EventPaymentGateway::create([
            'event_id' => $eventB->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => false,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
            'fee_fixed' => null,
            'fee_percent' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $eventA->uid])
            ->set('activeTab', 'pembayaran')
            ->set('paymentGatewayConfigs.'.$gateway->id.'.is_active', true)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_mode', EventPaymentGateway::FEE_MODE_MANUAL)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_fixed', 2500)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_percent', 2)
            ->call('saveEventPaymentGateway', $gateway->id);

        $this->assertDatabaseHas('event_payment_gateways', [
            'event_id' => $eventA->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => 1,
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 2500,
            'fee_percent' => 2,
        ]);

        $this->assertDatabaseHas('event_payment_gateways', [
            'event_id' => $eventB->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => 0,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
        ]);
    }

    public function test_globally_inactive_gateway_stays_unavailable_even_when_event_config_is_active(): void
    {
        $admin = $this->makeUser('admin');
        $buyer = $this->makeUser('user');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway([
            'is_active' => false,
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 3,
        ]);
        $cart = $this->makeCart($buyer, $event);
        $harga = $this->makeHarga($event, 100000);
        $this->makeHargaCart($cart, $harga);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->set('paymentGatewayConfigs.'.$gateway->id.'.is_active', true)
            ->call('saveEventPaymentGateway', $gateway->id);

        $pricing = app(TicketPricingService::class)->calculateCart($cart->fresh(), $gateway->fresh());

        $this->assertFalse($pricing['payment_gateway_available']);
        $this->assertSame(0, $pricing['internet_fee']);
    }

    public function test_global_payment_gateway_fee_does_not_change_when_event_config_changes(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway([
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 3,
        ]);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_mode', EventPaymentGateway::FEE_MODE_MANUAL)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_fixed', 4500)
            ->set('paymentGatewayConfigs.'.$gateway->id.'.fee_percent', 1.5)
            ->call('saveEventPaymentGateway', $gateway->id);

        $gateway->refresh();

        $this->assertSame('2000.00', $gateway->default_fee_fixed);
        $this->assertSame('3.0000', $gateway->default_fee_percent);
    }

    private function makeUser(string $role): User
    {
        static $counter = 1;
        $current = $counter++;

        return User::create([
            'uid' => $role.'-'.$current,
            'name' => ucfirst($role).' '.$current,
            'email' => $role.$current.'@example.test',
            'nomor' => '0812345678'.$current,
            'birthday' => '2000-01-01',
            'gender' => 'pria',
            'kota' => 'Jakarta',
            'alamat' => 'Alamat '.$current,
            'role' => $role,
            'password' => bcrypt('password'),
        ]);
    }

    private function makeEvent(array $overrides = []): Event
    {
        static $counter = 1;
        $current = $counter++;

        return Event::create(array_merge([
            'category_id' => null,
            'uid' => 'event-'.$current,
            'user_uid' => 'owner-'.$current,
            'event' => 'Event '.$current,
            'alamat' => 'Venue '.$current,
            'tanggal' => '2026-08-01 19:00',
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => null,
            'pajak' => 0,
            'start_sale' => '2026-07-01 10:00',
            'slug' => 'event-'.$current,
            'konfirmasi' => '1',
        ], $overrides));
    }

    private function makeGateway(array $overrides = []): PaymentGateway
    {
        static $counter = 1;
        $current = $counter++;

        return PaymentGateway::create(array_merge([
            'payment' => 'Gateway '.$current,
            'category' => 'bank',
            'biaya' => 0,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => 0,
            'default_fee_percent' => 0,
            'midtrans_code' => null,
            'icon' => null,
            'is_active' => true,
            'slug' => 'gateway-'.$current,
        ], $overrides));
    }

    private function makeCart(User $buyer, Event $event): Cart
    {
        return Cart::create([
            'uid' => 'cart-'.$buyer->uid.'-'.$event->uid,
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.$buyer->uid.'-'.$event->uid,
            'status' => Cart::STATUS_RESERVED,
            'payment_type' => null,
            'internet_fee' => 0,
            'pajak' => 0,
            'pajak_persen' => 0,
            'gross_amount' => null,
            'payment_gateway_id' => null,
            'payment_fee_mode' => null,
            'payment_fee_fixed' => null,
            'payment_fee_percent' => null,
            'konfirmasi' => null,
            'scanned_at' => null,
        ]);
    }

    private function makeHarga(Event $event, int $price): Harga
    {
        return Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Regular',
            'qty' => 100,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => $price,
            'status' => 'active',
        ]);
    }

    private function makeHargaCart(Cart $cart, Harga $harga): void
    {
        DB::table('harga_carts')->insert([
            'uid' => $cart->uid,
            'harga_id' => $harga->id,
            'event_uid' => $cart->event_uid,
            'quantity' => 1,
            'harga_ticket' => $harga->harga,
            'voucher' => null,
            'disc' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
