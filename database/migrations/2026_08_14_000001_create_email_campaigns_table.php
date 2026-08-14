<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->longText('content');
            $table->string('target_type', 20);
            $table->string('event_uid')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 40)->default('pending');
            $table->string('created_by');
            $table->timestamps();

            $table->index('status');
            $table->index('target_type');
            $table->index('event_uid');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
