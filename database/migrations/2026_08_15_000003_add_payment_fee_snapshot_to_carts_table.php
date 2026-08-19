<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('carts')) {
            return;
        }

        Schema::table('carts', function (Blueprint $table) {
            if (! Schema::hasColumn('carts', 'payment_fee_mode')) {
                $table->string('payment_fee_mode')->nullable()->after('payment_gateway_id');
            }

            if (! Schema::hasColumn('carts', 'payment_fee_fixed')) {
                $table->decimal('payment_fee_fixed', 15, 2)->nullable()->after('payment_fee_mode');
            }

            if (! Schema::hasColumn('carts', 'payment_fee_percent')) {
                $table->decimal('payment_fee_percent', 8, 4)->nullable()->after('payment_fee_fixed');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('carts')) {
            return;
        }

        Schema::table('carts', function (Blueprint $table) {
            $columns = [];

            foreach (['payment_fee_mode', 'payment_fee_fixed', 'payment_fee_percent'] as $column) {
                if (Schema::hasColumn('carts', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
