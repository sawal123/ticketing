<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penarikans', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('status');
        });

        DB::table('penarikans')
            ->whereRaw('LOWER(status) = ?', ['success'])
            ->whereNull('approved_at')
            ->update(['approved_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('penarikans', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
