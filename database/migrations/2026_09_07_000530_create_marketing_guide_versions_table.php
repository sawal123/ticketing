<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_guide_versions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->unsignedInteger('number')->default(1);
            $table->string('title');
            $table->string('status', 32)->default('draft');
            $table->string('created_by_uid')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('published_at');
            $table->foreign('created_by_uid')
                ->references('uid')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_guide_versions');
    }
};
