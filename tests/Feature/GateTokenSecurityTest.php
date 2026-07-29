<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Jobs\sendEmailETransaksi;
use App\Models\Cart;
use App\Models\Event;
use App\Models\HargaCart;
use App\Models\User;
use App\Services\Tickets\GateTokenService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GateTokenSecurityTest extends TestCase
{
    private GateTokenService $tokens;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('cache.default', 'array');
        Config::set('gate-tokens.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        Config::set('gate-tokens.active_event_uids', []);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->createSchema();
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        $this->tokens = app(GateTokenService::class);
    }

    public function test_raw_token_is_not_stored_and_encrypted_token_can_render_qr_again(): void
    {
        [$owner, $event, $buyer, $cart] = $this->ticket();
        $token = $this->tokens->issue($cart);
        $stored = DB::table('carts')->where('id', $cart->id)->first();

        $this->assertSame(hash('sha256', $token), $stored->gate_token_hash);
        $this->assertNotSame($token, $stored->gate_token_hash);
        $this->assertNotSame($token, $stored->gate_token_encrypted);
        $this->assertStringNotContainsString($token, $stored->gate_token_encrypted);
        $this->assertSame($token, $this->tokens->tokenForQr($cart->fresh()));

        $first = $this->actingAs($buyer)->get(route('barcode.generate', ['data' => $cart->invoice]));
        $first->assertOk()->assertSee($cart->invoice)->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->assertStringNotContainsString($token, $first->getContent());

        $this->actingAs($buyer)
            ->get(route('barcode.generate', ['data' => $cart->invoice]))
            ->assertOk();
    }

    public function test_invoice_is_rejected_but_new_gate_token_is_accepted(): void
    {
        [$owner, $event, $buyer, $cart] = $this->ticket();
        $token = $this->tokens->issue($cart);
        Sanctum::actingAs($owner);

        $this->postJson('/api/ticket/search', ['gate_token' => $cart->invoice])
            ->assertUnprocessable();

        $this->postJson('/api/ticket/search', ['invoice' => $cart->invoice])
            ->assertUnprocessable();

        $this->postJson('/api/ticket/search', ['gate_token' => $token])
            ->assertOk()
            ->assertJsonPath('data.invoice', $cart->invoice)
            ->assertJsonMissing(['gate_token_hash', 'gate_token_encrypted'])
            ->assertDontSee($token);
    }

    public function test_fake_token_and_unpaid_ticket_are_rejected(): void
    {
        [$owner] = $this->ticket();
        Sanctum::actingAs($owner);

        $this->postJson('/api/ticket/search', [
            'gate_token' => rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='),
        ])->assertNotFound();

        [, , , $unpaid] = $this->ticket(owner: $owner, cartAttributes: [
            'status' => Cart::STATUS_PENDING,
        ]);
        $unpaidToken = $this->tokens->issue($unpaid);

        $this->postJson('/api/ticket/search', ['gate_token' => $unpaidToken])
            ->assertUnprocessable();
    }

    public function test_staff_cannot_scan_another_owners_event_and_event_token_is_rejected(): void
    {
        [$ownerA] = $this->ticket();
        [$ownerB, $eventB, $buyerB, $cartB] = $this->ticket();
        $tokenB = $this->tokens->issue($cartB);
        $staffA = $this->user([
            'role' => 'staff',
            'parent_uid' => $ownerA->uid,
        ]);
        Sanctum::actingAs($staffA);

        $this->postJson('/api/ticket/search', ['gate_token' => $tokenB])
            ->assertForbidden();

        $this->postJson('/api/ticket/confirm', ['gate_token' => $tokenB])
            ->assertForbidden();
    }

    public function test_user_cannot_view_another_users_ticket(): void
    {
        [$owner, $event, $buyer, $cart] = $this->ticket();
        $this->tokens->issue($cart);
        $otherBuyer = $this->user();

        $this->actingAs($otherBuyer)
            ->get(route('barcode.generate', ['data' => $cart->invoice]))
            ->assertForbidden();

        auth()->logout();
        $this->actingAs($owner)
            ->get(route('barcode.generate', ['data' => $cart->invoice]))
            ->assertOk();
    }

    public function test_second_and_competing_scan_only_allow_one_success(): void
    {
        [$owner, $event, $buyer, $cart] = $this->ticket();
        $token = $this->tokens->issue($cart);
        $staff = $this->user([
            'role' => 'staff',
            'parent_uid' => $owner->uid,
        ]);
        Sanctum::actingAs($staff);

        $this->postJson('/api/ticket/confirm', [
            'gate_token' => $token,
            'scan_device_id' => 'gate-a',
        ])->assertOk();

        $this->postJson('/api/ticket/confirm', [
            'gate_token' => $token,
            'scan_device_id' => 'gate-b',
        ])->assertStatus(409);

        $cart->refresh();
        $this->assertSame('1', (string) $cart->konfirmasi);
        $this->assertNotNull($cart->scanned_at);
        $this->assertSame($staff->uid, $cart->scanned_by);
        $this->assertSame('gate-a', $cart->scan_device_id);
    }

    public function test_rotation_invalidates_old_token_increments_version_and_leaves_other_event_untouched(): void
    {
        [$owner, $targetEvent, $buyer, $targetCart] = $this->ticket(eventName: 'Purnama Bersantai');
        $oldToken = $this->tokens->issue($targetCart);
        [$otherOwner, $otherEvent, $otherBuyer, $otherCart] = $this->ticket(eventName: 'Nommensen');
        $otherToken = $this->tokens->issue($otherCart);
        $targetInvoice = $targetCart->invoice;
        Config::set('gate-tokens.active_event_uids', [$targetEvent->uid]);

        $this->artisan('tickets:rotate-gate-tokens', [
            'event_uid' => $targetEvent->uid,
            '--dry-run' => true,
        ])->expectsOutputToContain('Purnama Bersantai')
            ->expectsOutputToContain($targetEvent->uid)
            ->assertSuccessful();

        $this->assertSame(hash('sha256', $oldToken), $targetCart->fresh()->gate_token_hash);

        $prompt = "ROTASI 1 tiket untuk Purnama Bersantai ({$targetEvent->uid})?";
        $this->artisan('tickets:rotate-gate-tokens', [
            'event_uid' => $targetEvent->uid,
            '--execute' => true,
        ])->expectsConfirmation($prompt, 'yes')
            ->assertSuccessful();

        $targetCart->refresh();
        $this->assertSame(2, $targetCart->gate_token_version);
        $this->assertSame($targetInvoice, $targetCart->invoice);
        $this->assertNotSame(hash('sha256', $oldToken), $targetCart->gate_token_hash);
        $this->assertSame(hash('sha256', $otherToken), $otherCart->fresh()->gate_token_hash);
        $this->assertSame(1, $otherCart->fresh()->gate_token_version);

        Sanctum::actingAs($owner);
        $this->postJson('/api/ticket/search', ['gate_token' => $oldToken])
            ->assertNotFound();

        $newToken = $this->tokens->tokenForQr($targetCart);
        $this->postJson('/api/ticket/search', ['gate_token' => $newToken])
            ->assertOk();
    }

    public function test_rotation_skips_scanned_and_non_success_tickets(): void
    {
        [$owner, $event, $buyer, $eligible] = $this->ticket(eventName: 'Purnama Bersantai');
        $eligibleToken = $this->tokens->issue($eligible);
        [, , , $scanned] = $this->ticket(owner: $owner, event: $event, cartAttributes: [
            'konfirmasi' => '1',
            'scanned_at' => now(),
        ]);
        $scannedToken = $this->tokens->issue($scanned);
        [, , , $pending] = $this->ticket(owner: $owner, event: $event, cartAttributes: [
            'status' => Cart::STATUS_PENDING,
        ]);
        $pendingToken = $this->tokens->issue($pending);
        Config::set('gate-tokens.active_event_uids', [$event->uid]);

        $prompt = "ROTASI 1 tiket untuk Purnama Bersantai ({$event->uid})?";
        $this->artisan('tickets:rotate-gate-tokens', [
            'event_uid' => $event->uid,
            '--execute' => true,
        ])->expectsConfirmation($prompt, 'yes')
            ->assertSuccessful();

        $this->assertNotSame(hash('sha256', $eligibleToken), $eligible->fresh()->gate_token_hash);
        $this->assertSame(hash('sha256', $scannedToken), $scanned->fresh()->gate_token_hash);
        $this->assertSame(hash('sha256', $pendingToken), $pending->fresh()->gate_token_hash);
    }

    public function test_resend_command_is_scoped_and_queues_no_raw_token(): void
    {
        Queue::fake();
        [$owner, $event, $buyer, $cart] = $this->ticket(eventName: 'Purnama Bersantai');
        $token = $this->tokens->issue($cart);
        Config::set('gate-tokens.active_event_uids', [$event->uid]);

        $this->artisan('tickets:resend-gate-tickets', [
            'event_uid' => $event->uid,
            '--dry-run' => true,
        ])->expectsOutputToContain('Akan kirim : 1')
            ->assertSuccessful();
        Queue::assertNothingPushed();

        $prompt = "KIRIM ULANG 1 tiket untuk Purnama Bersantai ({$event->uid})?";
        $this->artisan('tickets:resend-gate-tickets', [
            'event_uid' => $event->uid,
            '--execute' => true,
        ])->expectsConfirmation($prompt, 'yes')
            ->assertSuccessful();

        Queue::assertPushed(sendEmailETransaksi::class, function (sendEmailETransaksi $job) use ($buyer, $cart, $token) {
            $serialized = serialize($job);

            return $job->userUid === $buyer->uid
                && $job->cartUid === $cart->uid
                && ! str_contains($serialized, $token);
        });
    }

    public function test_automatic_issuance_is_limited_to_explicit_event_allowlist(): void
    {
        [$owner, $targetEvent, $targetBuyer, $targetCart] = $this->ticket(eventName: 'Purnama Bersantai');
        [$otherOwner, $otherEvent, $otherBuyer, $otherCart] = $this->ticket(eventName: 'Nommensen');
        Config::set('gate-tokens.active_event_uids', [$targetEvent->uid]);

        $this->assertTrue($this->tokens->issueIfEnabled($targetCart));
        $this->assertFalse($this->tokens->issueIfEnabled($otherCart));
        $this->assertNotNull($targetCart->fresh()->gate_token_hash);
        $this->assertNull($otherCart->fresh()->gate_token_hash);
    }

    private function ticket(
        ?User $owner = null,
        ?Event $event = null,
        array $cartAttributes = [],
        string $eventName = 'Gate Event',
    ): array {
        $owner ??= $this->user(['role' => 'penyewa']);
        $event ??= $this->event($owner, ['event' => $eventName]);
        $buyer = $this->user();
        $cart = Cart::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.Str::upper(Str::random(10)),
            'status' => Cart::STATUS_SUCCESS,
            'konfirmasi' => null,
            'payment_type' => 'bank_transfer',
            'gross_amount' => 100000,
        ], $cartAttributes));

        HargaCart::create([
            'uid' => $cart->uid,
            'event_uid' => $event->uid,
            'orderBy' => '1',
            'kategori_harga' => 'REGULAR',
            'quantity' => 1,
            'harga_ticket' => 100000,
        ]);

        return [$owner, $event, $buyer, $cart];
    }

    private function user(array $attributes = []): User
    {
        return User::create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Test User',
            'email' => Str::random(10).'@example.test',
            'password' => bcrypt('password'),
            'role' => 'user',
        ], $attributes));
    }

    private function event(User $owner, array $attributes = []): Event
    {
        return Event::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $owner->uid,
            'event' => 'Gate Event',
            'alamat' => 'Jakarta',
            'tanggal' => now()->addMonth()->toDateTimeString(),
            'status' => 'active',
            'cover' => 'cover.webp',
            'konfirmasi' => '1',
            'deskripsi' => '-',
            'map' => '-',
        ], $attributes));
    }

    private function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('parent_uid')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('user_uid');
            $table->string('event');
            $table->string('alamat');
            $table->string('tanggal');
            $table->string('status');
            $table->string('cover')->nullable();
            $table->string('konfirmasi')->nullable();
            $table->text('deskripsi')->nullable();
            $table->text('map')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('invoice')->nullable();
            $table->char('gate_token_hash', 64)->nullable()->unique();
            $table->text('gate_token_encrypted')->nullable();
            $table->timestamp('gate_token_issued_at')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->string('scanned_by')->nullable();
            $table->string('scan_device_id')->nullable();
            $table->unsignedInteger('gate_token_version')->default(1);
            $table->string('status');
            $table->string('konfirmasi')->nullable();
            $table->string('payment_type')->nullable();
            $table->unsignedBigInteger('gross_amount')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('event_uid');
            $table->string('orderBy');
            $table->string('kategori_harga');
            $table->unsignedInteger('quantity');
            $table->integer('harga_ticket');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cashes', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('uid_user');
            $table->string('uid_event');
            $table->string('name');
            $table->string('email');
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
