<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Jobs\sendEmailETransaksi;
use App\Jobs\sendEmailTrnsaksi;
use App\Livewire\Dashboard\DemoIndex;
use App\Livewire\Dashboard\EventDetail as DashboardEventDetail;
use App\Mail\CashNotifikasiMail;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardCashCheckoutTest extends TestCase
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
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
    }

    public function test_cash_checkout_creates_success_transaction_and_reduces_remaining_stock(): void
    {
        Queue::fake();

        $owner = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $owner->uid, 'fee' => 0]);
        $harga = $this->harga($event, ['qty' => 10, 'harga' => 92000]);

        Livewire::actingAs($owner)
            ->test(DemoIndex::class)
            ->call('selectEvent', $event->uid)
            ->assertSet('availableTickets.0.remaining_stock', 10)
            ->call('addTicket', $harga->id)
            ->assertSet('availableTickets.0.remaining_stock', 10)
            ->set('selectedTickets.0.qty', 2)
            ->set('buyerName', 'Pembeli Cash')
            ->set('buyerEmail', 'cash@example.test')
            ->set('buyerBirthday', '2000-01-01')
            ->set('buyerGender', 'pria')
            ->call('checkout')
            ->assertNoRedirect()
            ->assertDispatched('close-modal', fn ($event, $params) => ($params['name'] ?? null) === 'sell-modal')
            ->assertDispatched('open-modal', fn ($event, $params) => ($params['name'] ?? null) === 'cash-transaction-success-modal')
            ->assertSet('selectedTickets', [])
            ->assertSet('cashTransactionResult.buyer_name', 'Pembeli Cash')
            ->assertSet('cashTransactionResult.buyer_email', 'cash@example.test')
            ->assertSet('cashTransactionResult.quantity', 2)
            ->assertSet('cashTransactionResult.subtotal', 184000)
            ->assertSet('cashTransactionResult.total', 184000)
            ->assertSet('cashTransactionResult.payment_status', 'Lunas')
            ->assertSet('cashTransactionResult.attendance_status', 'Belum Hadir')
            ->assertSet('cashTransactionResult.email_status', 'scheduled');

        $cart = Cart::first();

        $this->assertNotNull($cart);
        $this->assertSame(Cart::STATUS_SUCCESS, $cart->status);
        $this->assertSame('cash', $cart->payment_type);
        $this->assertSame($owner->uid, $cart->user_uid);
        $this->assertNotSame('1', (string) $cart->konfirmasi);
        $this->assertDatabaseHas('harga_carts', [
            'uid' => $cart->uid,
            'harga_id' => $harga->id,
            'orderBy' => '1',
            'quantity' => 2,
            'harga_ticket' => 92000,
            'kategori_harga' => $harga->kategori,
        ]);
        $this->assertDatabaseHas('transactions', [
            'uid' => $cart->uid,
            'invoice' => $cart->invoice,
            'payment_type' => 'cash',
            'status_transaksi' => Cart::STATUS_SUCCESS,
        ]);
        $this->assertDatabaseHas('cashes', [
            'uid' => $cart->uid,
            'uid_user' => $owner->uid,
            'uid_event' => $event->uid,
            'name' => 'Pembeli Cash',
            'email' => 'cash@example.test',
        ]);
        $this->assertSame(2, (int) $harga->fresh()->sold_qty);
        $this->assertSame(8, $harga->fresh()->remainingQty());
        Queue::assertPushed(sendEmailTrnsaksi::class, function (sendEmailTrnsaksi $job) use ($cart) {
            return $job->recipientEmail === 'cash@example.test'
                && $job->recipientName === 'Pembeli Cash'
                && $job->cartUid === $cart->uid
                && $job->barcode === $cart->invoice;
        });
    }

    public function test_cash_checkout_with_direct_entry_marks_cart_as_present(): void
    {
        Queue::fake();

        $owner = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $owner->uid]);
        $harga = $this->harga($event, ['qty' => 10]);

        Livewire::actingAs($owner)
            ->test(DemoIndex::class)
            ->call('selectEvent', $event->uid)
            ->call('addTicket', $harga->id)
            ->set('buyerName', 'Pembeli Hadir')
            ->set('buyerEmail', 'hadir@example.test')
            ->set('buyerBirthday', '2000-01-01')
            ->set('buyerGender', 'wanita')
            ->set('isDirectEntry', true)
            ->call('checkout')
            ->assertNoRedirect()
            ->assertDispatched('open-modal', fn ($event, $params) => ($params['name'] ?? null) === 'cash-transaction-success-modal')
            ->assertSet('cashTransactionResult.attendance_status', 'Hadir')
            ->assertSet('cashTransactionResult.email_status', 'scheduled');

        $this->assertSame('1', Cart::first()->konfirmasi);
        Queue::assertPushed(sendEmailTrnsaksi::class, fn (sendEmailTrnsaksi $job) => $job->recipientEmail === 'hadir@example.test');
    }

    public function test_cash_checkout_rejects_quantity_that_exceeds_current_stock(): void
    {
        $owner = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $owner->uid]);
        $harga = $this->harga($event, ['qty' => 10]);

        Livewire::actingAs($owner)
            ->test(DemoIndex::class)
            ->set('selectedEventId', $event->uid)
            ->set('selectedTickets', [[
                'id' => $harga->id,
                'name' => $harga->kategori,
                'price' => $harga->harga,
                'qty' => 11,
                'max_qty' => 10,
            ]])
            ->set('buyerName', 'Pembeli Cash')
            ->set('buyerEmail', 'cash@example.test')
            ->set('buyerBirthday', '2000-01-01')
            ->set('buyerGender', 'pria')
            ->call('checkout')
            ->assertHasErrors('selectedTickets');

        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('harga_carts', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(0, (int) $harga->fresh()->sold_qty);
        $this->assertSame(10, $harga->fresh()->remainingQty());
    }

    public function test_cash_checkout_rolls_back_when_later_insert_fails(): void
    {
        Queue::fake();

        $owner = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $owner->uid]);
        $harga = $this->harga($event, ['qty' => 10]);

        Cash::creating(function () {
            throw new \RuntimeException('cash insert failed');
        });

        try {
            Livewire::actingAs($owner)
                ->test(DemoIndex::class)
                ->call('selectEvent', $event->uid)
                ->call('addTicket', $harga->id)
                ->set('selectedTickets.0.qty', 2)
                ->set('buyerName', 'Pembeli Cash')
                ->set('buyerEmail', 'cash@example.test')
                ->set('buyerBirthday', '2000-01-01')
                ->set('buyerGender', 'pria')
                ->call('checkout');
        } finally {
            Cash::flushEventListeners();
        }

        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('harga_carts', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('cashes', 0);
        $this->assertSame(0, (int) $harga->fresh()->sold_qty);
        $this->assertSame(10, $harga->fresh()->remainingQty());
        Queue::assertNotPushed(sendEmailTrnsaksi::class);
    }

    public function test_event_transaction_table_uses_cash_buyer_and_online_user(): void
    {
        $operator = $this->user(['role' => 'penyewa', 'name' => 'Seller Operator', 'email' => 'seller@example.test']);
        $onlineBuyer = $this->user(['role' => 'user', 'name' => 'Online Buyer', 'email' => 'online@example.test']);
        $event = $this->event(['user_uid' => $operator->uid]);
        $harga = $this->harga($event);

        $this->cashTransaction($operator, $event, $harga, [
            'name' => 'Cash Buyer',
            'email' => 'cashbuyer@example.test',
        ]);
        $this->onlineTransaction($onlineBuyer, $event, $harga);

        Livewire::actingAs($operator)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'transaksi')
            ->assertSee('Cash Buyer')
            ->assertSee('cashbuyer@example.test')
            ->assertSee('Online Buyer')
            ->assertSee('online@example.test')
            ->assertDontSee('seller@example.test');
    }

    public function test_event_transaction_table_shows_attendance_from_konfirmasi_only(): void
    {
        $operator = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $operator->uid]);
        $harga = $this->harga($event);

        $this->cashTransaction($operator, $event, $harga, ['name' => 'Not Present Buyer'], ['konfirmasi' => null]);
        $this->cashTransaction($operator, $event, $harga, ['name' => 'Present Buyer'], ['konfirmasi' => '1']);

        Livewire::actingAs($operator)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'transaksi')
            ->assertSee('Belum Hadir')
            ->assertSee('Hadir');
    }

    public function test_event_transaction_search_uses_cash_and_online_buyers(): void
    {
        $operator = $this->user(['role' => 'penyewa', 'name' => 'Seller Operator', 'email' => 'seller@example.test']);
        $onlineBuyer = $this->user(['role' => 'user', 'name' => 'Online Searchable', 'email' => 'online-search@example.test']);
        $event = $this->event(['user_uid' => $operator->uid]);
        $harga = $this->harga($event);

        $this->cashTransaction($operator, $event, $harga, [
            'name' => 'Cash Searchable',
            'email' => 'cash-search@example.test',
        ]);
        $this->onlineTransaction($onlineBuyer, $event, $harga);

        Livewire::actingAs($operator)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'transaksi')
            ->set('searchTransaction', 'cash-search@example.test')
            ->assertSee('Cash Searchable')
            ->assertDontSee('Online Searchable')
            ->set('searchTransaction', 'Online Searchable')
            ->assertSee('Online Searchable')
            ->assertDontSee('Cash Searchable')
            ->set('searchTransaction', 'seller@example.test')
            ->assertDontSee('Online Searchable')
            ->assertDontSee('Cash Searchable');
    }

    public function test_resend_email_uses_cash_buyer_or_online_user_recipient(): void
    {
        Queue::fake();

        $operator = $this->user(['role' => 'penyewa', 'email' => 'seller@example.test']);
        $onlineBuyer = $this->user(['role' => 'user', 'name' => 'Online Buyer', 'email' => 'online@example.test']);
        $event = $this->event(['user_uid' => $operator->uid]);
        $harga = $this->harga($event);
        $cashCart = $this->cashTransaction($operator, $event, $harga, [
            'name' => 'Cash Buyer',
            'email' => 'cashbuyer@example.test',
        ]);
        $onlineCart = $this->onlineTransaction($onlineBuyer, $event, $harga);

        Livewire::actingAs($operator)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('resendEmailUid', $cashCart->uid)
            ->call('resendEmail');

        Queue::assertPushed(sendEmailTrnsaksi::class, function (sendEmailTrnsaksi $job) use ($cashCart) {
            return $job->recipientEmail === 'cashbuyer@example.test'
                && $job->recipientName === 'Cash Buyer'
                && $job->cartUid === $cashCart->uid
                && $job->barcode === $cashCart->invoice;
        });

        Livewire::actingAs($operator)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('resendEmailUid', $onlineCart->uid)
            ->call('resendEmail');

        Queue::assertPushed(sendEmailETransaksi::class, function (sendEmailETransaksi $job) use ($onlineBuyer, $onlineCart) {
            return $job->user->is($onlineBuyer)
                && $job->carts->is($onlineCart)
                && $job->order_id === $onlineCart->invoice;
        });
    }

    public function test_dashboard_event_detail_rejects_transaction_detail_from_other_event(): void
    {
        $ownerA = $this->user(['role' => 'penyewa', 'name' => 'Owner A']);
        $ownerB = $this->user(['role' => 'penyewa', 'name' => 'Owner B']);
        $eventA = $this->event(['user_uid' => $ownerA->uid, 'event' => 'Event A']);
        $eventB = $this->event(['user_uid' => $ownerB->uid, 'event' => 'Event B']);
        $hargaB = $this->harga($eventB);
        $otherCart = $this->cashTransaction($ownerB, $eventB, $hargaB, [
            'name' => 'Other Event Buyer',
            'email' => 'other-event@example.test',
        ]);

        Livewire::actingAs($ownerA)
            ->test(DashboardEventDetail::class, ['uid' => $eventA->uid])
            ->call('showTransactionDetail', $otherCart->uid)
            ->assertSet('selectedTransactionId', null)
            ->assertNotDispatched('open-modal', fn ($event, $params) => ($params['name'] ?? null) === 'transaction-detail-modal')
            ->assertDontSee('Other Event Buyer');
    }

    public function test_dashboard_event_detail_rejects_resend_email_from_other_event(): void
    {
        Queue::fake();

        $ownerA = $this->user(['role' => 'penyewa']);
        $ownerB = $this->user(['role' => 'penyewa']);
        $eventA = $this->event(['user_uid' => $ownerA->uid, 'event' => 'Event A']);
        $eventB = $this->event(['user_uid' => $ownerB->uid, 'event' => 'Event B']);
        $hargaB = $this->harga($eventB);
        $otherCart = $this->cashTransaction($ownerB, $eventB, $hargaB, [
            'name' => 'Other Event Buyer',
            'email' => 'other-event@example.test',
        ]);

        Livewire::actingAs($ownerA)
            ->test(DashboardEventDetail::class, ['uid' => $eventA->uid])
            ->set('resendEmailUid', $otherCart->uid)
            ->call('resendEmail');

        Queue::assertNotPushed(sendEmailTrnsaksi::class);
        Queue::assertNotPushed(sendEmailETransaksi::class);
    }

    public function test_cash_checkout_success_modal_actions_reset_form_or_open_transaction_detail(): void
    {
        Queue::fake();

        $owner = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $owner->uid]);
        $harga = $this->harga($event, ['qty' => 10]);

        $component = Livewire::actingAs($owner)
            ->test(DemoIndex::class)
            ->call('selectEvent', $event->uid)
            ->call('addTicket', $harga->id)
            ->set('selectedTickets.0.qty', 2)
            ->set('buyerName', 'Pembeli Modal')
            ->set('buyerEmail', 'modal@example.test')
            ->set('buyerBirthday', '2000-01-01')
            ->set('buyerGender', 'wanita')
            ->call('checkout')
            ->assertNoRedirect()
            ->assertSet('cashTransactionResult.invoice', fn ($invoice) => filled($invoice));

        $invoice = $component->get('cashTransactionResult.invoice');

        $component
            ->call('viewLastCashTransaction')
            ->assertRedirectContains('activeTab=transaksi')
            ->assertRedirectContains('filterPayment=cash')
            ->assertRedirectContains('searchTransaction='.$invoice);

        Livewire::actingAs($owner)
            ->test(DemoIndex::class)
            ->call('selectEvent', $event->uid)
            ->call('addTicket', $harga->id)
            ->set('selectedTickets.0.qty', 1)
            ->set('buyerName', 'Pembeli Reset')
            ->set('buyerEmail', 'reset@example.test')
            ->set('buyerBirthday', '2000-01-01')
            ->set('buyerGender', 'pria')
            ->set('isDirectEntry', true)
            ->call('checkout')
            ->call('startAnotherCashTransaction')
            ->assertSet('selectedEventId', $event->uid)
            ->assertSet('selectedTickets', [])
            ->assertSet('buyerName', null)
            ->assertSet('buyerEmail', null)
            ->assertSet('isDirectEntry', false)
            ->assertSet('cashTransactionResult', [])
            ->assertSet('availableTickets.0.remaining_stock', 7)
            ->assertDispatched('open-modal', fn ($event, $params) => ($params['name'] ?? null) === 'sell-modal');
    }

    public function test_cash_checkout_keeps_committed_transaction_when_email_dispatch_fails(): void
    {
        $this->app->instance(BusDispatcher::class, new class implements BusDispatcher {
            public function dispatch($command)
            {
                throw new \RuntimeException('queue down');
            }

            public function dispatchSync($command, $handler = null)
            {
                return null;
            }

            public function dispatchNow($command, $handler = null)
            {
                return null;
            }

            public function hasCommandHandler($command)
            {
                return false;
            }

            public function getCommandHandler($command)
            {
                return false;
            }

            public function pipeThrough(array $pipes)
            {
                return $this;
            }

            public function map(array $map)
            {
                return $this;
            }
        });

        $owner = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $owner->uid]);
        $harga = $this->harga($event, ['qty' => 10]);

        Livewire::actingAs($owner)
            ->test(DemoIndex::class)
            ->call('selectEvent', $event->uid)
            ->call('addTicket', $harga->id)
            ->set('buyerName', 'Pembeli Email Gagal')
            ->set('buyerEmail', 'failed-email@example.test')
            ->set('buyerBirthday', '2000-01-01')
            ->set('buyerGender', 'pria')
            ->call('checkout')
            ->assertNoRedirect()
            ->assertDispatched('open-modal', fn ($event, $params) => ($params['name'] ?? null) === 'cash-transaction-success-modal')
            ->assertSet('cashTransactionResult.email_status', 'failed');

        $cart = Cart::first();

        $this->assertNotNull($cart);
        $this->assertSame(Cart::STATUS_SUCCESS, $cart->status);
        $this->assertDatabaseHas('cashes', [
            'uid' => $cart->uid,
            'email' => 'failed-email@example.test',
        ]);
        $this->assertDatabaseHas('transactions', [
            'uid' => $cart->uid,
            'status_transaksi' => Cart::STATUS_SUCCESS,
        ]);
        $this->assertSame(1, (int) $harga->fresh()->sold_qty);
    }

    public function test_dashboard_demographics_use_cash_buyer_identity_and_ignore_invalid_birthdates(): void
    {
        $operator = $this->user([
            'role' => 'penyewa',
            'gender' => 'pria',
            'birthday' => '1980-01-01',
        ]);
        $onlineBuyer = $this->user([
            'role' => 'user',
            'name' => 'Online Demographic',
            'gender' => 'pria',
            'birthday' => now()->subYears(20)->toDateString(),
        ]);
        $event = $this->event(['user_uid' => $operator->uid]);
        $harga = $this->harga($event);

        $this->cashTransaction($operator, $event, $harga, [
            'name' => 'Cash Demographic',
            'gender' => 'wanita',
            'lahir' => now()->subYears(30)->toDateString(),
        ]);
        $this->cashTransaction($operator, $event, $harga, [
            'name' => 'Cash Invalid Birthday',
            'gender' => 'wanita',
            'lahir' => 'not-a-date',
        ]);
        $this->onlineTransaction($onlineBuyer, $event, $harga);

        $gender = Livewire::actingAs($operator)
            ->test(DemoIndex::class)
            ->viewData('gender');

        $this->assertSame(1, $gender['pria']);
        $this->assertSame(2, $gender['wanita']);
        $this->assertSame(1, $gender['age_18_25']);
        $this->assertSame(1, $gender['age_gt_25']);
        $this->assertSame(0, $gender['age_lt_18']);
    }

    public function test_dashboard_ticket_total_is_not_duplicated_by_matching_vouchers(): void
    {
        $operator = $this->user(['role' => 'penyewa']);
        $buyer = $this->user(['role' => 'user']);
        $event = $this->event(['user_uid' => $operator->uid]);
        $harga = $this->harga($event);
        $cart = $this->onlineTransaction($buyer, $event, $harga);

        HargaCart::where('uid', $cart->uid)->update(['voucher' => 'PROMO']);

        Voucher::create([
            'uid' => 'voucher-1',
            'event_uid' => $event->uid,
            'code' => 'PROMO',
            'unit' => 'rupiah',
            'nominal' => 1000,
            'max_disc' => 0,
        ]);

        Voucher::create([
            'uid' => 'voucher-2',
            'event_uid' => $event->uid,
            'code' => 'PROMO',
            'unit' => 'rupiah',
            'nominal' => 1000,
            'max_disc' => 0,
        ]);

        $stats = Livewire::actingAs($operator)
            ->test(DemoIndex::class)
            ->viewData('stats');

        $this->assertSame(1, (int) $stats['tiket']);
        $this->assertSame(1, (int) $stats['transaksi']);
    }

    public function test_api_list_event_ticket_totals_ignore_soft_deleted_ticket_items(): void
    {
        $operator = $this->user(['role' => 'penyewa']);
        $buyer = $this->user(['role' => 'user']);
        $event = $this->event(['user_uid' => $operator->uid, 'event' => 'API Soft Delete Event']);
        $harga = $this->harga($event);
        $cart = $this->onlineTransaction($buyer, $event, $harga, [
            'konfirmasi' => '1',
        ]);

        $this->hargaCart($cart, $harga, 2)->delete();

        Sanctum::actingAs($operator);

        $response = $this->getJson('/api/listEvent')
            ->assertOk()
            ->json('data.0');

        $this->assertSame($event->uid, $response['uid']);
        $this->assertSame(1, (int) $response['tiket_terjual']);
        $this->assertSame(1, (int) $response['tiket_terverifikasi']);
    }

    public function test_cash_buyer_can_open_signed_ticket_url_without_login(): void
    {
        $operator = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $operator->uid, 'event' => 'Signed Cash Event']);
        $harga = $this->harga($event, ['kategori' => 'VIP']);
        $cart = $this->cashTransaction($operator, $event, $harga, [
            'name' => 'Signed Cash Buyer',
            'email' => 'signed-cash@example.test',
        ]);

        $this->assertGuest();

        $this->get($this->uriFromUrl($this->signedCashTicketUrl($cart)))
            ->assertOk()
            ->assertSee($cart->invoice)
            ->assertSee('Signed Cash Event')
            ->assertSee('Signed Cash Buyer')
            ->assertSee('VIP')
            ->assertSee('Link dan barcode ini bersifat rahasia');
    }

    public function test_cash_ticket_without_signature_is_rejected(): void
    {
        $operator = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $operator->uid]);
        $harga = $this->harga($event);
        $cart = $this->cashTransaction($operator, $event, $harga);

        $this->get(route('cash.ticket.show', ['uid' => $cart->uid], false))
            ->assertForbidden();
    }

    public function test_expired_cash_ticket_signature_is_rejected(): void
    {
        $operator = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $operator->uid]);
        $harga = $this->harga($event);
        $cart = $this->cashTransaction($operator, $event, $harga);

        $this->get($this->uriFromUrl($this->signedCashTicketUrl($cart, now()->subMinute())))
            ->assertForbidden();
    }

    public function test_online_cart_cannot_use_cash_ticket_route(): void
    {
        $operator = $this->user(['role' => 'penyewa']);
        $onlineBuyer = $this->user(['role' => 'user']);
        $event = $this->event(['user_uid' => $operator->uid]);
        $harga = $this->harga($event);
        $cart = $this->onlineTransaction($onlineBuyer, $event, $harga);

        $this->get($this->uriFromUrl($this->signedCashTicketUrl($cart)))
            ->assertNotFound();
    }

    public function test_cash_ticket_requires_success_status(): void
    {
        $operator = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $operator->uid]);
        $harga = $this->harga($event);
        $cart = $this->cashTransaction($operator, $event, $harga, [], [
            'status' => Cart::STATUS_UNPAID,
        ]);

        $this->get($this->uriFromUrl($this->signedCashTicketUrl($cart)))
            ->assertNotFound()
            ->assertDontSee($cart->invoice);
    }

    public function test_cash_ticket_uid_cannot_be_swapped_without_new_signature(): void
    {
        $operator = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $operator->uid]);
        $harga = $this->harga($event);
        $cartA = $this->cashTransaction($operator, $event, $harga, ['name' => 'Cart A']);
        $cartB = $this->cashTransaction($operator, $event, $harga, ['name' => 'Cart B']);

        $tamperedUrl = str_replace($cartA->uid, $cartB->uid, $this->signedCashTicketUrl($cartA));

        $this->get($this->uriFromUrl($tamperedUrl))
            ->assertForbidden();
    }

    public function test_cash_email_contains_valid_signed_ticket_url(): void
    {
        Mail::fake();

        $operator = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $operator->uid, 'event' => 'Email Signed Event']);
        $harga = $this->harga($event);
        $cart = $this->cashTransaction($operator, $event, $harga, [
            'name' => 'Email Cash Buyer',
            'email' => 'email-cash@example.test',
        ]);

        (new sendEmailTrnsaksi('email-cash@example.test', 'Email Cash Buyer', $cart->uid, $cart->invoice))->handle();

        Mail::assertSent(CashNotifikasiMail::class, function (CashNotifikasiMail $mail) use ($cart) {
            $html = $mail->render();
            $ticketUrl = $this->extractCashTicketUrl($html);

            $this->assertTrue($mail->hasTo('email-cash@example.test'));
            $this->assertStringContainsString('/cash-ticket/'.$cart->uid, $html);
            $this->assertStringNotContainsString('/generate-barcode/', $html);
            $this->assertStringNotContainsString('/login', $html);
            $this->assertNotNull($ticketUrl);

            $this->get($this->uriFromUrl($ticketUrl))->assertOk();

            return true;
        });
    }

    public function test_online_barcode_still_requires_login_and_owner(): void
    {
        $operator = $this->user(['role' => 'penyewa']);
        $buyer = $this->user(['role' => 'user', 'name' => 'Online Owner']);
        $otherBuyer = $this->user(['role' => 'user']);
        $event = $this->event(['user_uid' => $operator->uid, 'event' => 'Online Barcode Event']);
        $harga = $this->harga($event);
        $cart = $this->onlineTransaction($buyer, $event, $harga);

        $this->get(route('barcode.generate', ['data' => $cart->invoice]))
            ->assertRedirect(route('barcode.login', ['data' => $cart->invoice]));

        $this->actingAs($buyer)
            ->get(route('barcode.generate', ['data' => $cart->invoice]))
            ->assertOk()
            ->assertSee($cart->invoice)
            ->assertSee('Online Barcode Event');

        auth()->logout();

        $this->actingAs($otherBuyer)
            ->get(route('barcode.generate', ['data' => $cart->invoice]))
            ->assertForbidden();
    }

    public function test_resend_cash_email_generates_valid_signed_ticket_url(): void
    {
        Queue::fake();

        $operator = $this->user(['role' => 'penyewa']);
        $event = $this->event(['user_uid' => $operator->uid, 'event' => 'Resend Signed Event']);
        $harga = $this->harga($event);
        $cart = $this->cashTransaction($operator, $event, $harga, [
            'name' => 'Resend Cash Buyer',
            'email' => 'resend-cash@example.test',
        ]);
        $capturedJob = null;

        Livewire::actingAs($operator)
            ->test(DashboardEventDetail::class, ['uid' => $event->uid])
            ->set('resendEmailUid', $cart->uid)
            ->call('resendEmail');

        Queue::assertPushed(sendEmailTrnsaksi::class, function (sendEmailTrnsaksi $job) use (&$capturedJob) {
            $capturedJob = $job;

            return $job->recipientEmail === 'resend-cash@example.test';
        });

        Mail::fake();
        $capturedJob->handle();

        Mail::assertSent(CashNotifikasiMail::class, function (CashNotifikasiMail $mail) use ($cart) {
            $html = $mail->render();
            $ticketUrl = $this->extractCashTicketUrl($html);

            $this->assertTrue($mail->hasTo('resend-cash@example.test'));
            $this->assertNotNull($ticketUrl);
            $this->assertStringContainsString('/cash-ticket/'.$cart->uid, $ticketUrl);

            $this->get($this->uriFromUrl($ticketUrl))->assertOk();

            return true;
        });
    }

    protected function signedCashTicketUrl(Cart $cart, $expiration = null): string
    {
        return URL::temporarySignedRoute(
            'cash.ticket.show',
            $expiration ?? now()->addDays(7),
            ['uid' => $cart->uid],
        );
    }

    protected function extractCashTicketUrl(string $html): ?string
    {
        if (! preg_match('/href="([^"]*\/cash-ticket\/[^"]+)"/', $html, $matches)) {
            return null;
        }

        return html_entity_decode($matches[1], ENT_QUOTES);
    }

    protected function uriFromUrl(string $url): string
    {
        $parts = parse_url($url);
        $uri = $parts['path'] ?? '/';

        if (! empty($parts['query'])) {
            $uri .= '?'.$parts['query'];
        }

        return $uri;
    }

    protected function cashTransaction(User $operator, Event $event, Harga $harga, array $cashAttributes = [], array $cartAttributes = []): Cart
    {
        $cart = Cart::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $operator->uid,
            'event_uid' => $event->uid,
            'invoice' => 'CASH-'.Str::upper(Str::random(8)),
            'status' => Cart::STATUS_SUCCESS,
            'konfirmasi' => null,
            'payment_type' => 'cash',
            'gross_amount' => $harga->harga,
            'paid_at' => now(),
        ], $cartAttributes));

        $this->hargaCart($cart, $harga, 1);

        Cash::create(array_merge([
            'uid' => $cart->uid,
            'uid_partner' => null,
            'uid_user' => $operator->uid,
            'uid_event' => $event->uid,
            'name' => 'Cash Buyer',
            'email' => 'cash@example.test',
            'nomor' => '080000000000',
            'alamat' => '-',
            'lahir' => '2000-01-01',
            'gender' => 'pria',
        ], $cashAttributes));

        return $cart;
    }

    protected function onlineTransaction(User $buyer, Event $event, Harga $harga, array $cartAttributes = []): Cart
    {
        $cart = Cart::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.Str::upper(Str::random(8)),
            'status' => Cart::STATUS_SUCCESS,
            'konfirmasi' => null,
            'payment_type' => 'bank_transfer',
            'gross_amount' => $harga->harga,
            'paid_at' => now(),
        ], $cartAttributes));

        $this->hargaCart($cart, $harga, 1);

        return $cart;
    }

    protected function hargaCart(Cart $cart, Harga $harga, int $quantity): HargaCart
    {
        return HargaCart::create([
            'uid' => $cart->uid,
            'orderBy' => '1',
            'harga_id' => $harga->id,
            'event_uid' => $cart->event_uid,
            'quantity' => $quantity,
            'harga_ticket' => $harga->harga,
            'kategori_harga' => $harga->kategori,
            'voucher' => null,
            'disc' => 0,
        ]);
    }

    protected function createSchema(): void
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
            $table->string('kategori')->nullable();
            $table->unsignedInteger('qty')->default(0);
            $table->unsignedInteger('sold_qty')->default(0);
            $table->unsignedInteger('reserved_qty')->default(0);
            $table->integer('harga')->nullable();
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

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->foreignId('harga_id')->nullable();
            $table->string('orderBy');
            $table->string('uid');
            $table->string('event_uid');
            $table->unsignedInteger('quantity');
            $table->integer('harga_ticket');
            $table->string('kategori_harga');
            $table->string('voucher')->nullable();
            $table->integer('disc')->nullable();
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

        Schema::create('cashes', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('uid_partner')->nullable();
            $table->string('uid_user');
            $table->string('uid_event');
            $table->string('name');
            $table->string('email');
            $table->string('nomor');
            $table->string('alamat');
            $table->string('lahir');
            $table->string('gender');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('partners', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('event_uid')->nullable();
            $table->string('referensi')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('hp')->nullable();
            $table->string('alamat')->nullable();
            $table->string('city')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('vouchers', function ($table) {
            $table->id();
            $table->string('uid')->nullable();
            $table->string('event_uid')->nullable();
            $table->string('code')->nullable();
            $table->string('unit')->nullable();
            $table->integer('nominal')->default(0);
            $table->integer('max_disc')->default(0);
            $table->timestamps();
        });
    }

    protected function user(array $attributes = []): User
    {
        return User::create(array_merge([
            'uid' => 'user-'.Str::random(6),
            'name' => 'Test User',
            'email' => Str::random(6).'@example.test',
            'password' => bcrypt('password'),
            'role' => 'user',
        ], $attributes));
    }

    protected function event(array $attributes = []): Event
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

    protected function harga(Event $event, array $attributes = []): Harga
    {
        return Harga::create(array_merge([
            'uid' => $event->uid,
            'kategori' => 'NOMMENSEN TICKET',
            'qty' => 10,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => 92000,
            'status' => 'active',
        ], $attributes));
    }
}
