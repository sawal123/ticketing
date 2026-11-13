<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LandingPageThemeTest extends TestCase
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
            $table->string('cover')->nullable();
            $table->integer('fee')->default(0);
            $table->text('deskripsi')->nullable();
            $table->text('map')->nullable();
            $table->integer('pajak')->default(0);
            $table->string('start_sale')->nullable();
            $table->string('slug');
            $table->string('konfirmasi')->default('0');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sliders', function ($table) {
            $table->id();
            $table->string('gambar')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();
        });

        Schema::create('hargas', function ($table) {
            $table->id();
            $table->string('event_uid');
            $table->string('name')->nullable();
            $table->string('harga')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role')->default('user');
            $table->timestamps();
        });
    }

    public function test_landing_page_renders_theme_toggle_and_scripts(): void
    {
        DB::table('landings')->insert([
            'description' => 'Gotik description',
            'keyword' => 'ticket',
            'logo' => 'logo.png',
            'icon' => 'icon.png',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('gotik_theme');
        $response->assertSee('data-theme');
        $response->assertSee('toggleGotikTheme');
        $response->assertSee('theme-toggle-btn');
        $response->assertSee('theme-icon-dark');
        $response->assertSee('theme-icon-light');
    }
}
