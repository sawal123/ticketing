<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('event_uid')->unique();
            $table->string('bank_name', 100);
            $table->string('account_number', 50);
            $table->string('account_holder_name');
            $table->string('bank_book_path');
            $table->string('bank_book_original_name')->nullable();
            $table->string('bank_book_mime')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by')->nullable();
            $table->timestamps();

            $table->foreign('event_uid')
                ->references('uid')
                ->on('events')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_bank_accounts');
    }
};
