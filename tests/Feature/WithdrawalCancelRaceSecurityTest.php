<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\PenarikanIndex as DashboardPenarikanIndex;
use App\Models\Cart;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Penarikan;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Withdrawals\WithdrawalBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class WithdrawalCancelRaceSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('logo', [(object) ['logo' => '']]);
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
    }

    public function test_tenant_can_cancel_own_pending_withdrawal(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->paidOnlineCart($tenant, $event, 100000);
        $withdrawal = $this->withdrawal($tenant, 60000, Penarikan::STATUS_PENDING);

        Livewire::actingAs($tenant)
            ->test(DashboardPenarikanIndex::class)
            ->call('confirmDelete', $withdrawal->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('penarikans', ['id' => $withdrawal->id]);
        $this->assertSame(100000, app(WithdrawalBalanceService::class)->availableBalanceFor($tenant->uid));
    }

    public function test_tenant_cannot_cancel_processing_or_terminal_withdrawals(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->paidOnlineCart($tenant, $event, 500000);

        foreach ([
            Penarikan::STATUS_PROCESSING,
            Penarikan::STATUS_SUCCESS,
            Penarikan::STATUS_REJECTED,
            Penarikan::STATUS_CANCELLED,
            Penarikan::STATUS_FAILED,
        ] as $status) {
            $withdrawal = $this->withdrawal($tenant, 50000, $status);
            $approvedAt = $withdrawal->approved_at?->toDateTimeString();

            Livewire::actingAs($tenant)
                ->test(DashboardPenarikanIndex::class)
                ->set('penarikan_id', $withdrawal->id)
                ->call('delete')
                ->assertHasErrors('withdrawal');

            $withdrawal->refresh();

            $this->assertNull($withdrawal->deleted_at);
            $this->assertSame($status, $withdrawal->status);
            $this->assertSame($approvedAt, $withdrawal->approved_at?->toDateTimeString());
        }

        $this->assertSame(100000, app(WithdrawalBalanceService::class)->deductedWithdrawalsFor($tenant->uid));
    }

    public function test_tenant_cannot_cancel_another_tenants_withdrawal(): void
    {
        [$tenantA] = $this->tenantWithEvent(['email' => 'tenant-a-cancel@example.test']);
        [$tenantB] = $this->tenantWithEvent(['email' => 'tenant-b-cancel@example.test']);
        $withdrawalB = $this->withdrawal($tenantB, 50000, Penarikan::STATUS_PENDING);

        Livewire::actingAs($tenantA)
            ->test(DashboardPenarikanIndex::class)
            ->set('penarikan_id', $withdrawalB->id)
            ->call('delete')
            ->assertHasErrors('withdrawal');

        $withdrawalB->refresh();

        $this->assertNull($withdrawalB->deleted_at);
        $this->assertSame(Penarikan::STATUS_PENDING, $withdrawalB->status);
    }

    public function test_toctou_status_change_to_success_before_delete_is_not_deleted(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->paidOnlineCart($tenant, $event, 100000);
        $withdrawal = $this->withdrawal($tenant, 100000, Penarikan::STATUS_PENDING);

        $component = Livewire::actingAs($tenant)
            ->test(DashboardPenarikanIndex::class)
            ->call('confirmDelete', $withdrawal->id);

        $withdrawal->update([
            'status' => Penarikan::STATUS_SUCCESS,
            'approved_at' => now(),
        ]);

        $component
            ->call('delete')
            ->assertHasErrors('withdrawal');

        $withdrawal->refresh();

        $this->assertNull($withdrawal->deleted_at);
        $this->assertSame(Penarikan::STATUS_SUCCESS, $withdrawal->status);
        $this->assertSame(100000, app(WithdrawalBalanceService::class)->deductedWithdrawalsFor($tenant->uid));
        $this->assertSame(0, app(WithdrawalBalanceService::class)->availableBalanceFor($tenant->uid));
    }

    public function test_confirm_delete_is_not_authorization_for_final_delete(): void
    {
        [$tenant] = $this->tenantWithEvent();
        $withdrawal = $this->withdrawal($tenant, 50000, Penarikan::STATUS_PROCESSING);

        Livewire::actingAs($tenant)
            ->test(DashboardPenarikanIndex::class)
            ->set('penarikan_id', $withdrawal->id)
            ->call('delete')
            ->assertHasErrors('withdrawal');

        $this->assertNull($withdrawal->fresh()->deleted_at);
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
            'event' => 'Withdrawal Cancel Event '.$uid,
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->format('Y-m-d H:i'),
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Event',
            'map' => 'https://example.test/map',
            'pajak' => 0,
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'withdrawal-cancel-event-'.$uid,
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
