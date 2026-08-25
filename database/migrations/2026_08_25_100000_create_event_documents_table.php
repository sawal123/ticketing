<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_documents', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('event_uid');
            $table->string('document_type');
            $table->string('document_number', 100);
            $table->date('document_date');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->string('status')->default('pending');
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('event_uid')
                ->references('uid')
                ->on('events')
                ->cascadeOnDelete();

            $table->unique(['event_uid', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_documents');
    }
};
