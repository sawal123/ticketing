<?php

namespace Tests\Feature;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\PenarikanIndex as AdminPenarikanIndex;
use App\Livewire\Dashboard\PenarikanIndex as DashboardPenarikanIndex;
use App\Models\Bank;
use App\Models\Penarikan;
use App\Models\User;
use App\Services\Withdrawals\WithdrawalBalanceService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPenarikanDetailTest extends TestCase
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
            $table->string('gambar')->nullable();
            $table->string('password');
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

        $this->createPenarikansTable(withSnapshot: true);

        view()->share('logo', collect([(object) ['logo' => '', 'icon' => '']]));
    }

    public function test_penarikan_baru_menyimpan_snapshot_rekening_dan_tidak_berubah_saat_rekening_diubah(): void
    {
        $tenant = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);
        $bank = $this->makeBank($tenant, [
            'bank' => 'BCA Snapshot',
            'nama' => 'Nama Snapshot',
            'norek' => '111222333',
        ]);

        $this->mockWithdrawalBalance(200000);

        Livewire::actingAs($tenant)
            ->test(DashboardPenarikanIndex::class)
            ->set('amount', 50000)
            ->set('note', 'Tarik saldo')
            ->call('save');

        $penarikan = Penarikan::firstOrFail();

        $this->assertSame('BCA Snapshot', $penarikan->bank_name);
        $this->assertSame('Nama Snapshot', $penarikan->bank_account_name);
        $this->assertSame('111222333', $penarikan->bank_account_number);

        $bank->update([
            'bank' => 'Mandiri Baru',
            'nama' => 'Nama Baru',
            'norek' => '999888777',
        ]);

        $penarikan->refresh();

        $this->assertSame('BCA Snapshot', $penarikan->bank_name);
        $this->assertSame('Nama Snapshot', $penarikan->bank_account_name);
        $this->assertSame('111222333', $penarikan->bank_account_number);
    }

    public function test_detail_admin_menampilkan_snapshot_rekening(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $tenant = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);
        $penarikan = $this->makePenarikan($tenant, [
            'bank_name' => 'BNI Snapshot',
            'bank_account_name' => 'Pemilik Snapshot',
            'bank_account_number' => '123123123',
        ]);

        $this->makeBank($tenant, [
            'bank' => 'Bank Aktif Berbeda',
            'nama' => 'Nama Aktif',
            'norek' => '000000',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('openDetail', $penarikan->uid)
            ->assertSee('BNI Snapshot')
            ->assertSee('Pemilik Snapshot')
            ->assertSee('123123123')
            ->assertDontSee('Bank Aktif Berbeda');
    }

    public function test_penarikan_lama_dapat_fallback_ke_rekening_existing(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $tenant = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);
        $penarikan = $this->makePenarikan($tenant);

        $this->makeBank($tenant, [
            'bank' => 'BRI Legacy',
            'nama' => 'Pemilik Legacy',
            'norek' => '555666777',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('openDetail', $penarikan->uid)
            ->assertSee('BRI Legacy')
            ->assertSee('Pemilik Legacy')
            ->assertSee('555666777');
    }

    public function test_migration_backfill_tidak_overwrite_snapshot_existing_dan_aman_tanpa_rekening(): void
    {
        Schema::drop('penarikans');
        $this->createPenarikansTable(withSnapshot: false);

        $tenantWithBank = $this->makeUser(['uid' => 'tenant-bank', 'role' => 'penyewa']);
        $tenantWithoutBank = $this->makeUser(['uid' => 'tenant-no-bank', 'role' => 'penyewa']);
        $this->makeBank($tenantWithBank, [
            'bank' => 'CIMB Backfill',
            'nama' => 'Backfill Owner',
            'norek' => '101010',
        ]);

        DB::table('penarikans')->insert([
            [
                'uid' => 'legacy-with-bank',
                'uid_user' => $tenantWithBank->uid,
                'amount' => '10000',
                'note' => 'Legacy',
                'kwitansi' => '100000',
                'status' => Penarikan::STATUS_PENDING,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uid' => 'legacy-no-bank',
                'uid_user' => $tenantWithoutBank->uid,
                'amount' => '10000',
                'note' => 'Legacy',
                'kwitansi' => '100000',
                'status' => Penarikan::STATUS_PENDING,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->snapshotMigration()->up();

        $this->assertDatabaseHas('penarikans', [
            'uid' => 'legacy-with-bank',
            'bank_name' => 'CIMB Backfill',
            'bank_account_name' => 'Backfill Owner',
            'bank_account_number' => '101010',
        ]);

        $this->assertDatabaseHas('penarikans', [
            'uid' => 'legacy-no-bank',
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
        ]);

        DB::table('penarikans')->insert([
            'uid' => 'snapshot-existing',
            'uid_user' => $tenantWithBank->uid,
            'amount' => '20000',
            'note' => 'Existing snapshot',
            'kwitansi' => '100000',
            'status' => Penarikan::STATUS_PENDING,
            'bank_name' => 'Existing Bank',
            'bank_account_name' => 'Existing Owner',
            'bank_account_number' => '202020',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->snapshotMigration()->up();

        $this->assertDatabaseHas('penarikans', [
            'uid' => 'snapshot-existing',
            'bank_name' => 'Existing Bank',
            'bank_account_name' => 'Existing Owner',
            'bank_account_number' => '202020',
        ]);
    }

    public function test_migration_backfill_chunk_by_id_memproses_semua_legacy_penarikan(): void
    {
        Schema::drop('penarikans');
        $this->createPenarikansTable(withSnapshot: false);

        $tenant = $this->makeUser(['uid' => 'tenant-bulk', 'role' => 'penyewa']);
        $this->makeBank($tenant, [
            'bank' => 'Bulk Bank',
            'nama' => 'Bulk Owner',
            'norek' => '250250',
        ]);

        $rows = [];
        for ($i = 1; $i <= 250; $i++) {
            $rows[] = [
                'uid' => 'legacy-bulk-'.$i,
                'uid_user' => $tenant->uid,
                'amount' => '10000',
                'note' => 'Legacy bulk',
                'kwitansi' => '100000',
                'status' => Penarikan::STATUS_PENDING,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('penarikans')->insert($rows);

        $this->snapshotMigration()->up();

        $this->assertSame(250, DB::table('penarikans')->where('bank_name', 'Bulk Bank')->count());
        $this->assertSame(0, DB::table('penarikans')->whereNull('bank_name')->count());

        $this->assertDatabaseHas('penarikans', [
            'uid' => 'legacy-bulk-250',
            'uid_user' => $tenant->uid,
            'amount' => '10000',
            'status' => Penarikan::STATUS_PENDING,
            'bank_name' => 'Bulk Bank',
            'bank_account_name' => 'Bulk Owner',
            'bank_account_number' => '250250',
        ]);
    }

    public function test_invoice_penarikan_menggunakan_snapshot_rekening(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $tenant = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);
        $penarikan = $this->makePenarikan($tenant, [
            'bank_name' => 'Bank Invoice Snapshot',
            'bank_account_name' => 'Invoice Owner',
            'bank_account_number' => '303030',
            'status' => Penarikan::STATUS_SUCCESS,
            'approved_at' => now(),
        ]);

        $this->makeBank($admin, [
            'bank' => 'Bank Admin',
            'nama' => 'Admin Owner',
            'norek' => '404040',
        ]);
        $this->makeBank($tenant, [
            'bank' => 'Bank Aktif Tenant',
            'nama' => 'Tenant Aktif',
            'norek' => '505050',
        ]);

        $this->actingAs($admin);

        $view = app(Controller::class)->invoice($penarikan->uid);
        $bankPenyewa = $view->getData()['bankPenyewa'];

        $this->assertSame('Bank Invoice Snapshot', $bankPenyewa->bank);
        $this->assertSame('Invoice Owner', $bankPenyewa->nama);
        $this->assertSame('303030', $bankPenyewa->norek);
    }

    public function test_approve_existing_tetap_berjalan(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $tenant = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);
        $penarikan = $this->makePenarikan($tenant, [
            'status' => Penarikan::STATUS_PENDING,
            'bank_name' => 'Bank Snapshot',
            'bank_account_name' => 'Owner Snapshot',
            'bank_account_number' => '606060',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminPenarikanIndex::class)
            ->call('approve', $penarikan->uid);

        $penarikan->refresh();

        $this->assertSame(Penarikan::STATUS_SUCCESS, $penarikan->status);
        $this->assertNotNull($penarikan->approved_at);
        $this->assertSame('Bank Snapshot', $penarikan->bank_name);
        $this->assertSame('Owner Snapshot', $penarikan->bank_account_name);
        $this->assertSame('606060', $penarikan->bank_account_number);
    }

    private function createPenarikansTable(bool $withSnapshot): void
    {
        Schema::create('penarikans', function ($table) use ($withSnapshot) {
            $table->id();
            $table->string('uid');
            $table->string('uid_user');
            $table->string('amount');
            $table->string('note')->nullable();
            $table->string('kwitansi');
            $table->string('status');
            $table->timestamp('approved_at')->nullable();
            if ($withSnapshot) {
                $table->string('bank_name')->nullable();
                $table->string('bank_account_name')->nullable();
                $table->string('bank_account_number')->nullable();
            }
            $table->timestamps();
            $table->softDeletes();
        });
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
            'gambar' => null,
            'password' => bcrypt('password'),
        ], $overrides));
    }

    private function makeBank(User $user, array $overrides = []): Bank
    {
        static $counter = 1;

        return Bank::create(array_merge([
            'uid' => $user->uid,
            'uid_user' => $user->uid,
            'nama' => 'Pemilik '.$counter,
            'bank' => 'Bank '.$counter,
            'norek' => '000'.$counter++,
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
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
        ], $overrides));
    }

    private function mockWithdrawalBalance(int $available): void
    {
        $this->mock(WithdrawalBalanceService::class, function ($mock) use ($available) {
            $mock->shouldReceive('grossEarningsFor')->andReturn($available);
            $mock->shouldReceive('deductedWithdrawalsFor')->andReturn(0);
            $mock->shouldReceive('availableBalanceFor')->andReturn($available);
        });
    }

    private function snapshotMigration(): object
    {
        return require database_path('migrations/2026_08_11_000001_add_bank_snapshot_to_penarikans_table.php');
    }
}
