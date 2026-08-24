<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventCreate;
use App\Livewire\Dashboard\EventDetail;
use App\Livewire\Dashboard\VoucherIndex;
use App\Models\Cart;
use App\Models\CartVoucher;
use App\Models\Category;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Tickets\TicketPricingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class VoucherTaxTicketSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        View::share('logo', [(object) ['logo' => '']]);
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        Storage::fake('public');
        $this->artisan('migrate:fresh', ['--database' => 'sqlite']);
    }

    public function test_voucher_code_from_event_a_cannot_be_used_for_event_b(): void
    {
        [$tenantA, $eventA] = $this->tenantWithEvent(['email' => 'tenant-a-voucher@example.test']);
        [$tenantB, $eventB] = $this->tenantWithEvent(['email' => 'tenant-b-voucher@example.test']);
        $this->voucher($tenantA, $eventA, ['code' => 'PROMO']);
        $cartB = $this->cart($tenantB, $eventB, Cart::STATUS_RESERVED);
        $this->hargaCart($cartB, $this->harga($eventB), 100000);

        $this->actingAs($tenantB)
            ->from('/detail-ticket/'.$cartB->uid.'/'.$tenantB->uid)
            ->post('/checkVoucer', [
                'cartUid' => $cartB->uid,
                'code' => 'PROMO',
            ])
            ->assertRedirect('/detail-ticket/'.$cartB->uid.'/'.$tenantB->uid)
            ->assertSessionHas('vError');

        $this->assertDatabaseCount('cart_vouchers', 0);
    }

    public function test_voucher_dashboard_only_shows_current_tenant_vouchers(): void
    {
        [$tenantA, $eventA] = $this->tenantWithEvent(['email' => 'tenant-a-voucher-list@example.test']);
        [$tenantB, $eventB] = $this->tenantWithEvent(['email' => 'tenant-b-voucher-list@example.test']);
        $voucherA = $this->voucher($tenantA, $eventA, ['code' => 'AONLY']);
        $voucherB = $this->voucher($tenantB, $eventB, ['code' => 'BONLY']);

        Livewire::actingAs($tenantA)
            ->test(VoucherIndex::class)
            ->assertSee($voucherA->code)
            ->assertDontSee($voucherB->code);
    }

    public function test_voucher_transaction_history_is_filtered_by_event_owner_and_event_uid(): void
    {
        [$tenantA, $eventA] = $this->tenantWithEvent(['email' => 'tenant-a-history@example.test']);
        [$tenantB, $eventB] = $this->tenantWithEvent(['email' => 'tenant-b-history@example.test']);
        $voucherA = $this->voucher($tenantA, $eventA, ['code' => 'SAMECODE']);
        $voucherB = $this->voucher($tenantB, $eventB, ['code' => 'SAMECODE']);
        $cartA = $this->successfulVoucherCart($tenantA, $eventA, $voucherA, 'INV-A');
        $cartB = $this->successfulVoucherCart($tenantB, $eventB, $voucherB, 'INV-B');

        Livewire::actingAs($tenantA)
            ->test(VoucherIndex::class)
            ->call('viewTransactions', $voucherA->id)
            ->assertSet('transactions.0.uid', $cartA->uid);

        $this->assertNotSame($cartB->uid, $cartA->uid);
    }

    public function test_same_voucher_code_uses_voucher_from_current_cart_event(): void
    {
        [$tenantA, $eventA] = $this->tenantWithEvent(['email' => 'tenant-a-same-code@example.test']);
        [$tenantB, $eventB] = $this->tenantWithEvent(['email' => 'tenant-b-same-code@example.test']);
        $voucherA = $this->voucher($tenantA, $eventA, ['code' => 'DOUBLE']);
        $voucherB = $this->voucher($tenantB, $eventB, ['code' => 'DOUBLE', 'nominal' => 20000]);
        $cartB = $this->cart($tenantB, $eventB, Cart::STATUS_RESERVED);
        $this->hargaCart($cartB, $this->harga($eventB), 100000);

        $this->actingAs($tenantB)
            ->from('/detail-ticket/'.$cartB->uid.'/'.$tenantB->uid)
            ->post('/checkVoucer', [
                'cartUid' => $cartB->uid,
                'code' => 'DOUBLE',
            ])
            ->assertRedirect('/detail-ticket/'.$cartB->uid.'/'.$tenantB->uid)
            ->assertSessionHas('voucher');

        $cartVoucher = CartVoucher::where('uid', $cartB->uid)->firstOrFail();

        $this->assertSame($voucherB->uid, $cartVoucher->uid_vouchers);
        $this->assertNotSame($voucherA->uid, $cartVoucher->uid_vouchers);
        $this->assertSame($eventB->uid, $cartVoucher->event_uid);
    }

    public function test_inactive_or_over_limit_voucher_cannot_be_used(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $inactive = $this->voucher($tenant, $event, ['code' => 'INACTIVE', 'status' => 'inactive']);
        $limited = $this->voucher($tenant, $event, ['code' => 'LIMITED', 'limit' => 1, 'digunakan' => 1]);

        foreach ([$inactive, $limited] as $voucher) {
            $cart = $this->cart($tenant, $event, Cart::STATUS_RESERVED);
            $this->hargaCart($cart, $this->harga($event), 100000);

            $this->actingAs($tenant)
                ->from('/detail-ticket/'.$cart->uid.'/'.$tenant->uid)
                ->post('/checkVoucer', [
                    'cartUid' => $cart->uid,
                    'code' => $voucher->code,
                ])
                ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$tenant->uid)
                ->assertSessionHas('vError');
        }

        $this->assertDatabaseCount('cart_vouchers', 0);
    }

    public function test_cart_owner_can_open_detail_ticket_with_owner_url_parameter(): void
    {
        [$owner, $event] = $this->tenantWithEvent();
        $cart = $this->cart($owner, $event, Cart::STATUS_RESERVED);
        $this->hargaCart($cart, $this->harga($event), 100000);

        $this->actingAs($owner)
            ->get('/detail-ticket/'.$cart->uid.'/'.$owner->uid)
            ->assertOk();
    }

    public function test_other_user_cannot_open_detail_ticket_even_with_correct_owner_url_parameter(): void
    {
        [$owner, $event] = $this->tenantWithEvent(['email' => 'detail-owner@example.test']);
        $otherUser = $this->user(['email' => 'detail-other@example.test']);
        $cart = $this->cart($owner, $event, Cart::STATUS_RESERVED);
        $this->hargaCart($cart, $this->harga($event), 100000);

        $this->actingAs($otherUser)
            ->get('/detail-ticket/'.$cart->uid.'/'.$owner->uid)
            ->assertRedirect('/');
    }

    public function test_cart_owner_can_open_detail_ticket_even_when_url_user_parameter_is_spoofed(): void
    {
        [$owner, $event] = $this->tenantWithEvent(['email' => 'detail-owner-spoof@example.test']);
        $otherUser = $this->user(['email' => 'detail-param-spoof@example.test']);
        $cart = $this->cart($owner, $event, Cart::STATUS_RESERVED);
        $this->hargaCart($cart, $this->harga($event), 100000);

        $this->actingAs($owner)
            ->get('/detail-ticket/'.$cart->uid.'/'.$otherUser->uid)
            ->assertOk();
    }

    public function test_check_voucher_still_uses_authenticated_user_for_cart_scope(): void
    {
        [$owner, $event] = $this->tenantWithEvent(['email' => 'voucher-owner-auth@example.test']);
        $otherUser = $this->user(['email' => 'voucher-other-auth@example.test']);
        $voucher = $this->voucher($owner, $event, ['code' => 'AUTHONLY']);
        $cart = $this->cart($owner, $event, Cart::STATUS_RESERVED);
        $this->hargaCart($cart, $this->harga($event), 100000);

        $this->actingAs($otherUser)
            ->from('/detail-ticket/'.$cart->uid.'/'.$owner->uid)
            ->post('/checkVoucer', [
                'cartUid' => $cart->uid,
                'code' => $voucher->code,
            ])
            ->assertRedirect('/detail-ticket/'.$cart->uid.'/'.$owner->uid)
            ->assertSessionHas('vError');

        $this->assertDatabaseCount('cart_vouchers', 0);
    }

    public function test_voucher_dashboard_counts_are_scoped_by_event_uid_when_codes_match(): void
    {
        [$tenantA, $eventA] = $this->tenantWithEvent(['email' => 'tenant-a-count@example.test']);
        [$tenantB, $eventB] = $this->tenantWithEvent(['email' => 'tenant-b-count@example.test']);
        $voucherA = $this->voucher($tenantA, $eventA, ['code' => 'COUNTME']);
        $voucherB = $this->voucher($tenantB, $eventB, ['code' => 'COUNTME']);
        $this->successfulVoucherCart($tenantA, $eventA, $voucherA, 'INV-COUNT-A');
        $this->successfulVoucherCart($tenantB, $eventB, $voucherB, 'INV-COUNT-B');

        Livewire::actingAs($tenantA)
            ->test(VoucherIndex::class)
            ->assertViewHas('vouchers', function ($vouchers) use ($voucherA, $voucherB) {
                $itemA = $vouchers->getCollection()->firstWhere('id', $voucherA->id);
                $itemB = $vouchers->getCollection()->firstWhere('id', $voucherB->id);

                return $itemA
                    && ! $itemB
                    && (int) $itemA->success_count === 1
                    && (int) $itemA->cart_voucher_count === 1;
            });

        Livewire::actingAs($tenantB)
            ->test(VoucherIndex::class)
            ->assertViewHas('vouchers', function ($vouchers) use ($voucherB) {
                $itemB = $vouchers->getCollection()->firstWhere('id', $voucherB->id);

                return $itemB
                    && (int) $itemB->success_count === 1
                    && (int) $itemB->cart_voucher_count === 1;
            });
    }

    public function test_livewire_create_event_saves_fee_to_events_fee(): void
    {
        $tenant = $this->user(['role' => 'penyewa']);
        $category = Category::create(['name' => 'Music', 'slug' => 'music']);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class)
            ->set('event', 'Fee Event')
            ->set('fee', 10)
            ->set('start_sale', now()->format('Y-m-d H:i'))
            ->set('event_start', now()->addDay()->format('Y-m-d H:i'))
            ->set('event_end', now()->addDay()->addHours(2)->format('Y-m-d H:i'))
            ->set('venue_name', 'Istora Senayan')
            ->set('venue_address', 'Jl. Pintu Satu Senayan')
            ->set('venue_city', 'Jakarta Pusat')
            ->set('venue_province', 'DKI Jakarta')
            ->set('map', 'https://example.test/map')
            ->set('cover', UploadedFile::fake()->image('cover.jpg'))
            ->set('deskripsi', 'Event description')
            ->set('category_id', $category->id)
            ->call('save');

        $event = Event::where('event', 'Fee Event')->firstOrFail();

        $this->assertSame(10, (int) $event->fee);
        $this->assertSame(0, (int) $event->pajak);
    }

    public function test_livewire_update_event_saves_fee_to_events_fee(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $event->update(['fee' => 5]);
        $category = Category::create(['name' => 'Sports', 'slug' => 'sports']);
        $event->update(['category_id' => $category->id]);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('fee', 12)
            ->call('save');

        $this->assertSame(12, (int) $event->fresh()->fee);
    }

    public function test_livewire_event_fee_must_be_between_zero_and_one_hundred(): void
    {
        $tenant = $this->user(['role' => 'penyewa']);
        $category = Category::create(['name' => 'Talk', 'slug' => 'talk']);

        foreach ([-1, 101] as $fee) {
            Livewire::actingAs($tenant)
                ->test(EventCreate::class)
                ->set('event', 'Invalid Fee')
                ->set('fee', $fee)
                ->set('start_sale', now()->format('Y-m-d H:i'))
                ->set('event_start', now()->addDay()->format('Y-m-d H:i'))
                ->set('event_end', now()->addDay()->addHours(2)->format('Y-m-d H:i'))
                ->set('venue_name', 'Istora Senayan')
                ->set('venue_address', 'Jl. Pintu Satu Senayan')
                ->set('venue_city', 'Jakarta Pusat')
                ->set('venue_province', 'DKI Jakarta')
                ->set('map', 'https://example.test/map')
                ->set('cover', UploadedFile::fake()->image('cover.jpg'))
                ->set('deskripsi', 'Event description')
                ->set('category_id', $category->id)
                ->call('save')
                ->assertHasErrors('fee');
        }
    }

    public function test_ticket_pricing_uses_event_fee_not_pajak_column(): void
    {
        [$buyer, $event] = $this->tenantWithEvent();
        $event->update(['fee' => 10, 'pajak' => 99]);
        $cart = $this->cart($buyer, $event, Cart::STATUS_RESERVED);
        $this->hargaCart($cart, $this->harga($event, ['harga' => 100000]), 100000);

        $pricing = app(TicketPricingService::class)->calculateCart($cart);

        $this->assertSame(10, $pricing['tax_percent']);
        $this->assertSame(10000, $pricing['tax_amount']);
    }

    public function test_legacy_add_harga_rejects_negative_harga_or_qty(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        foreach ([['harga' => -1, 'qty' => 1], ['harga' => 1000, 'qty' => -1]] as $payload) {
            $this->actingAs($tenant)
                ->from('/dashboard/old/event/'.$event->uid)
                ->post('/dashboard/old/addHarga', array_merge([
                    'uid' => $event->uid,
                    'kategori' => 'Regular '.Str::random(5),
                ], $payload))
                ->assertRedirect('/dashboard/old/event/'.$event->uid)
                ->assertSessionHasErrors();
        }

        $this->assertDatabaseCount('hargas', 0);
    }

    public function test_legacy_add_harga_ignores_spoofed_status_and_stock_fields(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $this->actingAs($tenant)
            ->from('/dashboard/old/event/'.$event->uid)
            ->post('/dashboard/old/addHarga', [
                'uid' => $event->uid,
                'kategori' => 'VIP',
                'qty' => 10,
                'harga' => 100000,
                'status' => 'inactive',
                'sold_qty' => 9,
                'reserved_qty' => 8,
            ])
            ->assertRedirect('/dashboard/old/event/'.$event->uid);

        $harga = Harga::firstOrFail();

        $this->assertSame('active', $harga->status);
        $this->assertSame(0, (int) $harga->sold_qty);
        $this->assertSame(0, (int) $harga->reserved_qty);
    }

    public function test_legacy_edit_harga_rejects_negative_harga_and_ignores_spoofed_fields(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $harga = $this->harga($event, [
            'status' => 'active',
            'sold_qty' => 2,
            'reserved_qty' => 1,
        ]);

        $this->actingAs($tenant)
            ->from('/dashboard/old/event/'.$event->uid)
            ->post('/dashboard/old/editHarga', [
                'id' => $harga->id,
                'kategori' => 'VIP',
                'qty' => 10,
                'harga' => -1,
            ])
            ->assertRedirect('/dashboard/old/event/'.$event->uid)
            ->assertSessionHasErrors('harga');

        $this->actingAs($tenant)
            ->from('/dashboard/old/event/'.$event->uid)
            ->post('/dashboard/old/editHarga', [
                'id' => $harga->id,
                'kategori' => 'Updated',
                'qty' => 10,
                'harga' => 120000,
                'status' => 'inactive',
                'sold_qty' => 99,
                'reserved_qty' => 88,
                'uid' => 'fake-event',
            ])
            ->assertRedirect('/dashboard/old/event/'.$event->uid);

        $harga->refresh();

        $this->assertSame('Updated', $harga->kategori);
        $this->assertSame('active', $harga->status);
        $this->assertSame(2, (int) $harga->sold_qty);
        $this->assertSame(1, (int) $harga->reserved_qty);
        $this->assertSame($event->uid, $harga->uid);
    }

    public function test_livewire_add_and_update_ticket_reject_negative_price_and_ignore_spoofed_fields(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $harga = $this->harga($event, [
            'status' => 'active',
            'sold_qty' => 2,
            'reserved_qty' => 1,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('newHarga.kategori', 'Bad')
            ->set('newHarga.qty', 1)
            ->set('newHarga.harga', -1)
            ->call('addTicket')
            ->assertHasErrors('newHarga.harga');

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('editTicket', $harga->id)
            ->set('editingHarga.harga', -1)
            ->call('updateTicket')
            ->assertHasErrors('editingHarga.harga');

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('editTicket', $harga->id)
            ->set('editingHarga.kategori', 'Secure Update')
            ->set('editingHarga.qty', 10)
            ->set('editingHarga.harga', 130000)
            ->set('editingHarga.status', 'inactive')
            ->set('editingHarga.sold_qty', 99)
            ->set('editingHarga.reserved_qty', 88)
            ->call('updateTicket');

        $harga->refresh();

        $this->assertSame('Secure Update', $harga->kategori);
        $this->assertSame('active', $harga->status);
        $this->assertSame(2, (int) $harga->sold_qty);
        $this->assertSame(1, (int) $harga->reserved_qty);
    }

    public function test_livewire_toggle_ticket_status_only_produces_active_or_inactive(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $harga = $this->harga($event, ['status' => 'inactive']);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('toggleTicketStatus', $harga->id);

        $this->assertContains($harga->fresh()->status, ['active', 'inactive']);
    }

    public function test_tenant_cannot_add_or_edit_ticket_for_another_tenants_event(): void
    {
        [$tenantA] = $this->tenantWithEvent(['email' => 'tenant-a-ticket@example.test']);
        [$tenantB, $eventB] = $this->tenantWithEvent(['email' => 'tenant-b-ticket@example.test']);
        $hargaB = $this->harga($eventB, ['harga' => 100000]);

        $this->actingAs($tenantA)
            ->post('/dashboard/old/addHarga', [
                'uid' => $eventB->uid,
                'kategori' => 'Hack',
                'qty' => 1,
                'harga' => 1000,
            ])
            ->assertNotFound();

        $this->actingAs($tenantA)
            ->post('/dashboard/old/editHarga', [
                'id' => $hargaB->id,
                'kategori' => 'Hack',
                'qty' => 1,
                'harga' => 1000,
            ])
            ->assertNotFound();

        $this->assertSame(100000, (int) $hargaB->fresh()->harga);
        $this->assertSame($tenantB->uid, $eventB->user_uid);
    }

    private function tenantWithEvent(array $userOverrides = []): array
    {
        $tenant = $this->user(array_merge(['role' => 'penyewa'], $userOverrides));
        $event = $this->event($tenant);

        return [$tenant, $event];
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Security User',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'user',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function event(User $tenant, array $overrides = []): Event
    {
        $uid = (string) Str::uuid();

        return Event::create(array_merge([
            'uid' => $uid,
            'user_uid' => $tenant->uid,
            'event' => 'Security Event '.$uid,
            'alamat' => 'Istora Senayan, Jl. Pintu Satu Senayan, Jakarta Pusat, DKI Jakarta',
            'tanggal' => now()->addDay()->format('Y-m-d H:i'),
            'event_end' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
            'venue_name' => 'Istora Senayan',
            'venue_address' => 'Jl. Pintu Satu Senayan',
            'venue_city' => 'Jakarta Pusat',
            'venue_province' => 'DKI Jakarta',
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'pajak' => 0,
            'deskripsi' => 'Event',
            'map' => 'https://example.test/map',
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'security-event-'.$uid,
            'konfirmasi' => '1',
        ], $overrides));
    }

    private function harga(Event $event, array $overrides = []): Harga
    {
        return Harga::create(array_merge([
            'uid' => $event->uid,
            'kategori' => 'Regular '.Str::random(5),
            'qty' => 10,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => 100000,
            'status' => 'active',
        ], $overrides));
    }

    private function voucher(User $tenant, Event $event, array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'code' => 'PROMO'.Str::upper(Str::random(5)),
            'unit' => 'rupiah',
            'nominal' => 10000,
            'min_beli' => 0,
            'max_disc' => 10000,
            'digunakan' => 0,
            'limit' => 10,
            'status' => 'active',
        ], $overrides));
    }

    private function cart(User $user, Event $event, string $status): Cart
    {
        return Cart::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $user->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.Str::upper(Str::random(8)),
            'status' => $status,
            'payment_type' => 'bank_transfer',
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    private function hargaCart(Cart $cart, Harga $harga, int $ticketPrice): HargaCart
    {
        return HargaCart::create([
            'uid' => $cart->uid,
            'harga_id' => $harga->id,
            'orderBy' => '1',
            'event_uid' => $cart->event_uid,
            'quantity' => 1,
            'harga_ticket' => $ticketPrice,
            'kategori_harga' => $harga->kategori,
        ]);
    }

    private function successfulVoucherCart(User $user, Event $event, Voucher $voucher, string $invoice): Cart
    {
        $cart = $this->cart($user, $event, Cart::STATUS_SUCCESS);
        $cart->update(['invoice' => $invoice]);
        $hargaCart = $this->hargaCart($cart, $this->harga($event), 100000);
        $hargaCart->update([
            'voucher' => $voucher->code,
            'disc' => 10000,
        ]);

        CartVoucher::create([
            'uid' => $cart->uid,
            'uid_vouchers' => $voucher->uid,
            'user_uid' => $user->uid,
            'event_uid' => $event->uid,
            'code' => $voucher->code,
        ]);

        return $cart;
    }
}
