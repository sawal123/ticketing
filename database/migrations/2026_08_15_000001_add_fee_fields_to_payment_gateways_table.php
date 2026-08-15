<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_gateways')) {
            return;
        }

        Schema::table('payment_gateways', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_gateways', 'default_fee_fixed')) {
                $table->decimal('default_fee_fixed', 15, 2)->default(0);
            }

            if (! Schema::hasColumn('payment_gateways', 'default_fee_percent')) {
                $table->decimal('default_fee_percent', 8, 4)->default(0);
            }

            if (! Schema::hasColumn('payment_gateways', 'midtrans_code')) {
                $table->string('midtrans_code')->nullable();
            }
        });

        if (Schema::hasColumn('payment_gateways', 'biaya') && Schema::hasColumn('payment_gateways', 'biaya_type')) {
            DB::table('payment_gateways')
                ->where('biaya_type', 'rupiah')
                ->update([
                    'default_fee_fixed' => DB::raw('COALESCE(biaya, 0)'),
                    'default_fee_percent' => 0,
                ]);

            DB::table('payment_gateways')
                ->where('biaya_type', 'persen')
                ->update([
                    'default_fee_fixed' => 0,
                    'default_fee_percent' => DB::raw('COALESCE(biaya, 0)'),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_gateways')) {
            return;
        }

        Schema::table('payment_gateways', function (Blueprint $table) {
            $columns = [];

            foreach (['default_fee_fixed', 'default_fee_percent', 'midtrans_code'] as $column) {
                if (Schema::hasColumn('payment_gateways', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
