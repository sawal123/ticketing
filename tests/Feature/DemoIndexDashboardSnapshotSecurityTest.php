<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\DemoIndex;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DemoIndexDashboardSnapshotSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_dashboard_total_omzet_uses_snapshot_after_master_data_changes(): void
    {
        [$tenant, $event, $harga, $voucher] = $this->successfulSnapshot();

        $this->assertDashboardStats($tenant, [
            'omset' => 198000,
            'tiket' => 2,
            'transaksi' => 1,
        ]);

        $this->mutateMasterData($event, $harga, $voucher);

        $this->assertDashboardStats($tenant, [
            'omset' => 198000,
            'tiket' => 2,
            'transaksi' => 1,
        ]);
    }

    public function test_dashboard_chart_revenue_uses_snapshot_after_master_data_changes(): void
    {
        [$tenant, $event, $harga, $voucher] = $this->successfulSnapshot();

        $this->mutateMasterData($event, $harga, $voucher);

        Livewire::actingAs($tenant)
            ->test(DemoIndex::class)
            ->assertViewHas('chart', fn ($chart) => (int) end($chart['revenue']) === 198000);
    }

    public function test_soft_deleted_cart_is_excluded_from_dashboard_stats(): void
    {
        [$tenant, , , , $cart] = $this->successfulSnapshot();
        $cart->delete();

        $this->assertDashboardStats($tenant, [
            'omset' => 0,
            'tiket' => 0,
            'transaksi' => 0,
        ]);
    }

    public function test_multi_line_cart_does_not_double_count_tax(): void
    {
        $tenant = $this->user();
        $event = $this->event($tenant, ['fee' => 10]);
        $hargaA = $this->harga($event, ['kategori' => 'A', 'harga' => 100000]);
        $hargaB = $this->harga($event, ['kategori' => 'B', 'harga' => 50000]);
        $cart = $this->cart($tenant, $event, [
            'gross_amount' => 154000,
            'pajak' => 14000,
            'pajak_persen' => 10,
        ]);

        $this->hargaCart($cart, $event, $hargaA, [
            'quantity' => 1,
            'harga_ticket' => 100000,
            'disc' => 10000,
        ]);
        $this->hargaCart($cart, $event, $hargaB, [
            'quantity' => 1,
            'harga_ticket' => 50000,
            'disc' => 0,
        ]);
        $this->cash($cart, $tenant, $event);

        $this->assertDashboardStats($tenant, [
            'omset' => 154000,
            'tiket' => 2,
            'transaksi' => 1,
        ]);
    }

    public function test_admin_sees_all_owners_and_tenant_sees_only_own_snapshot_stats(): void
    {
        [$tenantA] = $this->successfulSnapshot();
        [$tenantB, $eventB] = $this->successfulSnapshot([
            'tenant' => ['email' => 'tenant-b-demo-snapshot@example.test'],
            'cart' => ['gross_amount' => 99000, 'pajak' => 9000],
            'line' => ['quantity' => 1, 'harga_ticket' => 90000, 'disc' => 0],
        ]);
        $admin = $this->user([
            'role' => 'admin',
            'email' => 'admin-demo-snapshot@example.test',
        ]);

        $this->assertDashboardStats($tenantA, [
            'omset' => 198000,
            'transaksi' => 1,
        ]);

        Livewire::actingAs($admin)
            ->test(DemoIndex::class)
            ->assertViewHas('stats', fn ($stats) => (int) $stats['omset'] === 297000
                && (int) $stats['transaksi'] === 2);

        $this->assertSame($tenantB->uid, $eventB->user_uid);
    }

    public function test_demographics_are_cart_level_for_multi_line_cash_cart(): void
    {
        $tenant = $this->user();
        $event = $this->event($tenant);
        $hargaA = $this->harga($event, ['kategori' => 'A']);
        $hargaB = $this->harga($event, ['kategori' => 'B']);
        $cart = $this->cart($tenant, $event, [
            'gross_amount' => 200000,
            'pajak' => 0,
        ]);

        $this->hargaCart($cart, $event, $hargaA);
        $this->hargaCart($cart, $event, $hargaB);
        $this->cash($cart, $tenant, $event, ['gender' => 'pria']);

        Livewire::actingAs($tenant)
            ->test(DemoIndex::class)
            ->assertViewHas('gender', fn ($gender) => (int) $gender['pria'] === 1
                && (int) $gender['wanita'] === 0);
    }

    private function assertDashboardStats(User $tenant, array $expected): void
    {
        Livewire::actingAs($tenant)
            ->test(DemoIndex::class)
            ->assertViewHas('stats', fn ($stats) => collect($expected)->every(
                fn ($value, $key) => (int) $stats[$key] === $value
            ));
    }

    private function successfulSnapshot(array $overrides = []): array
    {
        $tenant = $this->user($overrides['tenant'] ?? []);
        $event = $this->event($tenant, ['fee' => 10]);
        $harga = $this->harga($event, ['harga' => 100000]);
        $voucher = $this->voucher($tenant, $event);
        $cart = $this->cart($tenant, $event, array_merge([
            'gross_amount' => 198000,
            'pajak' => 18000,
            'pajak_persen' => 10,
        ], $overrides['cart'] ?? []));
        $this->hargaCart($cart, $event, $harga, array_merge([
            'quantity' => 2,
            'harga_ticket' => 100000,
            'voucher' => $voucher->code,
            'disc' => 20000,
        ], $overrides['line'] ?? []));
        $this->cash($cart, $tenant, $event);

        return [$tenant, $event, $harga, $voucher, $cart];
    }

    private function mutateMasterData(Event $event, Harga $harga, Voucher $voucher): void
    {
        $event->update(['fee' => 99]);
        $voucher->update(['nominal' => 90000]);
        $harga->update(['harga' => 1]);
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Demo Snapshot User',
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

    private function event(User $tenant, array $overrides = []): Event
    {
        $uid = (string) Str::uuid();

        return Event::create(array_merge([
            'uid' => $uid,
            'user_uid' => $tenant->uid,
            'event' => 'Demo Snapshot Event '.$uid,
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->format('Y-m-d H:i'),
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => 'https://example.test/map',
            'pajak' => 0,
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'demo-snapshot-event-'.$uid,
            'konfirmasi' => '1',
        ], $overrides));
    }

    private function harga(Event $event, array $overrides = []): Harga
    {
        return Harga::create(array_merge([
            'uid' => $event->uid,
            'kategori' => 'Regular',
            'qty' => 10,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => 100000,
            'status' => 'active',
        ], $overrides));
    }

    private function voucher(User $tenant, Event $event): Voucher
    {
        return Voucher::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'code' => 'DEMO20',
            'unit' => 'rupiah',
            'nominal' => 20000,
            'min_beli' => 0,
            'max_disc' => 0,
            'digunakan' => 0,
            'limit' => 10,
            'status' => 'active',
        ]);
    }

    private function cart(User $tenant, Event $event, array $overrides = []): Cart
    {
        $cart = Cart::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.Str::upper(Str::random(8)),
            'status' => Cart::STATUS_SUCCESS,
            'payment_type' => 'cash',
            'internet_fee' => 0,
            'pajak' => 0,
            'pajak_persen' => 0,
            'gross_amount' => 0,
            'paid_at' => now(),
        ], $overrides));

        Transaction::create([
            'uid' => $cart->uid,
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'amount' => (string) $cart->gross_amount,
            'gross_amount' => $cart->gross_amount,
            'invoice' => $cart->invoice,
            'payment_type' => $cart->payment_type,
            'status_transaksi' => Cart::STATUS_SUCCESS,
            'paid_at' => now(),
        ]);

        return $cart;
    }

    private function hargaCart(Cart $cart, Event $event, Harga $harga, array $overrides = []): HargaCart
    {
        return HargaCart::create(array_merge([
            'uid' => $cart->uid,
            'harga_id' => $harga->id,
            'orderBy' => '1',
            'event_uid' => $event->uid,
            'quantity' => 1,
            'harga_ticket' => $harga->harga,
            'kategori_harga' => $harga->kategori,
            'voucher' => null,
            'disc' => 0,
        ], $overrides));
    }

    private function cash(Cart $cart, User $tenant, Event $event, array $overrides = []): Cash
    {
        return Cash::create(array_merge([
            'uid' => $cart->uid,
            'uid_partner' => null,
            'uid_user' => $tenant->uid,
            'uid_event' => $event->uid,
            'name' => 'Cash Buyer',
            'email' => 'cash@example.test',
            'nomor' => '08123456789',
            'alamat' => 'Jakarta',
            'lahir' => '2000-01-01',
            'gender' => 'pria',
        ], $overrides));
    }
}
