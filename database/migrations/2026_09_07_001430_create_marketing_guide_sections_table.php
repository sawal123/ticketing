<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_guide_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')
                ->constrained('marketing_guide_versions')
                ->cascadeOnDelete();
            $table->string('key', 96);
            $table->string('title');
            $table->string('slug', 96)->nullable();
            $table->string('nav_group', 64)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['version_id', 'key']);
            $table->index(['version_id', 'position']);
            $table->index(['version_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_guide_sections');
    }
};
