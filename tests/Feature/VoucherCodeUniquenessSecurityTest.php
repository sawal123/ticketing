<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\VoucherIndex;
use App\Models\Cart;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class VoucherCodeUniquenessSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '', 'icon' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_tenant_can_create_voucher_code_for_event(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $this->createVoucherViaLivewire($tenant, $event, 'HEMAT10')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vouchers', [
            'event_uid' => $event->uid,
            'code' => 'HEMAT10',
        ]);
    }

    public function test_duplicate_code_on_same_event_is_rejected(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->voucher($tenant, $event, ['code' => 'HEMAT10']);

        $this->createVoucherViaLivewire($tenant, $event, 'HEMAT10')
            ->assertHasErrors('code');

        $this->assertSame(1, Voucher::where('event_uid', $event->uid)->where('code', 'HEMAT10')->count());
    }

    public function test_case_insensitive_duplicate_on_same_event_is_rejected(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->voucher($tenant, $event, ['code' => 'HEMAT10']);

        $this->createVoucherViaLivewire($tenant, $event, 'hemat10')
            ->assertHasErrors('code');

        $this->assertSame(1, Voucher::where('event_uid', $event->uid)->where('code', 'HEMAT10')->count());
    }

    public function test_same_code_is_allowed_on_different_events(): void
    {
        [$tenant, $eventA] = $this->tenantWithEvent();
        $eventB = $this->event($tenant);

        $this->voucher($tenant, $eventA, ['code' => 'HEMAT10']);

        $this->createVoucherViaLivewire($tenant, $eventB, 'HEMAT10')
            ->assertHasNoErrors();

        $this->assertSame(2, Voucher::where('code', 'HEMAT10')->count());
        $this->assertSame(1, Voucher::where('event_uid', $eventA->uid)->where('code', 'HEMAT10')->count());
        $this->assertSame(1, Voucher::where('event_uid', $eventB->uid)->where('code', 'HEMAT10')->count());
    }

    public function test_update_to_code_used_by_another_voucher_on_same_event_is_rejected(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $voucherA = $this->voucher($tenant, $event, ['code' => 'FIRST']);
        $this->voucher($tenant, $event, ['code' => 'SECOND']);

        Livewire::actingAs($tenant)
            ->test(VoucherIndex::class)
            ->call('openEditModal', $voucherA->id)
            ->set('code', 'second')
            ->call('save')
            ->assertHasErrors('code');

        $this->assertDatabaseHas('vouchers', ['id' => $voucherA->id, 'code' => 'FIRST']);
    }

    public function test_update_same_voucher_without_changing_code_is_allowed(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $voucher = $this->voucher($tenant, $event, ['code' => 'SAMECODE']);

        Livewire::actingAs($tenant)
            ->test(VoucherIndex::class)
            ->call('openEditModal', $voucher->id)
            ->set('nominal', 20)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'code' => 'SAMECODE', 'nominal' => 20]);
    }

    public function test_update_to_code_used_on_other_event_is_allowed(): void
    {
        [$tenant, $eventA] = $this->tenantWithEvent();
        $eventB = $this->event($tenant);
        $voucherA = $this->voucher($tenant, $eventA, ['code' => 'FIRST']);
        $this->voucher($tenant, $eventB, ['code' => 'OTHER']);

        Livewire::actingAs($tenant)
            ->test(VoucherIndex::class)
            ->call('openEditModal', $voucherA->id)
            ->set('code', 'other')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vouchers', ['id' => $voucherA->id, 'code' => 'OTHER']);
    }

    public function test_code_is_saved_uppercase_and_trimmed(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $this->createVoucherViaLivewire($tenant, $event, ' hemat10 ')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vouchers', [
            'event_uid' => $event->uid,
            'code' => 'HEMAT10',
        ]);
    }

    public function test_unsafe_code_characters_are_rejected(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        foreach (['HEMAT 10', 'HEMAT/10', '<script>', 'HEMAT,10'] as $code) {
            $this->createVoucherViaLivewire($tenant, $event, $code)
                ->assertHasErrors('code');
        }

        $this->assertSame(0, Voucher::where('event_uid', $event->uid)->count());
    }

    public function test_check_voucher_normalizes_lowercase_and_spaces(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $harga = $this->harga($event);
        $voucher = $this->voucher($tenant, $event, ['code' => 'HEMAT10']);
        $cart = $this->cart($tenant, $event);
        $this->hargaCart($cart, $event, $harga);

        $this->actingAs($tenant)
            ->from('/detail-ticket/'.$cart->uid.'/'.$tenant->uid)
            ->post('/checkVoucer', [
                'cartUid' => $cart->uid,
                'code' => ' hemat10 ',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_vouchers', [
            'uid' => $cart->uid,
            'event_uid' => $event->uid,
            'code' => $voucher->code,
        ]);
        $this->assertDatabaseHas('harga_carts', [
            'uid' => $cart->uid,
            'event_uid' => $event->uid,
            'voucher' => $voucher->code,
        ]);
    }

    public function test_check_voucher_does_not_use_same_code_from_other_event(): void
    {
        [$tenant, $eventA] = $this->tenantWithEvent();
        $eventB = $this->event($tenant);
        $harga = $this->harga($eventA);
        $this->voucher($tenant, $eventB, ['code' => 'EVENTBONLY']);
        $cart = $this->cart($tenant, $eventA);
        $this->hargaCart($cart, $eventA, $harga);

        $this->actingAs($tenant)
            ->from('/detail-ticket/'.$cart->uid.'/'.$tenant->uid)
            ->post('/checkVoucer', [
                'cartUid' => $cart->uid,
                'code' => 'EVENTBONLY',
            ])
            ->assertRedirect()
            ->assertSessionHas('vError');

        $this->assertDatabaseMissing('cart_vouchers', [
            'uid' => $cart->uid,
            'code' => 'EVENTBONLY',
        ]);
    }

    public function test_database_unique_index_blocks_duplicate_event_code(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $this->voucher($tenant, $event, ['code' => 'RACE10']);

        $this->expectException(QueryException::class);

        Voucher::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'code' => 'race10',
            'unit' => 'rupiah',
            'nominal' => 10000,
            'min_beli' => 0,
            'max_disc' => 10000,
            'digunakan' => 0,
            'limit' => 10,
            'status' => 'active',
        ]);
    }

    private function createVoucherViaLivewire(User $tenant, Event $event, string $code)
    {
        return Livewire::actingAs($tenant)
            ->test(VoucherIndex::class)
            ->set('selected_event_uid', $event->uid)
            ->set('code', $code)
            ->set('unit', 'rupiah')
            ->set('nominal', 10000)
            ->set('min_beli', 0)
            ->set('max_disc', 10000)
            ->set('limit', 10)
            ->call('save');
    }

    private function tenantWithEvent(): array
    {
        $tenant = $this->user();

        return [$tenant, $this->event($tenant)];
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Voucher User',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function event(User $tenant): Event
    {
        $uid = (string) Str::uuid();

        return Event::create([
            'uid' => $uid,
            'user_uid' => $tenant->uid,
            'event' => 'Voucher Event '.$uid,
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->format('Y-m-d H:i'),
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Event',
            'map' => 'https://example.test/map',
            'pajak' => 0,
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'voucher-event-'.$uid,
            'konfirmasi' => '1',
        ]);
    }

    private function harga(Event $event): Harga
    {
        return Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Regular',
            'qty' => 10,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => 100000,
            'status' => 'active',
        ]);
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

    private function cart(User $tenant, Event $event): Cart
    {
        return Cart::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.Str::upper(Str::random(8)),
            'status' => Cart::STATUS_UNPAID,
            'payment_type' => 'bank_transfer',
            'gross_amount' => 100000,
            'reservation_expires_at' => now()->addMinutes(10),
        ]);
    }

    private function hargaCart(Cart $cart, Event $event, Harga $harga): HargaCart
    {
        return HargaCart::create([
            'uid' => $cart->uid,
            'harga_id' => $harga->id,
            'orderBy' => '1',
            'event_uid' => $event->uid,
            'quantity' => 1,
            'harga_ticket' => 100000,
            'disc' => 0,
            'kategori_harga' => $harga->kategori,
        ]);
    }
}
