<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserProfileDefaultsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('name');
            $table->string('email');
            $table->string('nomor');
            $table->string('role');
            $table->string('birthday');
            $table->string('gender');
            $table->string('kota');
            $table->string('alamat');
            $table->string('password');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_user_create_gets_profile_defaults_when_dashboard_payload_is_minimal(): void
    {
        $user = User::create([
            'uid' => (string) Str::uuid(),
            'name' => 'Penyewa Baru',
            'email' => 'penyewa@example.test',
            'nomor' => '0865656',
            'role' => 'penyewa',
            'password' => bcrypt('password'),
        ]);

        $this->assertSame('2000-01-01', $user->birthday);
        $this->assertSame('pria', $user->gender);
        $this->assertSame('-', $user->kota);
        $this->assertSame('-', $user->alamat);
        $this->assertSame('-', $user->user_uid);
    }
}
