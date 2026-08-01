<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Admin\PenarikanIndex as AdminPenarikanIndex;
use App\Livewire\Dashboard\PenarikanIndex as DashboardPenarikanIndex;
use App\Models\Cart;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Penarikan;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Withdrawals\WithdrawalBalanceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class WithdrawalSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('logo', [(object) ['logo' => '']]);
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
    }

    public function test_tenant_can_create_withdrawal_when_balance_is_sufficient(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->paidOnlineCart($tenant, $event, 150000);

        $this->actingAs($tenant)
            ->from('/dashboard/old/money')
            ->post('/dashboard/old/addPenarikan', ['amount' => 100000])
            ->assertRedirect('/dashboard/old/money')
            ->assertSessionHas('penarikan');

        $this->assertDatabaseHas('penarikans', [
            'uid_user' => $tenant->uid,
            'amount' => 100000,
            'kwitansi' => 150000,
            'status' => Penarikan::STATUS_PENDING,
        ]);
    }

    public function test_tenant_cannot_create_withdrawal_above_balance(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->paidOnlineCart($tenant, $event, 50000);

        $this->actingAs($tenant)
            ->from('/dashboard/old/money')
            ->post('/dashboard/old/addPenarikan', ['amount' => 60000])
            ->assertRedirect('/dashboard/old/money')
            ->assertSessionHas('error', 'Saldo Anda tidak mencukupi.');

        $this->assertDatabaseCount('penarikans', 0);
    }

    public function test_request_cannot_spoof_uid_user_status_or_kwitansi(): void
    {
        [$tenantA, $eventA] = $this->tenantWithEvent(['email' => 'tenant-a@example.test']);
        [$tenantB] = $this->tenantWithEvent(['email' => 'tenant-b@example.test']);
        $this->paidOnlineCart($tenantA, $eventA, 200000);

        $this->actingAs($tenantA)
            ->from('/dashboard/old/money')
            ->post('/dashboard/old/addPenarikan', [
                'amount' => 100000,
                'uid_user' => $tenantB->uid,
                'status' => Penarikan::STATUS_SUCCESS,
                'kwitansi' => 999999999,
            ])
            ->assertRedirect('/dashboard/old/money')
            ->assertSessionHas('penarikan');

        $withdrawal = Penarikan::firstOrFail();

        $this->assertSame($tenantA->uid, $withdrawal->uid_user);
        $this->assertSame(Penarikan::STATUS_PENDING, $withdrawal->status);
        $this->assertSame(200000, (int) $withdrawal->kwitansi);
    }

    public function test_pending_withdrawal_reduces_available_balance(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->paidOnlineCart($tenant, $event, 200000);
        $this->withdrawal($tenant, 75000, Penarikan::STATUS_PENDING);

        $this->assertSame(125000, app(WithdrawalBalanceService::class)->availableBalanceFor($tenant->uid));
    }

    public function test_success_withdrawal_reduces_available_balance(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->paidOnlineCart($tenant, $event, 200000);
        $this->withdrawal($tenant, 90000, Penarikan::STATUS_SUCCESS);

        $this->assertSame(110000, app(WithdrawalBalanceService::class)->availableBalanceFor($tenant->uid));
    }

    public function test_rejected_cancelled_and_failed_withdrawals_do_not_reduce_available_balance(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->paidOnlineCart($tenant, $event, 200000);
        $this->withdrawal($tenant, 50000, Penarikan::STATUS_REJECTED);
        $this->withdrawal($tenant, 40000, Penarikan::STATUS_CANCELLED);
        $this->withdrawal($tenant, 30000, Penarikan::STATUS_FAILED);

        $this->assertSame(200000, app(WithdrawalBalanceService::class)->availableBalanceFor($tenant->uid));
    }

    public function test_two_sequential_withdrawals_cannot_exceed_available_balance(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->paidOnlineCart($tenant, $event, 100000);

        $this->actingAs($tenant)
            ->from('/dashboard/old/money')
            ->post('/dashboard/old/addPenarikan', ['amount' => 70000])
            ->assertSessionHas('penarikan');

        $this->actingAs($tenant)
            ->from('/dashboard/old/money')
            ->post('/dashboard/old/addPenarikan', ['amount' => 40000])
            ->assertSessionHas('error', 'Saldo Anda tidak mencukupi.');

        $this->assertDatabaseCount('penarikans', 1);
        $this->assertSame(30000, app(WithdrawalBalanceService::class)->availableBalanceFor($tenant->uid));
    }

    public function test_tenant_cannot_view_or_access_another_tenants_withdrawal(): void
    {
        [$tenantA] = $this->tenantWithEvent(['email' => 'tenant-a@example.test']);
        [$tenantB] = $this->tenantWithEvent(['email' => 'tenant-b@example.test']);
        $withdrawalB = $this->withdrawal($tenantB, 50000, Penarikan::STATUS_PENDING, ['note' => 'Tenant B Secret']);

        Livewire::actingAs($tenantA)
            ->test(DashboardPenarikanIndex::class)
            ->assertDontSee('Tenant B Secret');

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($tenantA)
            ->test(DashboardPenarikanIndex::class)
            ->call('openEditModal', $withdrawalB->id);
    }

    public function test_tenant_cannot_update_another_tenants_withdrawal_status(): void
    {
        [$tenantA] = $this->tenantWithEvent();
        [$tenantB] = $this->tenantWithEvent(['email' => 'tenant-b@example.test']);
        $withdrawalB = $this->withdrawal($tenantB, 50000, Penarikan::STATUS_PENDING);

        $this->actingAs($tenantA)
            ->post('/admin/old/editPenarikan', ['uid' => $withdrawalB->uid])
            ->assertRedirect('/');

        $this->assertSame(Penarikan::STATUS_PENDING, $withdrawalB->fresh()->status);
    }

    public function test_admin_can_update_withdrawal_status(): void
    {
        [$tenant] = $this->tenantWithEvent();
        $admin = $this->user(['role' => 'admin', 'email' => 'admin@example.test']);
        $withdrawal = $this->withdrawal($tenant, 50000, Penarikan::STATUS_PENDING);

        $this->actingAs($admin)
            ->from('/admin/old/penarikan')
            ->post('/admin/old/editPenarikan', ['uid' => $withdrawal->uid])
            ->assertRedirect('/admin/old/penarikan')
            ->assertSessionHas('success');

        $withdrawal->refresh();

        $this->assertSame(Penarikan::STATUS_SUCCESS, $withdrawal->status);
        $this->assertNotNull($withdrawal->approved_at);
    }

    public function test_non_admin_cannot_mount_admin_withdrawal_component(): void
    {
        [$tenant] = $this->tenantWithEvent();

        Livewire::actingAs($tenant)
            ->test(AdminPenarikanIndex::class)
            ->assertForbidden();
    }

    public function test_invalid_amounts_are_rejected(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->paidOnlineCart($tenant, $event, 200000000);

        foreach ([0, -1, '10000.50', 'abc', 100000001] as $amount) {
            $this->actingAs($tenant)
                ->from('/dashboard/old/money')
                ->post('/dashboard/old/addPenarikan', ['amount' => $amount])
                ->assertRedirect('/dashboard/old/money')
                ->assertSessionHasErrors('amount');
        }

        $this->assertDatabaseCount('penarikans', 0);
    }

    public function test_balance_only_counts_events_owned_by_logged_in_tenant(): void
    {
        [$tenantA, $eventA] = $this->tenantWithEvent(['email' => 'tenant-a@example.test']);
        [$tenantB, $eventB] = $this->tenantWithEvent(['email' => 'tenant-b@example.test']);
        $this->paidOnlineCart($tenantA, $eventA, 100000);
        $this->paidOnlineCart($tenantB, $eventB, 900000);

        $this->assertSame(100000, app(WithdrawalBalanceService::class)->availableBalanceFor($tenantA->uid));

        $this->actingAs($tenantA)
            ->from('/dashboard/old/money')
            ->post('/dashboard/old/addPenarikan', ['amount' => 150000])
            ->assertSessionHas('error', 'Saldo Anda tidak mencukupi.');

        $this->assertDatabaseCount('penarikans', 0);
    }

    public function test_livewire_create_uses_server_balance_and_ignores_spoofed_state(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->paidOnlineCart($tenant, $event, 120000);

        Livewire::actingAs($tenant)
            ->test(DashboardPenarikanIndex::class)
            ->set('totalSaldo', 999999999)
            ->set('successWithdrawal', 0)
            ->set('pendingWithdrawal', 0)
            ->set('amount', 150000)
            ->call('save')
            ->assertHasErrors('amount');

        $this->assertDatabaseCount('penarikans', 0);
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
            'name' => 'Withdrawal User',
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
            'event' => 'Withdrawal Event '.$uid,
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->format('Y-m-d H:i'),
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Event',
            'map' => 'https://example.test/map',
            'pajak' => 0,
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'withdrawal-event-'.$uid,
            'konfirmasi' => '1',
        ]);
    }

    private function paidOnlineCart(User $buyer, Event $event, int $grossAmount): Cart
    {
        $harga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Regular',
            'qty' => 10,
            'sold_qty' => 1,
            'reserved_qty' => 0,
            'harga' => $grossAmount,
            'status' => 'active',
        ]);

        $cart = Cart::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.Str::upper(Str::random(8)),
            'status' => Cart::STATUS_SUCCESS,
            'payment_type' => 'bank_transfer',
            'gross_amount' => $grossAmount,
            'paid_at' => now(),
        ]);

        HargaCart::create([
            'uid' => $cart->uid,
            'harga_id' => $harga->id,
            'orderBy' => '1',
            'event_uid' => $event->uid,
            'quantity' => 1,
            'harga_ticket' => $grossAmount,
            'kategori_harga' => $harga->kategori,
        ]);

        Transaction::create([
            'uid' => $cart->uid,
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'amount' => (string) $grossAmount,
            'gross_amount' => $grossAmount,
            'invoice' => $cart->invoice,
            'payment_type' => 'bank_transfer',
            'status_transaksi' => Cart::STATUS_SUCCESS,
            'paid_at' => now(),
        ]);

        return $cart;
    }

    private function withdrawal(User $tenant, int $amount, string $status, array $overrides = []): Penarikan
    {
        return Penarikan::create(array_merge([
            'uid' => (string) Str::uuid(),
            'uid_user' => $tenant->uid,
            'amount' => $amount,
            'note' => 'Withdrawal',
            'kwitansi' => 0,
            'status' => $status,
            'approved_at' => $status === Penarikan::STATUS_SUCCESS ? now() : null,
        ], $overrides));
    }
}
