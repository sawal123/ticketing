<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('audit_category')->nullable();
            $table->string('action_key')->nullable();
            $table->string('event_uid')->nullable();
            $table->unsignedBigInteger('payment_gateway_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->index('audit_category');
            $table->index('action_key');
            $table->index('event_uid');
            $table->index('payment_gateway_id');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['audit_category']);
            $table->dropIndex(['action_key']);
            $table->dropIndex(['event_uid']);
            $table->dropIndex(['payment_gateway_id']);
            $table->dropColumn([
                'audit_category',
                'action_key',
                'event_uid',
                'payment_gateway_id',
                'old_values',
                'new_values',
            ]);
        });
    }
};
