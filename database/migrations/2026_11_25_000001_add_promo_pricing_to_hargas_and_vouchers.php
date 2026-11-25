<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hargas')) {
            Schema::table('hargas', function (Blueprint $table) {
                if (! Schema::hasColumn('hargas', 'is_promo')) {
                    $table->boolean('is_promo')->default(false)->after('harga');
                }
                if (! Schema::hasColumn('hargas', 'promo_type')) {
                    $table->string('promo_type', 20)->nullable()->after('is_promo');
                }
                if (! Schema::hasColumn('hargas', 'promo_value')) {
                    $table->unsignedInteger('promo_value')->nullable()->after('promo_type');
                }
            });
        }

        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                if (! Schema::hasColumn('vouchers', 'allow_promo_tickets')) {
                    $table->boolean('allow_promo_tickets')->default(false)->after('status');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hargas')) {
            Schema::table('hargas', function (Blueprint $table) {
                $columns = array_values(array_filter([
                    Schema::hasColumn('hargas', 'promo_value') ? 'promo_value' : null,
                    Schema::hasColumn('hargas', 'promo_type') ? 'promo_type' : null,
                    Schema::hasColumn('hargas', 'is_promo') ? 'is_promo' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('vouchers') && Schema::hasColumn('vouchers', 'allow_promo_tickets')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->dropColumn('allow_promo_tickets');
            });
        }
    }
};
