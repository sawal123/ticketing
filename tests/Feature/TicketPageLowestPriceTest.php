<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketPageLowestPriceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Cache::flush();

        Schema::create('landings', function ($table) {
            $table->id();
            $table->string('description')->nullable();
            $table->string('keyword')->nullable();
            $table->string('logo')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('contacts', function ($table) {
            $table->id();
            $table->string('sosmed')->nullable();
            $table->string('name')->nullable();
            $table->string('link')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
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
            $table->string('slug');
            $table->string('konfirmasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
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

        Schema::create('talent', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('talent');
            $table->string('gambar')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });

        Schema::create('fasilitas', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('event_fasilitas', function ($table) {
            $table->id();
            $table->string('event_uid');
            $table->unsignedBigInteger('fasilitas_id');
            $table->timestamps();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('invoice')->nullable();
            $table->string('status');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_mobile_bottom_price_uses_lowest_ticket_price(): void
    {
        DB::table('landings')->insert([
            'description' => 'Gotik',
            'keyword' => 'ticket',
            'logo' => 'logo.png',
            'icon' => 'icon.png',
        ]);

        DB::table('events')->insert([
            'uid' => 'event-uid',
            'user_uid' => 'owner-uid',
            'event' => 'HI REV ENDURANCE OTOFEST',
            'alamat' => 'Jl. Negara',
            'tanggal' => '2026-09-13 10:00:00',
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Event description',
            'map' => 'https://maps.example.test',
            'pajak' => 0,
            'start_sale' => '2026-07-24 10:00:00',
            'slug' => 'hi-rev-endurance-otofest',
            'konfirmasi' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hargas')->insert([
            [
                'uid' => 'event-uid',
                'kategori' => 'Regular',
                'qty' => 100,
                'sold_qty' => 0,
                'reserved_qty' => 0,
                'harga' => 160000,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uid' => 'event-uid',
                'kategori' => 'Presale',
                'qty' => 100,
                'sold_qty' => 0,
                'reserved_qty' => 0,
                'harga' => 50000,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->get('/ticket/hi-rev-endurance-otofest');

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/<div class="price-value">\s*Rp 50\.000\s*<\/div>/',
            $response->getContent()
        );
    }
}
