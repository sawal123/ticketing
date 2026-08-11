<?php

namespace Tests\Feature;

use App\Livewire\Admin\PenyewaDetail;
use App\Livewire\Admin\UserIndex;
use App\Models\Bank;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPenyewaDetailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Cache::flush();

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('name');
            $table->string('email');
            $table->string('nomor')->nullable();
            $table->string('birthday')->nullable();
            $table->string('gender')->nullable();
            $table->string('kota')->nullable();
            $table->string('alamat')->nullable();
            $table->string('gambar')->nullable();
            $table->string('role');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->string('category_id')->nullable();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event');
            $table->string('alamat');
            $table->string('tanggal');
            $table->string('status');
            $table->string('cover')->nullable();
            $table->unsignedBigInteger('fee')->default(0);
            $table->text('deskripsi');
            $table->text('map')->nullable();
            $table->unsignedBigInteger('pajak')->default(0);
            $table->string('start_sale')->nullable();
            $table->string('slug')->nullable();
            $table->string('konfirmasi')->nullable();
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

        Schema::create('cashes', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('name');
            $table->string('email');
            $table->string('nomor')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('landings', function ($table) {
            $table->id();
            $table->string('logo')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('contacts', function ($table) {
            $table->id();
            $table->string('icon')->nullable();
            $table->timestamps();
        });
    }

    public function test_admin_dapat_membuka_detail_penyewa(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $penyewa = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa', 'name' => 'Organizer A']);

        $this->actingAs($admin)
            ->get('/admin/user/penyewa/'.$penyewa->uid)
            ->assertOk()
            ->assertSee('Detail Penyewa')
            ->assertSee('Organizer A');
    }

    public function test_non_admin_ditolak(): void
    {
        $user = $this->makeUser(['uid' => 'user-uid', 'role' => 'user']);
        $penyewa = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);

        $this->actingAs($user)
            ->get('/admin/user/penyewa/'.$penyewa->uid)
            ->assertRedirect('/');
    }

    public function test_user_non_penyewa_404(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $user = $this->makeUser(['uid' => 'buyer-uid', 'role' => 'user']);

        $this->actingAs($admin)
            ->get('/admin/user/penyewa/'.$user->uid)
            ->assertNotFound();
    }

    public function test_event_hanya_milik_penyewa_target(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $target = $this->makeUser(['uid' => 'target-uid', 'role' => 'penyewa']);
        $other = $this->makeUser(['uid' => 'other-uid', 'role' => 'penyewa']);

        $this->makeEvent(['uid' => 'target-event', 'user_uid' => $target->uid, 'event' => 'Event Target']);
        $this->makeEvent(['uid' => 'other-event', 'user_uid' => $other->uid, 'event' => 'Event Lain']);

        Livewire::actingAs($admin)
            ->test(PenyewaDetail::class, ['uid' => $target->uid])
            ->assertSee('Event Target')
            ->assertDontSee('Event Lain');
    }

    public function test_rekening_uid_user_tampil(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $penyewa = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);

        Bank::create([
            'uid' => 'bank-uid',
            'uid_user' => $penyewa->uid,
            'nama' => 'Pemilik Rekening',
            'bank' => 'BCA',
            'norek' => '123456',
        ]);

        Livewire::actingAs($admin)
            ->test(PenyewaDetail::class, ['uid' => $penyewa->uid])
            ->assertSee('BCA')
            ->assertSee('123456')
            ->assertSee('Pemilik Rekening');
    }

    public function test_rekening_legacy_uid_tampil(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $penyewa = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);

        Bank::create([
            'uid' => $penyewa->uid,
            'uid_user' => 'legacy-other-value',
            'nama' => 'Legacy Owner',
            'bank' => 'Mandiri',
            'norek' => '987654',
        ]);

        Livewire::actingAs($admin)
            ->test(PenyewaDetail::class, ['uid' => $penyewa->uid])
            ->assertSee('Mandiri')
            ->assertSee('987654')
            ->assertSee('Legacy Owner');
    }

    public function test_penyewa_dengan_event_tidak_dapat_dihapus(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $penyewa = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);
        $this->makeEvent(['user_uid' => $penyewa->uid]);

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->set('activeTab', 'penyewa')
            ->set('deletingId', $penyewa->id)
            ->call('delete')
            ->assertSee('Penyewa tidak dapat dihapus karena masih memiliki event.');

        $this->assertNotSoftDeleted('users', ['id' => $penyewa->id]);
    }

    public function test_penyewa_dengan_event_tidak_dapat_diubah_role(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $penyewa = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);
        $this->makeEvent(['user_uid' => $penyewa->uid]);

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->set('activeTab', 'penyewa')
            ->set('editingId', $penyewa->id)
            ->set('name', $penyewa->name)
            ->set('email', $penyewa->email)
            ->set('nomor', $penyewa->nomor)
            ->set('role', 'user')
            ->call('save')
            ->assertHasErrors(['role']);

        $this->assertDatabaseHas('users', [
            'id' => $penyewa->id,
            'role' => 'penyewa',
        ]);
    }

    public function test_penyewa_tanpa_event_tetap_mengikuti_behavior_existing(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $penyewa = $this->makeUser(['uid' => 'tenant-uid', 'role' => 'penyewa']);
        $deletable = $this->makeUser(['uid' => 'delete-uid', 'role' => 'penyewa', 'email' => 'delete@example.test']);

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->set('activeTab', 'penyewa')
            ->set('editingId', $penyewa->id)
            ->set('name', 'Updated Organizer')
            ->set('email', $penyewa->email)
            ->set('nomor', $penyewa->nomor)
            ->set('role', 'user')
            ->call('save')
            ->assertSee('Data berhasil disimpan.');

        $this->assertDatabaseHas('users', [
            'id' => $penyewa->id,
            'name' => 'Updated Organizer',
            'role' => 'user',
        ]);

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->set('activeTab', 'penyewa')
            ->set('deletingId', $deletable->id)
            ->call('delete')
            ->assertSee('Data berhasil dihapus.');

        $this->assertSoftDeleted('users', ['id' => $deletable->id]);
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
            'birthday' => '2000-01-01',
            'gender' => 'pria',
            'kota' => 'Jakarta',
            'alamat' => 'Alamat '.$current,
            'gambar' => null,
            'role' => 'user',
            'password' => bcrypt('password'),
        ], $overrides));
    }

    private function makeEvent(array $overrides = []): Event
    {
        static $counter = 1;

        return Event::create(array_merge([
            'category_id' => null,
            'uid' => 'event-'.$counter++,
            'user_uid' => 'tenant-uid',
            'event' => 'Event Test',
            'alamat' => 'Venue',
            'tanggal' => '2026-08-01 19:00',
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Deskripsi',
            'map' => null,
            'pajak' => 0,
            'start_sale' => '2026-07-01 10:00',
            'slug' => 'event-test',
            'konfirmasi' => '1',
        ], $overrides));
    }
}
