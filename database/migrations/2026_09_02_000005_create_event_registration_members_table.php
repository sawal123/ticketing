<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registration_members', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('registration_uid')->index();
            $table->boolean('is_captain')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('answers')->nullable();
            $table->timestamps();

            $table->index(['registration_uid', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registration_members');
    }
};
