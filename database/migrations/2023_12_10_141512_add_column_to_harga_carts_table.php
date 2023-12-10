<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('harga_carts', function (Blueprint $table) {
            $table->string('disc')->after('harga_ticket')->nullable();
            $table->string('voucher')->after('harga_ticket')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('harga_carts', function (Blueprint $table) {
            //
        });
    }
};
