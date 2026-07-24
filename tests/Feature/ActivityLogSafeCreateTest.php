<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityLogSafeCreateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');
    }

    public function test_activity_log_write_failure_does_not_break_primary_flow(): void
    {
        $log = ActivityLog::safeCreate([
            'user_uid' => 'user-uid',
            'activity' => 'Data Mutation',
            'login_status' => 'Success',
            'description' => 'Attempted event creation',
            'impact_level' => 'Sensitif',
            'ip_address' => '127.0.0.1',
            'location' => 'Localhost',
            'user_agent' => 'Feature Test',
            'device_id' => 'device-id',
            'session_id' => 'session-id',
        ]);

        $this->assertNull($log);
    }
}
