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
        Schema::create('profile_email_change_otps', function (Blueprint $table) {
            $table->id();
            $table->string('user_uid')->index();
            $table->string('current_email');
            $table->string('new_email')->index();
            $table->string('otp_hash', 64);
            $table->string('purpose')->default('profile_email_change');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_email_change_otps');
    }
};
