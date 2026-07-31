<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('forgot_passwords')) {
            Schema::create('forgot_passwords', function (Blueprint $table) {
                $table->id();
                $table->string('uid')->nullable();
                $table->string('uid_user')->nullable();
                $table->string('email')->index();
                $table->string('token_hash', 64)->unique();
                $table->timestamp('expires_at')->index();
                $table->timestamp('used_at')->nullable()->index();
                $table->timestamps();
            });

            return;
        }

        Schema::table('forgot_passwords', function (Blueprint $table) {
            if (! Schema::hasColumn('forgot_passwords', 'email')) {
                $table->string('email')->nullable()->after('uid_user')->index();
            }

            if (! Schema::hasColumn('forgot_passwords', 'token_hash')) {
                $table->string('token_hash', 64)->nullable()->after('email')->unique();
            }

            if (! Schema::hasColumn('forgot_passwords', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('token_hash')->index();
            }

            if (! Schema::hasColumn('forgot_passwords', 'used_at')) {
                $table->timestamp('used_at')->nullable()->after('expires_at')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('forgot_passwords')) {
            return;
        }

        Schema::table('forgot_passwords', function (Blueprint $table) {
            $columns = array_filter(
                ['email', 'token_hash', 'expires_at', 'used_at'],
                fn (string $column) => Schema::hasColumn('forgot_passwords', $column)
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
