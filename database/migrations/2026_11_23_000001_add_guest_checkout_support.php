<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->string('user_uid')->nullable()->change();

                if (! Schema::hasColumn('carts', 'is_guest')) {
                    $table->boolean('is_guest')->default(false)->after('user_uid')->index('carts_is_guest_idx');
                }

                if (! Schema::hasColumn('carts', 'ticket_holder_gender')) {
                    $table->string('ticket_holder_gender', 20)->nullable()->after('ticket_recipient_email');
                }

                if (! Schema::hasColumn('carts', 'ticket_holder_phone')) {
                    $table->string('ticket_holder_phone', 30)->nullable()->after('ticket_holder_gender');
                }

                if (! Schema::hasColumn('carts', 'guest_token_hash')) {
                    $table->string('guest_token_hash', 64)->nullable()->after('ticket_holder_phone')->index('carts_guest_token_hash_idx');
                }

                if (! Schema::hasColumn('carts', 'guest_session_key')) {
                    $table->string('guest_session_key', 64)->nullable()->after('guest_token_hash')->index('carts_guest_session_event_idx');
                }
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('user_uid')->nullable()->change();
            });
        }

        if (Schema::hasTable('cart_vouchers')) {
            Schema::table('cart_vouchers', function (Blueprint $table) {
                $table->string('user_uid')->nullable()->change();
            });
        }

        if (Schema::hasTable('checkout_payment_otps')) {
            Schema::table('checkout_payment_otps', function (Blueprint $table) {
                $table->string('user_uid')->nullable()->change();

                if (! Schema::hasColumn('checkout_payment_otps', 'recipient_email')) {
                    $table->string('recipient_email')->nullable()->after('user_uid')->index('checkout_payment_otps_email_idx');
                }
            });
        }

        if (Schema::hasTable('event_registrations')) {
            Schema::table('event_registrations', function (Blueprint $table) {
                $table->string('user_uid')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('checkout_payment_otps')) {
            Schema::table('checkout_payment_otps', function (Blueprint $table) {
                if (Schema::hasColumn('checkout_payment_otps', 'recipient_email')) {
                    $table->dropIndex('checkout_payment_otps_email_idx');
                    $table->dropColumn('recipient_email');
                }
            });
        }

        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table) {
                if (Schema::hasColumn('carts', 'guest_session_key')) {
                    $table->dropIndex('carts_guest_session_event_idx');
                    $table->dropColumn('guest_session_key');
                }

                if (Schema::hasColumn('carts', 'guest_token_hash')) {
                    $table->dropIndex('carts_guest_token_hash_idx');
                    $table->dropColumn('guest_token_hash');
                }

                if (Schema::hasColumn('carts', 'ticket_holder_phone')) {
                    $table->dropColumn('ticket_holder_phone');
                }

                if (Schema::hasColumn('carts', 'ticket_holder_gender')) {
                    $table->dropColumn('ticket_holder_gender');
                }

                if (Schema::hasColumn('carts', 'is_guest')) {
                    $table->dropIndex('carts_is_guest_idx');
                    $table->dropColumn('is_guest');
                }
            });
        }
    }
};
