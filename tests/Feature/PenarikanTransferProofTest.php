<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Admin\PenarikanIndex as AdminPenarikanIndex;
use App\Livewire\Dashboard\PenarikanIndex as DashboardPenarikanIndex;
use App\Models\Penarikan;
use App\Models\User;
use App\Services\PrivateTransferProofStorage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PenarikanTransferProofTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Storage::fake('local');
        View::share('logo', [(object) ['logo' => '']]);
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('name');
            $table->string('email');
            $table->string('nomor')->nullable();
            $table->string('role');
            $table->string('gambar')->nullable();
            $table->string('password');
            $table->string('birthday')->nullable();
            $table->string('alamat')->nullable();
            $table->string('kota')->nullable();
            $table->string('gender')->nullable();
            $table->string('parent_uid')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('banks', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('uid_user');
            $table->string('nama');
            $table->string('bank');
            $table->string('norek');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('penarikans', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('uid_user');
            $table->string('amount');
            $table->string('note')->nullable();
            $table->string('kwitansi');
            $table->string('status');
            $table->timestamp('approved_at')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('transfer_proof')->nullable();
            $table->timestamp('transfer_proof_uploaded_at')->nullable();
            $table->string('transfer_proof_uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('event_uid')->nullable();
            $table->string('invoice')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_type')->nullable();
            $table->integer('gross_amount')->nullable();
            $table->integer('internet_fee')->nullable();
            $table->integer('pajak')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->integer('quantity')->default(0);
            $table->integer('harga_ticket')->default(0);
            $table->integer('disc')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_admin_can_upload_transfer_proof_while_pending_without_changing_status(): void
    {
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-proof-pending@example.test']);
        $tenant = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-pending@example.test']);
        $penarikan = $this->penarikan($tenant, ['status' => Penarikan::STATUS_PENDING]);

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('openTransferProofModal', $penarikan->uid)
            ->set('transferProof', UploadedFile::fake()->image('proof.png')->size(300))
            ->call('saveTransferProof')
            ->assertHasNoErrors();

        $penarikan->refresh();

        $this->assertSame(Penarikan::STATUS_PENDING, $penarikan->status);
        $this->assertNotNull($penarikan->transfer_proof);
        $this->assertNotNull($penarikan->transfer_proof_uploaded_at);
        $this->assertSame($admin->uid, $penarikan->transfer_proof_uploaded_by);
        $this->assertStringNotContainsString('/storage/', (string) $penarikan->transfer_proof);

        Storage::disk('local')->assertExists('private/penarikan-transfer-proofs/' . $penarikan->transfer_proof);
        Storage::disk('public')->assertMissing($penarikan->transfer_proof);
    }

    public function test_admin_can_upload_transfer_proof_after_success(): void
    {
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-proof-success@example.test']);
        $tenant = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-success@example.test']);
        $penarikan = $this->penarikan($tenant, [
            'status' => Penarikan::STATUS_SUCCESS,
            'approved_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('openTransferProofModal', $penarikan->uid)
            ->set('transferProof', UploadedFile::fake()->image('proof.webp')->size(400))
            ->call('saveTransferProof')
            ->assertHasNoErrors();

        $penarikan->refresh();

        $this->assertSame(Penarikan::STATUS_SUCCESS, $penarikan->status);
        $this->assertNotNull($penarikan->transfer_proof);
        Storage::disk('local')->assertExists('private/penarikan-transfer-proofs/' . $penarikan->transfer_proof);
    }

    public function test_admin_can_replace_existing_transfer_proof_and_old_file_is_removed(): void
    {
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-proof-replace@example.test']);
        $tenant = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-replace@example.test']);
        $penarikan = $this->penarikan($tenant, ['status' => Penarikan::STATUS_SUCCESS, 'approved_at' => now()]);

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('openTransferProofModal', $penarikan->uid)
            ->set('transferProof', UploadedFile::fake()->image('proof-a.png')->size(250))
            ->call('saveTransferProof')
            ->assertHasNoErrors();

        $firstStoredProof = $penarikan->fresh()->transfer_proof;

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('openTransferProofModal', $penarikan->uid)
            ->set('transferProof', UploadedFile::fake()->image('proof-b.png')->size(260))
            ->call('saveTransferProof')
            ->assertHasNoErrors();

        $penarikan->refresh();

        $this->assertNotSame($firstStoredProof, $penarikan->transfer_proof);
        Storage::disk('local')->assertMissing('private/penarikan-transfer-proofs/' . $firstStoredProof);
        Storage::disk('local')->assertExists('private/penarikan-transfer-proofs/' . $penarikan->transfer_proof);
    }

    public function test_invalid_and_oversized_transfer_proof_files_are_rejected(): void
    {
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-proof-invalid@example.test']);
        $tenant = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-invalid@example.test']);
        $penarikan = $this->penarikan($tenant, ['status' => Penarikan::STATUS_PENDING]);

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('openTransferProofModal', $penarikan->uid)
            ->set('transferProof', UploadedFile::fake()->create('proof.txt', 10, 'text/plain'))
            ->call('saveTransferProof')
            ->assertHasErrors('transferProof');

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('openTransferProofModal', $penarikan->uid)
            ->set('transferProof', UploadedFile::fake()->image('proof.png')->size(2501))
            ->call('saveTransferProof')
            ->assertHasErrors('transferProof');

        $penarikan->refresh();

        $this->assertNull($penarikan->transfer_proof);
    }

    public function test_admin_can_view_transfer_proof_via_private_route(): void
    {
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-proof-view@example.test']);
        $tenant = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-view@example.test']);
        $penarikan = $this->penarikanWithStoredProof($tenant, Penarikan::STATUS_PENDING);

        $response = $this->actingAs($admin)
            ->get(route('penarikan.transfer-proof.show', $penarikan->uid));

        $response->assertOk();
        $this->assertStringStartsWith('image/', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline;', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_owner_cannot_view_transfer_proof_while_pending(): void
    {
        $tenant = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-pending-view@example.test']);
        $penarikan = $this->penarikanWithStoredProof($tenant, Penarikan::STATUS_PENDING);

        $this->actingAs($tenant)
            ->get(route('penarikan.transfer-proof.show', $penarikan->uid))
            ->assertForbidden();
    }

    public function test_owner_cannot_view_transfer_proof_while_processing(): void
    {
        $tenant = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-processing-view@example.test']);
        $penarikan = $this->penarikanWithStoredProof($tenant, Penarikan::STATUS_PROCESSING);

        $this->actingAs($tenant)
            ->get(route('penarikan.transfer-proof.show', $penarikan->uid))
            ->assertForbidden();
    }

    public function test_admin_can_upload_transfer_proof_while_processing(): void
    {
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-proof-processing@example.test']);
        $tenant = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-processing@example.test']);
        $penarikan = $this->penarikan($tenant, ['status' => Penarikan::STATUS_PROCESSING]);

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('openTransferProofModal', $penarikan->uid)
            ->set('transferProof', UploadedFile::fake()->image('proof-processing.png')->size(300))
            ->call('saveTransferProof')
            ->assertHasNoErrors();

        $penarikan->refresh();

        $this->assertSame(Penarikan::STATUS_PROCESSING, $penarikan->status);
        $this->assertNotNull($penarikan->transfer_proof);
        Storage::disk('local')->assertExists('private/penarikan-transfer-proofs/' . $penarikan->transfer_proof);
    }

    public function test_owner_and_staff_owner_can_view_transfer_proof_after_success(): void
    {
        $tenant = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-owner@example.test']);
        $staff = $this->user([
            'role' => 'staff',
            'parent_uid' => $tenant->uid,
            'email' => 'staff-proof-owner@example.test',
        ]);
        $penarikan = $this->penarikanWithStoredProof($tenant, Penarikan::STATUS_SUCCESS);

        $this->actingAs($tenant)
            ->get(route('penarikan.transfer-proof.show', $penarikan->uid))
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('penarikan.transfer-proof.show', $penarikan->uid))
            ->assertOk();
    }

    public function test_other_tenant_cannot_access_another_users_transfer_proof_by_uid(): void
    {
        $owner = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-owner-other@example.test']);
        $otherTenant = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-other@example.test']);
        $penarikan = $this->penarikanWithStoredProof($owner, Penarikan::STATUS_SUCCESS);

        $this->actingAs($otherTenant)
            ->get(route('penarikan.transfer-proof.show', $penarikan->uid))
            ->assertForbidden();
    }

    public function test_guest_is_rejected_from_transfer_proof_route(): void
    {
        $tenant = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-guest@example.test']);
        $penarikan = $this->penarikanWithStoredProof($tenant, Penarikan::STATUS_SUCCESS);

        $this->get(route('penarikan.transfer-proof.show', $penarikan->uid))
            ->assertRedirect('/');
    }

    public function test_success_without_transfer_proof_still_shows_invoice_and_safe_message_on_dashboard(): void
    {
        $tenant = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-dashboard@example.test']);
        $this->penarikan($tenant, [
            'status' => Penarikan::STATUS_SUCCESS,
            'approved_at' => now(),
            'transfer_proof' => null,
        ]);

        Livewire::actingAs($tenant)
            ->test(DashboardPenarikanIndex::class)
            ->assertSee('INVOICE')
            ->assertSee('Bukti transfer belum tersedia')
            ->assertDontSee('BUKTI TRANSFER');
    }

    public function test_non_admin_cannot_upload_transfer_proof_from_admin_component(): void
    {
        $tenant = $this->user(['role' => 'penyewa', 'email' => 'tenant-proof-no-admin@example.test']);
        $penarikan = $this->penarikan($tenant, ['status' => Penarikan::STATUS_PENDING]);

        Livewire::actingAs($tenant)
            ->test(AdminPenarikanIndex::class)
            ->assertForbidden();

        $this->assertNull($penarikan->fresh()->transfer_proof);
    }

    private function user(array $overrides = []): User
    {
        return User::create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Transfer Proof User',
            'email' => 'user-' . Str::lower(Str::random(8)) . '@example.test',
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
            'parent_uid' => null,
        ], $overrides));
    }

    private function penarikan(User $tenant, array $overrides = []): Penarikan
    {
        return Penarikan::create(array_merge([
            'uid' => (string) Str::uuid(),
            'uid_user' => $tenant->uid,
            'amount' => 50000,
            'note' => 'Withdrawal',
            'kwitansi' => 150000,
            'status' => Penarikan::STATUS_PENDING,
            'approved_at' => null,
            'bank_name' => 'BCA',
            'bank_account_name' => 'Pemilik Rekening',
            'bank_account_number' => '1234567890',
            'transfer_proof' => null,
            'transfer_proof_uploaded_at' => null,
            'transfer_proof_uploaded_by' => null,
        ], $overrides));
    }

    private function penarikanWithStoredProof(User $tenant, string $status): Penarikan
    {
        $storedProof = app(PrivateTransferProofStorage::class)
            ->storeBasename(UploadedFile::fake()->image('proof.png')->size(300));

        return $this->penarikan($tenant, [
            'status' => $status,
            'approved_at' => $status === Penarikan::STATUS_SUCCESS ? now() : null,
            'transfer_proof' => $storedProof,
            'transfer_proof_uploaded_at' => now(),
            'transfer_proof_uploaded_by' => $tenant->uid,
        ]);
    }
}
