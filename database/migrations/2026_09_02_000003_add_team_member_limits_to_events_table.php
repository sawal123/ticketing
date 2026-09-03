<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('team_min_members')->nullable()->after('registration_mode');
            $table->unsignedInteger('team_max_members')->nullable()->after('team_min_members');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['team_min_members', 'team_max_members']);
        });
    }
};
