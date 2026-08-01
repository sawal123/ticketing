<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventDetail as DashboardEventDetail;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Reports\FinancialSnapshotService;
use App\Services\Withdrawals\WithdrawalBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use ReflectionClass;
use Tests\TestCase;

class FinancialReportSnapshotSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '', 'icon' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_legacy_cash_report_uses_snapshot_before_and_after_master_data_changes(): void
    {
        [$tenant, $event, $harga, $voucher, $cart] = $this->successfulCashSnapshot();
        View::share('user', $tenant);

        $this->actingAs($tenant)
            ->get('/dashboard/old/cash')
            ->assertOk()
            ->assertViewHas('totalHargaCart', 198000);

        $this->mutateMasterData($event, $harga, $voucher);

        $this->actingAs($tenant)
            ->get('/dashboard/old/cash')
            ->assertOk()
            ->assertViewHas('totalHargaCart', 198000);

        $this->assertSame(198000, app(FinancialSnapshotService::class)->ownerRevenueForCart($cart->fresh('hargaCarts')));
    }

    public function test_dashboard_event_metrics_and_detail_discount_use_snapshots_after_master_data_changes(): void
    {
        [$tenant, $event, $harga, $voucher, $cart] = $this->successfulCashSnapshot();

        $this->mutateMasterData($event, $harga, $voucher);

        Livewire::actingAs($tenant)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'transaksi')
            ->call('showTransactionDetail', $cart->uid)
            ->assertViewHas('metrics', fn ($metrics) => (int) $metrics['total_revenue'] === 198000
                && (int) $metrics['total_discount'] === 20000
                && (int) $metrics['total_pajak'] === 18000)
            ->assertViewHas('discount', 20000)
            ->assertViewHas('voucherCode', $voucher->code);
    }

    public function test_export_query_uses_snapshot_line_discount_not_current_voucher_or_price(): void
    {
        [$tenant, $event, $harga, $voucher] = $this->successfulCashSnapshot();

        $this->mutateMasterData($event, $harga, $voucher);
        $this->actingAs($tenant);

        $component = new DashboardEventDetail;
        $component->eventUid = $event->uid;

        $method = (new ReflectionClass($component))->getMethod('getExportQuery');
        $method->setAccessible(true);

        $row = $method->invoke($component)->first();

        $this->assertSame(100000, (int) $row->harga_ticket);
        $this->assertSame(20000, (int) $row->disc);
        $this->assertSame(198000, (int) (($row->quantity * $row->harga_ticket) - $row->disc + $row->pajak));
    }

    public function test_online_owner_revenue_excludes_internet_fee(): void
    {
        $tenant = $this->user();
        $event = $this->event($tenant, ['fee' => 10]);
        $harga = $this->harga($event, ['harga' => 100000]);
        $cart = $this->cart($tenant, $event, [
            'payment_type' => 'bank_transfer',
            'gross_amount' => 115000,
            'internet_fee' => 5000,
            'pajak' => 10000,
            'pajak_persen' => 10,
        ]);
        $this->hargaCart($cart, $event, $harga, [
            'quantity' => 1,
            'harga_ticket' => 100000,
            'disc' => 0,
        ]);

        $this->assertSame(110000, app(FinancialSnapshotService::class)->ownerRevenueForCart($cart->fresh('hargaCarts')));
    }

    public function test_legacy_empty_pajak_infers_tax_from_gross_amount_without_current_event_fee(): void
    {
        $tenant = $this->user();
        $event = $this->event($tenant, ['fee' => 99]);
        $harga = $this->harga($event, ['harga' => 1]);
        $cart = $this->cart($tenant, $event, [
            'payment_type' => 'bank_transfer',
            'gross_amount' => 115000,
            'internet_fee' => 5000,
            'pajak' => 0,
            'pajak_persen' => 0,
        ]);
        $this->hargaCart($cart, $event, $harga, [
            'quantity' => 1,
            'harga_ticket' => 100000,
            'disc' => 0,
        ]);

        $this->assertSame(110000, app(FinancialSnapshotService::class)->ownerRevenueForCart($cart->fresh('hargaCarts')));
        $this->assertSame(10000, app(FinancialSnapshotService::class)->taxSnapshotForCart($cart->fresh('hargaCarts')));
    }

    public function test_same_voucher_code_other_event_and_other_tenant_do_not_change_owner_report(): void
    {
        [$tenantA, $eventA, $hargaA, $voucherA] = $this->successfulCashSnapshot([
            'payment_type' => 'bank_transfer',
        ]);
        $tenantB = $this->user(['email' => 'tenant-b-report@example.test']);
        $eventB = $this->event($tenantB, ['fee' => 99]);
        $hargaB = $this->harga($eventB, ['harga' => 999999]);
        $voucherB = $this->voucher($tenantB, $eventB, [
            'code' => $voucherA->code,
            'nominal' => 90000,
        ]);
        $cartB = $this->cart($tenantB, $eventB, ['gross_amount' => 999999]);
        $this->hargaCart($cartB, $eventB, $hargaB, ['voucher' => $voucherB->code, 'disc' => 90000]);

        $this->assertSame(198000, app(WithdrawalBalanceService::class)->grossEarningsFor($tenantA->uid));
    }

    public function test_withdrawal_balance_does_not_change_after_master_data_changes(): void
    {
        [$tenant, $event, $harga, $voucher] = $this->successfulCashSnapshot([
            'payment_type' => 'bank_transfer',
        ]);

        $this->assertSame(198000, app(WithdrawalBalanceService::class)->grossEarningsFor($tenant->uid));

        $this->mutateMasterData($event, $harga, $voucher);

        $this->assertSame(198000, app(WithdrawalBalanceService::class)->grossEarningsFor($tenant->uid));
    }

    private function successfulCashSnapshot(array $cartOverrides = []): array
    {
        $tenant = $this->user();
        $event = $this->event($tenant, ['fee' => 10]);
        $harga = $this->harga($event, ['harga' => 100000]);
        $voucher = $this->voucher($tenant, $event, [
            'code' => 'SNAPSHOT20',
            'nominal' => 20000,
        ]);
        $cart = $this->cart($tenant, $event, array_merge([
            'payment_type' => 'cash',
            'gross_amount' => 198000,
            'internet_fee' => 0,
            'pajak' => 18000,
            'pajak_persen' => 10,
        ], $cartOverrides));
        $this->hargaCart($cart, $event, $harga, [
            'quantity' => 2,
            'harga_ticket' => 100000,
            'voucher' => $voucher->code,
            'disc' => 20000,
        ]);
        Cash::create([
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
        ]);

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
            'name' => 'Report Tenant',
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
            'event' => 'Snapshot Event',
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->format('Y-m-d H:i'),
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => 'https://example.test/map',
            'pajak' => 0,
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'snapshot-event-'.$uid,
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

    private function voucher(User $tenant, Event $event, array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'code' => 'SNAPSHOT',
            'unit' => 'rupiah',
            'nominal' => 20000,
            'min_beli' => 0,
            'max_disc' => 0,
            'digunakan' => 0,
            'limit' => 10,
            'status' => 'active',
        ], $overrides));
    }

    private function cart(User $tenant, Event $event, array $overrides = []): Cart
    {
        $uid = (string) Str::uuid();
        $invoice = 'INV-'.$uid;
        $cart = Cart::create(array_merge([
            'uid' => $uid,
            'user_uid' => $tenant->uid,
            'event_uid' => $event->uid,
            'invoice' => $invoice,
            'status' => Cart::STATUS_SUCCESS,
            'konfirmasi' => null,
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
            'amount' => $cart->gross_amount,
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
            'orderBy' => '1',
            'uid' => $cart->uid,
            'harga_id' => $harga->id,
            'event_uid' => $event->uid,
            'quantity' => 1,
            'harga_ticket' => $harga->harga,
            'voucher' => null,
            'disc' => 0,
            'kategori_harga' => $harga->kategori,
        ], $overrides));
    }
}
