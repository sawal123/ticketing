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
            if (! Schema::hasColumn('penarikans', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('approved_at');
            }

            if (! Schema::hasColumn('penarikans', 'bank_account_name')) {
                $table->string('bank_account_name')->nullable()->after('bank_name');
            }

            if (! Schema::hasColumn('penarikans', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_account_name');
            }
        });

        $banksHasDeletedAt = Schema::hasColumn('banks', 'deleted_at');

        DB::table('penarikans')
            ->select(['id', 'uid_user', 'bank_name', 'bank_account_name', 'bank_account_number'])
            ->where(function ($query) {
                $query->whereNull('bank_name')
                    ->orWhereNull('bank_account_name')
                    ->orWhereNull('bank_account_number');
            })
            ->chunkById(100, function ($penarikans) use ($banksHasDeletedAt) {
                foreach ($penarikans as $penarikan) {
                    $bankQuery = DB::table('banks')
                        ->where(function ($query) use ($penarikan) {
                            $query->where('uid_user', $penarikan->uid_user)
                                ->orWhere('uid', $penarikan->uid_user);
                        })
                        ->orderByDesc('id');

                    if ($banksHasDeletedAt) {
                        $bankQuery->whereNull('deleted_at');
                    }

                    $bank = $bankQuery->first();

                    if (! $bank) {
                        continue;
                    }

                    $updates = [];

                    if ($penarikan->bank_name === null) {
                        $updates['bank_name'] = $bank->bank;
                    }

                    if ($penarikan->bank_account_name === null) {
                        $updates['bank_account_name'] = $bank->nama;
                    }

                    if ($penarikan->bank_account_number === null) {
                        $updates['bank_account_number'] = $bank->norek;
                    }

                    if ($updates !== []) {
                        DB::table('penarikans')->where('id', $penarikan->id)->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('penarikans', function (Blueprint $table) {
            foreach (['bank_name', 'bank_account_name', 'bank_account_number'] as $column) {
                if (Schema::hasColumn('penarikans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
