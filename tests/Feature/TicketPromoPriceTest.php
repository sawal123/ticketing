<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\DemoIndex;
use App\Livewire\Dashboard\EventDetail as DashboardEventDetail;
use App\Livewire\Dashboard\VoucherIndex;
use App\Models\Cart;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Tickets\TicketPricingService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TicketPromoPriceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');
        Config::set('gate-tokens.key', 'base64:'.base64_encode(str_repeat('g', 32)));

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

    public function test_harga_promo_calculates_persen_and_rupiah_effective_price_and_discount_percent(): void
    {
        $persen = new Harga([
            'harga' => 100000,
            'is_promo' => true,
            'promo_type' => Harga::PROMO_TYPE_PERSEN,
            'promo_value' => 15,
        ]);

        $this->assertTrue($persen->hasActivePromo());
        $this->assertSame(15000, $persen->promoDiscountAmount());
        $this->assertSame(85000, $persen->effectivePrice());
        $this->assertSame(15, $persen->promoDiscountPercent());

        $rupiah = new Harga([
            'harga' => 200000,
            'is_promo' => true,
            'promo_type' => Harga::PROMO_TYPE_RUPIAH,
            'promo_value' => 30000,
        ]);

        $this->assertTrue($rupiah->hasActivePromo());
        $this->assertSame(30000, $rupiah->promoDiscountAmount());
        $this->assertSame(170000, $rupiah->effectivePrice());
        $this->assertSame(15, $rupiah->promoDiscountPercent());

        $normal = new Harga([
            'harga' => 100000,
            'is_promo' => false,
            'promo_type' => null,
            'promo_value' => null,
        ]);

        $this->assertFalse($normal->hasActivePromo());
        $this->assertSame(100000, $normal->effectivePrice());
        $this->assertSame(0, $normal->promoDiscountPercent());
    }

    public function test_tenant_can_create_update_and_validate_ticket_promo_fields(): void
    {
        $tenant = $this->user('penyewa');
        $event = $this->event($tenant);

        Livewire::actingAs($tenant)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('newHarga', [
                'kategori' => 'VIP Early',
                'qty' => 50,
                'harga' => 100000,
                'max_order_qty' => 5,
                'description' => 'Promo 15%',
                'is_promo' => true,
                'promo_type' => 'persen',
                'promo_value' => 15,
            ])
            ->call('addTicket')
            ->assertHasNoErrors();

        $harga = Harga::where('uid', $event->uid)->where('kategori', 'VIP Early')->firstOrFail();
        $this->assertTrue($harga->is_promo);
        $this->assertSame('persen', $harga->promo_type);
        $this->assertSame(15, $harga->promo_value);
        $this->assertSame(85000, $harga->effectivePrice());

        // Invalid promo percent (>= 100) must fail validation
        Livewire::actingAs($tenant)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->call('editTicket', $harga->id)
            ->set('editingHarga.is_promo', true)
            ->set('editingHarga.promo_type', 'persen')
            ->set('editingHarga.promo_value', 100)
            ->call('updateTicket')
            ->assertHasErrors('editingHarga.promo_value');

        // Invalid promo rupiah (>= base price) must fail validation
        Livewire::actingAs($tenant)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->call('editTicket', $harga->id)
            ->set('editingHarga.is_promo', true)
            ->set('editingHarga.promo_type', 'rupiah')
            ->set('editingHarga.promo_value', 100000)
            ->call('updateTicket')
            ->assertHasErrors('editingHarga.promo_value');

        // Valid rupiah promo
        Livewire::actingAs($tenant)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->call('editTicket', $harga->id)
            ->set('editingHarga.is_promo', true)
            ->set('editingHarga.promo_type', 'rupiah')
            ->set('editingHarga.promo_value', 20000)
            ->call('updateTicket')
            ->assertHasNoErrors();

        $harga->refresh();
        $this->assertSame('rupiah', $harga->promo_type);
        $this->assertSame(20000, $harga->promo_value);
        $this->assertSame(80000, $harga->effectivePrice());
        $this->assertSame(20, $harga->promoDiscountPercent());

        // Turning promo off clears promo_type and promo_value
        Livewire::actingAs($tenant)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->call('editTicket', $harga->id)
            ->set('editingHarga.is_promo', false)
            ->call('updateTicket')
            ->assertHasNoErrors();

        $harga->refresh();
        $this->assertFalse($harga->is_promo);
        $this->assertNull($harga->promo_type);
        $this->assertNull($harga->promo_value);
    }

    public function test_checkout_uses_promo_price_and_respects_voucher_allow_promo_tickets_option(): void
    {
        $tenant = $this->user('penyewa');
        $buyer = $this->user('user', 'buyer-promo@example.test');
        $event = $this->event($tenant);

        $harga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Festival',
            'qty' => 20,
            'harga' => 100000,
            'is_promo' => true,
            'promo_type' => Harga::PROMO_TYPE_PERSEN,
            'promo_value' => 15,
            'status' => 'active',
            'max_order_qty' => 5,
        ]);

        // Create two vouchers via VoucherIndex: one disallowed on promo, one allowed on promo
        Livewire::actingAs($tenant)
            ->test(VoucherIndex::class)
            ->set('selected_event_uid', $event->uid)
            ->set('code', 'NOPROMO10')
            ->set('unit', 'persen')
            ->set('nominal', 10)
            ->set('min_beli', 0)
            ->set('max_disc', 50000)
            ->set('limit', 10)
            ->set('allow_promo_tickets', false)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($tenant)
            ->test(VoucherIndex::class)
            ->set('selected_event_uid', $event->uid)
            ->set('code', 'YESPROMO10')
            ->set('unit', 'persen')
            ->set('nominal', 10)
            ->set('min_beli', 0)
            ->set('max_disc', 50000)
            ->set('limit', 10)
            ->set('allow_promo_tickets', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse((bool) Voucher::where('code', 'NOPROMO10')->firstOrFail()->allow_promo_tickets);
        $this->assertTrue((bool) Voucher::where('code', 'YESPROMO10')->firstOrFail()->allow_promo_tickets);

        $this->actingAs($buyer)
            ->post('/checkout', [
                'event_uid' => $event->uid,
                'tickets' => [
                    ['harga_id' => $harga->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();

        $cart = Cart::where('event_uid', $event->uid)->where('user_uid', $buyer->uid)->firstOrFail();

        $hargaCart = HargaCart::where('uid', $cart->uid)->firstOrFail();
        $this->assertSame(85000, (int) $hargaCart->harga_ticket);

        $pricing = app(TicketPricingService::class)->calculateCart($cart);
        $this->assertSame(170000, $pricing['ticket_total']);

        // Voucher without allow_promo_tickets must be rejected via /checkVoucer
        $detailUrl = '/detail-ticket/'.$cart->uid.'/'.$buyer->uid;
        $this->actingAs($buyer)
            ->from($detailUrl)
            ->post('/checkVoucer', [
                'cartUid' => $cart->uid,
                'code' => 'NOPROMO10',
            ])
            ->assertRedirect($detailUrl)
            ->assertSessionHas('vError');

        $this->assertDatabaseMissing('cart_vouchers', [
            'uid' => $cart->uid,
            'code' => 'NOPROMO10',
        ]);

        // Voucher with allow_promo_tickets must succeed on top of promo price
        $this->actingAs($buyer)
            ->from($detailUrl)
            ->post('/checkVoucer', [
                'cartUid' => $cart->uid,
                'code' => 'YESPROMO10',
            ])
            ->assertRedirect($detailUrl);

        $this->assertDatabaseHas('cart_vouchers', [
            'uid' => $cart->uid,
            'code' => 'YESPROMO10',
        ]);

        $pricingWithVoucher = app(TicketPricingService::class)->calculateCart($cart->fresh());
        $this->assertSame(170000, $pricingWithVoucher['ticket_total']);
        $this->assertSame(17000, $pricingWithVoucher['discount']);
        $this->assertSame(153000, $pricingWithVoucher['subtotal']);
    }

    public function test_landing_ticket_view_displays_strikethrough_promo_price_and_percent_badge(): void
    {
        $tenant = $this->user('penyewa');
        $event = $this->event($tenant);

        $harga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Presale 1',
            'qty' => 20,
            'harga' => 100000,
            'is_promo' => true,
            'promo_type' => Harga::PROMO_TYPE_RUPIAH,
            'promo_value' => 15000,
            'status' => 'active',
            'max_order_qty' => 5,
        ]);

        $event->setRelation('fasilitas', collect());

        $html = view('frontend.page.ticket-new', [
            'title' => $event->event,
            'ticket' => $event,
            'tickets' => collect(),
            'list' => collect([$harga]),
            'lists' => collect([$harga]),
            'jmlhQty' => collect([$harga->id => 0]),
            'lowestTicketPrice' => $harga->effectivePrice(),
            'hasUnpaid' => false,
            'unpaidUid' => null,
            'unpaidUrl' => null,
            'tanggal' => collect(),
            'talent' => collect(),
            'term' => collect(),
            'cart' => null,
            'activeGuestCart' => null,
        ])->render();

        $this->assertStringContainsString('Rp 100.000', $html);
        $this->assertStringContainsString('-15%', $html);
        $this->assertStringContainsString('85.000', $html);
        $this->assertStringContainsString('data-price="85000"', $html);

        // Landing home card & search card also display strikethrough price, promo price, and -15% badge
        $event->setRelation('hargas', collect([$harga]));
        $event->setRelation('user', $tenant);

        $homeCardHtml = view('frontend.page.home.menu', [
            'events' => collect([$event]),
        ])->render();

        $this->assertStringContainsString('Rp 100.000', $homeCardHtml);
        $this->assertStringContainsString('-15%', $homeCardHtml);
        $this->assertStringContainsString('Rp 85.000', $homeCardHtml);

        $searchCardHtml = view('frontend.page.post.post-menu', [
            'event' => collect([$event]),
        ])->render();

        $this->assertStringContainsString('Rp 100.000', $searchCardHtml);
        $this->assertStringContainsString('-15%', $searchCardHtml);
        $this->assertStringContainsString('Rp 85.000', $searchCardHtml);
    }

    public function test_pos_cash_checkout_uses_promo_effective_price(): void
    {
        Mail::fake();

        $tenant = $this->user('penyewa');
        $event = $this->event($tenant);
        $harga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Cash Promo',
            'qty' => 10,
            'harga' => 100000,
            'is_promo' => true,
            'promo_type' => Harga::PROMO_TYPE_PERSEN,
            'promo_value' => 15,
            'status' => 'active',
            'max_order_qty' => 5,
        ]);

        Livewire::actingAs($tenant)
            ->test(DemoIndex::class)
            ->call('submitCashPos', [
                'event_uid' => $event->uid,
                'selected_tickets' => [
                    ['id' => $harga->id, 'qty' => 2],
                ],
                'buyer_name' => 'Pembeli Cash Promo',
                'buyer_email' => 'cash-promo@example.test',
                'buyer_birthday' => '1998-05-05',
                'buyer_gender' => 'pria',
                'partner_id' => null,
                'is_direct_entry' => false,
            ])
            ->assertHasNoErrors();

        $cart = Cart::where('event_uid', $event->uid)->firstOrFail();
        $this->assertSame(170000, (int) $cart->gross_amount);

        $hargaCart = HargaCart::where('uid', $cart->uid)->firstOrFail();
        $this->assertSame(85000, (int) $hargaCart->harga_ticket);
    }

    private function user(string $role = 'penyewa', ?string $email = null): User
    {
        return User::create([
            'uid' => (string) Str::uuid(),
            'name' => 'Test '.ucfirst($role),
            'email' => $email ?? ($role.'-'.Str::random(6).'@example.test'),
            'nomor' => '081234567890',
            'birthday' => '1995-01-01',
            'alamat' => 'Jakarta',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'role' => $role,
            'password' => Hash::make('password123'),
        ]);
    }

    private function event(User $tenant): Event
    {
        return Event::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event' => 'Promo Fest 2026',
            'slug' => 'promo-fest-'.Str::lower(Str::random(6)),
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

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('user_uid')->nullable();
            $table->boolean('is_guest')->default(false);
            $table->string('event_uid');
            $table->string('invoice')->nullable()->unique();
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
            $table->string('gate_token_hash')->nullable();
            $table->text('gate_token_encrypted')->nullable();
            $table->string('gate_manual_code_hash')->nullable();
            $table->text('gate_manual_code_encrypted')->nullable();
            $table->timestamp('gate_token_issued_at')->nullable();
            $table->unsignedInteger('gate_token_version')->default(1);
            $table->string('midtrans_status')->nullable();
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

        Schema::create('transactions', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('event_uid');
            $table->string('invoice');
            $table->string('amount')->nullable();
            $table->integer('gross_amount')->nullable();
            $table->string('status_transaksi');
            $table->string('payment_type')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cashes', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('uid_partner')->nullable();
            $table->string('uid_user');
            $table->string('uid_event');
            $table->string('name');
            $table->string('email');
            $table->string('nomor')->default('-');
            $table->string('alamat')->default('-');
            $table->string('lahir')->nullable();
            $table->string('gender')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('partners', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('user_uid');
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('talent', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('talent')->nullable();
            $table->string('gambar')->nullable();
            $table->timestamps();
        });

        Schema::create('tutorial_progress', function ($table) {
            $table->id();
            $table->string('user_uid');
            $table->string('tutorial_key');
            $table->string('status')->default('not_started');
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('penarikans', function ($table) {
            $table->id();
            $table->string('uid')->nullable();
            $table->string('user_uid');
            $table->integer('amount')->default(0);
            $table->string('status')->default('PENDING');
            $table->timestamps();
        });

        Schema::create('agreements', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('event_uid');
            $table->string('tenant_user_uid')->nullable();
            $table->string('type')->default('mou');
            $table->string('status')->default('DRAFT');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('event_bank_accounts', function ($table) {
            $table->id();
            $table->string('event_uid');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('status')->default('verified');
            $table->timestamps();
        });

        Schema::create('event_organizers', function ($table) {
            $table->id();
            $table->string('event_uid');
            $table->string('organizer_name')->nullable();
            $table->timestamps();
        });

        Schema::create('event_documents', function ($table) {
            $table->id();
            $table->string('event_uid');
            $table->string('document_type')->nullable();
            $table->timestamps();
        });

        Schema::create('event_payment_gateways', function ($table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('payment_gateway_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
}
