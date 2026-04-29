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
        Schema::create('event_fasilitas', function (Blueprint $table) {
            $table->id();
            $table->string('event_uid');
            $table->unsignedBigInteger('fasilitas_id');
            $table->timestamps();

            $table->foreign('event_uid')->references('uid')->on('events')->onDelete('cascade');
            $table->foreign('fasilitas_id')->references('id')->on('fasilitas')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_fasilitas');
    }
};
