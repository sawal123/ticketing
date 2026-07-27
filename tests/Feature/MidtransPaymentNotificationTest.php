<?php

namespace Tests\Feature;

use App\Jobs\sendEmailETransaksi;
use App\Mail\CashNotifikasiMail;
use App\Mail\MidtransPaymentNotification;
use App\Models\Cart;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MidtransPaymentNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
    }

    public function test_midtrans_payment_notification_email_renders_ticket_url_from_cart_invoice_and_recipient(): void
    {
        Mail::fake();

        $buyer = $this->user([
            'name' => 'Online Ticket Buyer',
            'email' => 'online-ticket@example.test',
        ]);
        $event = $this->event(['event' => 'Midtrans Render Event']);
        $cart = $this->onlineCart($buyer, $event, [
            'invoice' => 'INV-CART-123',
        ]);

        (new sendEmailETransaksi($buyer, $cart, 'BARCODE-PARAM-999'))->handle();

        Mail::assertSent(MidtransPaymentNotification::class, function (MidtransPaymentNotification $mail) use ($cart) {
            $html = $mail->render();

            $this->assertTrue($mail->hasTo('online-ticket@example.test'));
            $this->assertStringContainsString('/generate-barcode/'.$cart->invoice, $html);
            $this->assertStringContainsString('/generate-barcode/'.$cart->invoice, $mail->ticketUrl);
            $this->assertStringNotContainsString('/generate-barcode/BARCODE-PARAM-999', $html);
            $this->assertStringContainsString('INV-CART-123', $html);
            $this->assertStringContainsString('Midtrans Render Event', $html);

            return true;
        });
    }

    public function test_cash_payment_notification_email_keeps_signed_cash_ticket_url(): void
    {
        $operator = $this->user(['role' => 'penyewa']);
        $event = $this->event([
            'user_uid' => $operator->uid,
            'event' => 'Cash Signed Event',
        ]);
        $cart = $this->cashCart($operator, $event);

        $html = (new CashNotifikasiMail('Cash Ticket Buyer', $cart, $cart->invoice))->render();

        $this->assertStringContainsString('/cash-ticket/'.$cart->uid, $html);
        $this->assertStringContainsString('signature=', html_entity_decode($html, ENT_QUOTES));
        $this->assertStringNotContainsString('/generate-barcode/', $html);
        $this->assertStringContainsString($cart->invoice, $html);
        $this->assertStringContainsString('Cash Signed Event', $html);
    }

    private function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('parent_uid')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('nomor')->nullable();
            $table->string('birthday')->nullable();
            $table->string('alamat')->nullable();
            $table->string('kota')->nullable();
            $table->string('gender')->nullable();
            $table->string('gambar')->nullable();
            $table->string('role')->default('user');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('event');
            $table->string('alamat');
            $table->string('tanggal');
            $table->string('status');
            $table->integer('fee')->default(0);
            $table->string('cover')->nullable();
            $table->string('slug')->nullable();
            $table->string('konfirmasi')->nullable();
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
            $table->text('link')->nullable();
            $table->string('payment_type')->nullable();
            $table->integer('internet_fee')->default(0);
            $table->integer('pajak')->default(0);
            $table->integer('pajak_persen')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reservation_released_at')->nullable();
            $table->timestamp('payment_link_expires_at')->nullable();
            $table->unsignedBigInteger('gross_amount')->nullable();
            $table->unsignedBigInteger('payment_gateway_id')->nullable();
            $table->text('review_reason')->nullable();
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('midtrans_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function user(array $attributes = []): User
    {
        return User::create(array_merge([
            'uid' => 'user-'.Str::random(6),
            'name' => 'Test User',
            'email' => Str::random(6).'@example.test',
            'password' => bcrypt('password'),
            'role' => 'user',
        ], $attributes));
    }

    private function event(array $attributes = []): Event
    {
        return Event::create(array_merge([
            'uid' => 'event-'.Str::random(6),
            'user_uid' => 'owner',
            'event' => 'Demo Event',
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->toDateTimeString(),
            'status' => 'active',
            'fee' => 0,
            'cover' => 'cover.jpg',
            'slug' => 'demo',
            'konfirmasi' => '1',
            'deskripsi' => 'Demo',
            'map' => '-',
        ], $attributes));
    }

    private function onlineCart(User $buyer, Event $event, array $attributes = []): Cart
    {
        return Cart::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.Str::upper(Str::random(8)),
            'status' => Cart::STATUS_SUCCESS,
            'konfirmasi' => null,
            'payment_type' => 'bank_transfer',
            'gross_amount' => 92000,
            'paid_at' => now(),
        ], $attributes));
    }

    private function cashCart(User $operator, Event $event, array $attributes = []): Cart
    {
        return Cart::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $operator->uid,
            'event_uid' => $event->uid,
            'invoice' => 'CASH-'.Str::upper(Str::random(8)),
            'status' => Cart::STATUS_SUCCESS,
            'konfirmasi' => null,
            'payment_type' => 'cash',
            'gross_amount' => 92000,
            'paid_at' => now(),
        ], $attributes));
    }
}
