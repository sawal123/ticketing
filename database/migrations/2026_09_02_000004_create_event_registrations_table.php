<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('event_uid')->index();
            $table->string('user_uid')->index();
            $table->string('registration_mode');
            $table->string('team_name')->nullable();
            $table->json('answers')->nullable();
            $table->timestamps();

            $table->index(['event_uid', 'user_uid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
