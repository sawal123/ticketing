<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventPaymentGateway;
use App\Models\PaymentGateway;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventPaymentGatewayFoundationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->artisan('migrate', ['--database' => 'sqlite'])->assertExitCode(0);
    }

    public function test_payment_gateway_and_event_payment_gateway_schema_are_available(): void
    {
        foreach ([
            'default_fee_fixed',
            'default_fee_percent',
            'midtrans_code',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('payment_gateways', $column));
        }

        foreach ([
            'id',
            'event_id',
            'payment_gateway_id',
            'is_active',
            'fee_mode',
            'fee_fixed',
            'fee_percent',
            'created_at',
            'updated_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('event_payment_gateways', $column));
        }
    }

    public function test_event_payment_gateway_relations_support_global_and_manual_fees(): void
    {
        $event = $this->createEvent();
        $manualGateway = $this->createPaymentGateway(['slug' => 'manual-gateway']);
        $globalGateway = $this->createPaymentGateway(['slug' => 'global-gateway']);

        $manualConfig = EventPaymentGateway::create([
            'event_id' => $event->id,
            'payment_gateway_id' => $manualGateway->id,
            'is_active' => true,
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 2500,
            'fee_percent' => 2.75,
        ]);

        $globalConfig = EventPaymentGateway::create([
            'event_id' => $event->id,
            'payment_gateway_id' => $globalGateway->id,
            'is_active' => true,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
            'fee_fixed' => null,
            'fee_percent' => null,
        ]);

        $event->load('eventPaymentGateways.paymentGateway', 'paymentGateways');

        $this->assertCount(2, $event->eventPaymentGateways);
        $this->assertTrue($event->paymentGateways->contains('id', $manualGateway->id));
        $this->assertTrue($event->paymentGateways->contains('id', $globalGateway->id));
        $this->assertTrue($manualConfig->paymentGateway->is($manualGateway));
        $this->assertTrue($globalConfig->event->is($event));
        $this->assertSame(EventPaymentGateway::FEE_MODE_MANUAL, $manualConfig->fresh()->fee_mode);
        $this->assertSame('2500.00', $manualConfig->fresh()->fee_fixed);
        $this->assertSame('2.7500', $manualConfig->fresh()->fee_percent);
        $this->assertSame(EventPaymentGateway::FEE_MODE_GLOBAL, $globalConfig->fresh()->fee_mode);
    }

    public function test_new_event_payment_gateway_defaults_to_inactive_when_not_provided(): void
    {
        $event = $this->createEvent();
        $gateway = $this->createPaymentGateway();

        $config = EventPaymentGateway::create([
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
        ]);

        $this->assertFalse($config->fresh()->is_active);
    }

    public function test_event_and_payment_gateway_combination_must_be_unique(): void
    {
        $event = $this->createEvent();
        $gateway = $this->createPaymentGateway();

        EventPaymentGateway::create([
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => true,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
        ]);

        $this->expectException(QueryException::class);

        EventPaymentGateway::create([
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => false,
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 1000,
            'fee_percent' => 1.5,
        ]);
    }

    public function test_legacy_payment_gateway_data_is_migrated_and_existing_events_are_backfilled(): void
    {
        $this->artisan('migrate:fresh', [
            '--database' => 'sqlite',
            '--path' => base_path('database/migrations/2023_08_17_151941_create_events_table.php'),
            '--realpath' => true,
        ])->assertExitCode(0);

        $this->artisan('migrate', [
            '--database' => 'sqlite',
            '--path' => base_path('database/migrations/2024_09_15_103230_create_payment_gateways_table.php'),
            '--realpath' => true,
        ])->assertExitCode(0);

        $now = now();

        DB::table('events')->insert([
            'uid' => 'legacy-event',
            'event' => 'Legacy Event',
            'alamat' => 'Legacy Address',
            'tanggal' => '2026-08-15',
            'status' => 'active',
            'deskripsi' => 'Legacy Description',
            'map' => 'Legacy Map',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('payment_gateways')->insert([
            [
                'payment' => 'Legacy Fixed',
                'category' => 'bank_transfer',
                'biaya' => 3250.5,
                'biaya_type' => 'rupiah',
                'icon' => null,
                'is_active' => true,
                'slug' => 'legacy-fixed',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'payment' => 'Legacy Percent',
                'category' => 'ewallet',
                'biaya' => 2.5,
                'biaya_type' => 'persen',
                'icon' => null,
                'is_active' => false,
                'slug' => 'legacy-percent',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->artisan('migrate', [
            '--database' => 'sqlite',
            '--path' => base_path('database/migrations/2026_08_15_000001_add_fee_fields_to_payment_gateways_table.php'),
            '--realpath' => true,
        ])->assertExitCode(0);

        $this->artisan('migrate', [
            '--database' => 'sqlite',
            '--path' => base_path('database/migrations/2026_08_15_000002_create_event_payment_gateways_table.php'),
            '--realpath' => true,
        ])->assertExitCode(0);

        $fixedGateway = DB::table('payment_gateways')->where('slug', 'legacy-fixed')->first();
        $percentGateway = DB::table('payment_gateways')->where('slug', 'legacy-percent')->first();

        $this->assertSame(3250.5, (float) $fixedGateway->biaya);
        $this->assertSame('rupiah', $fixedGateway->biaya_type);
        $this->assertSame(3250.5, (float) $fixedGateway->default_fee_fixed);
        $this->assertSame(0.0, (float) $fixedGateway->default_fee_percent);
        $this->assertNull($fixedGateway->midtrans_code);

        $this->assertSame(2.5, (float) $percentGateway->biaya);
        $this->assertSame('persen', $percentGateway->biaya_type);
        $this->assertSame(0.0, (float) $percentGateway->default_fee_fixed);
        $this->assertSame(2.5, (float) $percentGateway->default_fee_percent);

        $this->assertDatabaseCount('event_payment_gateways', 2);
        $this->assertDatabaseHas('event_payment_gateways', [
            'event_id' => 1,
            'payment_gateway_id' => 1,
            'is_active' => 1,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
        ]);
        $this->assertDatabaseHas('event_payment_gateways', [
            'event_id' => 1,
            'payment_gateway_id' => 2,
            'is_active' => 0,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
        ]);
    }

    private function createEvent(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => 'user-'.Str::random(8),
            'event' => 'Event '.Str::random(6),
            'alamat' => 'Jakarta',
            'tanggal' => '2026-08-15',
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => 'Map event',
            'slug' => 'event-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
        ], $overrides));
    }

    private function createPaymentGateway(array $overrides = []): PaymentGateway
    {
        return PaymentGateway::create(array_merge([
            'payment' => 'Gateway '.Str::random(6),
            'category' => 'bank_transfer',
            'biaya' => 0,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => 0,
            'default_fee_percent' => 0,
            'midtrans_code' => null,
            'icon' => null,
            'is_active' => true,
            'slug' => 'gateway-'.Str::lower(Str::random(8)),
        ], $overrides));
    }
}
