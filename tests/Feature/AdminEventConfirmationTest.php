<?php

namespace Tests\Feature;

use App\Livewire\Admin\EventDetail;
use App\Livewire\Admin\EventIndex;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEventConfirmationTest extends TestCase
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

        Schema::create('talent', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('talent');
            $table->string('gambar')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });

        Schema::create('hargas', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('kategori');
            $table->unsignedInteger('qty')->default(0);
            $table->unsignedInteger('sold_qty')->default(0);
            $table->unsignedInteger('reserved_qty')->default(0);
            $table->unsignedBigInteger('harga')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('invoice')->nullable();
            $table->string('status');
            $table->string('payment_type')->nullable();
            $table->unsignedBigInteger('internet_fee')->default(0);
            $table->unsignedBigInteger('pajak')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->unsignedBigInteger('harga_id')->nullable();
            $table->string('event_uid')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedBigInteger('harga_ticket')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_admin_can_confirm_event_from_detail_page(): void
    {
        $admin = $this->makeAdmin();
        $event = $this->makePendingEvent();

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('confirmEvent');

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'konfirmasi' => '1',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_confirm_event_from_event_table(): void
    {
        $admin = $this->makeAdmin();
        $event = $this->makePendingEvent(['uid' => 'pending-event-table']);

        Livewire::actingAs($admin)
            ->test(EventIndex::class)
            ->call('confirmEvent', $event->uid);

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'konfirmasi' => '1',
            'status' => 'active',
        ]);
    }

    private function makeAdmin(): User
    {
        return User::create([
            'uid' => 'admin-uid',
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'nomor' => '08123456789',
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);
    }

    private function makePendingEvent(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'category_id' => null,
            'uid' => 'pending-event',
            'user_uid' => 'owner-uid',
            'event' => 'Pending Event',
            'alamat' => 'Test Venue',
            'tanggal' => '2026-08-01 19:00',
            'status' => 'inactive',
            'cover' => 'pending-event.jpg',
            'fee' => 0,
            'deskripsi' => 'Test description',
            'map' => null,
            'pajak' => 0,
            'start_sale' => '2026-07-25 10:00',
            'slug' => 'pending-event',
            'konfirmasi' => null,
        ], $overrides));
    }
}
