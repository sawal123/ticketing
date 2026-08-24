<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dateTime('event_end')->nullable()->after('tanggal');
            $table->string('venue_name')->nullable()->after('alamat');
            $table->text('venue_address')->nullable()->after('venue_name');
            $table->string('venue_city')->nullable()->after('venue_address');
            $table->string('venue_province')->nullable()->after('venue_city');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'event_end',
                'venue_name',
                'venue_address',
                'venue_city',
                'venue_province',
            ]);
        });
    }
};
