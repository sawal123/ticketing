<?php

namespace Tests\Feature;

use App\Models\TutorialProgress;
use App\Models\User;
use App\Services\Tutorials\TutorialProgressService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;
use Throwable;

class TutorialProgressFoundationTest extends TestCase
{
    private const CREATE_USERS_MIGRATION_PATH = 'database/migrations/2014_10_12_000000_create_users_table.php';
    private const ADD_USERS_UID_MIGRATION_PATH = 'database/migrations/2023_08_21_074707_add_column_to_users_table.php';
    private const ADD_USERS_UID_INDEX_MIGRATION_PATH = 'database/migrations/2026_08_31_095000_add_index_to_users_uid.php';
    private const CREATE_TUTORIAL_PROGRESS_MIGRATION_PATH = 'database/migrations/2026_08_31_100000_create_tutorial_progress_table.php';

    private ?string $temporaryMysqlDatabase = null;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $this->dropTemporaryMysqlDatabase();

        parent::tearDown();
    }

    public function test_migrations_match_production_user_uid_shape_and_create_required_index_and_foreign_key(): void
    {
        $this->prepareProductionLikeSchema();

        $this->assertTrue(Schema::hasColumn('users', 'uid'));
        $this->assertTrue($this->hasIndex('users', 'users_uid_index'));
        $this->assertTrue($this->hasForeignKey('tutorial_progress', 'user_uid', 'users', 'uid'));
        $this->assertTrue($this->hasIndex('tutorial_progress', 'tutorial_progress_user_uid_tutorial_key_unique'));
    }

    public function test_migration_persists_progress_and_allows_many_tutorial_keys_per_user(): void
    {
        $this->prepareRuntimeSchema();

        $user = $this->user();
        $service = app(TutorialProgressService::class);

        $service->markCompleted($user, 'dashboard.welcome');
        $service->markDismissed($user, 'dashboard.bank-account');

        $user->load('tutorialProgress');

        $this->assertTrue(Schema::hasTable('tutorial_progress'));
        $this->assertCount(2, $user->tutorialProgress);
        $this->assertDatabaseHas('tutorial_progress', [
            'user_uid' => $user->uid,
            'tutorial_key' => 'dashboard.welcome',
        ]);
        $this->assertDatabaseHas('tutorial_progress', [
            'user_uid' => $user->uid,
            'tutorial_key' => 'dashboard.bank-account',
        ]);
    }

    public function test_same_tutorial_key_is_isolated_per_user(): void
    {
        $this->prepareRuntimeSchema();

        $tenantA = $this->user('tenant-a@example.test');
        $tenantB = $this->user('tenant-b@example.test');
        $service = app(TutorialProgressService::class);

        $service->markCompleted($tenantA, 'dashboard.welcome');
        $service->markDismissed($tenantB, 'dashboard.welcome');

        $this->assertTrue($service->isCompleted($tenantA, 'dashboard.welcome'));
        $this->assertFalse($service->isDismissed($tenantA, 'dashboard.welcome'));
        $this->assertFalse($service->isCompleted($tenantB, 'dashboard.welcome'));
        $this->assertTrue($service->isDismissed($tenantB, 'dashboard.welcome'));
        $this->assertDatabaseCount('tutorial_progress', 2);
    }

    public function test_progress_records_are_unique_per_user_and_tutorial_key(): void
    {
        $this->prepareRuntimeSchema();

        $user = $this->user();
        $service = app(TutorialProgressService::class);

        Carbon::setTestNow('2026-08-31 10:00:00');
        $first = $service->markCompleted($user, 'dashboard.welcome');

        Carbon::setTestNow('2026-08-31 11:00:00');
        $second = $service->markCompleted($user, 'dashboard.welcome');

        $this->assertSame($first->id, $second->id);
        $this->assertTrue($first->completed_at->equalTo($second->completed_at));
        $this->assertDatabaseCount('tutorial_progress', 1);

        $this->expectException(QueryException::class);

        TutorialProgress::create([
            'user_uid' => $user->uid,
            'tutorial_key' => 'dashboard.welcome',
        ]);
    }

    public function test_complete_dismiss_and_reset_are_idempotent(): void
    {
        $this->prepareRuntimeSchema();

        $user = $this->user();
        $service = app(TutorialProgressService::class);

        Carbon::setTestNow('2026-08-31 09:00:00');
        $service->markCompleted($user, 'dashboard.welcome');

        $this->assertTrue($service->isCompleted($user, 'dashboard.welcome'));
        $this->assertFalse($service->isDismissed($user, 'dashboard.welcome'));

        Carbon::setTestNow('2026-08-31 09:30:00');
        $service->markDismissed($user, 'dashboard.welcome');
        $service->markDismissed($user->uid, 'dashboard.welcome');

        $progress = TutorialProgress::query()
            ->where('user_uid', $user->uid)
            ->where('tutorial_key', 'dashboard.welcome')
            ->firstOrFail();

        $this->assertNull($progress->completed_at);
        $this->assertNotNull($progress->dismissed_at);
        $this->assertFalse($service->isCompleted($user, 'dashboard.welcome'));
        $this->assertTrue($service->isDismissed($user, 'dashboard.welcome'));

        $service->reset($user, 'dashboard.welcome');
        $service->reset($user->uid, 'dashboard.welcome');

        $this->assertFalse($service->isCompleted($user, 'dashboard.welcome'));
        $this->assertFalse($service->isDismissed($user, 'dashboard.welcome'));
        $this->assertDatabaseMissing('tutorial_progress', [
            'user_uid' => $user->uid,
            'tutorial_key' => 'dashboard.welcome',
        ]);
    }

    public function test_index_and_tutorial_progress_migrations_can_roll_back_safely(): void
    {
        $this->prepareProductionLikeSchema();

        $tutorialMigration = $this->loadMigration(self::CREATE_TUTORIAL_PROGRESS_MIGRATION_PATH);
        $indexMigration = $this->loadMigration(self::ADD_USERS_UID_INDEX_MIGRATION_PATH);

        $tutorialMigration->down();
        $indexMigration->down();

        $this->assertFalse(Schema::hasTable('tutorial_progress'));
        $this->assertFalse($this->hasIndex('users', 'users_uid_index'));

        $indexMigration->up();
        $tutorialMigration->up();

        $this->assertTrue($this->hasIndex('users', 'users_uid_index'));
        $this->assertTrue($this->hasForeignKey('tutorial_progress', 'user_uid', 'users', 'uid'));
    }

    private function prepareProductionLikeSchema(): void
    {
        $this->bootPreferredConnection();
        $this->dropRelevantTables();
        $this->runMigration(self::CREATE_USERS_MIGRATION_PATH);
        $this->runMigration(self::ADD_USERS_UID_MIGRATION_PATH);

        $this->assertFalse($this->hasIndex('users', 'users_uid_index'));

        $this->runMigration(self::ADD_USERS_UID_INDEX_MIGRATION_PATH);
        $this->runMigration(self::CREATE_TUTORIAL_PROGRESS_MIGRATION_PATH);
    }

    private function prepareRuntimeSchema(): void
    {
        if ($this->bootIsolatedMysqlConnection()) {
            $this->prepareProductionLikeSchema();

            return;
        }

        $this->bootSqliteConnection();
        $this->dropRelevantTables();

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->runMigration(self::CREATE_TUTORIAL_PROGRESS_MIGRATION_PATH);
    }

    private function bootPreferredConnection(): void
    {
        if ($this->bootIsolatedMysqlConnection()) {
            return;
        }

        $this->bootSqliteConnection();
    }

    private function bootSqliteConnection(): void
    {
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::enableForeignKeyConstraints();
    }

    private function bootIsolatedMysqlConnection(): bool
    {
        if ($this->temporaryMysqlDatabase !== null) {
            Config::set('database.default', 'tutorial_progress_mysql');
            DB::purge('tutorial_progress_mysql');
            DB::reconnect('tutorial_progress_mysql');

            return true;
        }

        $mysqlConfig = Config::get('database.connections.mysql');

        if (! is_array($mysqlConfig) || empty($mysqlConfig['host'])) {
            return false;
        }

        Config::set('database.connections.tutorial_progress_mysql_admin', array_merge($mysqlConfig, [
            'database' => 'mysql',
        ]));

        try {
            DB::purge('tutorial_progress_mysql_admin');
            DB::connection('tutorial_progress_mysql_admin')->getPdo();
        } catch (Throwable) {
            return false;
        }

        $this->temporaryMysqlDatabase = 'gotik_tutorial_progress_'.Str::lower(Str::random(12));

        DB::connection('tutorial_progress_mysql_admin')
            ->statement("CREATE DATABASE `{$this->temporaryMysqlDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        Config::set('database.connections.tutorial_progress_mysql', array_merge($mysqlConfig, [
            'database' => $this->temporaryMysqlDatabase,
        ]));
        Config::set('database.default', 'tutorial_progress_mysql');

        DB::purge('tutorial_progress_mysql');
        DB::reconnect('tutorial_progress_mysql');
        Schema::enableForeignKeyConstraints();

        return true;
    }

    private function dropTemporaryMysqlDatabase(): void
    {
        if ($this->temporaryMysqlDatabase === null) {
            return;
        }

        $databaseName = $this->temporaryMysqlDatabase;
        $this->temporaryMysqlDatabase = null;

        DB::purge('tutorial_progress_mysql');

        $mysqlConfig = Config::get('database.connections.mysql');

        if (! is_array($mysqlConfig) || empty($mysqlConfig['host'])) {
            return;
        }

        Config::set('database.connections.tutorial_progress_mysql_admin', array_merge($mysqlConfig, [
            'database' => 'mysql',
        ]));

        try {
            DB::purge('tutorial_progress_mysql_admin');
            DB::connection('tutorial_progress_mysql_admin')->statement("DROP DATABASE IF EXISTS `{$databaseName}`");
        } catch (Throwable) {
            // Best effort cleanup for isolated test database.
        }
    }

    private function dropRelevantTables(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tutorial_progress');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();
    }

    private function user(string $email = 'tenant@example.test'): User
    {
        return User::create([
            'uid' => (string) Str::uuid(),
            'name' => 'Penyewa Test',
            'email' => $email,
            'password' => bcrypt('password'),
        ]);
    }

    private function runMigration(string $path): void
    {
        $this->loadMigration($path)->up();
    }

    private function loadMigration(string $path): object
    {
        return require base_path($path);
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->pluck('name')
                ->contains($indexName);
        }

        if ($driver === 'mysql') {
            return collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->pluck('Key_name')
                ->contains($indexName);
        }

        return false;
    }

    private function hasForeignKey(string $table, string $column, string $referencedTable, string $referencedColumn): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA foreign_key_list('{$table}')"))
                ->contains(function ($foreignKey) use ($column, $referencedTable, $referencedColumn) {
                    return $foreignKey->from === $column
                        && $foreignKey->table === $referencedTable
                        && $foreignKey->to === $referencedColumn;
                });
        }

        if ($driver === 'mysql') {
            return collect(DB::select(
                'SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND REFERENCED_TABLE_NAME IS NOT NULL',
                [$table]
            ))->contains(function ($foreignKey) use ($column, $referencedTable, $referencedColumn) {
                return $foreignKey->COLUMN_NAME === $column
                    && $foreignKey->REFERENCED_TABLE_NAME === $referencedTable
                    && $foreignKey->REFERENCED_COLUMN_NAME === $referencedColumn;
            });
        }

        return false;
    }
}
