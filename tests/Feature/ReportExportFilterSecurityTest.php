<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Admin\TransaksiIndex as AdminTransaksiIndex;
use App\Livewire\Dashboard\EventDetail as DashboardEventDetail;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ReportExportFilterSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';

        parent::setUp();

        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '', 'icon' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_export_csv_sanitizes_formula_fields_and_preserves_numeric_fields(): void
    {
        [$tenant, $event] = $this->successfulCashSnapshot([
            'invoice' => '@evil',
            'buyer_name' => "=\r\nHYPERLINK(\"http://evil.test\",\"klik\")",
            'buyer_email' => '+evil@example.test',
            'kategori' => '-SUM(1,1)',
        ]);

        $this->actingAs($tenant);

        $component = new DashboardEventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $component->exportExcel()->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString("'@evil", $csv);
        $this->assertStringContainsString("'=  HYPERLINK", $csv);
        $this->assertStringContainsString("'+evil@example.test", $csv);
        $this->assertStringContainsString("'-SUM(1,1)", $csv);
        $this->assertStringNotContainsString("=\r\nHYPERLINK", $csv);
        $this->assertStringContainsString("'-SUM(1,1)\",2,100000,20000,18000,198000", $csv);
    }

    public function test_export_print_returns_html_file_and_escapes_user_controlled_fields(): void
    {
        [$tenant, $event] = $this->successfulCashSnapshot([
            'invoice' => '<b>INV</b>',
            'buyer_name' => '<script>alert(1)</script>',
            'buyer_email' => '<img src=x onerror=alert(1)>',
            'kategori' => '<svg onload=alert(1)>',
        ]);

        $this->actingAs($tenant);

        $component = new DashboardEventDetail;
        $component->eventUid = $event->uid;
        $response = $component->exportPrint();

        $this->assertStringContainsString('text/html', $response->headers->get('content-type'));
        $this->assertStringContainsString('.html', $response->headers->get('content-disposition'));

        ob_start();
        $response->sendContent();
        $html = ob_get_clean();

        $this->assertStringStartsNotWith('%PDF', $html);
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringNotContainsString('<svg onload=alert(1)>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;INV&lt;/b&gt;', $html);
    }

    public function test_dashboard_event_detail_export_label_is_print_not_pdf(): void
    {
        [$tenant, $event] = $this->successfulCashSnapshot();

        Livewire::actingAs($tenant)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'transaksi')
            ->assertSee('Export Print')
            ->assertDontSee('Export PDF');
    }

    public function test_dashboard_event_detail_filter_inputs_are_sanitized(): void
    {
        [$tenant, $event] = $this->successfulCashSnapshot();

        Livewire::actingAs($tenant)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('perPage', 999)
            ->set('filterPayment', 'hack')
            ->set('activeTab', '../../x')
            ->set('searchTransaction', str_repeat('a', 1000))
            ->assertSet('perPage', 10)
            ->assertSet('filterPayment', 'all')
            ->assertSet('activeTab', 'umum')
            ->assertSet('searchTransaction', str_repeat('a', 100));
    }

    public function test_dashboard_event_detail_invalid_filter_ranges_do_not_throw(): void
    {
        [$tenant, $event] = $this->successfulCashSnapshot();

        Livewire::actingAs($tenant)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'transaksi')
            ->set('filterRange', 'not-a-date')
            ->assertSet('filterRange', null)
            ->assertOk();

        Livewire::actingAs($tenant)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'transaksi')
            ->set('filterRange', '2024-01-01 to 2025-01-02')
            ->assertSet('filterRange', null)
            ->assertOk();

        Livewire::actingAs($tenant)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'transaksi')
            ->set('filterRange', '2025-01-02 to 2025-01-01')
            ->assertSet('filterRange', null)
            ->assertOk();
    }

    public function test_dashboard_event_detail_valid_range_filters_transactions(): void
    {
        $tenant = $this->user();
        $event = $this->event($tenant);
        $harga = $this->harga($event);

        $oldCart = $this->cart($tenant, $event, [
            'gross_amount' => 110000,
            'pajak' => 10000,
        ]);
        $oldCart->forceFill(['created_at' => '2026-01-01 10:00:00'])->save();
        $this->hargaCart($oldCart, $event, $harga, [
            'quantity' => 1,
            'harga_ticket' => 100000,
        ]);

        $newCart = $this->cart($tenant, $event, [
            'gross_amount' => 220000,
            'pajak' => 20000,
        ]);
        $newCart->forceFill(['created_at' => '2026-02-01 10:00:00'])->save();
        $this->hargaCart($newCart, $event, $harga, [
            'quantity' => 2,
            'harga_ticket' => 100000,
        ]);

        Livewire::actingAs($tenant)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'transaksi')
            ->set('filterRange', '2026-02-01 to 2026-02-01')
            ->assertViewHas('metrics', fn ($metrics) => (int) $metrics['total_revenue'] === 220000);
    }

    public function test_admin_transaction_filters_are_sanitized(): void
    {
        $admin = $this->user([
            'role' => 'admin',
            'email' => 'admin-report-filter@example.test',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminTransaksiIndex::class)
            ->set('status', 'HACK')
            ->set('paymentType', 'wire')
            ->set('date', 'not-a-date')
            ->set('search', str_repeat('s', 1000))
            ->assertSet('status', 'SUCCESS')
            ->assertSet('paymentType', 'all')
            ->assertSet('date', '')
            ->assertSet('search', str_repeat('s', 100));
    }

    public function test_admin_arbitrary_event_uid_does_not_match_transactions(): void
    {
        $admin = $this->user([
            'role' => 'admin',
            'email' => 'admin-event-filter@example.test',
        ]);
        $tenant = $this->user();
        $event = $this->event($tenant);
        $harga = $this->harga($event);
        $cart = $this->cart($tenant, $event, ['invoice' => 'VISIBLE-INVOICE']);
        $this->hargaCart($cart, $event, $harga);

        Livewire::actingAs($admin)
            ->test(AdminTransaksiIndex::class)
            ->set('eventUid', 'arbitrary-event-uid')
            ->assertDontSee('VISIBLE-INVOICE');
    }

    public function test_legacy_report_invalid_date_filters_do_not_throw(): void
    {
        [$tenant] = $this->successfulCashSnapshot();
        View::share('user', $tenant);

        $this->actingAs($tenant)
            ->get('/dashboard/old/cash?filter=not-a-date')
            ->assertOk();

        $this->actingAs($tenant)
            ->get('/dashboard/old/transaksi?filter=not-a-date')
            ->assertOk();
    }

    public function test_legacy_report_uid_filter_does_not_show_other_tenant_event(): void
    {
        $tenantA = $this->user(['email' => 'tenant-a-report-filter@example.test']);
        [, $eventB] = $this->successfulCashSnapshot();
        View::share('user', $tenantA);

        $this->actingAs($tenantA)
            ->get('/dashboard/old/cash?uid='.$eventB->uid)
            ->assertOk()
            ->assertViewHas('event', null)
            ->assertViewHas('totalHargaCart', 0);

        $this->actingAs($tenantA)
            ->get('/dashboard/old/transaksi?uid='.$eventB->uid)
            ->assertOk()
            ->assertViewHas('event', null)
            ->assertViewHas('totalPenjualan', 0);
    }

    public function test_export_total_snapshot_is_stable_after_master_data_changes(): void
    {
        [$tenant, $event, $harga, $voucher] = $this->successfulCashSnapshot();
        $event->update(['fee' => 99]);
        $voucher->update(['nominal' => 999999]);
        $harga->update(['harga' => 1]);

        $this->actingAs($tenant);

        $component = new DashboardEventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $component->exportExcel()->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('"TOTAL OMZET SNAPSHOT",198000', $csv);
        $this->assertStringNotContainsString('999999', $csv);
    }

    public function test_export_includes_ticket_verification_status_and_scanned_at_time(): void
    {
        [$tenant, $event, $harga, , $scannedCart] = $this->successfulCashSnapshot();
        $scannedCart->forceFill([
            'scanned_at' => Carbon::parse('2026-08-11 10:11:12'),
        ])->save();

        $legacyCart = $this->cart($tenant, $event, [
            'invoice' => 'INV-LEGACY-VERIFIED',
            'gross_amount' => 100000,
            'konfirmasi' => '1',
        ]);
        $this->hargaCart($legacyCart, $event, $harga);

        $unverifiedCart = $this->cart($tenant, $event, [
            'invoice' => 'INV-NOT-VERIFIED',
            'gross_amount' => 100000,
        ]);
        $this->hargaCart($unverifiedCart, $event, $harga);

        $this->actingAs($tenant);

        $component = new DashboardEventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $component->exportExcel()->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('Status Verifikasi', $csv);
        $this->assertStringContainsString('Tanggal Verifikasi', $csv);
        $this->assertStringContainsString('Waktu Verifikasi', $csv);
        $this->assertStringContainsString('Terverifikasi,"11 Aug 2026",10:11:12', $csv);
        $this->assertStringContainsString('INV-LEGACY-VERIFIED', $csv);
        $this->assertStringContainsString('Terverifikasi,"Tidak tersedia","Tidak tersedia"', $csv);
        $this->assertStringContainsString('INV-NOT-VERIFIED', $csv);
        $this->assertStringContainsString('"Belum Diverifikasi","Tidak tersedia","Tidak tersedia"', $csv);
    }

    public function test_export_legacy_tax_uses_financial_snapshot_fallback(): void
    {
        $tenant = $this->user();
        $event = $this->event($tenant);
        $harga = $this->harga($event, ['harga' => 100000]);
        $cart = $this->cart($tenant, $event, [
            'invoice' => 'INV-LEGACY-TAX',
            'gross_amount' => 110000,
            'internet_fee' => 0,
            'pajak' => 0,
        ]);
        $this->hargaCart($cart, $event, $harga, [
            'quantity' => 1,
            'harga_ticket' => 100000,
            'disc' => 0,
        ]);

        $this->actingAs($tenant);

        $component = new DashboardEventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $component->exportExcel()->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('INV-LEGACY-TAX', $csv);
        $this->assertStringContainsString('10000,110000', $csv);
        $this->assertStringContainsString('"TOTAL OMZET SNAPSHOT",110000', $csv);

        $response = $component->exportPrint();
        ob_start();
        $response->sendContent();
        $html = ob_get_clean();

        $this->assertStringContainsString('Rp 110.000', $html);
        $this->assertStringContainsString('TOTAL OMZET SNAPSHOT', $html);
    }

    private function successfulCashSnapshot(array $overrides = []): array
    {
        $tenant = $this->user($overrides['tenant'] ?? []);
        $event = $this->event($tenant, $overrides['event'] ?? ['fee' => 10]);
        $harga = $this->harga($event, [
            'kategori' => $overrides['kategori'] ?? 'Regular',
            'harga' => 100000,
        ]);
        $voucher = $this->voucher($tenant, $event);
        $cart = $this->cart($tenant, $event, [
            'invoice' => $overrides['invoice'] ?? null,
            'gross_amount' => 198000,
            'pajak' => 18000,
            'pajak_persen' => 10,
        ]);
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
            'name' => $overrides['buyer_name'] ?? 'Cash Buyer',
            'email' => $overrides['buyer_email'] ?? 'cash@example.test',
            'nomor' => '08123456789',
            'alamat' => 'Jakarta',
            'lahir' => '2000-01-01',
            'gender' => 'pria',
        ]);

        return [$tenant, $event, $harga, $voucher, $cart];
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
            'event' => 'Report Filter Event',
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->format('Y-m-d H:i'),
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => 'https://example.test/map',
            'pajak' => 0,
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'report-filter-event-'.$uid,
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
            'code' => 'REPORT20',
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
        $uid = (string) Str::uuid();
        $invoice = $overrides['invoice'] ?? 'INV-'.$uid;
        unset($overrides['invoice']);

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
