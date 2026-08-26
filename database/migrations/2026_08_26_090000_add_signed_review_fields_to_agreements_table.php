<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->string('signed_review_status')->nullable()->after('signed_pdf_path');
            $table->string('signed_verified_by')->nullable()->after('signed_review_status');
            $table->timestamp('signed_verified_at')->nullable()->after('signed_verified_by');
            $table->text('signed_rejection_reason')->nullable()->after('signed_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn([
                'signed_review_status',
                'signed_verified_by',
                'signed_verified_at',
                'signed_rejection_reason',
            ]);
        });
    }
};
