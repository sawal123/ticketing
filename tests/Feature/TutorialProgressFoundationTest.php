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

class TutorialProgressFoundationTest extends TestCase
{
    private const MIGRATION_PATH = 'database/migrations/2026_08_31_100000_create_tutorial_progress_table.php';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::enableForeignKeyConstraints();

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
            $table->softDeletes();
        });

        $migration = require base_path(self::MIGRATION_PATH);
        $migration->up();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_migration_persists_progress_and_allows_many_tutorial_keys_per_user(): void
    {
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

    private function user(string $email = 'tenant@example.test'): User
    {
        return User::create([
            'uid' => (string) Str::uuid(),
            'name' => 'Penyewa Test',
            'email' => $email,
            'password' => bcrypt('password'),
        ]);
    }
}
