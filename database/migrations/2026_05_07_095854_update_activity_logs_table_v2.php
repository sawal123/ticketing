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
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('login_status')->nullable()->after('activity');
            $table->string('location')->nullable()->after('ip_address');
            $table->string('impact_level')->default('Normal')->after('description');
            $table->string('session_id')->nullable()->after('impact_level');
            $table->string('device_id')->nullable()->after('user_agent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['login_status', 'location', 'impact_level', 'session_id', 'device_id']);
        });
    }
};
