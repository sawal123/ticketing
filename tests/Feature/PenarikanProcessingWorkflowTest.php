<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Admin\PenarikanIndex as AdminPenarikanIndex;
use App\Livewire\Dashboard\PenarikanIndex as DashboardPenarikanIndex;
use App\Models\Bank;
use App\Models\Cart;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Penarikan;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Withdrawals\WithdrawalBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PenarikanProcessingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('logo', [(object) ['logo' => '']]);
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
    }

    public function test_create_withdrawal_yields_pending_without_processing_or_approval_timestamps(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $this->paidOnlineCart($tenant, $event, 150000);

        Livewire::actingAs($tenant)
            ->test(DashboardPenarikanIndex::class)
            ->set('amount', 100000)
            ->set('note', 'Pengajuan')
            ->call('save')
            ->assertHasNoErrors();

        $penarikan = Penarikan::firstOrFail();

        $this->assertSame(Penarikan::STATUS_PENDING, $penarikan->status);
        $this->assertNull($penarikan->processing_at);
        $this->assertNull($penarikan->approved_at);
    }

    public function test_pending_to_processing_sets_processing_at_and_keeps_approved_at_null(): void
    {
        [$tenant] = $this->tenantWithEvent();
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-proses@example.test']);
        $penarikan = $this->withdrawal($tenant, 50000, Penarikan::STATUS_PENDING);

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('process', $penarikan->uid);

        $penarikan->refresh();

        $this->assertSame(Penarikan::STATUS_PROCESSING, $penarikan->status);
        $this->assertNotNull($penarikan->processing_at);
        $this->assertNull($penarikan->approved_at);
    }

    public function test_processing_to_success_sets_approved_at(): void
    {
        [$tenant] = $this->tenantWithEvent();
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-selesai@example.test']);
        $penarikan = $this->withdrawal($tenant, 50000, Penarikan::STATUS_PROCESSING, [
            'processing_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('complete', $penarikan->uid);

        $penarikan->refresh();

        $this->assertSame(Penarikan::STATUS_SUCCESS, $penarikan->status);
        $this->assertNotNull($penarikan->approved_at);
        $this->assertNotNull($penarikan->processing_at);
    }

    public function test_pending_cannot_jump_directly_to_success(): void
    {
        [$tenant] = $this->tenantWithEvent();
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-lompat@example.test']);
        $penarikan = $this->withdrawal($tenant, 50000, Penarikan::STATUS_PENDING);

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('complete', $penarikan->uid)
            ->call('approve', $penarikan->uid);

        $penarikan->refresh();

        $this->assertSame(Penarikan::STATUS_PENDING, $penarikan->status);
        $this->assertNull($penarikan->processing_at);
        $this->assertNull($penarikan->approved_at);
    }

    public function test_success_cannot_be_reprocessed(): void
    {
        [$tenant] = $this->tenantWithEvent();
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-ulang@example.test']);
        $approvedAt = now()->subDay();
        $penarikan = $this->withdrawal($tenant, 50000, Penarikan::STATUS_SUCCESS, [
            'processing_at' => now()->subDays(2),
            'approved_at' => $approvedAt,
        ]);

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('process', $penarikan->uid)
            ->call('complete', $penarikan->uid);

        $penarikan->refresh();

        $this->assertSame(Penarikan::STATUS_SUCCESS, $penarikan->status);
        $this->assertSame($approvedAt->toDateTimeString(), $penarikan->approved_at?->toDateTimeString());
    }

    public function test_admin_can_upload_transfer_proof_in_pending_processing_and_success(): void
    {
        Storage::fake('local');

        [$tenant] = $this->tenantWithEvent();
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-upload-all@example.test']);

        foreach ([
            Penarikan::STATUS_PENDING,
            Penarikan::STATUS_PROCESSING,
            Penarikan::STATUS_SUCCESS,
        ] as $status) {
            $penarikan = $this->withdrawal($tenant, 50000, $status, [
                'approved_at' => $status === Penarikan::STATUS_SUCCESS ? now() : null,
            ]);

            Livewire::actingAs($admin)
                ->test(AdminPenarikanIndex::class)
                ->call('openTransferProofModal', $penarikan->uid)
                ->set('transferProof', UploadedFile::fake()->image("proof-{$status}.png")->size(300))
                ->call('saveTransferProof')
                ->assertHasNoErrors();

            $this->assertNotNull($penarikan->fresh()->transfer_proof);
        }
    }

    public function test_tenant_can_view_invoice_in_pending_processing_and_success(): void
    {
        [$tenant] = $this->tenantWithEvent();
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-invoice@example.test']);
        $this->adminBank($admin);

        foreach ([
            Penarikan::STATUS_PENDING,
            Penarikan::STATUS_PROCESSING,
            Penarikan::STATUS_SUCCESS,
        ] as $status) {
            $penarikan = $this->withdrawal($tenant, 50000, $status, [
                'approved_at' => $status === Penarikan::STATUS_SUCCESS ? now() : null,
            ]);

            $this->actingAs($tenant)
                ->get('/invoice/'.$penarikan->uid)
                ->assertOk()
                ->assertSee('Invoice Penarikan Saldo')
                ->assertSee($status);
        }
    }

    public function test_invoice_note_follows_status(): void
    {
        [$tenant] = $this->tenantWithEvent();
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-note@example.test']);
        $this->adminBank($admin);

        $expectedNotes = [
            Penarikan::STATUS_PENDING => 'Permintaan penarikan telah diterima sistem dan sedang menunggu proses administrasi.',
            Penarikan::STATUS_PROCESSING => 'Permintaan penarikan sedang diproses. Estimasi penyelesaian maksimal 1×24 jam.',
            Penarikan::STATUS_SUCCESS => 'Permintaan penarikan telah selesai diproses. Invoice ini merupakan dokumen administrasi dan bukan bukti transaksi perbankan. Bukti transfer, jika tersedia, dapat dilihat secara terpisah.',
        ];

        foreach ($expectedNotes as $status => $note) {
            $penarikan = $this->withdrawal($tenant, 50000, $status, [
                'approved_at' => $status === Penarikan::STATUS_SUCCESS ? now() : null,
            ]);

            $this->actingAs($tenant)
                ->get('/invoice/'.$penarikan->uid)
                ->assertOk()
                ->assertSee($note);
        }
    }

    public function test_penarikan_invoice_no_longer_claims_to_be_bank_transfer_proof(): void
    {
        [$tenant] = $this->tenantWithEvent();
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-klaim@example.test']);
        $this->adminBank($admin);
        $penarikan = $this->withdrawal($tenant, 50000, Penarikan::STATUS_SUCCESS, [
            'approved_at' => now(),
        ]);

        $this->actingAs($tenant)
            ->get('/invoice/'.$penarikan->uid)
            ->assertOk()
            ->assertDontSee('Dana telah berhasil ditransfer')
            ->assertDontSee('Verified Digitally')
            ->assertDontSee('Dokumen ini sah tanpa tanda tangan basah')
            ->assertDontSee('Bukti Penarikan Saldo')
            ->assertDontSee('Simpan bukti ini sebagai referensi resmi')
            ->assertSee('Referensi Sistem')
            ->assertSee('Rekening Admin Tercatat')
            ->assertSee('Rekening Tujuan Penarikan')
            ->assertSee('Dokumen ini diterbitkan otomatis oleh sistem GoTik sebagai referensi administrasi.');
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
            'name' => 'Processing Workflow User',
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
            'event' => 'Processing Workflow Event '.$uid,
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->format('Y-m-d H:i'),
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Event',
            'map' => 'https://example.test/map',
            'pajak' => 0,
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'processing-workflow-event-'.$uid,
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
            'processing_at' => null,
            'approved_at' => $status === Penarikan::STATUS_SUCCESS ? now() : null,
            'bank_name' => 'BCA',
            'bank_account_name' => 'Pemilik Rekening',
            'bank_account_number' => '1234567890',
        ], $overrides));
    }

    private function adminBank(User $admin): Bank
    {
        return Bank::create([
            'uid' => $admin->uid,
            'uid_user' => $admin->uid,
            'nama' => 'GoTik Admin',
            'bank' => 'BCA',
            'norek' => '999888777',
        ]);
    }
}
