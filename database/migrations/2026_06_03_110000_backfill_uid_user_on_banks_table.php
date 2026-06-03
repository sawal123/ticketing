<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('banks')
            ->where(function ($query) {
                $query->whereNull('uid_user')
                    ->orWhere('uid_user', '');
            })
            ->orderBy('id')
            ->each(function ($bank) {
                if (DB::table('users')->where('uid', $bank->uid)->exists()) {
                    DB::table('banks')
                        ->where('id', $bank->id)
                        ->update(['uid_user' => $bank->uid]);
                }
            });
    }

    public function down(): void
    {
        // Existing bank ownership data should not be removed.
    }
};
