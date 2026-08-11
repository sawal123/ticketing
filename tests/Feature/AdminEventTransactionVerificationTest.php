<?php

namespace Tests\Feature;

use App\Livewire\Admin\EventDetail;
use App\Models\Cart;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEventTransactionVerificationTest extends TestCase
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

        Schema::create('talent', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('talent');
            $table->string('gambar')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });

        Schema::create('hargas', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('kategori');
            $table->unsignedInteger('qty')->default(0);
            $table->unsignedInteger('sold_qty')->default(0);
            $table->unsignedInteger('reserved_qty')->default(0);
            $table->unsignedBigInteger('harga')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('invoice')->nullable();
            $table->string('status');
            $table->string('payment_type')->nullable();
            $table->unsignedBigInteger('internet_fee')->default(0);
            $table->unsignedBigInteger('pajak')->default(0);
            $table->unsignedInteger('pajak_persen')->default(0);
            $table->string('konfirmasi')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->unsignedBigInteger('harga_id')->nullable();
            $table->string('event_uid')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedBigInteger('harga_ticket')->default(0);
            $table->string('voucher')->nullable();
            $table->unsignedBigInteger('disc')->default(0);
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

    public function test_table_menampilkan_status_verifikasi_transaksi(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $buyer = $this->makeUser(['uid' => 'buyer-uid', 'role' => 'user']);
        $event = $this->makeEvent();

        $this->makeCart($buyer, $event, [
            'uid' => 'scanned-cart',
            'invoice' => 'INV-SCANNED',
            'scanned_at' => Carbon::parse('2026-08-11 10:11:12'),
        ]);
        $this->makeCart($buyer, $event, [
            'uid' => 'confirmed-cart',
            'invoice' => 'INV-CONFIRMED',
            'konfirmasi' => '1',
        ]);
        $this->makeCart($buyer, $event, [
            'uid' => 'unverified-cart',
            'invoice' => 'INV-UNVERIFIED',
        ]);

        $component = Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'transaksi')
            ->assertSee('Verifikasi')
            ->assertSee('INV-SCANNED')
            ->assertSee('INV-CONFIRMED')
            ->assertSee('INV-UNVERIFIED')
            ->assertSee('Terverifikasi')
            ->assertSee('Belum Diverifikasi');

        $html = $component->html();

        $this->assertSame(2, substr_count($html, 'Terverifikasi'));
        $this->assertSame(1, substr_count($html, 'Belum Diverifikasi'));
    }

    public function test_modal_menampilkan_tanggal_dan_waktu_dari_scanned_at(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $buyer = $this->makeUser(['uid' => 'buyer-uid', 'role' => 'user']);
        $event = $this->makeEvent();
        $cart = $this->makeCart($buyer, $event, [
            'uid' => 'scanned-cart',
            'invoice' => 'INV-SCANNED',
            'scanned_at' => Carbon::parse('2026-08-11 10:11:12'),
        ]);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('showTransactionDetail', $cart->uid)
            ->assertSee('Status Verifikasi')
            ->assertSee('Terverifikasi')
            ->assertSee('11 Aug 2026')
            ->assertSee('10:11:12');
    }

    public function test_modal_legacy_konfirmasi_tanpa_scanned_at_tidak_menampilkan_waktu_palsu(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $buyer = $this->makeUser(['uid' => 'buyer-uid', 'role' => 'user']);
        $event = $this->makeEvent();
        $cart = $this->makeCart($buyer, $event, [
            'uid' => 'legacy-confirmed-cart',
            'invoice' => 'INV-LEGACY',
            'konfirmasi' => '1',
            'scanned_at' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('showTransactionDetail', $cart->uid)
            ->assertSee('Terverifikasi')
            ->assertSee('Waktu verifikasi tidak tersedia');
    }

    public function test_transaksi_event_lain_tidak_boleh_terbaca(): void
    {
        $admin = $this->makeUser(['uid' => 'admin-uid', 'role' => 'admin']);
        $buyer = $this->makeUser(['uid' => 'buyer-uid', 'role' => 'user']);
        $event = $this->makeEvent(['uid' => 'target-event', 'event' => 'Target Event']);
        $otherEvent = $this->makeEvent(['uid' => 'other-event', 'event' => 'Other Event']);
        $otherCart = $this->makeCart($buyer, $otherEvent, [
            'uid' => 'other-cart',
            'invoice' => 'INV-OTHER',
            'scanned_at' => Carbon::parse('2026-08-11 12:00:00'),
        ]);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'transaksi')
            ->assertDontSee('INV-OTHER')
            ->call('showTransactionDetail', $otherCart->uid)
            ->assertSet('selectedTransactionId', null)
            ->assertSee('Transaksi tidak ditemukan pada event ini.');
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
        ], $overrides));
    }

    private function makeEvent(array $overrides = []): Event
    {
        static $counter = 1;
        $current = $counter++;

        return Event::create(array_merge([
            'category_id' => null,
            'uid' => 'event-'.$current,
            'user_uid' => 'owner-uid',
            'event' => 'Event '.$current,
            'alamat' => 'Venue',
            'tanggal' => '2026-08-01 19:00',
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Deskripsi',
            'map' => null,
            'pajak' => 0,
            'start_sale' => '2026-07-01 10:00',
            'slug' => 'event-'.$current,
            'konfirmasi' => '1',
        ], $overrides));
    }

    private function makeCart(User $buyer, Event $event, array $overrides = []): Cart
    {
        static $counter = 1;
        $current = $counter++;

        $cart = Cart::create(array_merge([
            'uid' => 'cart-'.$current,
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.$current,
            'status' => Cart::STATUS_SUCCESS,
            'payment_type' => 'bank_transfer',
            'internet_fee' => 0,
            'pajak' => 0,
            'pajak_persen' => 0,
            'konfirmasi' => null,
            'scanned_at' => null,
        ], $overrides));

        $harga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Regular '.$current,
            'qty' => 100,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => 50000,
            'status' => 'active',
        ]);

        HargaCart::create([
            'uid' => $cart->uid,
            'harga_id' => $harga->id,
            'event_uid' => $event->uid,
            'quantity' => 1,
            'harga_ticket' => 50000,
            'disc' => 0,
        ]);

        return $cart;
    }
}
