<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventCoverFillableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

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

    public function test_event_create_persists_cover_from_mass_assignment(): void
    {
        $event = Event::create([
            'category_id' => null,
            'uid' => 'event-uid',
            'user_uid' => 'owner-uid',
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
        ]);

        $this->assertSame('event-uid.jpg', $event->cover);
        $this->assertDatabaseHas('events', [
            'uid' => 'event-uid',
            'cover' => 'event-uid.jpg',
        ]);
    }
}
