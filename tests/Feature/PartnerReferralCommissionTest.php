<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Admin\EventDetail;
use App\Livewire\Dashboard\PartnerIndex;
use App\Models\Cart;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Partner;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Partners\PartnerReferralService;
use App\Services\Tickets\TicketPricingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerReferralCommissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_eo_can_create_and_edit_partner_with_discount_and_commission_and_validation_works(): void
    {
        $tenant = $this->user('penyewa');
        $event = $this->event($tenant);

        // Invalid discount percent (>= 100) rejected
        Livewire::actingAs($tenant)
            ->test(PartnerIndex::class)
            ->set('name', 'Komunitas Musik A')
            ->set('email', 'partner-a@example.test')
            ->set('city', 'Jakarta')
            ->set('alamat', 'Jl. Sudirman')
            ->set('hp', '081234567890')
            ->set('selected_event_uid', $event->uid)
            ->set('discount_type', 'persen')
            ->set('discount_value', 100)
            ->call('save')
            ->assertHasErrors('discount_value');

        // Valid create with discount + commission
        Livewire::actingAs($tenant)
            ->test(PartnerIndex::class)
            ->set('name', 'Komunitas Musik A')
            ->set('email', 'partner-a@example.test')
            ->set('city', 'Jakarta')
            ->set('alamat', 'Jl. Sudirman')
            ->set('hp', '081234567890')
            ->set('selected_event_uid', $event->uid)
            ->set('discount_type', 'persen')
            ->set('discount_value', 10)
            ->set('discount_max', 25000)
            ->set('allow_promo_tickets', false)
            ->set('commission_type', 'rupiah')
            ->set('commission_value', 5000)
            ->call('save')
            ->assertHasNoErrors();

        $partner = Partner::where('email', 'partner-a@example.test')->firstOrFail();
        $this->assertSame('persen', $partner->discount_type);
        $this->assertSame(10, $partner->discount_value);
        $this->assertSame(25000, $partner->discount_max);
        $this->assertFalse($partner->allow_promo_tickets);
        $this->assertSame('rupiah', $partner->commission_type);
        $this->assertSame(5000, $partner->commission_value);
        $this->assertStringContainsString('/ticket/'.$event->slug.'?ref='.$partner->referensi, (string) $partner->referralUrl($event));
    }

    public function test_referral_link_captures_active_partner_and_ignores_inactive_or_foreign_event(): void
    {
        $tenant = $this->user('penyewa');
        $eventA = $this->event($tenant, 'event-a');
        $eventB = $this->event($tenant, 'event-b');

        $activePartner = Partner::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $eventA->uid,
            'referensi' => 'PARTNER-ACTIVE1',
            'name' => 'Partner Aktif',
            'email' => 'active@example.test',
            'hp' => '08123',
            'alamat' => 'Jakarta',
            'city' => 'Jakarta',
            'status' => 'active',
            'discount_type' => 'persen',
            'discount_value' => 10,
        ]);

        $inactivePartner = Partner::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $eventA->uid,
            'referensi' => 'PARTNER-OFF1',
            'name' => 'Partner Nonaktif',
            'email' => 'off@example.test',
            'hp' => '08123',
            'alamat' => 'Jakarta',
            'city' => 'Jakarta',
            'status' => 'inactive',
        ]);

        $this->get('/ticket/'.$eventA->slug.'?ref='.$activePartner->referensi)
            ->assertOk()
            ->assertSee('Link Resmi Partner: Partner Aktif')
            ->assertSessionHas(PartnerReferralService::SESSION_KEY.'.'.$eventA->uid, $activePartner->uid);

        $this->flushSession();

        // Inactive partner is ignored
        $this->get('/ticket/'.$eventA->slug.'?ref='.$inactivePartner->referensi)
            ->assertOk()
            ->assertDontSee('Link Resmi Partner: Partner Nonaktif');

        // Partner for Event A is ignored on Event B
        $this->get('/ticket/'.$eventB->slug.'?ref='.$activePartner->referensi)
            ->assertOk()
            ->assertDontSee('Link Resmi Partner: Partner Aktif');
    }

    public function test_guest_checkout_via_partner_link_applies_discount_commission_and_blocks_manual_voucher(): void
    {
        $tenant = $this->user('penyewa');
        $event = $this->event($tenant, 'partner-fest');
        $harga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Regular',
            'qty' => 50,
            'harga' => 100000,
            'status' => 'active',
            'max_order_qty' => 5,
        ]);

        $partner = Partner::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'referensi' => 'PARTNER-FEST10',
            'name' => 'Partner Fest',
            'email' => 'fest@example.test',
            'hp' => '08123456',
            'alamat' => 'Bandung',
            'city' => 'Bandung',
            'status' => 'active',
            'discount_type' => 'persen',
            'discount_value' => 10,
            'discount_max' => 50000,
            'allow_promo_tickets' => false,
            'commission_type' => 'persen',
            'commission_value' => 5,
        ]);

        Voucher::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'code' => 'DOUBLE10',
            'unit' => 'persen',
            'nominal' => 10,
            'min_beli' => 0,
            'max_disc' => 50000,
            'limit' => 10,
            'status' => 'active',
        ]);

        // 1. Guest visits event via partner referral link
        $this->get('/ticket/'.$event->slug.'?ref='.$partner->referensi)->assertOk();

        // 2. Guest checks out 2 tickets (2 x 100.000 = 200.000; 10% partner discount = 20.000; net subtotal = 180.000; 5% commission = 9.000)
        $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $harga->id, 'quantity' => 2]],
        ])->assertRedirect();

        $cart = Cart::firstOrFail();
        $this->assertTrue($cart->isGuest());
        $this->assertSame($partner->uid, $cart->partner_uid);
        $this->assertSame(20000, (int) $cart->partner_discount_amount);
        $this->assertSame('persen', $cart->partner_commission_type);
        $this->assertSame(5, (int) $cart->partner_commission_value);
        $this->assertSame(9000, (int) $cart->partner_commission_amount);

        $pricing = app(TicketPricingService::class)->calculateCart($cart);
        $this->assertSame(200000, $pricing['ticket_total']);
        $this->assertSame(20000, $pricing['partner_discount']);
        $this->assertSame(180000, $pricing['subtotal']);
        $this->assertSame(9000, $pricing['partner_commission']);

        // 3. Attempting to stack manual voucher must be rejected
        $checkoutUrl = route('checkout.guest.show', ['uid' => $cart->uid]);
        $this->from($checkoutUrl)
            ->post('/checkVoucer', [
                'cartUid' => $cart->uid,
                'code' => 'DOUBLE10',
            ])
            ->assertRedirect($checkoutUrl)
            ->assertSessionHas('vError');

        $this->assertDatabaseMissing('cart_vouchers', [
            'uid' => $cart->uid,
            'code' => 'DOUBLE10',
        ]);
    }

    public function test_partner_allow_promo_tickets_and_commission_recap_preserves_historical_snapshot(): void
    {
        $tenant = $this->user('penyewa');
        $event = $this->event($tenant, 'promo-partner-event');
        $promoHarga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Promo Tier',
            'qty' => 50,
            'harga' => 100000,
            'is_promo' => true,
            'promo_type' => Harga::PROMO_TYPE_PERSEN,
            'promo_value' => 15, // effective 85.000
            'status' => 'active',
            'max_order_qty' => 5,
        ]);

        $partner = Partner::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'referensi' => 'PARTNER-COM1',
            'name' => 'Partner Komisi',
            'email' => 'komisi@example.test',
            'hp' => '08123456',
            'alamat' => 'Surabaya',
            'city' => 'Surabaya',
            'status' => 'active',
            'discount_type' => 'rupiah',
            'discount_value' => 10000,
            'allow_promo_tickets' => false,
            'commission_type' => 'rupiah',
            'commission_value' => 5000,
        ]);

        // Visit & checkout promo ticket: partner discount blocked because allow_promo_tickets=false, but partner_uid + commission still recorded!
        $this->get('/ticket/'.$event->slug.'?ref='.$partner->referensi)->assertOk();
        $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $promoHarga->id, 'quantity' => 2]],
        ])->assertRedirect();

        $cart = Cart::firstOrFail();
        $this->assertSame($partner->uid, $cart->partner_uid);
        $this->assertSame(0, (int) $cart->partner_discount_amount);
        $this->assertSame(10000, (int) $cart->partner_commission_amount); // 2 tickets x Rp 5.000

        // Mark cart SUCCESS, then change partner's commission rate; historical snapshot must remain 10.000
        $cart->update([
            'status' => Cart::STATUS_SUCCESS,
            'gross_amount' => 170000,
            'paid_at' => now(),
        ]);

        $partner->update(['commission_value' => 1000]);

        // PartnerIndex counts only SUCCESS transactions and keeps historical 10.000 commission
        Livewire::actingAs($tenant)
            ->test(PartnerIndex::class)
            ->assertSet('totalPartnerCommission', 10000)
            ->call('openTransactionsModal', $partner->id)
            ->assertSee($cart->invoice)
            ->assertSee('10.000');
    }

    public function test_historical_zero_commission_snapshot_and_inactive_partner_commission_guard(): void
    {
        $tenant = $this->user('penyewa');
        $event = $this->event($tenant, 'zero-commission-event');
        $harga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Reguler',
            'qty' => 20,
            'harga' => 100000,
            'status' => 'active',
            'max_order_qty' => 5,
        ]);

        $partner = Partner::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'referensi' => 'PARTNER-ZERO1',
            'name' => 'Partner Nol Komisi',
            'email' => 'zero@example.test',
            'hp' => '08123',
            'alamat' => 'Jakarta',
            'city' => 'Jakarta',
            'status' => 'active',
            'commission_type' => null,
            'commission_value' => null,
        ]);

        $this->get('/ticket/'.$event->slug.'?ref='.$partner->referensi)->assertOk();
        $this->post('/checkout', [
            'event_uid' => $event->uid,
            'tickets' => [['harga_id' => $harga->id, 'quantity' => 1]],
        ])->assertRedirect();

        $cart = Cart::firstOrFail();
        $cart->update([
            'status' => Cart::STATUS_SUCCESS,
            'gross_amount' => 100000,
            'partner_commission_amount' => 0,
            'paid_at' => now(),
        ]);

        // Later EO enables commission on partner; historical SUCCESS cart must remain 0
        $partner->update([
            'commission_type' => Partner::TYPE_PERSEN,
            'commission_value' => 10,
        ]);

        $pricing = app(TicketPricingService::class)->calculateCart($cart->fresh());
        $this->assertSame(0, $pricing['partner_commission']);

        // Deactivating partner disables active commission calculation for new carts
        $partner->update(['status' => 'inactive']);
        $this->assertFalse($partner->fresh()->hasActiveCommission());
    }

    public function test_admin_event_detail_cannot_mutate_ticket_from_other_event(): void
    {
        $admin = $this->user('admin');
        $tenant = $this->user('penyewa');
        $eventA = $this->event($tenant, 'admin-scope-a');
        $eventB = $this->event($tenant, 'admin-scope-b');

        $ticketB = Harga::create([
            'uid' => $eventB->uid,
            'kategori' => 'Ticket B',
            'qty' => 10,
            'harga' => 50000,
            'status' => 'active',
            'max_order_qty' => 5,
        ]);

        $thrown = false;
        try {
            Livewire::actingAs($admin)
                ->test(EventDetail::class, ['uid' => $eventA->uid])
                ->call('toggleTicketStatus', $ticketB->id);
        } catch (ModelNotFoundException) {
            $thrown = true;
        }

        $this->assertTrue($thrown, 'Expected ModelNotFoundException when mutating ticket from another event.');
        $this->assertSame('active', $ticketB->fresh()->status);
    }

    private function user(string $role = 'penyewa'): User
    {
        return User::create([
            'uid' => (string) Str::uuid(),
            'name' => 'Test '.ucfirst($role),
            'email' => $role.'-'.Str::random(6).'@example.test',
            'nomor' => '081234567890',
            'birthday' => '1995-01-01',
            'alamat' => 'Jakarta',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'role' => $role,
            'password' => Hash::make('password123'),
        ]);
    }

    private function event(User $tenant, ?string $slug = null): Event
    {
        return Event::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event' => 'Partner Event '.Str::upper(Str::random(4)),
            'slug' => $slug ?? ('partner-event-'.Str::lower(Str::random(6))),
            'cover' => 'cover.jpg',
            'tanggal' => '2027-01-20',
            'alamat' => 'Jakarta',
            'status' => 'active',
            'konfirmasi' => '1',
            'fee' => 0,
            'pajak' => 0,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('parent_uid')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('nomor')->default('-');
            $table->string('birthday')->default('2000-01-01');
            $table->string('alamat')->default('-');
            $table->string('kota')->default('-');
            $table->string('gender')->default('pria');
            $table->string('role')->default('user');
            $table->string('password');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('user_uid')->nullable();
            $table->string('event');
            $table->string('slug')->unique();
            $table->string('cover')->default('cover.jpg');
            $table->string('tanggal')->default('2027-01-20');
            $table->string('alamat')->default('Jakarta');
            $table->string('status')->default('active');
            $table->string('konfirmasi')->default('1');
            $table->integer('fee')->default(0);
            $table->integer('pajak')->default(0);
            $table->text('deskripsi')->nullable();
            $table->string('registration_mode')->default('ticketing');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('hargas', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('kategori');
            $table->integer('harga');
            $table->boolean('is_promo')->default(false);
            $table->string('promo_type', 20)->nullable();
            $table->unsignedInteger('promo_value')->nullable();
            $table->integer('qty');
            $table->integer('sold_qty')->default(0);
            $table->integer('reserved_qty')->default(0);
            $table->integer('max_order_qty')->default(5);
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('partners', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('user_uid');
            $table->string('event_uid')->nullable();
            $table->string('referensi');
            $table->string('name');
            $table->string('email');
            $table->string('hp');
            $table->text('alamat');
            $table->string('city');
            $table->string('status')->default('active');
            $table->string('discount_type', 20)->nullable();
            $table->unsignedInteger('discount_value')->nullable();
            $table->unsignedInteger('discount_max')->nullable();
            $table->boolean('allow_promo_tickets')->default(false);
            $table->string('commission_type', 20)->nullable();
            $table->unsignedInteger('commission_value')->nullable();
            $table->timestamps();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('user_uid')->nullable();
            $table->boolean('is_guest')->default(false);
            $table->string('event_uid');
            $table->string('partner_uid', 100)->nullable();
            $table->unsignedInteger('partner_discount_amount')->default(0);
            $table->string('partner_commission_type', 20)->nullable();
            $table->unsignedInteger('partner_commission_value')->nullable();
            $table->unsignedInteger('partner_commission_amount')->default(0);
            $table->string('invoice')->nullable()->unique();
            $table->string('ticket_holder_name')->nullable();
            $table->string('ticket_recipient_email')->nullable();
            $table->string('ticket_holder_gender', 20)->nullable();
            $table->string('ticket_holder_phone', 30)->nullable();
            $table->string('guest_token_hash', 64)->nullable();
            $table->string('guest_session_key', 64)->nullable();
            $table->string('status');
            $table->string('konfirmasi')->nullable();
            $table->text('link')->nullable();
            $table->string('payment_type')->nullable();
            $table->integer('internet_fee')->default(0);
            $table->integer('pajak')->default(0);
            $table->integer('pajak_persen')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reservation_released_at')->nullable();
            $table->timestamp('payment_link_expires_at')->nullable();
            $table->integer('gross_amount')->nullable();
            $table->unsignedBigInteger('payment_gateway_id')->nullable();
            $table->string('payment_fee_mode')->nullable();
            $table->decimal('payment_fee_fixed', 15, 2)->nullable();
            $table->decimal('payment_fee_percent', 8, 4)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('orderBy')->default('1');
            $table->string('event_uid');
            $table->unsignedBigInteger('harga_id');
            $table->integer('quantity');
            $table->integer('harga_ticket');
            $table->string('kategori_harga');
            $table->string('voucher')->nullable();
            $table->integer('disc')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('vouchers', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('code');
            $table->string('unit');
            $table->integer('nominal');
            $table->integer('min_beli')->default(0);
            $table->integer('max_disc')->default(0);
            $table->integer('digunakan')->default(0);
            $table->integer('limit')->default(100);
            $table->string('status')->default('active');
            $table->boolean('allow_promo_tickets')->default(false);
            $table->timestamps();
        });

        Schema::create('cart_vouchers', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('uid_vouchers')->nullable();
            $table->string('user_uid')->nullable();
            $table->string('event_uid');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('voucher_reservations', function ($table) {
            $table->id();
            $table->unsignedBigInteger('voucher_id');
            $table->string('voucher_uid');
            $table->string('cart_uid')->unique();
            $table->string('invoice')->nullable();
            $table->string('code');
            $table->string('status');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('voucher_usages', function ($table) {
            $table->id();
            $table->unsignedBigInteger('voucher_id');
            $table->string('voucher_uid');
            $table->string('cart_uid')->unique();
            $table->string('invoice');
            $table->integer('discount_amount');
            $table->timestamp('used_at');
            $table->timestamps();
        });

        Schema::create('talent', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('talent')->nullable();
            $table->string('gambar')->nullable();
            $table->timestamps();
        });

        Schema::create('fasilitas', function ($table) {
            $table->id();
            $table->string('nama')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('event_fasilitas', function ($table) {
            $table->id();
            $table->string('event_uid');
            $table->unsignedBigInteger('fasilitas_id');
            $table->timestamps();
        });
    }
}
