<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hargas', function (Blueprint $table) {
            if (! Schema::hasColumn('hargas', 'sold_qty')) {
                $table->unsignedInteger('sold_qty')->default(0)->after('qty');
            }

            if (! Schema::hasColumn('hargas', 'reserved_qty')) {
                $table->unsignedInteger('reserved_qty')->default(0)->after('sold_qty');
            }

            $table->index('uid', 'hargas_uid_idx');
            $table->index('status', 'hargas_status_idx');
            $table->index(['uid', 'status'], 'hargas_uid_status_idx');
        });

        Schema::table('carts', function (Blueprint $table) {
            if (! Schema::hasColumn('carts', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('pajak_persen');
            }

            if (! Schema::hasColumn('carts', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('expires_at');
            }

            if (! Schema::hasColumn('carts', 'reservation_released_at')) {
                $table->timestamp('reservation_released_at')->nullable()->after('paid_at');
            }

            if (! Schema::hasColumn('carts', 'payment_link_expires_at')) {
                $table->timestamp('payment_link_expires_at')->nullable()->after('reservation_released_at');
            }

            if (! Schema::hasColumn('carts', 'gross_amount')) {
                $table->unsignedBigInteger('gross_amount')->nullable()->after('payment_link_expires_at');
            }

            if (! Schema::hasColumn('carts', 'payment_gateway_id')) {
                $table->unsignedBigInteger('payment_gateway_id')->nullable()->after('gross_amount');
            }

            if (! Schema::hasColumn('carts', 'review_reason')) {
                $table->text('review_reason')->nullable()->after('payment_gateway_id');
            }

            if (! Schema::hasColumn('carts', 'midtrans_transaction_id')) {
                $table->string('midtrans_transaction_id')->nullable()->after('review_reason');
            }

            if (! Schema::hasColumn('carts', 'midtrans_status')) {
                $table->string('midtrans_status')->nullable()->after('midtrans_transaction_id');
            }

            $table->index('uid', 'carts_uid_idx');
            $table->index('invoice', 'carts_invoice_idx');
            $table->index(['event_uid', 'status'], 'carts_event_status_idx');
            $table->index(['user_uid', 'status'], 'carts_user_status_idx');
            $table->index(['expires_at', 'status'], 'carts_expires_status_idx');
        });

        Schema::table('harga_carts', function (Blueprint $table) {
            $table->index('uid', 'harga_carts_uid_idx');
            $table->index('harga_id', 'harga_carts_harga_id_idx');
            $table->index('event_uid', 'harga_carts_event_uid_idx');
            $table->index(['harga_id', 'event_uid'], 'harga_carts_harga_event_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'gross_amount')) {
                $table->unsignedBigInteger('gross_amount')->nullable()->after('amount');
            }

            if (! Schema::hasColumn('transactions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('status_transaksi');
            }

            $table->index('invoice', 'transactions_invoice_idx');
            $table->index('uid', 'transactions_uid_idx');
            $table->index(['event_uid', 'status_transaksi'], 'transactions_event_status_idx');
        });

        Schema::table('cart_vouchers', function (Blueprint $table) {
            $table->index('uid', 'cart_vouchers_uid_idx');
            $table->index('code', 'cart_vouchers_code_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cart_vouchers', function (Blueprint $table) {
            $table->dropIndex('cart_vouchers_uid_idx');
            $table->dropIndex('cart_vouchers_code_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_invoice_idx');
            $table->dropIndex('transactions_uid_idx');
            $table->dropIndex('transactions_event_status_idx');
            $table->dropColumn(['gross_amount', 'paid_at']);
        });

        Schema::table('harga_carts', function (Blueprint $table) {
            $table->dropIndex('harga_carts_uid_idx');
            $table->dropIndex('harga_carts_harga_id_idx');
            $table->dropIndex('harga_carts_event_uid_idx');
            $table->dropIndex('harga_carts_harga_event_idx');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('carts_uid_idx');
            $table->dropIndex('carts_invoice_idx');
            $table->dropIndex('carts_event_status_idx');
            $table->dropIndex('carts_user_status_idx');
            $table->dropIndex('carts_expires_status_idx');
            $table->dropColumn([
                'expires_at',
                'paid_at',
                'reservation_released_at',
                'payment_link_expires_at',
                'gross_amount',
                'payment_gateway_id',
                'review_reason',
                'midtrans_transaction_id',
                'midtrans_status',
            ]);
        });

        Schema::table('hargas', function (Blueprint $table) {
            $table->dropIndex('hargas_uid_idx');
            $table->dropIndex('hargas_status_idx');
            $table->dropIndex('hargas_uid_status_idx');
            $table->dropColumn(['sold_qty', 'reserved_qty']);
        });
    }
};
