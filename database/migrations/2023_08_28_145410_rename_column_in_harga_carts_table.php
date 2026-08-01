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
        if (Schema::hasColumn('harga_carts', 'harga_uid')) {
            Schema::table('harga_carts', function (Blueprint $table) {
                $table->dropColumn('harga_uid');
            });
        }

        if (! Schema::hasColumn('harga_carts', 'event_uid')) {
            Schema::table('harga_carts', function (Blueprint $table) {
                $table->string('event_uid')->after('uid');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('harga_carts', 'event_uid')) {
            Schema::table('harga_carts', function (Blueprint $table) {
                $table->dropColumn('event_uid');
            });
        }

        if (! Schema::hasColumn('harga_carts', 'harga_uid')) {
            Schema::table('harga_carts', function (Blueprint $table) {
                $table->string('harga_uid')->after('uid');
            });
        }
    }
};
