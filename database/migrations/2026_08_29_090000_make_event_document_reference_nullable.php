<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_documents', function (Blueprint $table) {
            $table->string('document_number', 100)->nullable()->change();
            $table->date('document_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('event_documents')
            ->whereNull('document_number')
            ->orWhereNull('document_date')
            ->exists()) {
            return;
        }

        Schema::table('event_documents', function (Blueprint $table) {
            $table->string('document_number', 100)->nullable(false)->change();
            $table->date('document_date')->nullable(false)->change();
        });
    }
};
