<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\PenarikanIndex;
use App\Models\Bank;
use App\Models\Penarikan;
use App\Models\User;
use App\Services\Tutorials\TutorialProgressService;
use App\Services\Withdrawals\WithdrawalBalanceService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardPenarikanBankAndWithdrawAllTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('name');
            $table->string('email');
            $table->string('nomor')->nullable();
            $table->string('role');
            $table->string('password');
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
            $table->timestamps();
            $table->softDeletes();
        });

        $this->mock(TutorialProgressService::class, function ($mock) {
            $mock->shouldReceive('isCompleted')->andReturnFalse();
            $mock->shouldReceive('isDismissed')->andReturnFalse();
        });
    }

    public function test_modal_create_menampilkan_rekening_aktif(): void
    {
        $tenant = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);
        $this->makeBank($tenant, [
            'bank' => 'BCA Aktif',
            'nama' => 'Pemilik Aktif',
            'norek' => '111222333',
        ]);
        $this->mockBalance(150000);

        Livewire::actingAs($tenant)
            ->test(PenarikanIndex::class)
            ->call('openCreateModal')
            ->assertSee('BCA Aktif')
            ->assertSee('Pemilik Aktif')
            ->assertSee('111222333');
    }

    public function test_modal_edit_menampilkan_snapshot_lama_walau_rekening_aktif_berubah(): void
    {
        $tenant = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);
        $penarikan = $this->makePenarikan($tenant, [
            'amount' => 50000,
            'bank_name' => 'Bank Snapshot Lama',
            'bank_account_name' => 'Pemilik Snapshot Lama',
            'bank_account_number' => '123123123',
        ]);
        $this->makeBank($tenant, [
            'bank' => 'Bank Aktif Baru',
            'nama' => 'Pemilik Aktif Baru',
            'norek' => '999888777',
        ]);
        $this->mockBalance(150000);

        Livewire::actingAs($tenant)
            ->test(PenarikanIndex::class)
            ->call('openEditModal', $penarikan->id)
            ->assertSee('Bank Snapshot Lama')
            ->assertSee('Pemilik Snapshot Lama')
            ->assertSee('123123123')
            ->assertDontSee('Bank Aktif Baru');
    }

    public function test_tarik_semua_create_mengisi_saldo_tersedia_dan_tidak_langsung_membuat_penarikan(): void
    {
        $tenant = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);
        $this->mockBalance(175000);

        Livewire::actingAs($tenant)
            ->test(PenarikanIndex::class)
            ->call('openCreateModal')
            ->call('fillWithdrawAll')
            ->assertSet('amount', 175000);

        $this->assertDatabaseCount('penarikans', 0);
    }

    public function test_tarik_semua_edit_menambahkan_kembali_nominal_request_yang_sedang_diedit(): void
    {
        $tenant = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);
        $penarikan = $this->makePenarikan($tenant, ['amount' => 40000]);
        $this->mockBalance(160000);

        Livewire::actingAs($tenant)
            ->test(PenarikanIndex::class)
            ->call('openEditModal', $penarikan->id)
            ->call('fillWithdrawAll')
            ->assertSet('amount', 200000);

        $this->assertDatabaseCount('penarikans', 1);
        $this->assertDatabaseHas('penarikans', [
            'id' => $penarikan->id,
            'amount' => '40000',
        ]);
    }

    public function test_rekening_tidak_tersedia_tetap_aman(): void
    {
        $tenant = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);
        $this->mockBalance(150000);

        Livewire::actingAs($tenant)
            ->test(PenarikanIndex::class)
            ->call('openCreateModal')
            ->assertSee('Rekening belum tersedia')
            ->assertSet('selectedBank.bank_name', null)
            ->assertSet('selectedBank.bank_account_name', null)
            ->assertSet('selectedBank.bank_account_number', null);
    }

    private function makeUser(array $overrides = []): User
    {
        static $counter = 1;
        $current = $counter++;

        return User::create(array_merge([
            'uid' => 'user-'.$current,
            'name' => 'User '.$current,
            'email' => 'user'.$current.'@example.test',
            'nomor' => '0812345678'.$current,
            'role' => 'user',
            'password' => bcrypt('password'),
            'parent_uid' => null,
        ], $overrides));
    }

    private function makeBank(User $user, array $overrides = []): Bank
    {
        return Bank::create(array_merge([
            'uid' => $user->uid,
            'uid_user' => $user->uid,
            'nama' => 'Pemilik Rekening',
            'bank' => 'Bank Test',
            'norek' => '000111222',
        ], $overrides));
    }

    private function makePenarikan(User $user, array $overrides = []): Penarikan
    {
        static $counter = 1;
        $current = $counter++;

        return Penarikan::create(array_merge([
            'uid' => 'penarikan-'.$current,
            'uid_user' => $user->uid,
            'amount' => 50000,
            'note' => 'Penarikan test',
            'kwitansi' => 200000,
            'status' => Penarikan::STATUS_PENDING,
            'approved_at' => null,
            'bank_name' => 'Bank Snapshot',
            'bank_account_name' => 'Pemilik Snapshot',
            'bank_account_number' => '123456789',
        ], $overrides));
    }

    private function mockBalance(int $available): void
    {
        $this->mock(WithdrawalBalanceService::class, function ($mock) use ($available) {
            $mock->shouldReceive('grossEarningsFor')->andReturn($available);
            $mock->shouldReceive('deductedWithdrawalsFor')->andReturn(0);
            $mock->shouldReceive('availableBalanceFor')->andReturn($available);
        });
    }
}
