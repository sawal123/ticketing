<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->char('gate_token_hash', 64)
                ->nullable()
                ->unique('carts_gate_token_hash_unique')
                ->after('invoice');
            $table->text('gate_token_encrypted')->nullable()->after('gate_token_hash');
            $table->char('gate_manual_code_hash', 64)
                ->nullable()
                ->unique('carts_gate_manual_code_hash_unique')
                ->after('gate_token_encrypted');
            $table->text('gate_manual_code_encrypted')->nullable()->after('gate_manual_code_hash');
            $table->timestamp('gate_token_issued_at')->nullable()->after('gate_manual_code_encrypted');
            $table->timestamp('scanned_at')->nullable()->after('gate_token_issued_at');
            $table->string('scanned_by')->nullable()->after('scanned_at');
            $table->string('scan_device_id')->nullable()->after('scanned_by');
            $table->unsignedInteger('gate_token_version')->default(1)->after('scan_device_id');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropUnique('carts_gate_manual_code_hash_unique');
            $table->dropUnique('carts_gate_token_hash_unique');
            $table->dropColumn([
                'gate_token_hash',
                'gate_token_encrypted',
                'gate_manual_code_hash',
                'gate_manual_code_encrypted',
                'gate_token_issued_at',
                'scanned_at',
                'scanned_by',
                'scan_device_id',
                'gate_token_version',
            ]);
        });
    }
};
