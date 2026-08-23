<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->string('ticket_holder_name')->nullable()->after('invoice');
            $table->string('ticket_recipient_email')->nullable()->after('ticket_holder_name');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn(['ticket_holder_name', 'ticket_recipient_email']);
        });
    }
};
