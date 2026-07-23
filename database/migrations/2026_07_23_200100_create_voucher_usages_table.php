<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('voucher_usages')) {
            return;
        }

        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->string('voucher_uid')->nullable();
            $table->string('cart_uid');
            $table->string('invoice')->nullable();
            $table->string('code');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->unique('cart_uid', 'voucher_usages_cart_uid_unique');
            $table->index('code', 'voucher_usages_code_idx');
            $table->index('voucher_uid', 'voucher_usages_voucher_uid_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_usages');
    }
};
