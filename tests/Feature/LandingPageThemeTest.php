<?php

namespace Tests\Feature;

use App\Models\User;
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
            $table->string('uid')->nullable();
            $table->string('event_uid')->nullable();
            $table->string('kategori')->nullable();
            $table->string('name')->nullable();
            $table->string('harga')->nullable();
            $table->integer('qty')->default(100);
            $table->integer('sold_qty')->default(0);
            $table->integer('reserved_qty')->default(0);
            $table->string('status')->default('active');
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

        Schema::create('talent', function ($table) {
            $table->id();
            $table->string('uid')->nullable();
            $table->string('talent')->nullable();
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
            $table->softDeletes();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid')->nullable();
            $table->string('user_uid')->nullable();
            $table->string('event_uid')->nullable();
            $table->string('invoice')->nullable();
            $table->string('status')->default('PAID');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->string('uid')->nullable();
            $table->string('harga_uid')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();
            $table->softDeletes();
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

    public function test_search_page_renders_with_theme_tokens_and_toggle(): void
    {
        DB::table('landings')->insert([
            'description' => 'Gotik description',
            'keyword' => 'ticket',
            'logo' => 'logo.png',
            'icon' => 'icon.png',
        ]);

        $response = $this->get('/search');

        $response->assertStatus(200);
        $response->assertSee('search-page-wrap');
        $response->assertSee('var(--bg)');
        $response->assertSee('theme-toggle-btn');
        $response->assertSee('gotik_theme');
        $response->assertSee('Tidak ada event yang ditemukan');
    }

    public function test_search_page_renders_events_with_dynamic_theme_cards(): void
    {
        DB::table('landings')->insert([
            'description' => 'Gotik description',
            'keyword' => 'ticket',
            'logo' => 'logo.png',
            'icon' => 'icon.png',
        ]);

        DB::table('users')->insert([
            'uid' => 'org-1',
            'name' => 'GoTik Organizer',
            'email' => 'org@example.com',
            'role' => 'penyewa',
        ]);

        DB::table('events')->insert([
            'uid' => 'event-konser-1',
            'user_uid' => 'org-1',
            'event' => 'Konser Musik Keren',
            'alamat' => 'Jakarta Convention Center',
            'tanggal' => '2026-12-01 19:00:00',
            'status' => 'active',
            'slug' => 'konser-musik-keren',
            'konfirmasi' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hargas')->insert([
            'uid' => 'event-konser-1',
            'event_uid' => 'event-konser-1',
            'name' => 'VIP',
            'harga' => '150000',
            'status' => 'active',
        ]);

        $response = $this->get('/search/Konser');

        $response->assertStatus(200);
        $response->assertSee('Konser Musik Keren');
        $response->assertSee('Jakarta Convention Center');
        $response->assertSee('Rp 150.000');
        $response->assertSee('event-card');
        $response->assertSee('theme-toggle-btn');
    }

    public function test_login_page_renders_password_eye_toggle(): void
    {
        DB::table('landings')->insert([
            'description' => 'Gotik description',
            'keyword' => 'ticket',
            'logo' => 'logo.png',
            'icon' => 'icon.png',
        ]);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('showPassword');
        $response->assertSee('id="password"', false);
        $response->assertSee(':type="showPassword ? \'text\' : \'password\'"', false);
    }

    public function test_ticket_detail_page_renders_with_refined_typography(): void
    {
        DB::table('landings')->insert([
            'description' => 'Gotik description',
            'keyword' => 'ticket',
            'logo' => 'logo.png',
            'icon' => 'icon.png',
        ]);

        DB::table('users')->insert([
            'uid' => 'org-2',
            'name' => 'Krakatau Park Organizer',
            'email' => 'krakatau@example.com',
            'role' => 'penyewa',
        ]);

        DB::table('events')->insert([
            'uid' => 'event-krakatau-1',
            'user_uid' => 'org-2',
            'event' => 'Krakatau Park',
            'alamat' => 'Krakatau Park, Indonesia',
            'tanggal' => '2026-12-01 10:00:00',
            'status' => 'active',
            'deskripsi' => 'Berlibur ke Lampung rasanya belum lengkap jika belum mengunjungi Krakatau Park Lampung.',
            'slug' => 'krakatau-park',
            'konfirmasi' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hargas')->insert([
            'uid' => 'event-krakatau-1',
            'event_uid' => 'event-krakatau-1',
            'kategori' => 'Tiket Masuk',
            'name' => 'Tiket Masuk',
            'harga' => '60000',
            'qty' => 100,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'status' => 'active',
        ]);

        $response = $this->get('/ticket/krakatau-park');

        $response->assertStatus(200);
        $response->assertSee('Krakatau Park');
        $response->assertSee('Berlibur ke Lampung');
        $response->assertSee('event-title');
        $response->assertSee('ticket-panel');
        $response->assertSee('tier-name');
        $response->assertSee('description-text');
    }

    public function test_detail_ticket_checkout_page_assets_use_refined_fonts_and_theme_variables(): void
    {
        $bayarCss = file_get_contents(public_path('landing/css/bayartiket.css'));
        $bayarBlade = file_get_contents(resource_path('views/frontend/page/bayartiket.blade.php'));

        // Breadcrumb, event name, and card title use clean sans-serif instead of serif / monospace
        $this->assertStringNotContainsString('.event-name {
    font-family: "Playfair Display"', $bayarCss);
        $this->assertStringNotContainsString('.card-title {
    font-family: "Playfair Display"', $bayarCss);
        $this->assertStringNotContainsString('.total-row .total-value {
    font-family: "Playfair Display"', $bayarCss);

        // Alert text has readable color in both modes
        $this->assertStringContainsString('.alert-text {
    color: var(--text);', $bayarCss);

        // Form labels do not have hardcoded white text overriding theme
        $this->assertStringNotContainsString('Nama Lengkap sesuai KTP</label>', $bayarBlade);
        $this->assertStringContainsString('color:var(--text);', $bayarBlade);
    }

    public function test_transaksi_page_renders_with_theme_and_refined_fonts(): void
    {
        DB::table('landings')->insert([
            'description' => 'Gotik description',
            'keyword' => 'ticket',
            'logo' => 'logo.png',
            'icon' => 'icon.png',
        ]);

        $userId = DB::table('users')->insertGetId([
            'uid' => 'user-tx-1',
            'name' => 'Sawalinto',
            'email' => 'sawalinto@example.com',
            'role' => 'user',
        ]);
        $user = User::find($userId);

        DB::table('events')->insert([
            'uid' => 'event-tx-1',
            'user_uid' => 'org-2',
            'event' => 'Festival Musik Gotik',
            'alamat' => 'Medan, SUMUT',
            'tanggal' => '2026-12-16 12:00:00',
            'status' => 'active',
            'slug' => 'festival-musik-gotik',
            'konfirmasi' => '1',
            'cover' => 'sample.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('carts')->insert([
            'uid' => 'cart-tx-1',
            'user_uid' => 'user-tx-1',
            'event_uid' => 'event-tx-1',
            'invoice' => 'INV-2026-001',
            'status' => 'PAID',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/transaksi');

        $response->assertStatus(200);
        $response->assertSee('Riwayat Transaksi');
        $response->assertSee('page-eyebrow');
        $response->assertSee('filter-chips');
        $response->assertSee('list-transaksi.css');
        $response->assertSee('Festival Musik Gotik');
        $response->assertSee('INV-2026-001');
    }

    public function test_transaksi_css_uses_theme_variables_and_refined_fonts(): void
    {
        $txCss = file_get_contents(public_path('landing/css/list-transaksi.css'));

        $this->assertStringContainsString('[data-theme="light"]', $txCss);
        $this->assertStringContainsString('[data-theme="dark"]', $txCss);
        $this->assertStringNotContainsString('Playfair Display', $txCss);
        $this->assertStringNotContainsString('Space Mono', $txCss);
    }
}
