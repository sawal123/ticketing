<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_logs') || ! Schema::hasColumn('activity_logs', 'id')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $database = DB::connection()->getDatabaseName();
        $column = DB::selectOne(
            'select COLUMN_TYPE as column_type, EXTRA as extra
             from information_schema.COLUMNS
             where TABLE_SCHEMA = ? and TABLE_NAME = ? and COLUMN_NAME = ?',
            [$database, 'activity_logs', 'id']
        );

        if (! $column || str_contains(strtolower((string) $column->extra), 'auto_increment')) {
            return;
        }

        $primary = DB::selectOne(
            'select CONSTRAINT_NAME as constraint_name
             from information_schema.TABLE_CONSTRAINTS
             where TABLE_SCHEMA = ? and TABLE_NAME = ? and CONSTRAINT_TYPE = ?',
            [$database, 'activity_logs', 'PRIMARY KEY']
        );

        if (! $primary) {
            DB::statement('alter table `activity_logs` add primary key (`id`)');
        }

        DB::statement('alter table `activity_logs` modify `id` bigint unsigned not null auto_increment');
    }

    public function down(): void
    {
        // Intentionally no-op. Removing AUTO_INCREMENT from production logs is not a safe rollback.
    }
};
