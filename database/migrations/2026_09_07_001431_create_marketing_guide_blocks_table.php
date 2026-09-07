<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_guide_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')
                ->constrained('marketing_guide_sections')
                ->cascadeOnDelete();
            $table->string('type', 32);
            $table->unsignedInteger('position')->default(0);
            $table->json('data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['section_id', 'position']);
            $table->index(['section_id', 'type']);
            $table->index(['section_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_guide_blocks');
    }
};
