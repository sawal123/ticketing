<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('cart_uid')->nullable()->unique()->after('uid');
            $table->string('invoice')->nullable()->index()->after('cart_uid');
            $table->string('status')->default('PENDING')->after('registration_mode');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropUnique(['cart_uid']);
            $table->dropIndex(['invoice']);
            $table->dropColumn(['cart_uid', 'invoice', 'status']);
        });
    }
};
