<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'users_uid_index';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'uid') || $this->usersUidIsIndexed()) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->index('uid', self::INDEX_NAME);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'uid') || ! $this->hasNamedIndex()) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(self::INDEX_NAME);
        });
    }

    private function usersUidIsIndexed(): bool
    {
        foreach ($this->indexesForUsersTable() as $index) {
            if (in_array('uid', $index['columns'], true)) {
                return true;
            }
        }

        return false;
    }

    private function hasNamedIndex(): bool
    {
        foreach ($this->indexesForUsersTable() as $index) {
            if ($index['name'] === self::INDEX_NAME) {
                return true;
            }
        }

        return false;
    }

    private function indexesForUsersTable(): array
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            return collect(DB::select('SHOW INDEX FROM `users`'))
                ->groupBy('Key_name')
                ->map(fn ($rows, $name) => [
                    'name' => (string) $name,
                    'columns' => collect($rows)
                        ->sortBy('Seq_in_index')
                        ->pluck('Column_name')
                        ->map(fn ($column) => (string) $column)
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all();
        }

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('users')"))
                ->map(function ($index) {
                    $name = (string) $index->name;

                    return [
                        'name' => $name,
                        'columns' => collect(DB::select("PRAGMA index_info('{$name}')"))
                            ->sortBy('seqno')
                            ->pluck('name')
                            ->map(fn ($column) => (string) $column)
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all();
        }

        return [];
    }
};
