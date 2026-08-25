<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('event_uid');
            $table->string('tenant_user_uid');
            $table->string('type');
            $table->string('document_number')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('DRAFT');
            $table->string('template_version')->nullable();
            $table->json('event_snapshot')->nullable();
            $table->json('party_snapshot')->nullable();
            $table->json('bank_snapshot')->nullable();
            $table->json('document_snapshot')->nullable();
            $table->json('commercial_snapshot')->nullable();
            $table->string('privy_document_id')->nullable();
            $table->string('privy_status')->nullable();
            $table->string('privy_reference')->nullable();
            $table->string('unsigned_pdf_path')->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->timestamp('sent_to_privy_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->foreign('event_uid')
                ->references('uid')
                ->on('events')
                ->cascadeOnDelete();
            $table->index('tenant_user_uid');
            $table->index('status');
            $table->index('type');
            $table->unique(['event_uid', 'type', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
