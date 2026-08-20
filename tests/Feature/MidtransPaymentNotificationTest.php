<?php

namespace Tests\Feature;

use App\Jobs\sendEmailETransaksi;
use App\Jobs\sendEmailTrnsaksi;
use App\Mail\CashNotifikasiMail;
use App\Mail\MidtransPaymentNotification;
use App\Models\Cart;
use App\Models\Event;
use App\Models\User;
use App\Services\Tickets\GateTokenService;
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
        Config::set('gate-tokens.key', 'base64:'.base64_encode(str_repeat('g', 32)));
        Config::set('services.midtrans.serverKey', 'test-server-key');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
    }

    public function test_midtrans_payment_notification_email_renders_secure_ticket_url_and_recipient(): void
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

        (new sendEmailETransaksi($buyer, $cart))->handle();

        Mail::assertSent(MidtransPaymentNotification::class, function (MidtransPaymentNotification $mail) use ($cart) {
            $html = $mail->render();

            $this->assertTrue($mail->hasTo('online-ticket@example.test'));
            $this->assertFalse($mail->isResend);
            $this->assertSame(
                'Barcode Verifikasi GOTIK - Midtrans Render Event',
                $mail->envelope()->subject,
            );
            $this->assertFalse($mail->content()->with['isResendTicket']);
            $this->assertStringContainsString('/ticket-access/'.$cart->uid, html_entity_decode($html, ENT_QUOTES));
            $this->assertStringContainsString('/ticket-access/'.$cart->uid, $mail->ticketUrl);
            $this->assertStringContainsString('expires=', $mail->ticketUrl);
            $this->assertStringContainsString('signature=', $mail->ticketUrl);
            $this->assertStringNotContainsString('/generate-barcode/', $mail->ticketUrl);
            $this->assertStringNotContainsString($cart->invoice, $mail->ticketUrl);
            $this->assertStringContainsString('INV-CART-123', $html);
            $this->assertStringContainsString('Midtrans Render Event', $html);
            $this->assertStringNotContainsString('Barcode lama tidak berlaku lagi', $html);

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

        $mail = new CashNotifikasiMail('Cash Ticket Buyer', $cart);
        $html = $mail->render();

        $this->assertFalse($mail->isResend);
        $this->assertSame('Barcode Verifikasi GOTIK - Cash Signed Event', $mail->envelope()->subject);
        $this->assertFalse($mail->content()->with['isResendTicket']);
        $this->assertStringContainsString('/cash-ticket/'.$cart->uid, $html);
        $this->assertStringContainsString('signature=', html_entity_decode($html, ENT_QUOTES));
        $this->assertStringNotContainsString('/generate-barcode/', $html);
        $this->assertStringContainsString($cart->invoice, $html);
        $this->assertStringContainsString('Cash Signed Event', $html);
        $this->assertStringNotContainsString('Barcode lama tidak berlaku lagi', $html);
    }

    public function test_resend_jobs_and_mailables_use_distinct_identity_subject_and_copy(): void
    {
        $buyer = $this->user([
            'name' => 'Resend Online Buyer',
            'email' => 'resend-online@example.test',
        ]);
        $event = $this->event(['event' => 'Purnama Resend Event']);
        $onlineCart = $this->onlineCart($buyer, $event);
        $normalJob = new sendEmailETransaksi($buyer, $onlineCart);
        $resendJob = new sendEmailETransaksi($buyer, $onlineCart, true);

        $this->assertFalse($normalJob->isResend);
        $this->assertSame('ticket-email:'.$onlineCart->uid, $normalJob->uniqueId());
        $this->assertTrue($resendJob->isResend);
        $this->assertSame('ticket-email-resend:'.$onlineCart->uid, $resendJob->uniqueId());

        $onlineMail = new MidtransPaymentNotification($buyer, $onlineCart, true);
        $onlineHtml = $onlineMail->render();
        $this->assertTrue($onlineMail->content()->with['isResendTicket']);
        $this->assertSame(
            'PENTING: Barcode Tiket Terbaru GOTIK - Purnama Resend Event',
            $onlineMail->envelope()->subject,
        );
        $this->assertStringContainsString('Barcode Tiket Terbaru', $onlineHtml);
        $this->assertStringContainsString('Barcode lama tidak berlaku lagi', $onlineHtml);
        $this->assertStringContainsString('Tunjukan Barcode', $onlineHtml);
        $this->assertStringContainsString($onlineCart->invoice, $onlineHtml);
        $this->assertStringContainsString(
            app(GateTokenService::class)->manualCodeForDisplay($onlineCart->fresh()),
            $onlineHtml,
        );
        $this->assertStringContainsString(
            'Kode manual ini hanya digunakan apabila barcode tidak dapat dipindai oleh panitia',
            $onlineHtml,
        );

        $cashCart = $this->cashCart($buyer, $event);
        $cashJob = new sendEmailTrnsaksi(
            'resend-cash@example.test',
            'Resend Cash Buyer',
            $cashCart->uid,
            true,
        );
        $this->assertTrue($cashJob->isResend);

        Mail::fake();
        $cashJob->handle();
        Mail::assertSent(CashNotifikasiMail::class, function (CashNotifikasiMail $mail) {
            $this->assertTrue($mail->isResend);
            $this->assertTrue($mail->content()->with['isResendTicket']);
            $this->assertStringContainsString('PENTING', $mail->envelope()->subject);
            $this->assertStringContainsString('Barcode lama tidak berlaku lagi', $mail->render());

            return true;
        });
    }

    public function test_midtrans_callback_accepts_qris_payment_type_without_changing_selected_gateway_id(): void
    {
        $buyer = $this->user();
        $event = $this->event();
        $cart = Cart::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-QRIS-CALLBACK',
            'status' => Cart::STATUS_PENDING,
            'payment_type' => 'qris',
            'payment_gateway_id' => 99,
            'gross_amount' => 92000,
            'expires_at' => now()->addMinutes(10),
        ]);

        DB::table('transactions')->insert([
            'uid' => $cart->uid,
            'user_uid' => $cart->user_uid,
            'event_uid' => $cart->event_uid,
            'amount' => '92000',
            'gross_amount' => 92000,
            'invoice' => $cart->invoice,
            'payment_type' => 'qris',
            'status_transaksi' => Cart::STATUS_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $grossAmount = '92000.00';

        $this->postJson('/api/callback', [
            'transaction_status' => 'pending',
            'payment_type' => 'qris',
            'fraud_status' => 'accept',
            'order_id' => $cart->invoice,
            'status_code' => '201',
            'gross_amount' => $grossAmount,
            'transaction_id' => 'midtrans-qris-callback',
            'signature_key' => hash('sha512', $cart->invoice.'201'.$grossAmount.'test-server-key'),
        ])->assertOk();

        $cart->refresh();

        $this->assertSame(99, $cart->payment_gateway_id);
        $this->assertSame('qris', $cart->payment_type);
        $this->assertSame(Cart::STATUS_PENDING, $cart->status);
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
            $table->char('gate_token_hash', 64)->nullable()->unique();
            $table->text('gate_token_encrypted')->nullable();
            $table->char('gate_manual_code_hash', 64)->nullable()->unique();
            $table->text('gate_manual_code_encrypted')->nullable();
            $table->timestamp('gate_token_issued_at')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->string('scanned_by')->nullable();
            $table->string('scan_device_id')->nullable();
            $table->unsignedInteger('gate_token_version')->default(1);
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

        Schema::create('transactions', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('amount');
            $table->unsignedBigInteger('gross_amount')->nullable();
            $table->string('invoice');
            $table->string('payment_type')->nullable();
            $table->string('status_transaksi');
            $table->timestamp('paid_at')->nullable();
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
        $cart = Cart::create(array_merge([
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

        app(GateTokenService::class)->issue($cart);

        return $cart;
    }

    private function cashCart(User $operator, Event $event, array $attributes = []): Cart
    {
        $cart = Cart::create(array_merge([
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

        app(GateTokenService::class)->issue($cart);

        return $cart;
    }
}
