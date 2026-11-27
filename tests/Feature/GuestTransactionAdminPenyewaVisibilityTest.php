<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Admin\EventDetail as AdminEventDetail;
use App\Livewire\Admin\TransaksiIndex as AdminTransaksiIndex;
use App\Livewire\Dashboard\EventDetail as DashboardEventDetail;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\HargaCart;
use App\Models\User;
use App\Services\Tutorials\GettingStartedChecklistService;
use App\Services\Tutorials\TutorialProgressService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GuestTransactionAdminPenyewaVisibilityTest extends TestCase
{
    protected string $originalDatabaseDefault;

    protected mixed $originalSqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDatabaseDefault = (string) Config::get('database.default');
        $this->originalSqliteDatabase = Config::get('database.connections.sqlite.database');

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);

        $defaultLanding = [(object) ['logo' => '', 'icon' => '', 'keyword' => '', 'description' => '']];
        View::share([
            'user' => null,
            'csrfToken' => 'test-csrf',
            'cari' => null,
            'logo' => $defaultLanding,
            'seo' => $defaultLanding,
            'contact' => collect(),
            'contactus' => collect(),
        ]);

        $this->mock(GettingStartedChecklistService::class, function ($mock) {
            $mock->shouldReceive('buildForUser')->andReturn([
                'visible' => false,
                'dismissed' => false,
                'completed' => false,
                'tutorial_key' => GettingStartedChecklistService::TUTORIAL_KEY,
                'completed_count' => 0,
                'total_steps' => 6,
                'progress_percentage' => 0,
                'steps' => [],
                'next_step' => null,
                'primary_url' => null,
                'event_uid' => null,
                'event_name' => null,
            ]);
        });

        $this->mock(TutorialProgressService::class, function ($mock) {
            $mock->shouldReceive('isCompleted')->andReturnFalse();
            $mock->shouldReceive('isDismissed')->andReturnFalse();
        });
    }

    protected function tearDown(): void
    {
        Config::set('database.default', $this->originalDatabaseDefault);
        Config::set('database.connections.sqlite.database', $this->originalSqliteDatabase);
        DB::purge('sqlite');

        parent::tearDown();
    }

    protected function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('parent_uid')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('nomor')->nullable();
            $table->string('birthday')->nullable();
            $table->string('alamat')->nullable();
            $table->string('kota')->nullable();
            $table->string('gender')->nullable();
            $table->string('gambar')->nullable();
            $table->string('role')->default('user');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->string('category_id')->nullable();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('event');
            $table->string('alamat');
            $table->string('tanggal');
            $table->string('status');
            $table->integer('fee')->default(0);
            $table->string('cover')->nullable();
            $table->string('slug')->nullable();
            $table->string('konfirmasi')->nullable();
            $table->text('deskripsi')->nullable();
            $table->text('map')->nullable();
            $table->unsignedBigInteger('pajak')->default(0);
            $table->string('start_sale')->nullable();
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
            $table->string('kategori')->nullable();
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
            $table->string('user_uid')->nullable();
            $table->string('event_uid');
            $table->string('partner_uid')->nullable();
            $table->string('invoice')->nullable();
            $table->string('status');
            $table->string('payment_type')->nullable();
            $table->unsignedBigInteger('internet_fee')->default(0);
            $table->unsignedBigInteger('pajak')->default(0);
            $table->unsignedInteger('pajak_persen')->default(0);
            $table->string('konfirmasi')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->string('ticket_holder_name')->nullable();
            $table->string('ticket_recipient_email')->nullable();
            $table->string('ticket_holder_phone')->nullable();
            $table->string('ticket_holder_gender')->nullable();
            $table->boolean('is_guest')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->string('link')->nullable();
            $table->timestamp('payment_link_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('kategori_harga')->nullable();
            $table->unsignedBigInteger('harga_ticket')->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('disc')->default(0);
            $table->string('voucher')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cashes', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('uid_partner')->nullable();
            $table->string('uid_user')->nullable();
            $table->string('uid_event')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('nomor')->nullable();
            $table->string('alamat')->nullable();
            $table->string('lahir')->nullable();
            $table->string('gender')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Test User',
            'email' => Str::random(8).'@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ], $attrs));
    }

    protected function makeEvent(array $attrs = []): Event
    {
        return Event::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => (string) Str::uuid(),
            'event' => 'Konser Musik Gotik',
            'alamat' => 'Medan',
            'tanggal' => '2026-12-31',
            'status' => 'active',
            'fee' => 0,
            'pajak' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => 'https://maps.google.com',
            'start_sale' => '2026-01-01',
            'slug' => 'konser-musik-gotik',
        ], $attrs));
    }

    public function test_admin_can_see_guest_name_and_email_in_event_detail(): void
    {
        $admin = $this->makeUser(['role' => 'admin', 'name' => 'Admin User', 'email' => 'admin@gotik.test']);
        $penyewa = $this->makeUser(['role' => 'penyewa', 'name' => 'Penyewa Organizer', 'email' => 'penyewa@gotik.test']);
        $event = $this->makeEvent(['user_uid' => $penyewa->uid]);

        // 1. Guest transaction
        $guestCart = Cart::create([
            'uid' => 'cart-guest-001',
            'user_uid' => null,
            'event_uid' => $event->uid,
            'invoice' => 'INV-261125-GUEST1',
            'status' => 'SUCCESS',
            'payment_type' => 'qris',
            'ticket_holder_name' => 'Budi Santoso Guest',
            'ticket_recipient_email' => 'budisantoso@guest.test',
            'ticket_holder_phone' => '081234567890',
            'ticket_holder_gender' => 'pria',
            'is_guest' => true,
        ]);
        HargaCart::create([
            'uid' => $guestCart->uid,
            'kategori_harga' => 'VIP',
            'harga_ticket' => 150000,
            'quantity' => 1,
        ]);

        $component = Livewire::actingAs($admin)
            ->test(AdminEventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'transaksi')
            ->assertSee('Budi Santoso Guest')
            ->assertSee('budisantoso@guest.test')
            ->assertSee('INV-261125-GUEST1');

        $html = $component->html();
        $this->assertStringContainsString('Budi Santoso Guest', $html);
        $this->assertStringContainsString('budisantoso@guest.test', $html);
    }

    public function test_penyewa_can_see_guest_name_and_email_in_dashboard_event_detail(): void
    {
        $penyewa = $this->makeUser(['role' => 'penyewa', 'name' => 'Penyewa Organizer', 'email' => 'penyewa@gotik.test']);
        $event = $this->makeEvent(['user_uid' => $penyewa->uid]);

        $guestCart = Cart::create([
            'uid' => 'cart-guest-002',
            'user_uid' => null,
            'event_uid' => $event->uid,
            'invoice' => 'INV-261125-GUEST2',
            'status' => 'SUCCESS',
            'payment_type' => 'qris',
            'ticket_holder_name' => 'Siti Aminah Guest',
            'ticket_recipient_email' => 'sitiaminah@guest.test',
            'ticket_holder_phone' => '089876543210',
            'ticket_holder_gender' => 'wanita',
            'is_guest' => true,
        ]);
        HargaCart::create([
            'uid' => $guestCart->uid,
            'kategori_harga' => 'Festival',
            'harga_ticket' => 75000,
            'quantity' => 2,
        ]);

        $component = Livewire::actingAs($penyewa)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'transaksi')
            ->assertSee('Siti Aminah Guest')
            ->assertSee('sitiaminah@guest.test')
            ->assertSee('INV-261125-GUEST2');

        $html = $component->html();
        $this->assertStringContainsString('Siti Aminah Guest', $html);
        $this->assertStringContainsString('sitiaminah@guest.test', $html);
    }

    public function test_cart_model_buyer_methods_work_across_guest_registered_and_cash(): void
    {
        $regUser = $this->makeUser(['name' => 'Registered Buyer', 'email' => 'regbuyer@gotik.test', 'nomor' => '0811111111']);
        $event = $this->makeEvent();

        // Guest Cart
        $guestCart = Cart::create([
            'uid' => 'cart-g-test',
            'user_uid' => null,
            'event_uid' => $event->uid,
            'payment_type' => 'qris',
            'status' => 'SUCCESS',
            'ticket_holder_name' => 'Guest Person',
            'ticket_recipient_email' => 'guest@test.id',
            'ticket_holder_phone' => '0822222222',
            'ticket_holder_gender' => 'pria',
            'is_guest' => true,
        ]);

        $this->assertTrue($guestCart->isGuest());
        $this->assertSame('Guest Person', $guestCart->buyerName());
        $this->assertSame('guest@test.id', $guestCart->buyerEmail());
        $this->assertSame('0822222222', $guestCart->buyerPhone());
        $this->assertSame('pria', $guestCart->buyerGender());

        // Registered User Cart
        $userCart = Cart::create([
            'uid' => 'cart-u-test',
            'user_uid' => $regUser->uid,
            'event_uid' => $event->uid,
            'payment_type' => 'qris',
            'status' => 'SUCCESS',
            'is_guest' => false,
        ]);

        $this->assertFalse($userCart->isGuest());
        $this->assertSame('Registered Buyer', $userCart->buyerName());
        $this->assertSame('regbuyer@gotik.test', $userCart->buyerEmail());
        $this->assertSame('0811111111', $userCart->buyerPhone());

        // Cash Cart
        $cashCart = Cart::create([
            'uid' => 'cart-c-test',
            'user_uid' => $regUser->uid, // Cashier
            'event_uid' => $event->uid,
            'payment_type' => 'cash',
            'status' => 'SUCCESS',
            'is_guest' => false,
        ]);
        Cash::create([
            'uid' => $cashCart->uid,
            'name' => 'Cash Customer',
            'email' => 'cash@offline.test',
            'nomor' => '0833333333',
            'gender' => 'wanita',
        ]);

        $this->assertSame('Cash Customer', $cashCart->buyerName());
        $this->assertSame('cash@offline.test', $cashCart->buyerEmail());
        $this->assertSame('0833333333', $cashCart->buyerPhone());
        $this->assertSame('wanita', $cashCart->buyerGender());
    }

    public function test_admin_transaksi_index_displays_guest_name_and_email(): void
    {
        $admin = $this->makeUser(['role' => 'admin', 'name' => 'Admin User', 'email' => 'admin@gotik.test']);
        $event = $this->makeEvent();

        $guestCart = Cart::create([
            'uid' => 'cart-guest-index',
            'user_uid' => null,
            'event_uid' => $event->uid,
            'invoice' => 'INV-261125-INDEX1',
            'status' => 'SUCCESS',
            'payment_type' => 'qris',
            'ticket_holder_name' => 'Index Guest User',
            'ticket_recipient_email' => 'indexguest@gotik.test',
            'is_guest' => true,
        ]);
        HargaCart::create([
            'uid' => $guestCart->uid,
            'kategori_harga' => 'Reguler',
            'harga_ticket' => 50000,
            'quantity' => 1,
        ]);

        $component = Livewire::actingAs($admin)
            ->test(AdminTransaksiIndex::class)
            ->assertSee('Index Guest User')
            ->assertSee('indexguest@gotik.test')
            ->assertSee('INV-261125-INDEX1');

        $html = $component->html();
        $this->assertStringContainsString('Index Guest User', $html);
        $this->assertStringContainsString('indexguest@gotik.test', $html);
    }
}
