<?php

use App\Models\EventPaymentGateway;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('event_payment_gateways')) {
            Schema::create('event_payment_gateways', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payment_gateway_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->enum('fee_mode', [
                    EventPaymentGateway::FEE_MODE_GLOBAL,
                    EventPaymentGateway::FEE_MODE_MANUAL,
                ])->default(EventPaymentGateway::FEE_MODE_GLOBAL);
                $table->decimal('fee_fixed', 15, 2)->nullable();
                $table->decimal('fee_percent', 8, 4)->nullable();
                $table->timestamps();

                $table->unique(['event_id', 'payment_gateway_id']);
            });
        }

        $this->backfillExistingEventGatewayRows();
    }

    public function down(): void
    {
        Schema::dropIfExists('event_payment_gateways');
    }

    private function backfillExistingEventGatewayRows(): void
    {
        if (! Schema::hasTable('events') || ! Schema::hasTable('payment_gateways') || ! Schema::hasTable('event_payment_gateways')) {
            return;
        }

        $gateways = DB::table('payment_gateways')
            ->select('id', 'is_active')
            ->orderBy('id')
            ->get();

        if ($gateways->isEmpty()) {
            return;
        }

        $now = now();

        DB::table('events')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($events) use ($gateways, $now) {
                $rows = [];

                foreach ($events as $event) {
                    $existingGatewayIds = DB::table('event_payment_gateways')
                        ->where('event_id', $event->id)
                        ->pluck('payment_gateway_id')
                        ->all();

                    $existingGatewayIds = array_map('intval', $existingGatewayIds);

                    foreach ($gateways as $gateway) {
                        if (in_array((int) $gateway->id, $existingGatewayIds, true)) {
                            continue;
                        }

                        $rows[] = [
                            'event_id' => $event->id,
                            'payment_gateway_id' => $gateway->id,
                            'is_active' => (bool) $gateway->is_active,
                            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
                            'fee_fixed' => null,
                            'fee_percent' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($rows !== []) {
                    DB::table('event_payment_gateways')->insert($rows);
                }
            });
    }
};
