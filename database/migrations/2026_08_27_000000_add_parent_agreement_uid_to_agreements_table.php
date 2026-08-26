<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->string('parent_agreement_uid')->nullable()->after('type');
            $table->index('parent_agreement_uid');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropIndex(['parent_agreement_uid']);
            $table->dropColumn('parent_agreement_uid');
        });
    }
};
