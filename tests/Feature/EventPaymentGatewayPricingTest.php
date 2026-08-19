<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Models\Cart;
use App\Models\Event;
use App\Models\EventPaymentGateway;
use App\Models\Harga;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Tickets\TicketPricingService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class EventPaymentGatewayPricingTest extends TestCase
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
        Config::set('gate-tokens.key', 'base64:'.base64_encode(str_repeat('w', 32)));

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
    }

    public function test_global_fee_supports_fixed_and_percent(): void
    {
        $event = $this->event();
        $cart = $this->cart($this->user(), $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 3,
        ]);
        $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
            'is_active' => true,
        ]);

        $pricing = app(TicketPricingService::class)->calculateCart($cart, $gateway);

        $this->assertTrue($pricing['payment_gateway_available']);
        $this->assertSame('global', $pricing['payment_fee_mode']);
        $this->assertSame('2000.00', $pricing['payment_fee_fixed']);
        $this->assertSame('3.0000', $pricing['payment_fee_percent']);
        $this->assertSame(5000, $pricing['internet_fee']);
    }

    public function test_manual_fee_supports_fixed_and_percent_and_overrides_global(): void
    {
        $event = $this->event();
        $cart = $this->cart($this->user(), $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'default_fee_fixed' => 500,
            'default_fee_percent' => 1,
        ]);
        $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 2000,
            'fee_percent' => 3,
        ]);

        $pricing = app(TicketPricingService::class)->calculateCart($cart, $gateway);

        $this->assertSame('manual', $pricing['payment_fee_mode']);
        $this->assertSame('2000.00', $pricing['payment_fee_fixed']);
        $this->assertSame('3.0000', $pricing['payment_fee_percent']);
        $this->assertSame(5000, $pricing['internet_fee']);
    }

    public function test_fixed_only_fee_supported(): void
    {
        $event = $this->event();
        $cart = $this->cart($this->user(), $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'default_fee_fixed' => 4000,
            'default_fee_percent' => 0,
        ]);
        $this->eventGateway($event, $gateway);

        $pricing = app(TicketPricingService::class)->calculateCart($cart, $gateway);

        $this->assertSame(4000, $pricing['internet_fee']);
    }

    public function test_percent_only_fee_supported(): void
    {
        $event = $this->event();
        $cart = $this->cart($this->user(), $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'default_fee_fixed' => 0,
            'default_fee_percent' => 3,
        ]);
        $this->eventGateway($event, $gateway);

        $pricing = app(TicketPricingService::class)->calculateCart($cart, $gateway);

        $this->assertSame(3000, $pricing['internet_fee']);
    }

    public function test_zero_fee_supported(): void
    {
        $event = $this->event();
        $cart = $this->cart($this->user(), $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'default_fee_fixed' => 0,
            'default_fee_percent' => 0,
            'biaya' => 0,
        ]);
        $this->eventGateway($event, $gateway);

        $pricing = app(TicketPricingService::class)->calculateCart($cart, $gateway);

        $this->assertSame(0, $pricing['internet_fee']);
    }

    public function test_zero_default_fee_does_not_fallback_to_legacy_biaya(): void
    {
        $event = $this->event();
        $cart = $this->cart($this->user(), $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'default_fee_fixed' => 0,
            'default_fee_percent' => 0,
            'biaya' => 4000,
            'biaya_type' => 'rupiah',
        ]);
        $this->eventGateway($event, $gateway);

        $pricing = app(TicketPricingService::class)->calculateCart($cart, $gateway);

        $this->assertSame('0.00', $pricing['payment_fee_fixed']);
        $this->assertSame('0.0000', $pricing['payment_fee_percent']);
        $this->assertSame(0, $pricing['internet_fee']);
    }

    public function test_legacy_fee_fallback_still_works_when_new_default_fee_values_are_missing(): void
    {
        $gateway = new PaymentGateway([
            'payment' => 'Legacy Gateway',
            'category' => 'bank',
            'biaya' => 4000,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => null,
            'default_fee_percent' => null,
            'is_active' => true,
            'slug' => 'legacy-gateway',
        ]);

        $this->assertSame(4000, app(TicketPricingService::class)->internetFee($gateway, 100000));
    }

    public function test_inactive_event_gateway_is_rejected_in_paynow(): void
    {
        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => false]);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/paynow', [
                'cart_uid' => $cart->uid,
                'payment_gateway_id' => $gateway->id,
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $this->assertSame(Cart::STATUS_RESERVED, $cart->fresh()->status);
        $this->assertNull($cart->fresh()->payment_gateway_id);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_inactive_global_gateway_is_rejected_even_if_event_configuration_is_active(): void
    {
        $event = $this->event();
        $cart = $this->cart($this->user(), $event);
        $harga = $this->harga($event);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway(['is_active' => false]);
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $pricing = app(TicketPricingService::class)->calculateCart($cart, $gateway);

        $this->assertFalse($pricing['payment_gateway_available']);
        $this->assertSame(0, $pricing['internet_fee']);
        $this->assertNull($pricing['payment_fee_mode']);
    }

    public function test_paynow_stores_fee_snapshot_and_keeps_it_immutable_after_configuration_changes(): void
    {
        $this->fakeMidtransRedirect();

        $user = $this->user();
        $event = $this->event(['fee' => 10]);
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'default_fee_fixed' => 1000,
            'default_fee_percent' => 1,
        ]);
        $eventGateway = $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 2000,
            'fee_percent' => 3,
        ]);

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
        ])->assertRedirect('https://pay.example.test/snap');

        $cart->refresh();

        $this->assertSame(Cart::STATUS_PENDING, $cart->status);
        $this->assertSame($gateway->id, $cart->payment_gateway_id);
        $this->assertSame('manual', $cart->payment_fee_mode);
        $this->assertSame('2000.00', $cart->payment_fee_fixed);
        $this->assertSame('3.0000', $cart->payment_fee_percent);
        $this->assertSame(10, (int) $cart->pajak_persen);
        $this->assertSame(10000, (int) $cart->pajak);
        $this->assertSame(5000, (int) $cart->internet_fee);
        $this->assertSame(115000, (int) $cart->gross_amount);
        $this->assertSame(115000, (int) Transaction::first()->gross_amount);

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
        $this->assertSame('manual', $pricing['payment_fee_mode']);
        $this->assertSame('2000.00', $pricing['payment_fee_fixed']);
        $this->assertSame('3.0000', $pricing['payment_fee_percent']);
        $this->assertSame('manual', $cart->fresh()->payment_fee_mode);
        $this->assertSame('2000.00', $cart->fresh()->payment_fee_fixed);
        $this->assertSame('3.0000', $cart->fresh()->payment_fee_percent);
    }

    public function test_percent_fee_uses_subtotal_after_voucher_discount(): void
    {
        $event = $this->event();
        $cart = $this->cart($this->user(), $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $voucher = $this->voucher($event);
        DB::table('cart_vouchers')->insert([
            'uid' => $cart->uid,
            'uid_vouchers' => $voucher->uid,
            'user_uid' => $cart->user_uid,
            'event_uid' => $event->uid,
            'code' => $voucher->code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $gateway = $this->gateway([
            'default_fee_fixed' => 0,
            'default_fee_percent' => 3,
        ]);
        $this->eventGateway($event, $gateway);

        $pricing = app(TicketPricingService::class)->calculateCart($cart, $gateway);

        $this->assertSame(10000, $pricing['discount']);
        $this->assertSame(90000, $pricing['subtotal']);
        $this->assertSame(2700, $pricing['internet_fee']);
    }

    public function test_negative_global_fixed_fee_is_normalized_to_zero(): void
    {
        $event = $this->event();
        $cart = $this->cart($this->user(), $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'default_fee_fixed' => -4000,
            'default_fee_percent' => 0,
        ]);
        $this->eventGateway($event, $gateway);

        $pricing = app(TicketPricingService::class)->calculateCart($cart, $gateway);

        $this->assertSame('0.00', $pricing['payment_fee_fixed']);
        $this->assertSame('0.0000', $pricing['payment_fee_percent']);
        $this->assertSame(0, $pricing['internet_fee']);
    }

    public function test_negative_global_percent_fee_is_normalized_to_zero(): void
    {
        $event = $this->event();
        $cart = $this->cart($this->user(), $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'default_fee_fixed' => 2000,
            'default_fee_percent' => -3,
        ]);
        $this->eventGateway($event, $gateway);

        $pricing = app(TicketPricingService::class)->calculateCart($cart, $gateway);

        $this->assertSame('2000.00', $pricing['payment_fee_fixed']);
        $this->assertSame('0.0000', $pricing['payment_fee_percent']);
        $this->assertSame(2000, $pricing['internet_fee']);
    }

    public function test_negative_manual_fee_values_are_normalized_to_zero(): void
    {
        $event = $this->event();
        $cart = $this->cart($this->user(), $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'default_fee_fixed' => 1000,
            'default_fee_percent' => 2,
        ]);
        $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => -2000,
            'fee_percent' => -3,
        ]);

        $pricing = app(TicketPricingService::class)->calculateCart($cart, $gateway);

        $this->assertSame('manual', $pricing['payment_fee_mode']);
        $this->assertSame('0.00', $pricing['payment_fee_fixed']);
        $this->assertSame('0.0000', $pricing['payment_fee_percent']);
        $this->assertSame(0, $pricing['internet_fee']);
    }

    public function test_negative_fee_is_saved_as_zero_in_snapshot_during_paynow(): void
    {
        $this->fakeMidtransRedirect();

        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'default_fee_fixed' => -4000,
            'default_fee_percent' => -3,
        ]);
        $this->eventGateway($event, $gateway);

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
        ])->assertRedirect('https://pay.example.test/snap');

        $cart->refresh();

        $this->assertSame('0.00', $cart->payment_fee_fixed);
        $this->assertSame('0.0000', $cart->payment_fee_percent);
        $this->assertSame(0, (int) $cart->internet_fee);
    }

    public function test_positive_fee_values_still_produce_the_same_nominal(): void
    {
        $event = $this->event();
        $cart = $this->cart($this->user(), $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 3,
        ]);
        $this->eventGateway($event, $gateway);

        $pricing = app(TicketPricingService::class)->calculateCart($cart, $gateway);

        $this->assertSame('2000.00', $pricing['payment_fee_fixed']);
        $this->assertSame('3.0000', $pricing['payment_fee_percent']);
        $this->assertSame(5000, $pricing['internet_fee']);
    }

    public function test_new_cart_without_payment_snapshot_uses_current_event_tax_configuration(): void
    {
        $event = $this->event(['fee' => 10]);
        $event->update(['fee' => 15]);
        $cart = $this->cart($this->user(), $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);

        $pricing = app(TicketPricingService::class)->calculateCart($cart->fresh());

        $this->assertSame(15, $pricing['tax_percent']);
        $this->assertSame(15000, $pricing['tax_amount']);
        $this->assertSame(115000, $pricing['gross_amount']);
    }

    public function test_calculate_cart_without_gateway_uses_stored_snapshot_instead_of_latest_configuration(): void
    {
        $event = $this->event();
        $gateway = $this->gateway([
            'slug' => 'bca',
            'default_fee_fixed' => 9000,
            'default_fee_percent' => 9,
        ]);
        $eventGateway = $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 8000,
            'fee_percent' => 8,
        ]);
        $cart = $this->cart($this->user(), $event, [
            'status' => Cart::STATUS_PENDING,
            'payment_type' => 'bca',
            'payment_gateway_id' => $gateway->id,
            'payment_fee_mode' => 'manual',
            'payment_fee_fixed' => 2000,
            'payment_fee_percent' => 3,
            'internet_fee' => 5000,
            'gross_amount' => 105000,
        ]);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);

        $gateway->update([
            'default_fee_fixed' => 12000,
            'default_fee_percent' => 12,
        ]);
        $eventGateway->update([
            'fee_fixed' => 11000,
            'fee_percent' => 11,
        ]);

        $pricing = app(TicketPricingService::class)->calculateCart($cart->fresh());

        $this->assertSame('manual', $pricing['payment_fee_mode']);
        $this->assertSame('2000.00', $pricing['payment_fee_fixed']);
        $this->assertSame('3.0000', $pricing['payment_fee_percent']);
        $this->assertSame(5000, $pricing['internet_fee']);
        $this->assertSame(105000, $pricing['gross_amount']);
    }

    public function test_partial_legacy_snapshot_with_payment_gateway_id_is_not_treated_as_complete_snapshot(): void
    {
        $event = $this->event(['fee' => 10]);
        $gateway = $this->gateway([
            'default_fee_fixed' => 9000,
            'default_fee_percent' => 9,
        ]);
        $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 8000,
            'fee_percent' => 8,
        ]);
        $cart = $this->cart($this->user(), $event, [
            'status' => Cart::STATUS_PENDING,
            'payment_type' => 'bank_transfer',
            'payment_gateway_id' => $gateway->id,
            'internet_fee' => 7200,
            'payment_fee_mode' => null,
            'payment_fee_fixed' => null,
            'payment_fee_percent' => null,
            'gross_amount' => null,
            'pajak' => 0,
            'pajak_persen' => 0,
        ]);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);

        $pricing = app(TicketPricingService::class)->calculateCart($cart->fresh());

        $this->assertSame(10, $pricing['tax_percent']);
        $this->assertSame(10000, $pricing['tax_amount']);
        $this->assertSame(7200, $pricing['internet_fee']);
        $this->assertSame(117200, $pricing['gross_amount']);
        $this->assertNull($pricing['payment_fee_mode']);
        $this->assertNull($pricing['payment_fee_fixed']);
        $this->assertNull($pricing['payment_fee_percent']);
    }

    public function test_legacy_cart_with_partial_snapshot_keeps_internet_fee_and_falls_back_for_tax_and_gross_amount(): void
    {
        $event = $this->event(['fee' => 10]);
        $cart = $this->cart($this->user(), $event, [
            'status' => Cart::STATUS_PENDING,
            'internet_fee' => 7200,
            'payment_type' => 'bank_transfer',
            'payment_fee_mode' => null,
            'payment_fee_fixed' => null,
            'payment_fee_percent' => null,
            'gross_amount' => null,
            'pajak' => 0,
            'pajak_persen' => 0,
        ]);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);

        $pricing = app(TicketPricingService::class)->calculateCart($cart->fresh());

        $this->assertSame(10, $pricing['tax_percent']);
        $this->assertSame(10000, $pricing['tax_amount']);
        $this->assertSame(7200, $pricing['internet_fee']);
        $this->assertSame(117200, $pricing['gross_amount']);
        $this->assertNull($pricing['payment_fee_mode']);
        $this->assertNull($pricing['payment_fee_fixed']);
        $this->assertNull($pricing['payment_fee_percent']);
    }

    public function test_checkout_preview_fee_matches_paynow_snapshot_and_ignores_legacy_biaya_fields(): void
    {
        $this->fakeMidtransRedirect();

        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $voucher = $this->voucher($event);
        DB::table('cart_vouchers')->insert([
            'uid' => $cart->uid,
            'uid_vouchers' => $voucher->uid,
            'user_uid' => $cart->user_uid,
            'event_uid' => $event->uid,
            'code' => $voucher->code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $gateway = $this->gateway([
            'default_fee_fixed' => 0,
            'default_fee_percent' => 3,
            'biaya' => 9900,
            'biaya_type' => 'rupiah',
        ]);
        $this->eventGateway($event, $gateway);
        $cart->update([
            'payment_gateway_id' => $gateway->id,
            'payment_type' => $gateway->slug,
        ]);

        $this->actingAs($user)
            ->get('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertOk()
            ->assertViewHas('payment', function ($payment) use ($gateway) {
                $previewGateway = collect($payment)->firstWhere('id', $gateway->id);

                return $previewGateway
                    && (int) $previewGateway->resolved_internet_fee === 2700;
            })
            ->assertViewHas('grandTotal', 92700);

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
        ])->assertRedirect('https://pay.example.test/snap');

        $cart->refresh();

        $this->assertSame(2700, (int) $cart->internet_fee);
        $this->assertSame(92700, (int) $cart->gross_amount);
    }

    public function test_detail_ticket_pending_cart_uses_gross_amount_snapshot_and_stored_fee(): void
    {
        $user = $this->user();
        $event = $this->event(['fee' => 25]);
        $cart = $this->cart($user, $event, [
            'status' => Cart::STATUS_PENDING,
            'payment_type' => 'bank_transfer',
            'payment_gateway_id' => null,
            'payment_fee_mode' => 'manual',
            'payment_fee_fixed' => 2000,
            'payment_fee_percent' => 3,
            'internet_fee' => 5000,
            'pajak' => 10000,
            'pajak_persen' => 10,
            'gross_amount' => 115000,
        ]);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);

        $this->actingAs($user)
            ->get('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertOk()
            ->assertViewHas('selectInternetFee', 5000)
            ->assertViewHas('nilaiPajak', 10000)
            ->assertViewHas('pajakPersen', 10)
            ->assertViewHas('grandTotal', 115000);
    }

    public function test_existing_snapshot_values_are_returned_as_stored_without_re_normalization(): void
    {
        $event = $this->event();
        $cart = $this->cart($this->user(), $event, [
            'status' => Cart::STATUS_PENDING,
            'payment_type' => 'bank_transfer',
            'payment_fee_mode' => 'manual',
            'payment_fee_fixed' => -1500,
            'payment_fee_percent' => -2.5,
            'internet_fee' => -3500,
            'gross_amount' => 96500,
        ]);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);

        $pricing = app(TicketPricingService::class)->calculateCart($cart->fresh());

        $this->assertSame('manual', $pricing['payment_fee_mode']);
        $this->assertSame('-1500.00', $pricing['payment_fee_fixed']);
        $this->assertSame('-2.5000', $pricing['payment_fee_percent']);
        $this->assertSame(-3500, $pricing['internet_fee']);
        $this->assertSame(96500, $pricing['gross_amount']);
    }

    public function test_global_active_and_event_active_gateway_is_available_in_checkout_and_paynow(): void
    {
        $this->fakeMidtransRedirect();

        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway([
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 3,
        ]);
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $this->actingAs($user)
            ->get('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertOk()
            ->assertSee($gateway->payment)
            ->assertViewHas('hasAvailablePaymentGateways', true);

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
        ])->assertRedirect('https://pay.example.test/snap');

        $this->assertSame(Cart::STATUS_PENDING, $cart->fresh()->status);
        $this->assertSame($gateway->id, $cart->fresh()->payment_gateway_id);
    }

    public function test_inactive_global_gateway_is_rejected_in_paynow_even_if_event_configuration_is_active(): void
    {
        Mockery::mock('alias:Midtrans\Snap')
            ->shouldNotReceive('createTransaction');

        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway(['is_active' => false]);
        $this->eventGateway($event, $gateway, ['is_active' => true]);

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
        $this->assertNull($cart->link);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_missing_event_payment_gateway_is_rejected_in_paynow(): void
    {
        Mockery::mock('alias:Midtrans\Snap')
            ->shouldNotReceive('createTransaction');

        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway();

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/paynow', [
                'cart_uid' => $cart->uid,
                'payment_gateway_id' => $gateway->id,
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $this->assertSame(Cart::STATUS_RESERVED, $cart->fresh()->status);
        $this->assertNull($cart->fresh()->payment_gateway_id);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_cross_event_payment_gateway_tampering_is_rejected(): void
    {
        Mockery::mock('alias:Midtrans\Snap')
            ->shouldNotReceive('createTransaction');

        $user = $this->user();
        $eventA = $this->event(['uid' => 'event-a']);
        $eventB = $this->event(['uid' => 'event-b']);
        $cart = $this->cart($user, $eventA);
        $harga = $this->harga($eventA);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway(['slug' => 'bni']);
        $this->eventGateway($eventB, $gateway, ['is_active' => true]);

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

    public function test_unknown_payment_gateway_id_is_rejected_in_paynow(): void
    {
        Mockery::mock('alias:Midtrans\Snap')
            ->shouldNotReceive('createTransaction');

        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $this->hargaCart($cart, $harga, 1);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/paynow', [
                'cart_uid' => $cart->uid,
                'payment_gateway_id' => 999999,
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $this->assertSame(Cart::STATUS_RESERVED, $cart->fresh()->status);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_paynow_rejects_cart_that_belongs_to_other_user(): void
    {
        Mockery::mock('alias:Midtrans\Snap')
            ->shouldNotReceive('createTransaction');

        $owner = $this->user();
        $attacker = $this->user();
        $event = $this->event();
        $cart = $this->cart($owner, $event);
        $harga = $this->harga($event);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway);

        $this->actingAs($attacker)
            ->from('/detail-ticket/'.$cart->uid.'/'.$attacker->uid)
            ->post('/paynow', [
                'cart_uid' => $cart->uid,
                'payment_gateway_id' => $gateway->id,
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$attacker->uid);

        $this->assertSame(Cart::STATUS_RESERVED, $cart->fresh()->status);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_event_without_active_gateway_shows_message_and_cannot_continue_payment(): void
    {
        Mockery::mock('alias:Midtrans\Snap')
            ->shouldNotReceive('createTransaction');

        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $this->hargaCart($cart, $harga, 1);

        $this->actingAs($user)
            ->get('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertOk()
            ->assertSee('Tidak ada metode pembayaran yang tersedia untuk event ini.')
            ->assertViewHas('hasAvailablePaymentGateways', false);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/paynow', [
                'cart_uid' => $cart->uid,
                'payment_gateway_id' => 1,
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $this->assertSame(Cart::STATUS_RESERVED, $cart->fresh()->status);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_checkout_preview_fee_matches_paynow_snapshot_for_fixed_plus_percent_after_voucher_discount(): void
    {
        $this->fakeMidtransRedirect();

        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event, ['harga' => 100000]);
        $this->hargaCart($cart, $harga, 1);
        $voucher = $this->voucher($event);
        DB::table('cart_vouchers')->insert([
            'uid' => $cart->uid,
            'uid_vouchers' => $voucher->uid,
            'user_uid' => $cart->user_uid,
            'event_uid' => $event->uid,
            'code' => $voucher->code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $gateway = $this->gateway([
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 3,
        ]);
        $this->eventGateway($event, $gateway);
        $cart->update([
            'payment_gateway_id' => $gateway->id,
            'payment_type' => $gateway->slug,
        ]);

        $this->actingAs($user)
            ->get('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertOk()
            ->assertViewHas('payment', function ($payment) use ($gateway) {
                $previewGateway = collect($payment)->firstWhere('id', $gateway->id);

                return $previewGateway
                    && (int) $previewGateway->resolved_internet_fee === 4700;
            })
            ->assertViewHas('selectInternetFee', 4700)
            ->assertViewHas('grandTotal', 94700);

        $this->actingAs($user)->post('/paynow', [
            'cart_uid' => $cart->uid,
            'payment_gateway_id' => $gateway->id,
        ])->assertRedirect('https://pay.example.test/snap');

        $cart->refresh();

        $this->assertSame(10000, $this->app->make(TicketPricingService::class)->calculateCart($cart)['discount']);
        $this->assertSame(4700, (int) $cart->internet_fee);
        $this->assertSame(94700, (int) $cart->gross_amount);
    }

    public function test_gateway_that_becomes_inactive_after_checkout_page_load_is_rejected_by_paynow(): void
    {
        Mockery::mock('alias:Midtrans\Snap')
            ->shouldNotReceive('createTransaction');

        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway();
        $eventGateway = $this->eventGateway($event, $gateway, ['is_active' => true]);

        $this->actingAs($user)
            ->get('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertOk()
            ->assertSee($gateway->payment);

        $eventGateway->update(['is_active' => false]);

        $this->actingAs($user)
            ->from('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->post('/paynow', [
                'cart_uid' => $cart->uid,
                'payment_gateway_id' => $gateway->id,
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$user->uid);

        $this->assertSame(Cart::STATUS_RESERVED, $cart->fresh()->status);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_soft_deleted_event_fails_closed_for_checkout_and_paynow(): void
    {
        Mockery::mock('alias:Midtrans\Snap')
            ->shouldNotReceive('createTransaction');

        $user = $this->user();
        $event = $this->event();
        $cart = $this->cart($user, $event);
        $harga = $this->harga($event);
        $this->hargaCart($cart, $harga, 1);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $event->delete();

        $this->actingAs($user)
            ->get('/detail-ticket/'.$cart->uid.'/'.$user->uid)
            ->assertRedirect('/');

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
            $table->timestamps();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('invoice')->nullable();
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
            $table->string('code')->nullable();
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

    protected function voucher(Event $event)
    {
        return DB::table('vouchers')->insertGetId([
            'uid' => 'voucher-'.Str::random(6),
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
        ]) ? \App\Models\Voucher::where('code', 'PROMO')->first() : null;
    }

    protected function fakeMidtransRedirect(string $url = 'https://pay.example.test/snap'): void
    {
        Mockery::mock('alias:Midtrans\Snap')
            ->shouldReceive('createTransaction')
            ->once()
            ->andReturn((object) ['redirect_url' => $url]);
    }
}
