<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partners')) {
            Schema::table('partners', function (Blueprint $table) {
                if (! Schema::hasColumn('partners', 'discount_type')) {
                    $table->string('discount_type', 20)->nullable()->after('status');
                }
                if (! Schema::hasColumn('partners', 'discount_value')) {
                    $table->unsignedInteger('discount_value')->nullable()->after('discount_type');
                }
                if (! Schema::hasColumn('partners', 'discount_max')) {
                    $table->unsignedInteger('discount_max')->nullable()->after('discount_value');
                }
                if (! Schema::hasColumn('partners', 'allow_promo_tickets')) {
                    $table->boolean('allow_promo_tickets')->default(false)->after('discount_max');
                }
                if (! Schema::hasColumn('partners', 'commission_type')) {
                    $table->string('commission_type', 20)->nullable()->after('allow_promo_tickets');
                }
                if (! Schema::hasColumn('partners', 'commission_value')) {
                    $table->unsignedInteger('commission_value')->nullable()->after('commission_type');
                }
            });
        }

        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table) {
                if (! Schema::hasColumn('carts', 'partner_uid')) {
                    $table->string('partner_uid', 100)->nullable()->index()->after('event_uid');
                }
                if (! Schema::hasColumn('carts', 'partner_discount_amount')) {
                    $table->unsignedInteger('partner_discount_amount')->default(0)->after('partner_uid');
                }
                if (! Schema::hasColumn('carts', 'partner_commission_type')) {
                    $table->string('partner_commission_type', 20)->nullable()->after('partner_discount_amount');
                }
                if (! Schema::hasColumn('carts', 'partner_commission_value')) {
                    $table->unsignedInteger('partner_commission_value')->nullable()->after('partner_commission_type');
                }
                if (! Schema::hasColumn('carts', 'partner_commission_amount')) {
                    $table->unsignedInteger('partner_commission_amount')->default(0)->after('partner_commission_value');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table) {
                $columns = array_values(array_filter([
                    Schema::hasColumn('carts', 'partner_commission_amount') ? 'partner_commission_amount' : null,
                    Schema::hasColumn('carts', 'partner_commission_value') ? 'partner_commission_value' : null,
                    Schema::hasColumn('carts', 'partner_commission_type') ? 'partner_commission_type' : null,
                    Schema::hasColumn('carts', 'partner_discount_amount') ? 'partner_discount_amount' : null,
                    Schema::hasColumn('carts', 'partner_uid') ? 'partner_uid' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('partners')) {
            Schema::table('partners', function (Blueprint $table) {
                $columns = array_values(array_filter([
                    Schema::hasColumn('partners', 'commission_value') ? 'commission_value' : null,
                    Schema::hasColumn('partners', 'commission_type') ? 'commission_type' : null,
                    Schema::hasColumn('partners', 'allow_promo_tickets') ? 'allow_promo_tickets' : null,
                    Schema::hasColumn('partners', 'discount_max') ? 'discount_max' : null,
                    Schema::hasColumn('partners', 'discount_value') ? 'discount_value' : null,
                    Schema::hasColumn('partners', 'discount_type') ? 'discount_type' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
