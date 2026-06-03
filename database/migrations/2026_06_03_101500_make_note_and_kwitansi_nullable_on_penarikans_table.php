<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penarikans', function (Blueprint $table) {
            $table->string('note')->nullable()->change();
            $table->string('kwitansi')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('penarikans', function (Blueprint $table) {
            $table->string('note')->nullable(false)->change();
            $table->string('kwitansi')->nullable(false)->change();
        });
    }
};
