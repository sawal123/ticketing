<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penarikans', function (Blueprint $table) {
            $table->string('transfer_proof')->nullable();
            $table->timestamp('transfer_proof_uploaded_at')->nullable();
            $table->string('transfer_proof_uploaded_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('penarikans', function (Blueprint $table) {
            $table->dropColumn([
                'transfer_proof',
                'transfer_proof_uploaded_at',
                'transfer_proof_uploaded_by',
            ]);
        });
    }
};
