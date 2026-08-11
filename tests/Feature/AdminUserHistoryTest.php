<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserIndex;
use App\Models\Cash;
use App\Models\Cart;
use App\Models\Event;
use App\Models\HargaCart;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserHistoryTest extends TestCase
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
            $table->string('birthday')->nullable();
            $table->string('gender')->nullable();
            $table->string('kota')->nullable();
            $table->string('alamat')->nullable();
            $table->string('role');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('event');
            $table->string('alamat')->nullable();
            $table->string('tanggal')->nullable();
            $table->string('status')->nullable();
            $table->text('deskripsi')->nullable();
            $table->text('map')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('invoice')->nullable();
            $table->string('status');
            $table->string('konfirmasi')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('kategori_harga');
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedBigInteger('harga_ticket')->default(0);
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
    }

    public function test_history_maksimal_20_transaksi_dan_terbaru_lebih_dulu(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $user = $this->makeUser(['uid' => 'buyer-uid', 'role' => 'user']);
        $event = $this->makeEvent();

        for ($i = 1; $i <= 25; $i++) {
            $cart = $this->makeCart($user, $event, [
                'uid' => 'cart-'.$i,
                'invoice' => 'INV-'.$i,
                'created_at' => now()->subMinutes(25 - $i),
                'updated_at' => now()->subMinutes(25 - $i),
            ]);
            $this->makeHargaCart($cart);
        }

        $component = Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('openHistory', $user->id);

        $historyItems = $component->get('historyItems');

        $this->assertCount(20, $historyItems);
        $this->assertSame('INV-25', $historyItems->first()->invoice);
        $this->assertSame('INV-6', $historyItems->last()->invoice);

        $component->assertSee('INV-25')
            ->assertSee('INV-6')
            ->assertDontSee('INV-5');
    }

    public function test_history_menampilkan_status_verifikasi(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $user = $this->makeUser(['uid' => 'buyer-uid', 'role' => 'user']);
        $event = $this->makeEvent();

        $scanned = $this->makeCart($user, $event, [
            'uid' => 'cart-scanned',
            'invoice' => 'INV-SCANNED',
            'scanned_at' => now(),
            'created_at' => now()->subMinutes(3),
            'updated_at' => now()->subMinutes(3),
        ]);
        $confirmed = $this->makeCart($user, $event, [
            'uid' => 'cart-confirmed',
            'invoice' => 'INV-CONFIRMED',
            'konfirmasi' => '1',
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);
        $unverified = $this->makeCart($user, $event, [
            'uid' => 'cart-unverified',
            'invoice' => 'INV-UNVERIFIED',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        foreach ([$scanned, $confirmed, $unverified] as $cart) {
            $this->makeHargaCart($cart);
        }

        $component = Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('openHistory', $user->id);

        $component->assertSee('INV-SCANNED')
            ->assertSee('INV-CONFIRMED')
            ->assertSee('INV-UNVERIFIED')
            ->assertSee('Terverifikasi')
            ->assertSee('Belum Diverifikasi');

        $html = $component->html();

        $this->assertSame(2, substr_count($html, 'Terverifikasi'));
        $this->assertSame(1, substr_count($html, 'Belum Diverifikasi'));
    }

    public function test_history_cashes_tetap_menampilkan_seluruh_transaksi_berdasarkan_email(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $user = $this->makeUser(['uid' => 'buyer-uid', 'role' => 'user']);
        $event = $this->makeEvent();
        $email = 'cash@example.test';
        $firstCashId = null;

        for ($i = 1; $i <= 25; $i++) {
            $cash = Cash::create([
                'uid' => 'cash-cart-'.$i,
                'name' => 'Cash Buyer',
                'email' => $email,
                'nomor' => '08123456789',
            ]);

            $firstCashId ??= $cash->id;

            $cart = $this->makeCart($user, $event, [
                'uid' => $cash->uid,
                'invoice' => 'CASH-INV-'.$i,
                'created_at' => now()->subMinutes(25 - $i),
                'updated_at' => now()->subMinutes(25 - $i),
            ]);
            $this->makeHargaCart($cart);
        }

        $component = Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->set('activeTab', 'cashes')
            ->call('openHistory', $firstCashId);

        $historyItems = $component->get('historyItems');

        $this->assertCount(25, $historyItems);
        $this->assertSame('CASH-INV-25', $historyItems->first()->invoice);
        $this->assertSame('CASH-INV-1', $historyItems->last()->invoice);

        $component->assertSee('CASH-INV-25')
            ->assertSee('CASH-INV-1');
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
            'role' => 'user',
            'password' => bcrypt('password'),
        ], $overrides));
    }

    private function makeEvent(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'uid' => 'event-uid',
            'user_uid' => 'owner-uid',
            'event' => 'Event Test',
            'alamat' => 'Venue',
            'tanggal' => '2026-08-01 19:00',
            'status' => 'active',
            'deskripsi' => 'Deskripsi',
            'map' => null,
        ], $overrides));
    }

    private function makeCart(User $user, Event $event, array $overrides = []): Cart
    {
        static $counter = 1;
        $current = $counter++;

        $createdAt = $overrides['created_at'] ?? now();
        $updatedAt = $overrides['updated_at'] ?? $createdAt;
        unset($overrides['created_at'], $overrides['updated_at']);

        $cart = Cart::create(array_merge([
            'uid' => 'cart-uid-'.$current,
            'user_uid' => $user->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.$current,
            'status' => 'SUCCESS',
            'konfirmasi' => null,
            'scanned_at' => null,
        ], $overrides));

        $cart->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ])->save();

        return $cart;
    }

    private function makeHargaCart(Cart $cart): HargaCart
    {
        return HargaCart::create([
            'uid' => $cart->uid,
            'kategori_harga' => 'Regular',
            'quantity' => 2,
            'harga_ticket' => 50000,
        ]);
    }
}
