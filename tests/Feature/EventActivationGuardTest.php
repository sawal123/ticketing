<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Admin\EventIndex as AdminEventIndex;
use App\Livewire\Dashboard\EventIndex as DashboardEventIndex;
use App\Models\Agreement;
use App\Models\Cart;
use App\Models\Event;
use App\Models\EventBankAccount;
use App\Models\EventDate;
use App\Models\EventDocument;
use App\Models\EventOrganizer;
use App\Models\EventPaymentGateway;
use App\Models\Harga;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\Events\EventActivationGuardService;
use App\Services\Tickets\TicketReservationService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class EventActivationGuardTest extends TestCase
{
    private const DIR = 'private/activation-guard';

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
        Storage::fake('local');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '', 'icon' => '']]);
    }

    public function test_all_valid_prerequisites_allow_admin_activation(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['status' => 'inactive', 'konfirmasi' => null]);

        $this->seedActivationReadyState($tenant, $event);

        Livewire::actingAs($admin)
            ->test(AdminEventIndex::class)
            ->call('toggleStatus', $event->uid);

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'status' => 'active',
            'konfirmasi' => '1',
        ]);
    }

    public function test_draft_or_ready_agreement_cannot_activate_event(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();

        foreach ([Agreement::STATUS_DRAFT, Agreement::STATUS_READY] as $status) {
            $event = $this->event($tenant, ['uid' => (string) Str::uuid()]);
            $this->seedActivationReadyState($tenant, $event, [
                'agreement' => [
                    'status' => $status,
                    'signed_review_status' => Agreement::SIGNED_REVIEW_VERIFIED,
                    'completed_at' => $status === Agreement::STATUS_COMPLETED ? now() : null,
                ],
            ]);

            $this->assertActivationFails($event, $admin, 'MOU belum selesai.');
            $this->assertSame('inactive', $event->fresh()->status);
        }
    }

    public function test_completed_agreement_without_verified_signed_review_cannot_activate(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);

        $this->seedActivationReadyState($tenant, $event, [
            'agreement' => [
                'signed_review_status' => Agreement::SIGNED_REVIEW_PENDING,
            ],
        ]);

        $this->assertActivationFails($event, $admin, 'MOU bertanda tangan belum diverifikasi.');
        $this->assertSame('inactive', $event->fresh()->status);
    }

    public function test_missing_physical_signed_pdf_blocks_activation(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);

        $this->seedActivationReadyState($tenant, $event, [
            'files' => [
                'signed_pdf' => false,
            ],
        ]);

        $this->assertActivationFails($event, $admin, 'Dokumen MOU bertanda tangan belum tersedia.');
    }

    public function test_unverified_bank_account_blocks_activation(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);

        $this->seedActivationReadyState($tenant, $event, [
            'bank' => ['status' => 'pending'],
        ]);

        $this->assertActivationFails($event, $admin, 'Rekening event belum diverifikasi.');
    }

    public function test_missing_bank_book_file_blocks_activation(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);

        $this->seedActivationReadyState($tenant, $event, [
            'files' => [
                'bank_book' => false,
            ],
        ]);

        $this->assertActivationFails($event, $admin, 'File buku rekening fisik tidak ditemukan.');
    }

    public function test_unverified_organizer_letter_blocks_activation(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);

        $this->seedActivationReadyState($tenant, $event, [
            'letter' => ['status' => 'pending'],
        ]);

        $this->assertActivationFails($event, $admin, 'Surat penyelenggara belum diverifikasi.');
    }

    public function test_missing_organizer_letter_file_blocks_activation(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);

        $this->seedActivationReadyState($tenant, $event, [
            'files' => [
                'organizer_letter' => false,
            ],
        ]);

        $this->assertActivationFails($event, $admin, 'File surat penyelenggara fisik tidak ditemukan.');
    }

    public function test_invalid_payment_configuration_blocks_activation(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);

        $this->seedActivationReadyState($tenant, $event, [
            'event_gateway' => ['fee_mode' => 'broken'],
        ]);

        $this->assertActivationFails($event, $admin, 'Konfigurasi payment event belum valid.');
    }

    public function test_no_effective_active_payment_method_blocks_activation(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);

        $this->seedActivationReadyState($tenant, $event, [
            'event_gateway' => ['is_active' => false],
        ]);

        $this->assertActivationFails($event, $admin, 'Belum ada payment gateway event yang efektif aktif.');
    }

    public function test_tenant_cannot_activate_via_dashboard_livewire(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->seedActivationReadyState($tenant, $event);

        Livewire::actingAs($tenant)
            ->test(DashboardEventIndex::class)
            ->call('toggleStatus', $event->uid)
            ->assertForbidden();

        $this->assertSame('inactive', $event->fresh()->status);
    }

    public function test_staff_cannot_activate_via_dashboard_livewire(): void
    {
        $tenant = $this->tenant();
        $staff = $this->user([
            'email' => 'staff-activation@example.test',
            'role' => 'staff',
            'parent_uid' => $tenant->uid,
        ]);
        $event = $this->event($tenant);
        $this->seedActivationReadyState($tenant, $event);

        Livewire::actingAs($staff)
            ->test(DashboardEventIndex::class)
            ->call('toggleStatus', $event->uid)
            ->assertForbidden();

        $this->assertSame('inactive', $event->fresh()->status);
    }

    public function test_legacy_tenant_route_cannot_bypass_activation_guard(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->seedActivationReadyState($tenant, $event);

        $this->actingAs($tenant)
            ->post('/dashboard/old/event/toggle-status/'.$event->uid)
            ->assertForbidden();

        $this->assertSame('inactive', $event->fresh()->status);
    }

    public function test_confirm_event_failure_keeps_status_and_confirmation_unchanged(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['status' => 'inactive', 'konfirmasi' => null]);

        $this->seedActivationReadyState($tenant, $event, [
            'agreement' => ['status' => Agreement::STATUS_READY],
        ]);

        Livewire::actingAs($admin)
            ->test(AdminEventIndex::class)
            ->call('confirmEvent', $event->uid);

        $event->refresh();

        $this->assertSame('inactive', $event->status);
        $this->assertNull($event->konfirmasi);
    }

    public function test_existing_active_to_close_behavior_still_works(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'status' => 'active',
            'konfirmasi' => '1',
        ]);

        Livewire::actingAs($tenant)
            ->test(DashboardEventIndex::class)
            ->call('toggleStatus', $event->uid);

        $this->assertSame('close', $event->fresh()->status);
        $this->assertSame('1', $event->fresh()->konfirmasi);
    }

    public function test_existing_active_legacy_event_is_not_changed_automatically(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'status' => 'active',
            'konfirmasi' => '1',
        ]);

        $evaluation = app(EventActivationGuardService::class)->evaluateForEvent($event);

        $this->assertFalse($evaluation['can_activate']);
        $this->assertSame('active', $event->fresh()->status);
        $this->assertSame('1', $event->fresh()->konfirmasi);
    }

    public function test_checkout_rejects_inactive_event_server_side(): void
    {
        $tenant = $this->tenant();
        $buyer = $this->user(['email' => 'buyer-inactive@example.test']);
        $event = $this->event($tenant, ['status' => 'inactive', 'konfirmasi' => null]);
        $ticket = $this->ticket($event);

        try {
            app(TicketReservationService::class)->reserve($event, $buyer->uid, [
                ['harga_id' => $ticket->id, 'quantity' => 1],
            ]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Event tidak aktif atau belum dikonfirmasi.', collect($e->errors())->flatten()->first());
        }
    }

    public function test_checkout_existing_active_event_without_agreement_still_works(): void
    {
        $tenant = $this->tenant();
        $buyer = $this->user(['email' => 'buyer-active@example.test']);
        $event = $this->event($tenant, ['status' => 'active', 'konfirmasi' => '1']);
        $ticket = $this->ticket($event);

        $cart = app(TicketReservationService::class)->reserve($event, $buyer->uid, [
            ['harga_id' => $ticket->id, 'quantity' => 1],
        ]);

        $this->assertSame($event->uid, $cart->event_uid);
        $this->assertSame(Cart::STATUS_RESERVED, $cart->status);
    }

    public function test_activation_isolated_per_event(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $eventA = $this->event($tenant, ['event' => 'Activation A']);
        $eventB = $this->event($tenant, ['event' => 'Activation B']);

        $this->seedActivationReadyState($tenant, $eventA);
        $agreementB = $this->seedActivationReadyState($tenant, $eventB, [
            'agreement' => ['status' => Agreement::STATUS_READY],
        ]);
        $beforeB = $agreementB->refresh()->toArray();

        app(EventActivationGuardService::class)->activateForEvent($eventA, $admin->uid, true);

        $eventB->refresh();
        $afterB = $agreementB->refresh()->toArray();

        $this->assertSame('active', $eventA->fresh()->status);
        $this->assertSame('inactive', $eventB->status);
        $this->assertSame($beforeB['status'], $afterB['status']);
        $this->assertSame($beforeB['signed_review_status'], $afterB['signed_review_status']);
        $this->assertSame($beforeB['signed_pdf_path'], $afterB['signed_pdf_path']);
    }

    public function test_activation_does_not_change_agreement_snapshots_or_review_metadata(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $agreement = $this->seedActivationReadyState($tenant, $event, [
            'agreement' => [
                'event_snapshot' => ['event_name' => 'Snapshot Event'],
                'party_snapshot' => ['organizer_name' => 'PT Snapshot'],
                'bank_snapshot' => ['bank_name' => 'Bank Snapshot'],
                'document_snapshot' => ['document_number' => 'DOC-SNAPSHOT'],
                'commercial_snapshot' => ['buyer_fee' => ['mode' => 'percent', 'value' => 10.0]],
                'privy_document_id' => 'privy-doc-001',
                'privy_status' => 'completed',
                'privy_reference' => 'privy-ref-001',
                'sent_to_privy_at' => now()->subDay(),
                'signed_verified_by' => $admin->uid,
                'signed_verified_at' => now()->subHour(),
            ],
        ]);

        $before = $agreement->refresh()->toArray();

        app(EventActivationGuardService::class)->activateForEvent($event, $admin->uid, true);

        $after = $agreement->refresh()->toArray();

        $this->assertSame($before['event_snapshot'], $after['event_snapshot']);
        $this->assertSame($before['party_snapshot'], $after['party_snapshot']);
        $this->assertSame($before['bank_snapshot'], $after['bank_snapshot']);
        $this->assertSame($before['document_snapshot'], $after['document_snapshot']);
        $this->assertSame($before['commercial_snapshot'], $after['commercial_snapshot']);
        $this->assertSame($before['unsigned_pdf_path'], $after['unsigned_pdf_path']);
        $this->assertSame($before['signed_pdf_path'], $after['signed_pdf_path']);
        $this->assertSame($before['signed_review_status'], $after['signed_review_status']);
        $this->assertSame($before['signed_verified_by'], $after['signed_verified_by']);
        $this->assertSame($before['signed_verified_at'], $after['signed_verified_at']);
        $this->assertSame($before['signed_rejection_reason'], $after['signed_rejection_reason']);
        $this->assertSame($before['privy_document_id'], $after['privy_document_id']);
        $this->assertSame($before['privy_status'], $after['privy_status']);
        $this->assertSame($before['privy_reference'], $after['privy_reference']);
        $this->assertSame($before['sent_to_privy_at'], $after['sent_to_privy_at']);
    }

    public function test_legacy_tenant_edit_event_cannot_activate_inactive_event(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['status' => 'inactive']);
        $this->eventDate($event);

        $response = $this->actingAs($tenant)
            ->post('/dashboard/old/editEventPenyewa', [
                'uid' => $event->uid,
                'event' => 'Attempted Activation',
                'alamat' => 'Alamat Event',
                'tanggal' => '2026-09-10 19:00:00',
                'fee' => 10,
                'start' => '2026-09-10 19:00',
                'end' => '2026-09-10 22:00',
                'status' => 'active',
                'deskripsi' => 'Deskripsi',
                'map' => 'https://map.test',
            ]);

        $response->assertForbidden();
        $this->assertSame('inactive', $event->fresh()->status);
    }

    public function test_legacy_admin_edit_event_cannot_activate_when_prerequisites_fail(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['status' => 'inactive', 'konfirmasi' => null]);

        $this->seedActivationReadyState($tenant, $event, [
            'agreement' => ['status' => Agreement::STATUS_DRAFT],
        ]);

        $response = $this->actingAs($admin)
            ->post('/admin/old/editEvent', [
                'uid' => $event->uid,
                'event' => $event->event,
                'alamat' => $event->alamat,
                'tanggal' => $event->tanggal,
                'fee' => 10,
                'status' => 'active',
                'deskripsi' => $event->deskripsi,
                'map' => $event->map,
            ]);

        $response->assertRedirect('/admin/event/eventDetail/' . $event->uid);
        $event->refresh();
        $this->assertSame('inactive', $event->status);
        $this->assertNull($event->konfirmasi);
    }

    public function test_legacy_admin_edit_event_activates_via_guard_when_prerequisites_valid(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['status' => 'inactive', 'konfirmasi' => null]);

        $this->seedActivationReadyState($tenant, $event);

        $response = $this->actingAs($admin)
            ->post('/admin/old/editEvent', [
                'uid' => $event->uid,
                'event' => $event->event,
                'alamat' => $event->alamat,
                'tanggal' => $event->tanggal,
                'fee' => 10,
                'status' => 'active',
                'deskripsi' => $event->deskripsi,
                'map' => $event->map,
            ]);

        $response->assertRedirect('/admin/event/eventDetail/' . $event->uid);
        $event->refresh();
        $this->assertSame('active', $event->status);
    }

    public function test_legacy_admin_setujui_event_cannot_bypass_prerequisites(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['status' => 'inactive', 'konfirmasi' => null]);

        $this->seedActivationReadyState($tenant, $event, [
            'agreement' => ['status' => Agreement::STATUS_DRAFT],
        ]);

        $this->actingAs($admin)
            ->get('/admin/old/setujuiEvent/' . $event->uid);

        $event->refresh();
        $this->assertSame('inactive', $event->status);
        $this->assertNull($event->konfirmasi);

        $eventReady = $this->event($tenant, ['status' => 'inactive', 'konfirmasi' => null]);
        $this->seedActivationReadyState($tenant, $eventReady);

        $this->actingAs($admin)
            ->get('/admin/old/setujuiEvent/' . $eventReady->uid);

        $eventReady->refresh();
        $this->assertSame('active', $eventReady->status);
        $this->assertSame('1', $eventReady->konfirmasi);
    }

    public function test_legacy_edit_event_active_to_close_behavior_is_preserved(): void
    {
        $tenant = $this->tenant();
        $eventTenant = $this->event($tenant, ['status' => 'active', 'konfirmasi' => '1']);
        $this->eventDate($eventTenant);

        $this->actingAs($tenant)
            ->post('/dashboard/old/editEventPenyewa', [
                'uid' => $eventTenant->uid,
                'event' => $eventTenant->event,
                'alamat' => $eventTenant->alamat,
                'tanggal' => '2026-09-10 19:00:00',
                'fee' => 10,
                'start' => '2026-09-10 19:00',
                'end' => '2026-09-10 22:00',
                'status' => 'close',
                'deskripsi' => $eventTenant->deskripsi,
                'map' => $eventTenant->map,
            ]);

        $this->assertSame('close', $eventTenant->fresh()->status);

        $admin = $this->admin();
        $eventAdmin = $this->event($tenant, ['status' => 'active', 'konfirmasi' => '1']);

        $this->actingAs($admin)
            ->post('/admin/old/editEvent', [
                'uid' => $eventAdmin->uid,
                'event' => $eventAdmin->event,
                'alamat' => $eventAdmin->alamat,
                'tanggal' => $eventAdmin->tanggal,
                'fee' => 10,
                'status' => 'close',
                'deskripsi' => $eventAdmin->deskripsi,
                'map' => $eventAdmin->map,
            ]);

        $this->assertSame('close', $eventAdmin->fresh()->status);
    }

    private function assertActivationFails(Event $event, User $admin, string $message): void
    {
        try {
            app(EventActivationGuardService::class)->activateForEvent($event, $admin->uid, true);
            $this->fail('Expected LogicException was not thrown.');
        } catch (LogicException $e) {
            $this->assertStringContainsString($message, $e->getMessage());
        }
    }

    private function admin(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Admin Activation',
            'email' => 'admin-activation@example.test',
            'role' => 'admin',
        ], $overrides));
    }

    private function tenant(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Tenant Activation',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'User Activation',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat User',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'parent_uid' => null,
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function event(User $tenant, array $overrides = []): Event
    {
        $uid = $overrides['uid'] ?? (string) Str::uuid();

        return Event::create(array_merge([
            'category_id' => null,
            'uid' => $uid,
            'user_uid' => $tenant->uid,
            'event' => 'Activation Event '.$uid,
            'alamat' => 'Alamat Event',
            'tanggal' => '2026-09-10 19:00:00',
            'event_end' => '2026-09-10 22:00:00',
            'venue_name' => 'Venue Event',
            'venue_address' => 'Jl. Event',
            'venue_city' => 'Jakarta',
            'venue_province' => 'DKI Jakarta',
            'status' => 'inactive',
            'cover' => 'event.jpg',
            'fee' => 10,
            'pajak' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => 'https://maps.example.test/event',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'activation-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
        ], $overrides));
    }

    private function eventDate(Event $event, array $overrides = []): EventDate
    {
        return EventDate::create(array_merge([
            'uid' => $event->uid,
            'start' => '2026-09-10 19:00',
            'end' => '2026-09-10 22:00',
        ], $overrides));
    }

    private function ticket(Event $event, array $overrides = []): Harga
    {
        return Harga::create(array_merge([
            'uid' => $event->uid,
            'kategori' => 'Regular',
            'qty' => 100,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => 150000,
            'status' => 'active',
        ], $overrides));
    }

    private function seedActivationReadyState(User $tenant, Event $event, array $overrides = []): Agreement
    {
        $uid = $overrides['agreement']['uid'] ?? (string) Str::uuid();
        $bankPath = self::DIR.'/'.$event->uid.'/bank-book.pdf';
        $letterPath = self::DIR.'/'.$event->uid.'/organizer-letter.pdf';
        $unsignedPath = self::DIR.'/'.$uid.'/unsigned.pdf';
        $signedPath = self::DIR.'/'.$uid.'/signed.pdf';

        EventOrganizer::create(array_merge([
            'event_uid' => $event->uid,
            'organizer_name' => 'PT Activation Organizer',
            'responsible_name' => 'Budi',
            'responsible_position' => 'Direktur',
            'phone' => '08123456789',
            'email' => 'organizer@example.test',
            'address' => 'Jl. Organizer',
        ], $overrides['organizer'] ?? []));

        EventBankAccount::create(array_merge([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Activation',
            'account_number' => '1234567890',
            'account_holder_name' => 'PT Activation Organizer',
            'bank_book_path' => $bankPath,
            'bank_book_original_name' => 'bank-book.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'verified',
            'verified_by' => $this->admin()->uid,
            'verified_at' => now()->subDay(),
            'rejection_reason' => null,
        ], $overrides['bank'] ?? []));

        EventDocument::create(array_merge([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-ACT-001',
            'document_date' => '2026-08-20',
            'original_name' => 'organizer-letter.pdf',
            'file_path' => $letterPath,
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_by' => $this->admin()->uid,
            'verified_at' => now()->subDay(),
            'rejection_reason' => null,
        ], $overrides['letter'] ?? []));

        $paymentGateway = PaymentGateway::create(array_merge([
            'payment' => 'BCA Virtual Account',
            'category' => 'bank_transfer',
            'biaya' => 0,
            'biaya_type' => 'fixed',
            'default_fee_fixed' => 4000,
            'default_fee_percent' => 0,
            'midtrans_code' => 'bca_va',
            'icon' => 'bca.png',
            'is_active' => true,
            'slug' => 'bca-va-'.Str::lower(Str::random(6)),
        ], $overrides['gateway'] ?? []));

        EventPaymentGateway::create(array_merge([
            'event_id' => $event->id,
            'payment_gateway_id' => $paymentGateway->id,
            'is_active' => true,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
            'fee_fixed' => null,
            'fee_percent' => null,
        ], $overrides['event_gateway'] ?? []));

        $agreement = Agreement::create(array_merge([
            'uid' => $uid,
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
            'version' => 1,
            'status' => Agreement::STATUS_COMPLETED,
            'created_by' => $tenant->uid,
            'template_version' => 'mou-v1',
            'event_snapshot' => ['event_name' => $event->event],
            'party_snapshot' => ['organizer_name' => 'PT Activation Organizer'],
            'bank_snapshot' => ['bank_name' => 'Bank Activation'],
            'document_snapshot' => ['document_number' => 'DOC-ACT-001'],
            'commercial_snapshot' => ['buyer_fee' => ['mode' => 'percent', 'value' => 10.0]],
            'document_number' => 'MOU-ACT-001',
            'unsigned_pdf_path' => $unsignedPath,
            'signed_pdf_path' => $signedPath,
            'signed_review_status' => Agreement::SIGNED_REVIEW_VERIFIED,
            'signed_verified_by' => $this->admin()->uid,
            'signed_verified_at' => now()->subHour(),
            'signed_rejection_reason' => null,
            'privy_document_id' => null,
            'privy_status' => null,
            'privy_reference' => null,
            'sent_to_privy_at' => null,
            'signed_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ], $overrides['agreement'] ?? []));

        $files = array_merge([
            'bank_book' => true,
            'organizer_letter' => true,
            'signed_pdf' => true,
            'unsigned_pdf' => true,
        ], $overrides['files'] ?? []);

        if ($files['bank_book']) {
            Storage::disk('local')->put($bankPath, '%PDF-1.4 bank');
        }

        if ($files['organizer_letter']) {
            Storage::disk('local')->put($letterPath, '%PDF-1.4 letter');
        }

        if ($files['signed_pdf']) {
            Storage::disk('local')->put($signedPath, '%PDF-1.4 signed');
        }

        if ($files['unsigned_pdf']) {
            Storage::disk('local')->put($unsignedPath, '%PDF-1.4 unsigned');
        }

        return $agreement;
    }

    private function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('name');
            $table->string('email');
            $table->string('gambar')->nullable();
            $table->string('nomor');
            $table->string('birthday');
            $table->string('gender');
            $table->string('kota');
            $table->string('alamat');
            $table->string('role');
            $table->string('parent_uid')->nullable();
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
            $table->string('event_end')->nullable();
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('venue_city')->nullable();
            $table->string('venue_province')->nullable();
            $table->string('status')->default('inactive');
            $table->string('cover');
            $table->unsignedBigInteger('fee')->default(0);
            $table->text('deskripsi');
            $table->text('map')->nullable();
            $table->unsignedBigInteger('pajak')->default(0);
            $table->string('start_sale')->nullable();
            $table->string('slug')->nullable();
            $table->string('konfirmasi')->nullable();
            $table->boolean('payment_otp_enabled')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('event_organizers', function ($table) {
            $table->id();
            $table->string('event_uid');
            $table->string('organizer_name');
            $table->string('responsible_name');
            $table->string('responsible_position');
            $table->string('phone');
            $table->string('email');
            $table->text('address');
            $table->timestamps();
        });

        Schema::create('event_bank_accounts', function ($table) {
            $table->id();
            $table->string('event_uid');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('bank_book_path')->nullable();
            $table->string('bank_book_original_name')->nullable();
            $table->string('bank_book_mime')->nullable();
            $table->string('status')->default('pending');
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('event_documents', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('event_uid');
            $table->string('document_type');
            $table->string('document_number')->nullable();
            $table->date('document_date')->nullable();
            $table->string('original_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('status')->default('pending');
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_gateways', function ($table) {
            $table->id();
            $table->string('payment');
            $table->string('category');
            $table->decimal('biaya', 15, 2)->default(0);
            $table->string('biaya_type')->default('fixed');
            $table->decimal('default_fee_fixed', 15, 2)->nullable();
            $table->decimal('default_fee_percent', 8, 4)->nullable();
            $table->string('midtrans_code')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('event_payment_gateways', function ($table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('payment_gateway_id');
            $table->boolean('is_active')->default(true);
            $table->string('fee_mode')->default(EventPaymentGateway::FEE_MODE_GLOBAL);
            $table->decimal('fee_fixed', 15, 2)->nullable();
            $table->decimal('fee_percent', 8, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('agreements', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('event_uid');
            $table->string('tenant_user_uid');
            $table->string('type')->default('mou');
            $table->string('document_number')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('DRAFT');
            $table->string('template_version')->nullable();
            $table->text('event_snapshot')->nullable();
            $table->text('party_snapshot')->nullable();
            $table->text('bank_snapshot')->nullable();
            $table->text('document_snapshot')->nullable();
            $table->text('commercial_snapshot')->nullable();
            $table->string('privy_document_id')->nullable();
            $table->string('privy_status')->nullable();
            $table->string('privy_reference')->nullable();
            $table->string('unsigned_pdf_path')->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->string('signed_review_status')->nullable();
            $table->string('signed_verified_by')->nullable();
            $table->timestamp('signed_verified_at')->nullable();
            $table->text('signed_rejection_reason')->nullable();
            $table->timestamp('sent_to_privy_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('created_by')->nullable();
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
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('reservation_released_at')->nullable();
            $table->text('review_reason')->nullable();
            $table->unsignedBigInteger('gross_amount')->default(0);
            $table->unsignedBigInteger('internet_fee')->default(0);
            $table->unsignedBigInteger('pajak')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->unsignedBigInteger('harga_id')->nullable();
            $table->unsignedInteger('orderBy')->nullable();
            $table->string('event_uid')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedBigInteger('harga_ticket')->default(0);
            $table->string('voucher')->nullable();
            $table->unsignedBigInteger('disc')->default(0);
            $table->string('kategori_harga')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('event_dates', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('start');
            $table->string('end');
            $table->timestamps();
        });
    }
}
