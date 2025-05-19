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
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('payment'); // Nama payment gateway
            $table->string('category');
            $table->decimal('biaya', 15, 2); // Biaya dalam rupiah atau persen (15 digit, 2 digit desimal)
            $table->enum('biaya_type', ['rupiah', 'persen']); // Menentukan jenis biaya (rupiah atau persen)
            $table->string('icon')->nullable(); // URL atau nama file icon
            $table->boolean('is_active')->default(true); // Status aktif atau tidak
            $table->string('slug')->unique();
            $table->timestamps(); // Menyimpan waktu pembuatan dan update
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
