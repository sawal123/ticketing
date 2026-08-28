<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Models\Agreement;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventBankAccount;
use App\Models\EventDocument;
use App\Models\EventOrganizer;
use App\Models\EventPaymentGateway;
use App\Models\Harga;
use App\Models\PaymentGateway;
use App\Models\PlatformLegalProfile;
use App\Models\User;
use App\Services\Agreements\AgreementFinalizationService;
use App\Services\Agreements\AgreementPreviewService;
use App\Services\Agreements\AgreementSignedUploadService;
use App\Services\Agreements\AgreementSignedVerificationService;
use App\Services\Agreements\AgreementVersioningService;
use App\Services\Events\EventActivationGuardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgreementMouV2RegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '', 'icon' => '']]);
    }

    public function test_finalized_mou_v2_renders_full_document_from_frozen_parties(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $this->platformLegalProfile([
            'company_name' => 'PT Platform Regression',
            'legal_id' => 'NIB-REGRESSION',
            'representative_name' => 'Rani Platform',
            'representative_position' => 'Direktur',
        ]);

        $event = $this->readyEvent($tenant);
        $agreement = $this->mouAgreement($event);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid);
        $this->assertTrue($result['ok'], $result['message'] ?? 'Finalisasi MOU v2 gagal.');

        $ready = $agreement->fresh();
        $this->assertSame(AgreementFinalizationService::TEMPLATE_VERSION, $ready->template_version);
        $this->assertSame(Agreement::STATUS_READY, $ready->status);

        $payload = app(AgreementFinalizationService::class)->pdfPayloadForAgreement($ready);
        $this->assertSame(AgreementFinalizationService::TEMPLATE_VERSION, $payload['agreement']['template_version']);

        $html = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();

        // Cover, PARA PIHAK, Pasal 1-21, signature page, LAMPIRAN I & II.
        foreach (range(1, 21) as $number) {
            $this->assertStringContainsString('PASAL ' . $number, $html);
        }
        $this->assertStringContainsString('Pembukaan dan Para Pihak', $html);
        $this->assertStringContainsString('Halaman Tanda Tangan', $html);
        $this->assertStringContainsString('Persetujuan PARA PIHAK', $html);
        $this->assertStringContainsString('LAMPIRAN I', $html);
        $this->assertStringContainsString('LAMPIRAN II', $html);

        // PIHAK PERTAMA berasal dari platform_party_snapshot (frozen).
        $this->assertStringContainsString('PT Platform Regression', $html);
        $this->assertStringContainsString('Rani Platform', $html);

        // PIHAK KEDUA berasal dari party_snapshot (frozen).
        $this->assertStringContainsString('PT Organizer Regression', $html);
        $this->assertStringContainsString('Budi Santoso', $html);

        $pdf = Storage::disk('local')->get($ready->unsigned_pdf_path);
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_finalized_mou_v2_payload_is_frozen_against_live_changes(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $this->platformLegalProfile(['company_name' => 'PT Frozen Platform']);

        $event = $this->readyEvent($tenant);
        $agreement = $this->mouAgreement($event);

        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid)['ok']);
        $ready = $agreement->fresh();

        $before = app(AgreementFinalizationService::class)->pdfPayloadForAgreement($ready);
        $beforeHtml = view('agreements.mou-v2-pdf', ['payload' => $before])->render();

        // Mutate live data; the frozen MOU payload must not change.
        $event->update(['event' => 'Nama Live Berubah', 'venue_name' => 'Venue Live Berubah']);
        $event->organizer->update(['organizer_name' => 'PT Organizer Live Berubah']);
        $event->bankAccount->update(['bank_name' => 'Bank Live Berubah']);
        $event->organizerLetter->update(['document_number' => 'DOC/LIVE/CHANGED']);
        $event->eventPaymentGateways()->first()->update(['is_active' => false]);
        PlatformLegalProfile::query()->update(['company_name' => 'PT Live Berubah']);

        $after = app(AgreementFinalizationService::class)->pdfPayloadForAgreement($ready->fresh());
        $afterHtml = view('agreements.mou-v2-pdf', ['payload' => $after])->render();

        $this->assertSame($before, $after);
        $this->assertSame($beforeHtml, $afterHtml);
        $this->assertStringContainsString('PT Frozen Platform', $afterHtml);
        $this->assertStringNotContainsString('PT Live Berubah', $afterHtml);
    }

    public function test_existing_unsigned_pdf_is_never_overwritten_for_draft_or_ready(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $this->platformLegalProfile(['company_name' => 'PT Platform Guard']);

        // DRAFT agreement that already carries a historical unsigned file is blocked.
        $event = $this->readyEvent($tenant);
        $agreement = $this->mouAgreement($event);

        $path = 'private/agreements/' . $agreement->uid . '/unsigned.pdf';
        Storage::disk('local')->put($path, 'HISTORICAL-UNSIGNED');
        $agreement->forceFill(['unsigned_pdf_path' => $path])->save();

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid);

        $this->assertFalse($result['ok']);
        $this->assertSame('historical_file_exists', $result['reason'] ?? null);
        $this->assertSame(Agreement::STATUS_DRAFT, $agreement->fresh()->status);
        $this->assertNull($agreement->fresh()->template_version);
        $this->assertSame('HISTORICAL-UNSIGNED', Storage::disk('local')->get($path));

        // A DRAFT with a signed file indication is also blocked.
        $event2 = $this->readyEvent($tenant);
        $agreement2 = $this->mouAgreement($event2);
        $agreement2->forceFill([
            'signed_pdf_path' => 'private/agreements/' . $agreement2->uid . '/signed.pdf',
        ])->save();

        $result2 = app(AgreementFinalizationService::class)->finalizeForEvent($event2, $admin->uid, $agreement2->uid);

        $this->assertFalse($result2['ok']);
        $this->assertSame('historical_file_exists', $result2['reason'] ?? null);
        $this->assertSame(Agreement::STATUS_DRAFT, $agreement2->fresh()->status);

        // A READY agreement cannot be re-finalized and its file is untouched.
        $event3 = $this->readyEvent($tenant);
        $agreement3 = $this->mouAgreement($event3);
        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($event3, $admin->uid, $agreement3->uid)['ok']);

        $path3 = $agreement3->fresh()->unsigned_pdf_path;
        $content = Storage::disk('local')->get($path3);

        $retry = app(AgreementFinalizationService::class)->finalizeForEvent($event3, $admin->uid, $agreement3->uid);

        $this->assertFalse($retry['ok']);
        $this->assertSame('not_draft', $retry['reason'] ?? null);
        $this->assertSame($content, Storage::disk('local')->get($path3));
    }

    public function test_ready_and_completed_mou_do_not_build_live_preview(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $this->platformLegalProfile(['company_name' => 'PT Platform NoPreview']);

        $event = $this->readyEvent($tenant);
        $agreement = $this->mouAgreement($event);

        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid)['ok']);

        // READY MOU must not build a live preview.
        $this->assertNull(app(AgreementPreviewService::class)->buildForEvent($event->fresh()));

        $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid) . '?activeTab=mou')
            ->assertOk()
            ->assertDontSee('Comprehensive MOU Body');

        // COMPLETED MOU must not build a live preview either.
        $agreement->fresh()->forceFill([
            'status' => Agreement::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();

        $this->assertNull(app(AgreementPreviewService::class)->buildForEvent($event->fresh()));
    }

    public function test_historical_mou_v1_keeps_legacy_template_and_file_unchanged(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        $event = $this->event($tenant, ['event' => 'Konser Legacy V1']);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);

        $legacyPath = 'private/agreements/legacy-uid/unsigned.pdf';
        $agreement = $this->agreement($tenant, $event, [
            'document_number' => 'MOU/LEGACY/001',
            'status' => Agreement::STATUS_COMPLETED,
            'template_version' => 'mou-v1',
            'unsigned_pdf_path' => $legacyPath,
            'signed_pdf_path' => 'private/agreements/legacy-uid/signed.pdf',
            'completed_at' => now(),
            'event_snapshot' => ['event_name' => 'Konser Legacy V1', 'venue_name' => 'Venue Legacy'],
        ]);

        Storage::disk('local')->put($legacyPath, 'LEGACY-UNSIGNED');

        $payload = app(AgreementFinalizationService::class)->pdfPayloadForAgreement($agreement);
        $this->assertSame(AgreementFinalizationService::LEGACY_TEMPLATE_VERSION, $payload['agreement']['template_version']);
        $this->assertSame('Konser Legacy V1', $payload['event']['event_name']);

        // Legacy MOU is not affected by the v2 cutover: no live preview, same file.
        $this->assertNull(app(AgreementPreviewService::class)->buildForEvent($event->fresh()));
        $this->assertSame($legacyPath, $agreement->fresh()->unsigned_pdf_path);
        $this->assertSame('LEGACY-UNSIGNED', Storage::disk('local')->get($legacyPath));
    }

    public function test_private_file_authorization_isolates_owner_and_keeps_path_private(): void
    {
        $tenant = $this->tenant();
        $otherTenant = $this->tenant();
        $admin = $this->admin();
        $this->platformLegalProfile(['company_name' => 'PT Platform Auth']);

        $event = $this->readyEvent($tenant);
        $agreement = $this->mouAgreement($event);

        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid)['ok']);
        $ready = $agreement->fresh();

        $this->assertStringStartsWith('private/', $ready->unsigned_pdf_path);

        $signedPath = 'private/agreements/' . $ready->uid . '/signed.pdf';
        Storage::disk('local')->put($signedPath, 'SIGNED-PDF');
        $ready->forceFill(['signed_pdf_path' => $signedPath])->save();

        // Owner / penyewa can download unsigned and signed files.
        $this->actingAs($tenant)
            ->get(route('dashboard.event.mou.unsigned', $event->uid))
            ->assertOk();
        $this->actingAs($tenant)
            ->get(route('dashboard.event.mou.signed', $event->uid))
            ->assertOk();

        // A different tenant cannot access them (isolation, no existence leak).
        $this->actingAs($otherTenant)
            ->get(route('dashboard.event.mou.unsigned', $event->uid))
            ->assertNotFound();
        $this->actingAs($otherTenant)
            ->get(route('dashboard.event.mou.signed', $event->uid))
            ->assertNotFound();

        // The private storage path is not rendered on the owner dashboard page.
        $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid) . '?activeTab=mou')
            ->assertOk()
            ->assertDontSee($ready->unsigned_pdf_path);
    }

    public function test_manual_signing_flow_v2_completes_without_privy(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $this->platformLegalProfile(['company_name' => 'PT Platform Sign']);

        $event = $this->readyEvent($tenant);
        $agreement = $this->mouAgreement($event);

        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid)['ok']);
        $this->assertSame(Agreement::STATUS_READY, $agreement->fresh()->status);

        // READY -> tenant downloads unsigned.
        $this->actingAs($tenant)
            ->get(route('dashboard.event.mou.unsigned', $event->uid))
            ->assertOk();

        // -> sign independently -> upload signed PDF.
        $upload = app(AgreementSignedUploadService::class)->storeForEvent(
            $event,
            $tenant->uid,
            UploadedFile::fake()->create('signed-v2.pdf', 120, 'application/pdf'),
            $agreement->uid
        );
        $this->assertTrue($upload['ok'], $upload['message'] ?? 'Upload signed PDF gagal.');

        $signed = $agreement->fresh();
        $this->assertSame(Agreement::STATUS_READY, $signed->status);
        $this->assertNotNull($signed->signed_pdf_path);

        // -> admin review -> COMPLETED.
        $approve = app(AgreementSignedVerificationService::class)->approveForEvent($event, $admin->uid, $agreement->uid);
        $this->assertTrue($approve['ok'], $approve['message'] ?? 'Approve signed PDF gagal.');

        $completed = $agreement->fresh();
        $this->assertSame(Agreement::STATUS_COMPLETED, $completed->status);
        $this->assertSame(Agreement::SIGNED_REVIEW_VERIFIED, $completed->signed_review_status);
        $this->assertNotNull($completed->completed_at);

        // Manual signing flow must not involve Privy / OAuth / webhook / callback.
        $this->assertNull($completed->privy_document_id);
        $this->assertNull($completed->privy_status);
        $this->assertNull($completed->privy_reference);
        $this->assertNull($completed->sent_to_privy_at);
    }

    public function test_activation_guard_requires_completed_mou_and_blocks_pending_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $this->platformLegalProfile(['company_name' => 'PT Platform Active']);

        $event = $this->readyEvent($tenant);
        $agreement = $this->mouAgreement($event);

        // Not activatable before the MOU is COMPLETED.
        $this->assertFalse(app(EventActivationGuardService::class)->evaluateForEvent($event->fresh())['can_activate']);

        $this->completeAgreement($event, $agreement, $tenant, $admin, 'signed-active-mou.pdf');
        $this->assertSame(Agreement::STATUS_COMPLETED, $agreement->fresh()->status);

        $this->assertTrue(app(EventActivationGuardService::class)->evaluateForEvent($event->fresh())['can_activate']);

        // A pending Addendum blocks activation (M12).
        $event->update(['venue_name' => 'Venue Untuk Addendum']);
        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);
        $this->assertNotNull($addendum);
        $this->assertFalse(app(EventActivationGuardService::class)->evaluateForEvent($event->fresh())['can_activate']);

        // Completing the Addendum restores activation eligibility.
        $this->completeAgreement($event->fresh(), $addendum, $tenant, $admin, 'signed-active-addendum.pdf');
        $this->assertSame(Agreement::STATUS_COMPLETED, $addendum->fresh()->status);

        $this->assertTrue(app(EventActivationGuardService::class)->evaluateForEvent($event->fresh())['can_activate']);
        app(EventActivationGuardService::class)->activateForEvent($event->fresh(), $admin->uid, true);
        $this->assertSame('active', strtolower((string) $event->fresh()->status));
    }

    public function test_addendum_v2_follows_parent_lineage_and_uses_frozen_party(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $this->platformLegalProfile([
            'company_name' => 'PT Platform V2 Parent',
            'representative_name' => 'Rani V2',
        ]);

        $event = $this->readyEvent($tenant);
        $mou = $this->mouAgreement($event);

        $this->completeAgreement($event, $mou, $tenant, $admin, 'signed-mou-v2.pdf');
        $this->assertSame(Agreement::STATUS_COMPLETED, $mou->fresh()->status);

        // Live platform profile changes must NOT leak into the Addendum v2.
        PlatformLegalProfile::query()->update(['company_name' => 'PT Live Platform Baru']);

        $event->update(['venue_name' => 'Venue Addendum V2']);
        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNotNull($addendum);
        $this->assertSame(Agreement::TYPE_ADDENDUM, $addendum->type);
        $this->assertSame(AgreementVersioningService::TEMPLATE_VERSION, $addendum->template_version);
        $this->assertSame($mou->uid, $addendum->parent_agreement_uid);

        $preview = app(AgreementVersioningService::class)->buildAddendumPreview($event->fresh(), $addendum->fresh());
        $platform = $preview['after']['platform_party_snapshot'] ?? [];
        $this->assertSame('PT Platform V2 Parent', $platform['company_name'] ?? null);
        $this->assertStringNotContainsString('Tim Gotik Indonesia', (string) ($platform['company_name'] ?? ''));
        $this->assertNotEmpty($preview['diffs']);

        // parent addendum-v2 -> addendum-v2
        $this->completeAgreement($event->fresh(), $addendum, $tenant, $admin, 'signed-addendum-v2.pdf');
        $this->assertSame(Agreement::STATUS_COMPLETED, $addendum->fresh()->status);

        $event->update(['venue_name' => 'Venue Addendum V2.1']);
        $addendumV2 = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNotNull($addendumV2);
        $this->assertSame(2, (int) $addendumV2->version);
        $this->assertSame(AgreementVersioningService::TEMPLATE_VERSION, $addendumV2->template_version);
    }

    public function test_ticket_only_changes_do_not_create_addendum_or_leak_into_mou(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $this->platformLegalProfile(['company_name' => 'PT Platform Ticket']);

        $event = $this->readyEvent($tenant);
        $mou = $this->mouAgreement($event);

        $this->completeAgreement($event, $mou, $tenant, $admin, 'signed-ticket-mou.pdf');

        $payload = app(AgreementFinalizationService::class)->pdfPayloadForAgreement($mou->fresh());
        $html = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();

        $ticket = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Presale Ticket',
            'qty' => 100,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => 150000,
            'status' => 'active',
        ]);
        $ticket->update(['kategori' => 'VIP Ticket', 'harga' => 300000]);

        $result = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($result);
        $this->assertStringNotContainsString('Presale Ticket', $html);
        $this->assertStringNotContainsString('VIP Ticket', $html);
    }

    public function test_annex_i_keeps_zero_fee_components_and_frozen_gateways(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $this->platformLegalProfile(['company_name' => 'PT Platform Fee']);

        $event = $this->readyEvent($tenant, ['fee' => 2000]);
        $agreement = $this->mouAgreement($event);

        $this->attachGateway($event, [
            'payment' => 'Gateway Fixed+Percent',
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 3,
        ]);
        $this->attachGateway($event, [
            'payment' => 'Gateway Zero+Percent',
            'default_fee_fixed' => 0,
            'default_fee_percent' => 3,
        ]);
        $this->attachGateway($event, [
            'payment' => 'Gateway Fixed+Zero',
            'default_fee_fixed' => 4000,
            'default_fee_percent' => 0,
        ]);

        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid)['ok']);

        $payload = app(AgreementFinalizationService::class)->pdfPayloadForAgreement($agreement->fresh());
        $gateways = collect($payload['commercial']['payment_gateways'] ?? [])->keyBy('payment');

        $this->assertSame('2000.00', $gateways['Gateway Fixed+Percent']['resolved_fee_fixed'] ?? null);
        $this->assertSame('3', $gateways['Gateway Fixed+Percent']['resolved_fee_percent'] ?? null);
        $this->assertSame('0.00', $gateways['Gateway Zero+Percent']['resolved_fee_fixed'] ?? null);
        $this->assertSame('3', $gateways['Gateway Zero+Percent']['resolved_fee_percent'] ?? null);
        $this->assertSame('4000.00', $gateways['Gateway Fixed+Zero']['resolved_fee_fixed'] ?? null);
        $this->assertSame('0', $gateways['Gateway Fixed+Zero']['resolved_fee_percent'] ?? null);

        $html = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();

        $this->assertStringContainsString('Rp 2.000 + 3%', $html);
        $this->assertStringContainsString('Rp 0 + 3%', $html);
        $this->assertStringContainsString('Rp 4.000 + 0%', $html);
        $this->assertStringContainsString('Biaya Pembeli / Event Fee', $html);
        // "Platform Fee" dilarang hanya sebagai LABEL/ISTILAH biaya (sel berdiri
        // sendiri dalam markup), bukan sebagai substring dalam nama badan usaha
        // yang sah seperti "PT Platform Fee".
        $this->assertSame(
            0,
            preg_match('/>\s*Platform Fee\s*</', $html),
            'Label biaya "Platform Fee" tidak boleh digunakan.'
        );

        // Frozen gateways only: live gateway toggled off after finalization is ignored.
        $event->eventPaymentGateways()->whereHas('paymentGateway', function ($q) {
            $q->where('payment', 'Gateway Fixed+Percent');
        })->first()->update(['is_active' => false]);

        $after = app(AgreementFinalizationService::class)->pdfPayloadForAgreement($agreement->fresh());
        $this->assertSame('2000.00', collect($after['commercial']['payment_gateways'] ?? [])
            ->firstWhere('payment', 'Gateway Fixed+Percent')['resolved_fee_fixed'] ?? null);
    }

    public function test_multi_gateway_pdf_renders_all_thirty_gateways(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $this->platformLegalProfile(['company_name' => 'PT Platform Multi']);

        $event = $this->readyEvent($tenant);
        $agreement = $this->mouAgreement($event);

        $names = [];
        for ($i = 1; $i <= 30; $i++) {
            $name = 'Gateway Multi ' . $i;
            $names[] = $name;
            $this->attachGateway($event, [
                'payment' => $name,
                'default_fee_fixed' => 1000 + $i,
                'default_fee_percent' => $i % 5,
            ]);
        }

        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid)['ok']);

        $ready = $agreement->fresh();
        $payload = app(AgreementFinalizationService::class)->pdfPayloadForAgreement($ready);
        $html = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();

        foreach ($names as $name) {
            $this->assertStringContainsString($name, $html);
        }

        $pdf = Storage::disk('local')->get($ready->unsigned_pdf_path);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(0, strlen($pdf));
        // No exact page-count assertion: pagination is CSS-driven, not a hard
        // requirement that the whole table fit on one page.
    }

    public function test_annex_ii_readiness_marks_missing_data_belum_lengkap_without_private_paths(): void
    {
        $render = static fn(array $payload) => view('agreements.partials.mou-v2-annex-ii', ['payload' => $payload])->render();

        // Missing organizer / bank / letter data -> BELUM LENGKAP.
        $missing = $render([
            'organizer' => [
                'organizer_name' => 'PT Organizer',
                'responsible_name' => null,
                'responsible_position' => null,
                'phone' => null,
                'email' => null,
                'address' => null,
            ],
            'bank_account' => [
                'bank_name' => null,
                'account_number' => null,
                'account_holder_name' => null,
            ],
            'organizer_letter' => [
                'document_number' => null,
                'document_date' => null,
                'original_name' => null,
            ],
            'commercial' => ['payment_gateways' => []],
        ]);

        $this->assertSame(3, substr_count($missing, 'BELUM LENGKAP'));
        $this->assertStringContainsString('Buku Rekening Fisik', $missing);
        $this->assertStringContainsString('BELUM TERSEDIA', $missing);

        // Complete payload -> LENGKAP / TERSEDIA.
        $complete = $render([
            'agreement' => ['document_number' => 'MOU/ANNEX/001'],
            'organizer' => [
                'organizer_name' => 'PT Organizer',
                'responsible_name' => 'Budi',
                'responsible_position' => 'Direktur',
                'phone' => '0812',
                'email' => 'o@example.test',
                'address' => 'Jl. X',
            ],
            'bank_account' => [
                'bank_name' => 'BCA',
                'account_number' => '123',
                'account_holder_name' => 'PT Organizer',
                'verification_status' => 'verified',
            ],
            'organizer_letter' => [
                'document_type' => 'surat',
                'document_number' => 'DOC/001',
                'document_date' => '2026-08-20',
                'original_name' => 'surat.pdf',
                'verification_status' => 'verified',
            ],
            'commercial' => [
                'payment_gateways' => [
                    ['effective_is_active' => true],
                ],
            ],
        ]);

        $this->assertStringNotContainsString('BELUM LENGKAP', $complete);
        $this->assertStringContainsString('LENGKAP', $complete);
        $this->assertStringContainsString('TERSEDIA', $complete);

        // Private storage paths are never rendered inside Lampiran II.
        $this->assertStringNotContainsString('private/agreements', $complete);
        $this->assertStringNotContainsString('private/events', $complete);
    }

    // ---------------------------------------------------------------------
    // Setup helpers
    // ---------------------------------------------------------------------

    private function admin(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Admin Regression',
            'email' => 'admin-regression-' . Str::random(6) . '@example.test',
            'role' => 'admin',
        ], $overrides));
    }

    private function tenant(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Tenant Regression',
            'email' => 'tenant-regression-' . Str::random(6) . '@example.test',
            'role' => 'penyewa',
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'User Regression',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat User Regression',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function platformLegalProfile(array $overrides = []): PlatformLegalProfile
    {
        return PlatformLegalProfile::query()->updateOrCreate(
            ['profile_key' => PlatformLegalProfile::DEFAULT_KEY],
            array_merge([
                'company_name' => null,
                'legal_id' => null,
                'address' => null,
                'representative_name' => null,
                'representative_position' => null,
                'email' => null,
                'phone' => null,
                'website' => null,
            ], $overrides)
        );
    }

    private function event(User $tenant, array $overrides = []): Event
    {
        $category = Category::create([
            'name' => 'Category ' . Str::random(6),
            'slug' => 'category-' . Str::lower(Str::random(8)),
        ]);

        return Event::create(array_merge([
            'uid' => (string) Str::uuid(),
            'category_id' => $category->id,
            'user_uid' => $tenant->uid,
            'event' => 'Konser ' . Str::random(6),
            'alamat' => 'Jl. Pemuda No. 10',
            'tanggal' => '2026-09-10 19:00:00',
            'event_end' => '2026-09-10 22:00:00',
            'venue_name' => 'Stadion Utama',
            'venue_address' => 'Jl. Stadion',
            'venue_city' => 'Jakarta',
            'venue_province' => 'DKI Jakarta',
            'status' => 'inactive',
            'cover' => 'cover.jpg',
            'fee' => 5000,
            'pajak' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => 'https://maps.google.com/?q=venue',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'event-' . Str::lower(Str::random(8)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
        ], $overrides));
    }

    private function organizer(Event $event, array $overrides = []): EventOrganizer
    {
        return EventOrganizer::create(array_merge([
            'event_uid' => $event->uid,
            'organizer_name' => 'PT Organizer Regression',
            'responsible_name' => 'Budi Santoso',
            'responsible_position' => 'Direktur',
            'phone' => '081234567890',
            'email' => 'organizer-regression@example.test',
            'address' => 'Jl. Bisnis No. 1',
        ], $overrides));
    }

    private function verifiedBankAccount(Event $event, array $overrides = []): EventBankAccount
    {
        $account = EventBankAccount::create(array_merge([
            'event_uid' => $event->uid,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'PT Organizer Regression',
            'bank_book_path' => 'private/events/' . $event->uid . '/bank/book.pdf',
            'bank_book_original_name' => 'book.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'verified',
            'verified_by' => 'admin-existing',
            'verified_at' => now()->subDay(),
            'rejection_reason' => null,
        ], $overrides));

        Storage::disk('local')->put($account->bank_book_path, 'bank-book');

        return $account;
    }

    private function verifiedOrganizerLetter(Event $event, array $overrides = []): EventDocument
    {
        $document = EventDocument::create(array_merge([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC/2026/001',
            'document_date' => '2026-08-20',
            'original_name' => 'surat.pdf',
            'file_path' => 'private/events/' . $event->uid . '/documents/surat.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_by' => 'admin-existing',
            'verified_at' => now()->subDay(),
            'rejection_reason' => null,
        ], $overrides));

        Storage::disk('local')->put($document->file_path, 'organizer-letter');

        return $document;
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
            'document_number' => null,
            'template_version' => null,
            'event_snapshot' => null,
            'platform_party_snapshot' => null,
            'party_snapshot' => null,
            'bank_snapshot' => null,
            'document_snapshot' => null,
            'commercial_snapshot' => null,
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
            'payment' => 'BCA Virtual Account',
            'category' => 'bank_transfer',
            'biaya' => 2000,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 0,
            'midtrans_code' => null,
            'icon' => null,
            'is_active' => true,
            'slug' => 'gateway-' . Str::lower(Str::random(8)),
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

    private function attachGateway(Event $event, array $gatewayOverrides = [], array $configOverrides = []): EventPaymentGateway
    {
        return $this->eventGateway($event, $this->gateway($gatewayOverrides), $configOverrides);
    }

    /**
     * Build an event that passes the M7 readiness review plus a DRAFT MOU,
     * ready to be finalized.
     */
    private function readyEvent(User $tenant, array $eventOverrides = []): Event
    {
        $event = $this->event($tenant, $eventOverrides);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $this->agreement($tenant, $event, ['document_number' => 'MOU/REG/001']);
        $this->attachGateway($event);

        return $event->fresh();
    }

    private function mouAgreement(Event $event): Agreement
    {
        return $event->agreements()
            ->where('type', Agreement::TYPE_MOU)
            ->latest('id')
            ->firstOrFail();
    }

    /**
     * Run the full manual signing flow: finalize -> upload signed -> approve.
     */
    private function completeAgreement(Event $event, Agreement $agreement, User $tenant, User $admin, string $filename): void
    {
        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid)['ok']);

        $upload = app(AgreementSignedUploadService::class)->storeForEvent(
            $event,
            $tenant->uid,
            UploadedFile::fake()->create($filename, 120, 'application/pdf'),
            $agreement->uid
        );
        $this->assertTrue($upload['ok'], $upload['message'] ?? 'Upload signed PDF gagal.');

        $approve = app(AgreementSignedVerificationService::class)->approveForEvent($event, $admin->uid, $agreement->uid);
        $this->assertTrue($approve['ok'], $approve['message'] ?? 'Approve signed PDF gagal.');
    }
}
