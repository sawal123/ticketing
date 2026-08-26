<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Admin\EventDetail;
use App\Models\Agreement;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventBankAccount;
use App\Models\EventDocument;
use App\Models\EventOrganizer;
use App\Models\EventPaymentGateway;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\Agreements\AgreementFinalizationService;
use App\Services\Agreements\AgreementReviewService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class AgreementFinalizationTest extends TestCase
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
        Storage::fake('local');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '', 'icon' => '']]);
    }

    public function test_admin_can_finalize_mou_when_readiness_is_true(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'event' => 'Finalization Konser',
            'status' => 'inactive',
        ]);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'review-mou')
            ->call('finalizeAgreement')
            ->assertHasNoErrors();

        $agreement->refresh();
        $event->refresh();

        $this->assertSame(Agreement::STATUS_READY, $agreement->status);
        $this->assertSame(1, (int) $agreement->version);
        $this->assertSame(AgreementFinalizationService::TEMPLATE_VERSION, $agreement->template_version);
        $this->assertNotNull($agreement->event_snapshot);
        $this->assertNotNull($agreement->party_snapshot);
        $this->assertNotNull($agreement->bank_snapshot);
        $this->assertNotNull($agreement->document_snapshot);
        $this->assertNotNull($agreement->commercial_snapshot);
        $this->assertNotNull($agreement->unsigned_pdf_path);
        $this->assertTrue(Storage::disk('local')->exists($agreement->unsigned_pdf_path));
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($agreement->unsigned_pdf_path));
        $this->assertSame('inactive', $event->status);
    }

    public function test_non_admin_cannot_trigger_finalization_action(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('finalizeAgreement')
            ->assertStatus(403);

        $agreement->refresh();

        $this->assertSame(Agreement::STATUS_DRAFT, $agreement->status);
        $this->assertNull($agreement->event_snapshot);
        $this->assertNull($agreement->unsigned_pdf_path);
    }

    public function test_finalization_is_aborted_when_bank_is_pending(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->bankAccount($event, ['status' => 'pending']);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);

        $this->assertFalse($result['ok']);
        $this->assertSame('not_ready', $result['reason']);
        $this->assertSame('Event belum memenuhi syarat finalisasi MOU.', $result['message']);
        $this->assertContains('Rekening event belum diverifikasi.', $result['blocking_reasons']);

        $agreement->refresh();

        $this->assertSame(Agreement::STATUS_DRAFT, $agreement->status);
        $this->assertNull($agreement->event_snapshot);
        $this->assertNull($agreement->party_snapshot);
        $this->assertNull($agreement->bank_snapshot);
        $this->assertNull($agreement->document_snapshot);
        $this->assertNull($agreement->commercial_snapshot);
        $this->assertNull($agreement->unsigned_pdf_path);
        $this->assertNull($agreement->template_version);
        $this->assertFalse(Storage::disk('local')->exists('private/agreements/'.$agreement->uid.'/unsigned.pdf'));
    }

    public function test_finalization_is_rejected_when_organizer_document_or_payment_invalid(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();

        // 3a. Organizer missing entirely.
        $eventA = $this->event($tenant, ['event' => 'Finalization A']);
        $this->verifiedBankAccount($eventA);
        $this->verifiedOrganizerLetter($eventA);
        $agreementA = $this->agreement($tenant, $eventA);
        $gatewayA = $this->gateway();
        $this->eventGateway($eventA, $gatewayA, ['is_active' => true]);

        $resultA = app(AgreementFinalizationService::class)->finalizeForEvent($eventA, $admin->uid);
        $this->assertFalse($resultA['ok']);
        $this->assertSame('not_ready', $resultA['reason']);
        $this->assertSame(Agreement::STATUS_DRAFT, $agreementA->refresh()->status);

        // 3b. Organizer letter pending.
        $eventB = $this->event($tenant, ['event' => 'Finalization B']);
        $this->organizer($eventB);
        $this->verifiedBankAccount($eventB);
        $this->organizerLetter($eventB, ['status' => 'pending']);
        $agreementB = $this->agreement($tenant, $eventB);
        $gatewayB = $this->gateway();
        $this->eventGateway($eventB, $gatewayB, ['is_active' => true]);

        $resultB = app(AgreementFinalizationService::class)->finalizeForEvent($eventB, $admin->uid);
        $this->assertFalse($resultB['ok']);
        $this->assertSame('not_ready', $resultB['reason']);
        $this->assertContains('Surat penyelenggara belum diverifikasi.', $resultB['blocking_reasons']);
        $this->assertSame(Agreement::STATUS_DRAFT, $agreementB->refresh()->status);

        // 3c. No effective active payment gateway.
        $eventC = $this->event($tenant, ['event' => 'Finalization C']);
        $this->organizer($eventC);
        $this->verifiedBankAccount($eventC);
        $this->verifiedOrganizerLetter($eventC);
        $agreementC = $this->agreement($tenant, $eventC);
        $gatewayC = $this->gateway(['is_active' => false, 'payment' => 'Global Inactive C']);
        $this->eventGateway($eventC, $gatewayC, ['is_active' => true]);

        $resultC = app(AgreementFinalizationService::class)->finalizeForEvent($eventC, $admin->uid);
        $this->assertFalse($resultC['ok']);
        $this->assertSame('not_ready', $resultC['reason']);
        $this->assertContains('Belum ada payment gateway event yang efektif aktif.', $resultC['blocking_reasons']);
        $this->assertSame(Agreement::STATUS_DRAFT, $agreementC->refresh()->status);
    }

    public function test_snapshot_is_frozen_after_finalization(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'event' => 'Nama Frozen Lama',
            'fee' => 10,
        ]);
        $organizer = $this->organizer($event, ['organizer_name' => 'PT Organizer Lama']);
        $bankAccount = $this->verifiedBankAccount($event, ['bank_name' => 'Bank Lama']);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway(['default_fee_fixed' => 2000, 'default_fee_percent' => 2]);
        $eventGateway = $this->eventGateway($event, $gateway, ['is_active' => true]);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);
        $this->assertTrue($result['ok']);

        $event->update(['event' => 'Nama Frozen Baru']);
        $organizer->update(['organizer_name' => 'PT Organizer Baru']);
        $bankAccount->update(['bank_name' => 'Bank Baru', 'account_number' => '999999']);
        $eventGateway->update([
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 9999,
            'fee_percent' => 9,
        ]);

        $agreement->refresh();

        $this->assertSame('Nama Frozen Lama', $agreement->event_snapshot['event_name']);
        $this->assertSame('PT Organizer Lama', $agreement->party_snapshot['organizer_name']);
        $this->assertSame('Bank Lama', $agreement->bank_snapshot['bank_name']);
        $this->assertSame('1234567890', $agreement->bank_snapshot['account_number']);

        $commercial = $agreement->commercial_snapshot;
        $this->assertSame('percent', $commercial['buyer_fee']['mode']);
        $this->assertEquals(10.0, $commercial['buyer_fee']['value']);

        $gatewaySnapshot = $commercial['payment_gateways'][0];
        $this->assertSame('global', $gatewaySnapshot['fee_mode']);
        $this->assertSame('2000.00', $gatewaySnapshot['resolved_fee_fixed']);
        $this->assertSame('2', $gatewaySnapshot['resolved_fee_percent']);
    }

    public function test_pdf_is_authoritative_and_not_regenerated_from_live_data(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['event' => 'Frozen PDF Event']);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);
        $this->assertTrue($result['ok']);

        $agreement->refresh();
        $path = $agreement->unsigned_pdf_path;
        $originalPdf = Storage::disk('local')->get($path);
        $this->assertStringStartsWith('%PDF', $originalPdf);

        $event->update(['event' => 'Mutated Live Event Name']);

        // Downloading the existing PDF returns the identical frozen bytes.
        $response = $this->actingAs($admin)
            ->get(route('admin.event.review.mou.unsigned', $event->uid));
        $response->assertOk();
        $this->assertSame($originalPdf, $response->streamedContent());
        $this->assertSame($originalPdf, Storage::disk('local')->get($path));

        // The snapshot keeps the frozen name.
        $this->assertSame('Frozen PDF Event', $agreement->refresh()->event_snapshot['event_name']);

        // The PDF blade renders purely from the snapshot payload.
        $payload = app(AgreementFinalizationService::class)->pdfPayloadForAgreement($agreement);
        $html = view('agreements.mou-pdf', ['payload' => $payload])->render();
        $this->assertStringContainsString('Frozen PDF Event', $html);
        $this->assertStringNotContainsString('Mutated Live Event Name', $html);
    }

    public function test_commercial_snapshot_contains_resolved_values(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'event' => 'Komersial Finalization',
            'fee' => 11,
            'payment_otp_enabled' => true,
        ]);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway(['default_fee_fixed' => 2000, 'default_fee_percent' => 3]);
        $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 4500,
            'fee_percent' => 1.5,
            'is_active' => true,
        ]);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);
        $this->assertTrue($result['ok']);

        $agreement->refresh();
        $commercial = $agreement->commercial_snapshot;

        $this->assertSame('percent', $commercial['buyer_fee']['mode']);
        $this->assertEquals(11.0, $commercial['buyer_fee']['value']);
        $this->assertTrue($commercial['payment_otp_enabled']);
        $this->assertCount(1, $commercial['payment_gateways']);

        $gatewaySnapshot = $commercial['payment_gateways'][0];
        $this->assertSame($gateway->id, $gatewaySnapshot['payment_gateway_id']);
        $this->assertSame($gateway->payment, $gatewaySnapshot['payment']);
        $this->assertSame(EventPaymentGateway::FEE_MODE_MANUAL, $gatewaySnapshot['fee_mode']);
        $this->assertSame('4500.00', $gatewaySnapshot['resolved_fee_fixed']);
        $this->assertSame('1.5', $gatewaySnapshot['resolved_fee_percent']);
        $this->assertTrue($gatewaySnapshot['global_is_active']);
        $this->assertTrue($gatewaySnapshot['event_is_active']);
        $this->assertTrue($gatewaySnapshot['effective_is_active']);
    }

    public function test_legacy_gateway_fallback_resolves_like_checkout(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway([
            'payment' => 'Legacy Gateway Finalization',
            'biaya' => 4000,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => null,
            'default_fee_percent' => null,
        ]);
        $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
            'is_active' => true,
        ]);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);
        $this->assertTrue($result['ok']);

        $agreement->refresh();
        $gatewaySnapshot = $agreement->commercial_snapshot['payment_gateways'][0];

        $this->assertSame('4000.00', $gatewaySnapshot['resolved_fee_fixed']);
        $this->assertSame('0', $gatewaySnapshot['resolved_fee_percent']);
    }

    public function test_explicit_zero_gateway_defaults_do_not_fallback_to_legacy(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway([
            'payment' => 'Zero Default Finalization',
            'biaya' => 4000,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => 0,
            'default_fee_percent' => 0,
        ]);
        $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
            'is_active' => true,
        ]);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);
        $this->assertTrue($result['ok']);

        $agreement->refresh();
        $gatewaySnapshot = $agreement->commercial_snapshot['payment_gateways'][0];

        $this->assertSame('0.00', $gatewaySnapshot['resolved_fee_fixed']);
        $this->assertSame('0', $gatewaySnapshot['resolved_fee_percent']);
    }

    public function test_double_finalization_is_idempotent(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $first = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);
        $this->assertTrue($first['ok']);

        $agreement->refresh();
        $firstPath = $agreement->unsigned_pdf_path;
        $firstEventSnapshot = $agreement->event_snapshot;
        $firstPdf = Storage::disk('local')->get($firstPath);

        $second = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);
        $this->assertFalse($second['ok']);
        $this->assertSame('not_draft', $second['reason']);

        $agreement->refresh();

        $this->assertSame(Agreement::STATUS_READY, $agreement->status);
        $this->assertSame($firstPath, $agreement->unsigned_pdf_path);
        $this->assertSame($firstEventSnapshot, $agreement->event_snapshot);
        $this->assertSame($firstPdf, Storage::disk('local')->get($firstPath));
    }

    public function test_non_draft_agreement_cannot_be_finalized(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event, ['status' => Agreement::STATUS_READY]);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);

        $this->assertFalse($result['ok']);
        $this->assertSame('not_draft', $result['reason']);
        $this->assertSame(Agreement::STATUS_READY, $agreement->refresh()->status);
        $this->assertNull($agreement->refresh()->event_snapshot);
    }

    public function test_pdf_storage_failure_keeps_agreement_draft_and_no_orphan_pdf(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $realDisk = Storage::disk('local');
        $failing = new class($realDisk) {
            public function __construct(private $inner)
            {
            }

            public function exists(string $path): bool
            {
                return $this->inner->exists($path);
            }

            public function put(string $path, $contents, $options = []): bool
            {
                throw new RuntimeException('Simulated storage failure');
            }

            public function delete($paths): bool
            {
                return $this->inner->delete($paths);
            }

            public function get(string $path): ?string
            {
                return $this->inner->get($path);
            }

            public function readStream(string $path)
            {
                return $this->inner->readStream($path);
            }

            public function mimeType(string $path): string
            {
                return $this->inner->mimeType($path);
            }

            public function path(string $path): string
            {
                return $this->inner->path($path);
            }
        };

        Storage::shouldReceive('disk')->with('local')->andReturn($failing);

        try {
            app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Simulated storage failure', $e->getMessage());
        }

        $agreement->refresh();

        $this->assertSame(Agreement::STATUS_DRAFT, $agreement->status);
        $this->assertNull($agreement->event_snapshot);
        $this->assertNull($agreement->party_snapshot);
        $this->assertNull($agreement->bank_snapshot);
        $this->assertNull($agreement->document_snapshot);
        $this->assertNull($agreement->commercial_snapshot);
        $this->assertNull($agreement->unsigned_pdf_path);
        $this->assertFalse($realDisk->exists('private/agreements/'.$agreement->uid.'/unsigned.pdf'));
    }

    public function test_pdf_storage_return_false_keeps_agreement_draft_and_no_orphan_pdf(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $realDisk = Storage::disk('local');

        // The local disk is configured with 'throw' => false, so put() may
        // return false WITHOUT raising an exception. Simulate exactly that.
        $failing = new class($realDisk) {
            public function __construct(private $inner)
            {
            }

            public function exists(string $path): bool
            {
                return $this->inner->exists($path);
            }

            public function put(string $path, $contents, $options = []): bool
            {
                return false;
            }

            public function delete($paths): bool
            {
                return $this->inner->delete($paths);
            }

            public function get(string $path): ?string
            {
                return $this->inner->get($path);
            }

            public function readStream(string $path)
            {
                return $this->inner->readStream($path);
            }

            public function mimeType(string $path): string
            {
                return $this->inner->mimeType($path);
            }

            public function path(string $path): string
            {
                return $this->inner->path($path);
            }
        };

        Storage::shouldReceive('disk')->with('local')->andReturn($failing);

        try {
            app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Unsigned MOU PDF gagal disimpan.', $e->getMessage());
        }

        $agreement->refresh();

        $this->assertSame(Agreement::STATUS_DRAFT, $agreement->status);
        $this->assertNull($agreement->event_snapshot);
        $this->assertNull($agreement->party_snapshot);
        $this->assertNull($agreement->bank_snapshot);
        $this->assertNull($agreement->document_snapshot);
        $this->assertNull($agreement->commercial_snapshot);
        $this->assertNull($agreement->unsigned_pdf_path);
        $this->assertFalse($realDisk->exists('private/agreements/'.$agreement->uid.'/unsigned.pdf'));
    }

    public function test_db_failure_after_pdf_write_cleans_up_new_file(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        // Breaking tenant/owner match makes the agreement UPDATE fail after the
        // PDF file has already been written, simulating a DB write failure.
        $otherTenant = $this->tenant(['email' => 'other-owner@example.test']);
        $event->update(['user_uid' => $otherTenant->uid]);

        try {
            app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);
            $this->fail('Expected LogicException was not thrown.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('Agreement tenant', $e->getMessage());
        }

        $agreement->refresh();

        $this->assertSame(Agreement::STATUS_DRAFT, $agreement->status);
        $this->assertNull($agreement->unsigned_pdf_path);
        $this->assertNull($agreement->event_snapshot);
        $this->assertNull($agreement->party_snapshot);
        $this->assertNull($agreement->bank_snapshot);
        $this->assertNull($agreement->document_snapshot);
        $this->assertNull($agreement->commercial_snapshot);
        $this->assertFalse(Storage::disk('local')->exists('private/agreements/'.$agreement->uid.'/unsigned.pdf'));
    }

    public function test_private_unsigned_pdf_route_access_control(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);
        $this->assertTrue($result['ok']);
        $agreement->refresh();

        // Admin can stream the unsigned PDF inline.
        $response = $this->actingAs($admin)
            ->get(route('admin.event.review.mou.unsigned', $event->uid));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->streamedContent());

        // Penyewa cannot access.
        $this->actingAs($tenant)
            ->get(route('admin.event.review.mou.unsigned', $event->uid))
            ->assertRedirect('/');

        // Staff cannot access.
        $staff = $this->user([
            'email' => 'staff-finalization@example.test',
            'role' => 'staff',
            'parent_uid' => $tenant->uid,
        ]);
        $this->actingAs($staff)
            ->get(route('admin.event.review.mou.unsigned', $event->uid))
            ->assertRedirect('/');

        // Missing file / missing path resolves to 404.
        $eventMissing = $this->event($tenant, ['event' => 'Missing PDF Event']);
        $this->agreement($tenant, $eventMissing, [
            'status' => Agreement::STATUS_READY,
            'unsigned_pdf_path' => 'private/agreements/missing/unsigned.pdf',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.event.review.mou.unsigned', $eventMissing->uid))
            ->assertNotFound();
    }

    public function test_review_html_and_pdf_response_do_not_expose_physical_paths(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $bankAccount = $this->verifiedBankAccount($event);
        $document = $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);
        $this->assertTrue($result['ok']);
        $agreement->refresh();

        $this->actingAs($admin)
            ->get(route('admin.event.detail', $event->uid).'?activeTab=review-mou')
            ->assertOk()
            ->assertSeeText(Agreement::STATUS_READY)
            ->assertDontSeeText($agreement->unsigned_pdf_path)
            ->assertDontSeeText($bankAccount->bank_book_path)
            ->assertDontSeeText($document->file_path)
            ->assertDontSeeText('storage/app/private');

        $response = $this->actingAs($admin)
            ->get(route('admin.event.review.mou.unsigned', $event->uid));
        $response->assertOk();
        $this->assertStringNotContainsString($agreement->unsigned_pdf_path, (string) $response->headers->get('content-disposition'));
        $this->assertStringNotContainsString('storage/app/private', $response->streamedContent());
    }

    public function test_completed_agreement_cannot_be_finalized(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event, ['status' => Agreement::STATUS_COMPLETED]);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);

        $this->assertFalse($result['ok']);
        $this->assertSame('not_draft', $result['reason']);
        $this->assertSame(Agreement::STATUS_COMPLETED, $agreement->refresh()->status);
        $this->assertNull($agreement->refresh()->unsigned_pdf_path);
    }

    public function test_event_remains_inactive_after_ready(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['status' => 'inactive']);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);

        $this->assertTrue($result['ok']);
        $this->assertSame(Agreement::STATUS_READY, $event->fresh()->currentMouAgreement->status);
        $this->assertSame('inactive', $event->fresh()->status);
    }

    public function test_review_reports_ready_after_finalization(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid);

        $review = app(AgreementReviewService::class)->buildForEvent($event->fresh());

        $this->assertTrue($review['is_ready']);
        $this->assertSame('SIAP FINALISASI', $review['status_label']);
    }

    private function admin(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Admin Finalization',
            'email' => 'admin-finalization@example.test',
            'role' => 'admin',
        ], $overrides));
    }

    private function tenant(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Tenant Finalization',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Finalization User',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat Finalization User',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('nomor')->nullable();
            $table->string('birthday')->nullable();
            $table->string('gender')->nullable();
            $table->string('kota')->nullable();
            $table->string('alamat')->nullable();
            $table->string('gambar')->nullable();
            $table->string('role')->nullable();
            $table->string('parent_uid')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categories', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('event');
            $table->string('alamat');
            $table->string('tanggal');
            $table->string('event_end')->nullable();
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('venue_city')->nullable();
            $table->string('venue_province')->nullable();
            $table->string('status');
            $table->string('cover')->nullable();
            $table->unsignedBigInteger('fee')->default(0);
            $table->text('deskripsi')->nullable();
            $table->text('map')->nullable();
            $table->unsignedBigInteger('pajak')->default(0);
            $table->string('start_sale')->nullable();
            $table->string('slug')->nullable();
            $table->string('konfirmasi')->nullable();
            $table->boolean('payment_otp_enabled')->default(false);
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
            $table->unsignedBigInteger('harga')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid')->nullable();
            $table->string('event_uid');
            $table->string('invoice')->nullable();
            $table->string('status');
            $table->string('payment_type')->nullable();
            $table->unsignedBigInteger('internet_fee')->default(0);
            $table->unsignedBigInteger('pajak')->default(0);
            $table->string('konfirmasi')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->unsignedBigInteger('harga_id')->nullable();
            $table->string('uid');
            $table->string('event_uid')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedBigInteger('harga_ticket')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('event_organizers', function ($table) {
            $table->id();
            $table->string('event_uid')->unique();
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
            $table->string('event_uid')->unique();
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_holder_name');
            $table->string('bank_book_path')->nullable();
            $table->string('bank_book_original_name')->nullable();
            $table->string('bank_book_mime')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('event_documents', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('event_uid');
            $table->string('document_type');
            $table->string('document_number');
            $table->date('document_date');
            $table->string('original_name');
            $table->string('file_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('status')->default('pending');
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
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

        Schema::create('payment_gateways', function ($table) {
            $table->id();
            $table->string('payment');
            $table->string('category');
            $table->decimal('biaya', 15, 2)->default(0);
            $table->string('biaya_type');
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
            $table->boolean('is_active')->default(false);
            $table->string('fee_mode')->default('global');
            $table->decimal('fee_fixed', 15, 2)->nullable();
            $table->decimal('fee_percent', 8, 4)->nullable();
            $table->timestamps();
        });
    }

    private function event(User $tenant, array $overrides = []): Event
    {
        $category = Category::create([
            'name' => 'Category '.Str::random(6),
            'slug' => 'category-'.Str::lower(Str::random(8)),
        ]);
        $uid = (string) Str::uuid();

        return Event::create(array_merge([
            'uid' => $uid,
            'category_id' => $category->id,
            'user_uid' => $tenant->uid,
            'event' => 'Finalization Event '.$uid,
            'alamat' => 'Alamat Finalization Event',
            'tanggal' => '2026-09-10 19:00:00',
            'event_end' => '2026-09-10 22:00:00',
            'venue_name' => 'Venue Finalization',
            'venue_address' => 'Jl. Finalization',
            'venue_city' => 'Jakarta',
            'venue_province' => 'DKI Jakarta',
            'status' => 'inactive',
            'cover' => 'finalization-cover.jpg',
            'fee' => 10,
            'pajak' => 0,
            'deskripsi' => 'Deskripsi finalization',
            'map' => 'https://maps.google.com/?q=finalization',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'finalization-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
        ], $overrides));
    }

    private function organizer(Event $event, array $overrides = []): EventOrganizer
    {
        return EventOrganizer::create(array_merge([
            'event_uid' => $event->uid,
            'organizer_name' => 'PT Organizer Finalization',
            'responsible_name' => 'Responsible Finalization',
            'responsible_position' => 'Director',
            'phone' => '081234567890',
            'email' => 'organizer-finalization@example.test',
            'address' => 'Alamat organizer finalization',
        ], $overrides));
    }

    private function bankAccount(Event $event, array $overrides = []): EventBankAccount
    {
        $data = array_merge([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Finalization',
            'account_number' => '1234567890',
            'account_holder_name' => 'Organizer Finalization',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/book-finalization.pdf',
            'bank_book_original_name' => 'book-finalization.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
            'verified_at' => null,
            'verified_by' => null,
            'rejection_reason' => null,
        ], $overrides);

        $bankAccount = EventBankAccount::create($data);

        if (filled($bankAccount->bank_book_path) && empty($overrides['skip_storage'])) {
            Storage::disk('local')->put($bankAccount->bank_book_path, 'bank-finalization-file');
        }

        return $bankAccount;
    }

    private function verifiedBankAccount(Event $event, array $overrides = []): EventBankAccount
    {
        return $this->bankAccount($event, array_merge([
            'status' => 'verified',
            'verified_at' => now()->subDay(),
            'verified_by' => 'admin-existing',
            'rejection_reason' => null,
        ], $overrides));
    }

    private function organizerLetter(Event $event, array $overrides = []): EventDocument
    {
        $data = array_merge([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-FINALIZATION-001',
            'document_date' => '2026-08-20',
            'original_name' => 'organizer-finalization.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/organizer-finalization.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
            'rejection_reason' => null,
        ], $overrides);

        $document = EventDocument::create($data);

        if (filled($document->file_path) && empty($overrides['skip_storage'])) {
            Storage::disk('local')->put($document->file_path, 'organizer-finalization-file');
        }

        return $document;
    }

    private function verifiedOrganizerLetter(Event $event, array $overrides = []): EventDocument
    {
        return $this->organizerLetter($event, array_merge([
            'status' => 'verified',
            'verified_at' => now()->subDay(),
            'verified_by' => 'admin-existing',
            'rejection_reason' => null,
        ], $overrides));
    }

    private function agreement(User $tenant, Event $event, array $overrides = []): Agreement
    {
        return Agreement::create(array_merge([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
            'version' => 1,
            'status' => Agreement::STATUS_DRAFT,
            'created_by' => $tenant->uid,
            'event_snapshot' => null,
            'party_snapshot' => null,
            'bank_snapshot' => null,
            'document_snapshot' => null,
            'commercial_snapshot' => null,
            'document_number' => null,
            'template_version' => null,
            'unsigned_pdf_path' => null,
            'signed_pdf_path' => null,
            'privy_document_id' => null,
            'privy_status' => null,
            'privy_reference' => null,
            'sent_to_privy_at' => null,
            'signed_at' => null,
            'completed_at' => null,
        ], $overrides));
    }

    private function gateway(array $overrides = []): PaymentGateway
    {
        return PaymentGateway::create(array_merge([
            'payment' => 'Gateway Finalization '.Str::random(5),
            'category' => 'bank_transfer',
            'biaya' => 0,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 2,
            'midtrans_code' => null,
            'icon' => null,
            'is_active' => true,
            'slug' => 'gateway-finalization-'.Str::lower(Str::random(8)),
        ], $overrides));
    }

    private function eventGateway(Event $event, PaymentGateway $gateway, array $overrides = []): EventPaymentGateway
    {
        return EventPaymentGateway::create(array_merge([
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => true,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
            'fee_fixed' => null,
            'fee_percent' => null,
        ], $overrides));
    }
}
