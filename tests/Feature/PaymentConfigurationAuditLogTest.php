<?php

namespace Tests\Feature;

use App\Livewire\Admin\ActivityIndex;
use App\Livewire\Admin\EventDetail;
use App\Livewire\Admin\PaymentGatewayIndex;
use App\Models\ActivityLog;
use App\Models\Cart;
use App\Models\Event;
use App\Models\EventPaymentGateway;
use App\Models\Harga;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\Payments\PaymentConfigurationAuditService;
use App\Services\Tickets\TicketPricingService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentConfigurationAuditLogTest extends TestCase
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
            $table->boolean('payment_otp_enabled')->default(false);
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

        Schema::create('activity_logs', function ($table) {
            $table->id();
            $table->string('user_uid')->nullable();
            $table->string('activity');
            $table->string('audit_category')->nullable();
            $table->string('action_key')->nullable();
            $table->string('login_status')->nullable();
            $table->text('description')->nullable();
            $table->string('impact_level')->default('Normal');
            $table->string('event_uid')->nullable();
            $table->unsignedBigInteger('payment_gateway_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('location')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_id')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_global_gateway_status_change_creates_payment_audit_with_actor(): void
    {
        $admin = $this->makeUser('admin');
        $gateway = $this->makeGateway(['is_active' => true]);

        Livewire::actingAs($admin)
            ->test(PaymentGatewayIndex::class)
            ->call('toggleStatus', $gateway->id)
            ->assertHasNoErrors();

        $log = ActivityLog::query()->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame('payment', $log->audit_category);
        $this->assertSame('payment_gateway_status_updated', $log->action_key);
        $this->assertSame($admin->uid, $log->user_uid);
        $this->assertSame('Sensitif', $log->impact_level);
        $this->assertSame($gateway->id, $log->payment_gateway_id);
        $this->assertSame(['is_active' => true], $log->old_values);
        $this->assertSame(['is_active' => false], $log->new_values);
        $this->assertFalse((bool) $gateway->fresh()->is_active);
    }

    public function test_global_fee_update_stores_structured_old_and_new_values(): void
    {
        $admin = $this->makeUser('admin');
        $gateway = $this->makeGateway([
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 3,
            'midtrans_code' => 'bca_va',
            'biaya' => 9999,
            'biaya_type' => 'persen',
        ]);

        Livewire::actingAs($admin)
            ->test(PaymentGatewayIndex::class)
            ->call('edit', $gateway->id)
            ->set('default_fee_fixed', '4000')
            ->set('default_fee_percent', '1')
            ->set('midtrans_code', 'bca_va')
            ->call('save')
            ->assertHasNoErrors();

        $log = ActivityLog::query()->latest()->first();

        $this->assertSame('payment_gateway_fee_updated', $log->action_key);
        $this->assertSame([
            'default_fee_fixed' => '2000.00',
            'default_fee_percent' => '3.0000',
        ], $log->old_values);
        $this->assertSame([
            'default_fee_fixed' => '4000.00',
            'default_fee_percent' => '1.0000',
        ], $log->new_values);
        $this->assertSame('9999.00', $gateway->fresh()->biaya);
        $this->assertSame('persen', $gateway->fresh()->biaya_type);
    }

    public function test_global_fee_audit_preserves_legacy_negative_persisted_value(): void
    {
        $admin = $this->makeUser('admin');
        $gateway = $this->makeGateway([
            'default_fee_fixed' => -2000,
            'default_fee_percent' => 0,
            'midtrans_code' => 'bca_va',
        ]);

        Livewire::actingAs($admin)
            ->test(PaymentGatewayIndex::class)
            ->call('edit', $gateway->id)
            ->set('default_fee_fixed', '3000')
            ->set('default_fee_percent', '0')
            ->set('midtrans_code', 'bca_va')
            ->call('save')
            ->assertHasNoErrors();

        $log = ActivityLog::query()->latest()->first();

        $this->assertSame('payment_gateway_fee_updated', $log->action_key);
        $this->assertSame('-2000.00', $log->old_values['default_fee_fixed']);
        $this->assertSame('3000.00', $log->new_values['default_fee_fixed']);
        $this->assertSame('3000.00', $gateway->fresh()->default_fee_fixed);
    }

    public function test_midtrans_code_update_stores_structured_old_and_new_values(): void
    {
        $admin = $this->makeUser('admin');
        $gateway = $this->makeGateway([
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 3,
            'midtrans_code' => 'gopay',
        ]);

        Livewire::actingAs($admin)
            ->test(PaymentGatewayIndex::class)
            ->call('edit', $gateway->id)
            ->set('default_fee_fixed', '2000')
            ->set('default_fee_percent', '3')
            ->set('midtrans_code', 'other_qris')
            ->call('save')
            ->assertHasNoErrors();

        $log = ActivityLog::query()->latest()->first();

        $this->assertSame('payment_gateway_midtrans_code_updated', $log->action_key);
        $this->assertSame(['midtrans_code' => 'gopay'], $log->old_values);
        $this->assertSame(['midtrans_code' => 'other_qris'], $log->new_values);
        $this->assertSame('other_qris', $gateway->fresh()->midtrans_code);
    }

    public function test_event_gateway_toggle_creates_payment_audit_with_event_and_gateway_context(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->call('toggleEventPaymentGateway', $gateway->id)
            ->assertHasNoErrors();

        $log = ActivityLog::query()->latest()->first();
        $config = EventPaymentGateway::query()->first();

        $this->assertSame('event_payment_gateway_status_updated', $log->action_key);
        $this->assertSame($event->uid, $log->event_uid);
        $this->assertSame($gateway->id, $log->payment_gateway_id);
        $this->assertSame(['is_active' => false], $log->old_values);
        $this->assertSame(['is_active' => true], $log->new_values);
        $this->assertTrue((bool) $config->is_active);
    }

    public function test_event_gateway_manual_fee_save_creates_structured_audit_and_preserves_fee_resolution(): void
    {
        $admin = $this->makeUser('admin');
        $buyer = $this->makeUser('user');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway([
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 3,
        ]);

        $historicalCart = $this->makeCart($buyer, $event, [
            'status' => Cart::STATUS_PENDING,
            'payment_gateway_id' => $gateway->id,
            'payment_fee_mode' => 'global',
            'payment_fee_fixed' => 2000,
            'payment_fee_percent' => 3,
            'internet_fee' => 5000,
            'gross_amount' => 105000,
        ]);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->set("paymentGatewayConfigs.{$gateway->id}.is_active", true)
            ->set("paymentGatewayConfigs.{$gateway->id}.fee_mode", 'manual')
            ->set("paymentGatewayConfigs.{$gateway->id}.fee_fixed", '4000')
            ->set("paymentGatewayConfigs.{$gateway->id}.fee_percent", '3')
            ->call('saveEventPaymentGateway', $gateway->id)
            ->assertHasNoErrors();

        $log = ActivityLog::query()->latest()->first();
        $config = EventPaymentGateway::query()->first();

        $this->assertSame('event_payment_gateway_updated', $log->action_key);
        $this->assertSame($event->uid, $log->event_uid);
        $this->assertSame($gateway->id, $log->payment_gateway_id);
        $this->assertSame([
            'fee_fixed' => null,
            'fee_mode' => 'global',
            'fee_percent' => null,
            'is_active' => false,
        ], $log->old_values);
        $this->assertSame([
            'fee_fixed' => '4000.00',
            'fee_mode' => 'manual',
            'fee_percent' => '3.0000',
            'is_active' => true,
        ], $log->new_values);
        $this->assertSame('manual', $config->fee_mode);
        $this->assertSame('4000.00', $config->fee_fixed);
        $this->assertSame('3.0000', $config->fee_percent);

        $cart = $this->makeCart($buyer, $event);
        $harga = $this->makeHarga($event, 100000);
        $this->makeHargaCart($cart, $harga);

        $pricing = app(TicketPricingService::class)->calculateCart($cart->fresh(), $gateway->fresh());

        $this->assertTrue($pricing['payment_gateway_available']);
        $this->assertSame('manual', $pricing['payment_fee_mode']);
        $this->assertSame('4000.00', $pricing['payment_fee_fixed']);
        $this->assertSame('3.0000', $pricing['payment_fee_percent']);
        $this->assertSame(7000, $pricing['internet_fee']);
        $this->assertSame(105000, (int) $historicalCart->fresh()->gross_amount);
        $this->assertSame('2000.00', $historicalCart->fresh()->payment_fee_fixed);
        $this->assertSame('3.0000', $historicalCart->fresh()->payment_fee_percent);
        $this->assertSame($gateway->id, $historicalCart->fresh()->payment_gateway_id);
    }

    public function test_event_payment_otp_setting_on_and_off_creates_audit_without_secret_fields(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent(['payment_otp_enabled' => false]);
        $this->makeGateway();

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->set('paymentOtpEnabled', true)
            ->call('updatePaymentOtpSetting')
            ->assertHasNoErrors();

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->set('paymentOtpEnabled', false)
            ->call('updatePaymentOtpSetting')
            ->assertHasNoErrors();

        $logs = ActivityLog::query()
            ->where('action_key', 'event_payment_otp_updated')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $logs);
        $this->assertSame(['payment_otp_enabled' => false], $logs[0]->old_values);
        $this->assertSame(['payment_otp_enabled' => true], $logs[0]->new_values);
        $this->assertSame(['payment_otp_enabled' => true], $logs[1]->old_values);
        $this->assertSame(['payment_otp_enabled' => false], $logs[1]->new_values);
        $this->assertArrayNotHasKey('code_hash', $logs[0]->old_values);
        $this->assertArrayNotHasKey('email', $logs[0]->new_values);
    }

    public function test_save_without_changes_does_not_create_payment_audit(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->call('saveEventPaymentGateway', $gateway->id)
            ->assertHasNoErrors();

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_unauthorized_user_and_validation_failure_do_not_create_payment_audit(): void
    {
        $user = $this->makeUser('user');
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        Livewire::actingAs($user)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('toggleEventPaymentGateway', $gateway->id)
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->set("paymentGatewayConfigs.{$gateway->id}.is_active", true)
            ->set("paymentGatewayConfigs.{$gateway->id}.fee_mode", 'manual')
            ->set("paymentGatewayConfigs.{$gateway->id}.fee_fixed", '-1')
            ->set("paymentGatewayConfigs.{$gateway->id}.fee_percent", '3')
            ->call('saveEventPaymentGateway', $gateway->id)
            ->assertHasErrors(["paymentGatewayConfigs.{$gateway->id}.fee_fixed"]);

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_payment_activity_can_be_filtered_without_hiding_existing_non_payment_logs(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        ActivityLog::create([
            'user_uid' => $admin->uid,
            'activity' => 'Legacy Activity',
            'login_status' => 'Success',
            'description' => 'Existing non-payment activity',
            'impact_level' => 'Normal',
            'ip_address' => '127.0.0.1',
            'location' => 'Unknown',
            'user_agent' => 'Feature Test',
        ]);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'pembayaran')
            ->set("paymentGatewayConfigs.{$gateway->id}.is_active", true)
            ->call('saveEventPaymentGateway', $gateway->id)
            ->assertHasNoErrors();

        Livewire::actingAs($admin)
            ->test(ActivityIndex::class)
            ->assertSee('Existing non-payment activity')
            ->assertSee('Payment Configuration')
            ->set('filterCategory', 'payment')
            ->assertDontSee('Existing non-payment activity')
            ->assertSee('event_payment_gateway_updated')
            ->assertSee($event->uid)
            ->assertSee($gateway->payment);
    }

    public function test_audit_failure_rolls_back_payment_configuration_mutation(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->makeEvent();
        $gateway = $this->makeGateway();

        app()->instance(PaymentConfigurationAuditService::class, new class extends PaymentConfigurationAuditService
        {
            public function record(
                User $actor,
                string $actionKey,
                array $oldValues,
                array $newValues,
                ?Event $event = null,
                ?PaymentGateway $gateway = null,
                ?string $description = null
            ): ?ActivityLog {
                throw new \RuntimeException('Audit insert failed.');
            }
        });

        try {
            Livewire::actingAs($admin)
                ->test(EventDetail::class, ['uid' => $event->uid])
                ->set('activeTab', 'pembayaran')
                ->call('toggleEventPaymentGateway', $gateway->id);

            $this->fail('Audit failure was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Audit insert failed.', $exception->getMessage());
        }

        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertDatabaseCount('event_payment_gateways', 0);
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
            'midtrans_code' => 'bca_va',
            'icon' => null,
            'is_active' => true,
            'slug' => 'gateway-'.$current,
        ], $overrides));
    }

    private function makeCart(User $buyer, Event $event, array $overrides = []): Cart
    {
        static $counter = 1;
        $current = $counter++;

        return Cart::create(array_merge([
            'uid' => 'cart-'.$buyer->uid.'-'.$event->uid.'-'.$current,
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.$buyer->uid.'-'.$event->uid.'-'.$current,
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
        ], $overrides));
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
