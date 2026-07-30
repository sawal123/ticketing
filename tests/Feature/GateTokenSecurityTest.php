<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Jobs\sendEmailETransaksi;
use App\Mail\MidtransPaymentNotification;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\HargaCart;
use App\Models\User;
use App\Services\Tickets\GateTokenService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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

    public function test_manual_code_format_normalization_hmac_encryption_and_hidden_fields(): void
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            $generated = $this->tokens->generateManualCode();
            $this->assertSame(GateTokenService::MANUAL_CODE_LENGTH, strlen($generated));
            $this->assertMatchesRegularExpression(GateTokenService::MANUAL_CODE_PATTERN, $generated);
        }

        $this->assertSame('7K4M9Q2R', $this->tokens->normalizeManualCode(" 7k4m - 9q2r \n"));
        $this->assertTrue($this->tokens->isValidManualCode('7k4m-9q2r'));

        foreach (['O2345678', 'I2345678', '02345678', '12345678'] as $invalid) {
            $this->assertFalse($this->tokens->isValidManualCode($invalid));
        }

        [, , , $cart] = $this->ticket();
        $this->tokens->issue($cart);
        $cart->refresh();
        $display = $this->tokens->manualCodeForDisplay($cart);
        $canonical = str_replace('-', '', $display);
        $stored = DB::table('carts')->where('id', $cart->id)->first();

        $this->assertMatchesRegularExpression('/^[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}-[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}$/D', $display);
        $this->assertSame($this->tokens->hashManualCode($canonical), $stored->gate_manual_code_hash);
        $this->assertNotSame(hash('sha256', $canonical), $stored->gate_manual_code_hash);
        $this->assertNotSame($canonical, $stored->gate_manual_code_hash);
        $this->assertStringNotContainsString($canonical, $stored->gate_manual_code_encrypted);
        $this->assertSame($display, $this->tokens->manualCodeForDisplay($cart));
        $this->assertArrayNotHasKey('gate_manual_code_hash', $cart->toArray());
        $this->assertArrayNotHasKey('gate_manual_code_encrypted', $cart->toArray());
        $this->assertStringNotContainsString($canonical, $cart->toJson());

        $cart->gate_manual_code_hash = str_repeat('0', 64);
        try {
            $this->tokens->manualCodeForDisplay($cart);
            $this->fail('Kode manual dengan HMAC yang diubah seharusnya ditolak.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Integritas kode manual tidak valid.', $exception->getMessage());
        }
    }

    public function test_manual_code_collision_is_retried(): void
    {
        [, , , $existing] = $this->ticket();
        $this->tokens->issue($existing);
        $collisionCode = str_replace('-', '', $this->tokens->manualCodeForDisplay($existing->fresh()));

        $retryingTokens = new class($collisionCode) extends GateTokenService
        {
            private int $index = 0;

            public function __construct(private string $collisionCode) {}

            public function generateManualCode(): string
            {
                return $this->index++ === 0 ? $this->collisionCode : '23456789';
            }
        };

        [, , , $cart] = $this->ticket();
        $retryingTokens->issue($cart);

        $this->assertSame('2345-6789', $retryingTokens->manualCodeForDisplay($cart->fresh()));
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

    public function test_manual_search_accepts_normalized_input_and_never_returns_credentials(): void
    {
        [$owner, $event, $buyer, $cart] = $this->ticket();
        $token = $this->tokens->issue($cart);
        $manualDisplay = $this->tokens->manualCodeForDisplay($cart->fresh());
        $lowercaseWithSpaces = ' '.strtolower(str_replace('-', ' - ', $manualDisplay)).' ';
        Sanctum::actingAs($owner);

        $gateResponse = $this->postJson('/api/ticket/search', ['gate_token' => $token])
            ->assertOk();

        $manualResponse = $this->postJson('/api/ticket/manual/search', [
            'manual_code' => $lowercaseWithSpaces,
            'event_uid' => $event->uid,
        ])->assertOk()
            ->assertJsonPath('data.invoice', $cart->invoice)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => array_keys($gateResponse->json('data')),
            ])
            ->assertJsonMissing([
                'gate_token',
                'gate_token_hash',
                'gate_token_encrypted',
                'gate_manual_code_hash',
                'gate_manual_code_encrypted',
                'manual_code',
            ]);

        $canonical = str_replace('-', '', $manualDisplay);
        $this->assertStringNotContainsString($canonical, $manualResponse->getContent());
        $this->assertStringNotContainsString($token, $manualResponse->getContent());
    }

    public function test_manual_search_rejects_fake_invoice_missing_event_and_wrong_event_scope(): void
    {
        [$owner, $event, $buyer, $cart] = $this->ticket();
        $this->tokens->issue($cart);
        $manualCode = $this->tokens->manualCodeForDisplay($cart->fresh());
        $otherEvent = $this->event($owner);
        Sanctum::actingAs($owner);

        $this->postJson('/api/ticket/manual/search', [
            'manual_code' => '23456789',
            'event_uid' => $event->uid,
        ])->assertNotFound();

        $this->postJson('/api/ticket/manual/search', [
            'manual_code' => $cart->invoice,
            'event_uid' => $event->uid,
        ])->assertNotFound();

        $this->postJson('/api/ticket/manual/search', [
            'manual_code' => $manualCode,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('event_uid');

        $this->postJson('/api/ticket/manual/search', [
            'manual_code' => $manualCode,
            'event_uid' => $otherEvent->uid,
        ])->assertNotFound();
    }

    public function test_manual_event_access_status_and_staff_ownership_are_enforced(): void
    {
        [$ownerA] = $this->ticket();
        [$ownerB, $eventB, $buyerB, $cartB] = $this->ticket();
        $this->tokens->issue($cartB);
        $manualB = $this->tokens->manualCodeForDisplay($cartB->fresh());
        $staffA = $this->user([
            'role' => 'staff',
            'parent_uid' => $ownerA->uid,
        ]);
        Sanctum::actingAs($staffA);

        $this->postJson('/api/ticket/manual/search', [
            'manual_code' => $manualB,
            'event_uid' => $eventB->uid,
        ])->assertForbidden();

        [, $pendingEvent, , $pending] = $this->ticket(owner: $ownerB, cartAttributes: [
            'status' => Cart::STATUS_PENDING,
        ]);
        $this->tokens->issue($pending);
        $pendingManual = $this->tokens->manualCodeForDisplay($pending->fresh());
        Sanctum::actingAs($ownerB);

        $this->postJson('/api/ticket/manual/search', [
            'manual_code' => $pendingManual,
            'event_uid' => $pendingEvent->uid,
        ])->assertUnprocessable();
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

    public function test_second_and_competing_manual_scan_only_allow_one_success_and_list_leaks_nothing(): void
    {
        [$owner, $event, $buyer, $cart] = $this->ticket();
        $this->tokens->issue($cart);
        $manualDisplay = $this->tokens->manualCodeForDisplay($cart->fresh());
        $manualCanonical = str_replace('-', '', $manualDisplay);
        $staff = $this->user([
            'role' => 'staff',
            'parent_uid' => $owner->uid,
        ]);
        Sanctum::actingAs($staff);

        $this->postJson('/api/ticket/manual/confirm', [
            'manual_code' => strtolower($manualDisplay),
            'event_uid' => $event->uid,
            'scan_device_id' => 'manual-a',
        ])->assertOk();

        $this->postJson('/api/ticket/manual/confirm', [
            'manual_code' => $manualCanonical,
            'event_uid' => $event->uid,
            'scan_device_id' => 'manual-b',
        ])->assertStatus(409);

        $cart->refresh();
        $this->assertSame('1', (string) $cart->konfirmasi);
        $this->assertNotNull($cart->scanned_at);
        $this->assertSame($staff->uid, $cart->scanned_by);
        $this->assertSame('manual-a', $cart->scan_device_id);

        $list = $this->getJson("/api/event/{$event->uid}/verified-tickets")
            ->assertOk()
            ->assertJsonMissing([
                'gate_token_hash',
                'gate_token_encrypted',
                'gate_manual_code_hash',
                'gate_manual_code_encrypted',
                'manual_code',
            ]);
        $this->assertStringNotContainsString($manualCanonical, $list->getContent());
    }

    public function test_already_scanned_manual_ticket_returns_conflict(): void
    {
        [$owner, $event, $buyer, $cart] = $this->ticket();
        $this->tokens->issue($cart);
        $manualCode = $this->tokens->manualCodeForDisplay($cart->fresh());
        $cart->update([
            'konfirmasi' => '1',
            'scanned_at' => now(),
        ]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/ticket/manual/search', [
            'manual_code' => $manualCode,
            'event_uid' => $event->uid,
        ])->assertStatus(409);
    }

    public function test_rotation_invalidates_old_token_increments_version_and_leaves_other_event_untouched(): void
    {
        [$owner, $targetEvent, $buyer, $targetCart] = $this->ticket(eventName: 'Purnama Bersantai');
        $oldToken = $this->tokens->issue($targetCart);
        $oldManualCode = $this->tokens->manualCodeForDisplay($targetCart->fresh());
        [$otherOwner, $otherEvent, $otherBuyer, $otherCart] = $this->ticket(eventName: 'Nommensen');
        $otherToken = $this->tokens->issue($otherCart);
        $otherManualHash = $otherCart->fresh()->gate_manual_code_hash;
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
        $this->assertNotSame($this->tokens->hashManualCode($oldManualCode), $targetCart->gate_manual_code_hash);
        $this->assertSame(hash('sha256', $otherToken), $otherCart->fresh()->gate_token_hash);
        $this->assertSame($otherManualHash, $otherCart->fresh()->gate_manual_code_hash);
        $this->assertSame(1, $otherCart->fresh()->gate_token_version);

        Sanctum::actingAs($owner);
        $this->postJson('/api/ticket/search', ['gate_token' => $oldToken])
            ->assertNotFound();
        $this->postJson('/api/ticket/manual/search', [
            'manual_code' => $oldManualCode,
            'event_uid' => $targetEvent->uid,
        ])->assertNotFound();

        $newToken = $this->tokens->tokenForQr($targetCart);
        $this->postJson('/api/ticket/search', ['gate_token' => $newToken])
            ->assertOk();
        $this->postJson('/api/ticket/manual/search', [
            'manual_code' => $this->tokens->manualCodeForDisplay($targetCart),
            'event_uid' => $targetEvent->uid,
        ])->assertOk();
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
        $manualCode = str_replace('-', '', $this->tokens->manualCodeForDisplay($cart->fresh()));
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

        Queue::assertPushed(sendEmailETransaksi::class, function (sendEmailETransaksi $job) use ($buyer, $cart, $token, $manualCode) {
            $serialized = serialize($job);

            return $job->userUid === $buyer->uid
                && $job->cartUid === $cart->uid
                && ! str_contains($serialized, $token)
                && ! str_contains($serialized, $manualCode);
        });

        $mailHtml = (new MidtransPaymentNotification($buyer, $cart->fresh()))->render();
        $this->assertStringContainsString(
            $this->tokens->manualCodeForDisplay($cart->fresh()),
            $mailHtml,
        );
    }

    public function test_missing_manual_code_backfill_does_not_rotate_existing_gate_token(): void
    {
        [$owner, $event, $buyer, $cart] = $this->ticket(eventName: 'Purnama Bersantai');
        $token = $this->tokens->issue($cart);
        $cart->forceFill([
            'gate_manual_code_hash' => null,
            'gate_manual_code_encrypted' => null,
        ])->save();
        $before = $cart->fresh();
        Config::set('gate-tokens.active_event_uids', [$event->uid]);

        $this->artisan('tickets:issue-missing-manual-codes', [
            'event_uid' => $event->uid,
            '--dry-run' => true,
        ])->expectsOutputToContain('Gate token : tidak diubah')
            ->assertSuccessful();

        $prompt = "TERBITKAN 1 kode manual tanpa merotasi gate token untuk Purnama Bersantai ({$event->uid})?";
        $this->artisan('tickets:issue-missing-manual-codes', [
            'event_uid' => $event->uid,
            '--execute' => true,
        ])->expectsConfirmation($prompt, 'yes')
            ->assertSuccessful();

        $cart->refresh();
        $this->assertSame(hash('sha256', $token), $cart->gate_token_hash);
        $this->assertSame($before->gate_token_encrypted, $cart->gate_token_encrypted);
        $this->assertSame($before->gate_token_version, $cart->gate_token_version);
        $this->assertTrue($before->gate_token_issued_at->equalTo($cart->gate_token_issued_at));
        $this->assertNotNull($cart->gate_manual_code_hash);
        $this->assertMatchesRegularExpression(
            '/^[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}-[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}$/D',
            $this->tokens->manualCodeForDisplay($cart),
        );
    }

    public function test_ticket_pages_enforce_owner_and_signed_cash_access_and_show_manual_code(): void
    {
        [$owner, $event, $buyer, $cart] = $this->ticket();
        $this->tokens->issue($cart);
        $manualCode = $this->tokens->manualCodeForDisplay($cart->fresh());

        $this->actingAs($buyer)
            ->get(route('barcode.generate', ['data' => $cart->invoice]))
            ->assertOk()
            ->assertSee('Kode Manual')
            ->assertSee($manualCode);

        $otherBuyer = $this->user();
        $this->actingAs($otherBuyer)
            ->get(route('barcode.generate', ['data' => $cart->invoice]))
            ->assertForbidden()
            ->assertDontSee($manualCode);

        $cart->update(['payment_type' => 'cash']);
        Cash::create([
            'uid' => $cart->uid,
            'uid_user' => $owner->uid,
            'uid_event' => $event->uid,
            'name' => 'Cash Buyer',
            'email' => 'cash-buyer@example.test',
        ]);
        auth()->logout();

        $signedUrl = URL::temporarySignedRoute(
            'cash.ticket.show',
            now()->addMinute(),
            [
                'uid' => $cart->uid,
                'gate_access' => $this->tokens->cashTicketProof($cart->fresh()),
            ],
        );
        $this->get($signedUrl)
            ->assertOk()
            ->assertSee($manualCode);
        $this->get($signedUrl.'&tampered=1')
            ->assertForbidden()
            ->assertDontSee($manualCode);
    }

    public function test_manual_endpoints_are_rate_limited_without_limiting_camera_endpoint(): void
    {
        [$owner, $event, $buyer, $cart] = $this->ticket();
        $token = $this->tokens->issue($cart);
        Sanctum::actingAs($owner);

        for ($request = 0; $request < 10; $request++) {
            $this->postJson('/api/ticket/manual/search', [
                'manual_code' => '23456789',
                'event_uid' => $event->uid,
            ])->assertNotFound();
        }

        $this->postJson('/api/ticket/manual/search', [
            'manual_code' => '23456789',
            'event_uid' => $event->uid,
        ])->assertTooManyRequests();

        $this->postJson('/api/ticket/search', ['gate_token' => $token])
            ->assertOk();
    }

    public function test_automatic_issuance_is_limited_to_explicit_event_allowlist(): void
    {
        [$owner, $targetEvent, $targetBuyer, $targetCart] = $this->ticket(eventName: 'Purnama Bersantai');
        [$otherOwner, $otherEvent, $otherBuyer, $otherCart] = $this->ticket(eventName: 'Nommensen');
        Config::set('gate-tokens.active_event_uids', [$targetEvent->uid]);

        $this->assertTrue($this->tokens->issueIfEnabled($targetCart));
        $this->assertFalse($this->tokens->issueIfEnabled($otherCart));
        $this->assertNotNull($targetCart->fresh()->gate_token_hash);
        $this->assertNotNull($targetCart->fresh()->gate_manual_code_hash);
        $this->assertNull($otherCart->fresh()->gate_token_hash);
        $this->assertNull($otherCart->fresh()->gate_manual_code_hash);
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
            $table->string('gambar')->nullable();
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
            $table->char('gate_manual_code_hash', 64)->nullable()->unique();
            $table->text('gate_manual_code_encrypted')->nullable();
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
