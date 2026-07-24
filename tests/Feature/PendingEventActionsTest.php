<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\EventIndex;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class PendingEventActionsTest extends TestCase
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
            $table->string('name');
            $table->string('email');
            $table->string('nomor');
            $table->string('birthday');
            $table->string('gender');
            $table->string('kota');
            $table->string('alamat');
            $table->string('role');
            $table->string('parent_uid')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->string('category_id')->nullable();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event');
            $table->string('alamat');
            $table->string('tanggal');
            $table->string('status');
            $table->string('cover');
            $table->unsignedBigInteger('fee')->default(0);
            $table->text('deskripsi');
            $table->text('map')->nullable();
            $table->unsignedBigInteger('pajak')->default(0);
            $table->string('start_sale')->nullable();
            $table->string('slug')->nullable();
            $table->string('konfirmasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_owner_can_soft_delete_pending_event(): void
    {
        $user = $this->makeUser();
        $event = $this->makeEvent($user->uid, ['uid' => 'pending-event', 'konfirmasi' => null]);

        Livewire::actingAs($user)
            ->test(EventIndex::class)
            ->call('deletePendingEvent', $event->uid);

        $this->assertSoftDeleted('events', [
            'uid' => 'pending-event',
        ]);
    }

    public function test_approved_event_is_not_deleted_from_pending_action(): void
    {
        $user = $this->makeUser();
        $event = $this->makeEvent($user->uid, ['uid' => 'approved-event', 'konfirmasi' => 'approved']);

        Livewire::actingAs($user)
            ->test(EventIndex::class)
            ->call('deletePendingEvent', $event->uid);

        $this->assertDatabaseHas('events', [
            'uid' => 'approved-event',
            'deleted_at' => null,
        ]);
    }

    private function makeUser(): User
    {
        return User::create([
            'uid' => 'owner-uid',
            'name' => 'Penyewa',
            'email' => 'penyewa@example.test',
            'nomor' => '08123456789',
            'role' => 'penyewa',
            'password' => bcrypt('password'),
        ]);
    }

    private function makeEvent(string $ownerUid, array $overrides = []): Event
    {
        return Event::create(array_merge([
            'category_id' => null,
            'uid' => 'event-uid',
            'user_uid' => $ownerUid,
            'event' => 'Test Event',
            'alamat' => 'Test Venue',
            'tanggal' => '2026-08-01 19:00',
            'status' => 'inactive',
            'cover' => 'event-uid.jpg',
            'fee' => 0,
            'deskripsi' => 'Test description',
            'map' => null,
            'pajak' => 0,
            'start_sale' => '2026-07-25 10:00',
            'slug' => 'test-event',
            'konfirmasi' => null,
        ], $overrides));
    }
}
