<?php

namespace Tests\Feature;

use App\Console\Commands\ResendGateTickets;
use App\Jobs\sendEmailETransaksi;
use App\Jobs\sendEmailTrnsaksi;
use App\Mail\MidtransPaymentNotification;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\User;
use App\Services\Tickets\GateTokenService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketEmailDeliveryRoutingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');
        Config::set('gate-tokens.key', 'base64:'.base64_encode(str_repeat('g', 32)));
        Config::set('gate-tokens.active_event_uids', []);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
    }

    public function test_snapshot_email_alternative_is_used_for_ticket_delivery(): void
    {
        Mail::fake();

        $buyer = $this->user([
            'name' => 'Purchaser Name',
            'email' => 'buyer@example.test',
        ]);
        $event = $this->event();
        $cart = $this->onlineCart($buyer, $event, [
            'ticket_holder_name' => 'Friend Holder',
            'ticket_recipient_email' => 'friend@example.test',
        ]);
        $originalName = $buyer->name;
        $originalEmail = $buyer->email;

        (new sendEmailETransaksi($buyer, $cart))->handle();

        Mail::assertSent(MidtransPaymentNotification::class, function (MidtransPaymentNotification $mail) {
            return $mail->hasTo('friend@example.test')
                && ! $mail->hasTo('buyer@example.test')
                && str_contains($mail->render(), 'Hi, Friend Holder');
        });

        $this->assertSame($originalName, $buyer->fresh()->name);
        $this->assertSame($originalEmail, $buyer->fresh()->email);
    }

    public function test_snapshot_email_same_as_account_still_delivers_correctly(): void
    {
        Mail::fake();

        $buyer = $this->user([
            'name' => 'Same Email Buyer',
            'email' => 'same@example.test',
        ]);
        $event = $this->event();
        $cart = $this->onlineCart($buyer, $event, [
            'ticket_holder_name' => 'Same Email Holder',
            'ticket_recipient_email' => 'same@example.test',
        ]);

        (new sendEmailETransaksi($buyer, $cart))->handle();

        Mail::assertSent(MidtransPaymentNotification::class, function (MidtransPaymentNotification $mail) {
            return $mail->hasTo('same@example.test')
                && str_contains($mail->render(), 'Hi, Same Email Holder');
        });
    }

    public function test_legacy_cart_without_snapshot_falls_back_to_purchaser_email(): void
    {
        Mail::fake();

        $buyer = $this->user([
            'name' => 'Legacy Purchaser',
            'email' => 'legacy@example.test',
        ]);
        $event = $this->event();
        $cart = $this->onlineCart($buyer, $event, [
            'ticket_holder_name' => null,
            'ticket_recipient_email' => null,
        ]);

        (new sendEmailETransaksi($buyer, $cart))->handle();

        Mail::assertSent(MidtransPaymentNotification::class, function (MidtransPaymentNotification $mail) {
            return $mail->hasTo('legacy@example.test')
                && str_contains($mail->render(), 'Hi, Legacy Purchaser');
        });
    }

    public function test_invalid_snapshot_email_does_not_fallback_silently_to_purchaser(): void
    {
        Mail::fake();

        $buyer = $this->user([
            'name' => 'Fallback Buyer',
            'email' => 'fallback@example.test',
        ]);
        $event = $this->event();
        $cart = $this->onlineCart($buyer, $event, [
            'ticket_holder_name' => 'Broken Recipient',
            'ticket_recipient_email' => 'invalid-email',
        ]);
        $originalName = $buyer->name;
        $originalEmail = $buyer->email;

        (new sendEmailETransaksi($buyer, $cart))->handle();

        Mail::assertNothingSent();
        $this->assertSame($originalName, $buyer->fresh()->name);
        $this->assertSame($originalEmail, $buyer->fresh()->email);
    }

    public function test_resend_online_uses_snapshot_recipient_even_if_purchaser_email_is_invalid(): void
    {
        Queue::fake();

        $buyer = $this->user([
            'name' => 'Invalid Purchaser Email',
            'email' => 'not-an-email',
        ]);
        $event = $this->event(['event' => 'Resend Snapshot Event']);
        Config::set('gate-tokens.active_event_uids', [$event->uid]);
        $cart = $this->onlineCart($buyer, $event, [
            'ticket_holder_name' => 'Snapshot Holder',
            'ticket_recipient_email' => 'snapshot@example.test',
        ]);

        $this->artisan(ResendGateTickets::class, [
            'event_uid' => $event->uid,
            '--execute' => true,
        ])
            ->expectsConfirmation("KIRIM ULANG 1 tiket untuk {$event->event} ({$event->uid})?", 'yes')
            ->assertExitCode(0);

        Queue::assertPushed(sendEmailETransaksi::class, function (sendEmailETransaksi $job) use ($buyer, $cart) {
            return $job->userUid === $buyer->uid
                && $job->cartUid === $cart->uid
                && $job->isResend === true;
        });
    }

    public function test_resend_legacy_cart_falls_back_to_purchaser(): void
    {
        Queue::fake();

        $buyer = $this->user([
            'name' => 'Legacy Resend Buyer',
            'email' => 'legacy-resend@example.test',
        ]);
        $event = $this->event(['event' => 'Legacy Resend Event']);
        Config::set('gate-tokens.active_event_uids', [$event->uid]);
        $cart = $this->onlineCart($buyer, $event, [
            'ticket_holder_name' => null,
            'ticket_recipient_email' => null,
        ]);

        $this->artisan(ResendGateTickets::class, [
            'event_uid' => $event->uid,
            '--execute' => true,
        ])
            ->expectsConfirmation("KIRIM ULANG 1 tiket untuk {$event->event} ({$event->uid})?", 'yes')
            ->assertExitCode(0);

        Queue::assertPushed(sendEmailETransaksi::class, function (sendEmailETransaksi $job) use ($buyer, $cart) {
            return $job->userUid === $buyer->uid
                && $job->cartUid === $cart->uid
                && $job->isResend === true;
        });
    }

    public function test_resend_skips_online_cart_with_invalid_snapshot_recipient(): void
    {
        Queue::fake();

        $buyer = $this->user([
            'name' => 'Skip Invalid Snapshot Buyer',
            'email' => 'valid-buyer@example.test',
        ]);
        $event = $this->event(['event' => 'Invalid Snapshot Event']);
        Config::set('gate-tokens.active_event_uids', [$event->uid]);
        $this->onlineCart($buyer, $event, [
            'ticket_holder_name' => 'Invalid Snapshot Holder',
            'ticket_recipient_email' => 'broken-email',
        ]);

        $this->artisan(ResendGateTickets::class, [
            'event_uid' => $event->uid,
            '--execute' => true,
        ])
            ->expectsConfirmation("KIRIM ULANG 0 tiket untuk {$event->event} ({$event->uid})?", 'yes')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_resend_cash_ticket_keeps_using_cash_buyer(): void
    {
        Queue::fake();

        $buyer = $this->user([
            'name' => 'Cash Purchaser',
            'email' => 'cash-purchaser@example.test',
        ]);
        $event = $this->event(['event' => 'Cash Resend Event']);
        Config::set('gate-tokens.active_event_uids', [$event->uid]);
        $cart = $this->cashCart($buyer, $event);
        $cashBuyer = $this->cashBuyer($cart, [
            'name' => 'Cash Recipient',
            'email' => 'cash-recipient@example.test',
        ]);

        $this->artisan(ResendGateTickets::class, [
            'event_uid' => $event->uid,
            '--execute' => true,
        ])
            ->expectsConfirmation("KIRIM ULANG 1 tiket untuk {$event->event} ({$event->uid})?", 'yes')
            ->assertExitCode(0);

        Queue::assertPushed(sendEmailTrnsaksi::class, function (sendEmailTrnsaksi $job) use ($cashBuyer, $cart) {
            return $job->recipientEmail === $cashBuyer->email
                && $job->recipientName === $cashBuyer->name
                && $job->isResend === true
                && $job->cartUid === $cart->uid;
        });
    }

    public function test_resend_does_not_requeue_scanned_or_verified_tickets(): void
    {
        Queue::fake();

        $buyer = $this->user([
            'name' => 'Scanned Buyer',
            'email' => 'scanned@example.test',
        ]);
        $event = $this->event(['event' => 'Scanned Skip Event']);
        Config::set('gate-tokens.active_event_uids', [$event->uid]);
        $this->onlineCart($buyer, $event, [
            'ticket_holder_name' => 'Scanned Holder',
            'ticket_recipient_email' => 'scanned-holder@example.test',
            'scanned_at' => now(),
        ]);
        $this->onlineCart($buyer, $event, [
            'ticket_holder_name' => 'Verified Holder',
            'ticket_recipient_email' => 'verified-holder@example.test',
            'konfirmasi' => '1',
        ]);

        $this->artisan(ResendGateTickets::class, [
            'event_uid' => $event->uid,
            '--execute' => true,
        ])
            ->expectsConfirmation("KIRIM ULANG 0 tiket untuk {$event->event} ({$event->uid})?", 'yes')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
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
            $table->string('ticket_holder_name')->nullable();
            $table->string('ticket_recipient_email')->nullable();
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

        Schema::create('cashes', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('uid_partner')->nullable();
            $table->string('uid_user')->nullable();
            $table->string('uid_event')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('nomor')->nullable();
            $table->string('alamat')->nullable();
            $table->string('lahir')->nullable();
            $table->string('gender')->nullable();
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

    private function cashCart(User $buyer, Event $event, array $attributes = []): Cart
    {
        $cart = Cart::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $buyer->uid,
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

    private function cashBuyer(Cart $cart, array $attributes = []): Cash
    {
        return Cash::create(array_merge([
            'uid' => $cart->uid,
            'uid_partner' => 'partner',
            'uid_user' => $cart->user_uid,
            'uid_event' => $cart->event_uid,
            'name' => 'Cash Buyer',
            'email' => 'cash@example.test',
        ], $attributes));
    }
}
