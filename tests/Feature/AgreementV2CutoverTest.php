<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Admin\EventDetail as AdminEventDetail;
use App\Models\Agreement;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventBankAccount;
use App\Models\EventDocument;
use App\Models\EventOrganizer;
use App\Models\EventPaymentGateway;
use App\Models\PaymentGateway;
use App\Models\PlatformLegalProfile;
use App\Models\User;
use App\Services\Agreements\AgreementFinalizationService;
use App\Services\Agreements\AgreementVersioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AgreementV2CutoverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '', 'icon' => '']]);
    }

    public function test_dashboard_and_admin_draft_preview_use_mou_v2_template(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $event = $this->event($tenant, ['event' => 'Konser Preview V2']);

        $this->platformLegalProfile(['company_name' => 'PT Platform Preview']);
        $this->organizer($event);
        $this->bankAccount($event);
        $this->organizerLetter($event);
        $this->agreement($tenant, $event, ['document_number' => 'MOU/PREV/001']);

        $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid).'?activeTab=mou')
            ->assertOk()
            ->assertSeeText('Preview MOU V2')
            ->assertSeeText('Comprehensive MOU Body')
            ->assertSeeText('PT Platform Preview');

        Livewire::actingAs($admin)
            ->test(AdminEventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'review-mou')
            ->assertSee('Preview MOU V2')
            ->assertSee('Comprehensive MOU Body');
    }

    public function test_draft_mou_finalization_uses_mou_v2_pdf_and_stores_template_version(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $event = $this->event($tenant, ['event' => 'Konser Final MOU V2']);

        $this->platformLegalProfile([
            'company_name' => 'PT Platform Final',
            'representative_name' => 'Rani Platform',
            'representative_position' => 'Direktur',
        ]);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event, ['document_number' => 'MOU/FINAL/001']);
        $gateway = $this->gateway(['payment' => 'Gateway Final V2']);
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid);

        $this->assertTrue($result['ok']);

        $agreement->refresh();
        $payload = app(AgreementFinalizationService::class)->pdfPayloadForAgreement($agreement);
        $html = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();

        $this->assertSame(AgreementFinalizationService::TEMPLATE_VERSION, $agreement->template_version);
        $this->assertSame('private/agreements/'.$agreement->uid.'/unsigned.pdf', $agreement->unsigned_pdf_path);
        $this->assertTrue(Storage::disk('local')->exists($agreement->unsigned_pdf_path));
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($agreement->unsigned_pdf_path));
        $this->assertSame(AgreementFinalizationService::TEMPLATE_VERSION, $payload['agreement']['template_version']);
        $this->assertStringContainsString('Konser Final MOU V2', $html);
        $this->assertStringContainsString('PT Platform Final', $html);
    }

    public function test_historical_null_template_versions_fall_back_to_legacy_template_labels(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['event' => 'Konser Legacy']);

        $legacyMou = $this->agreement($tenant, $event, [
            'status' => Agreement::STATUS_COMPLETED,
            'template_version' => null,
            'event_snapshot' => $this->eventSnapshot($event, 'Konser Legacy'),
            'party_snapshot' => $this->partySnapshot(),
            'bank_snapshot' => $this->bankSnapshot(),
            'document_snapshot' => $this->documentSnapshot(),
            'commercial_snapshot' => $this->commercialSnapshot(),
            'completed_at' => now(),
        ]);

        $legacyAddendum = $this->agreement($tenant, $event, [
            'uid' => (string) Str::uuid(),
            'type' => Agreement::TYPE_ADDENDUM,
            'parent_agreement_uid' => $legacyMou->uid,
            'version' => 1,
            'status' => Agreement::STATUS_COMPLETED,
            'template_version' => null,
            'event_snapshot' => $this->eventSnapshot($event, 'Konser Legacy Addendum'),
            'party_snapshot' => $this->partySnapshot(),
            'bank_snapshot' => $this->bankSnapshot(),
            'document_snapshot' => $this->documentSnapshot(),
            'commercial_snapshot' => $this->commercialSnapshot(),
            'completed_at' => now(),
        ]);

        $mouPayload = app(AgreementFinalizationService::class)->pdfPayloadForAgreement($legacyMou);
        $addendumPayload = app(AgreementFinalizationService::class)->pdfPayloadForAgreement($legacyAddendum);

        $this->assertSame(AgreementFinalizationService::LEGACY_TEMPLATE_VERSION, $mouPayload['agreement']['template_version']);
        $this->assertSame(AgreementVersioningService::LEGACY_TEMPLATE_VERSION, $addendumPayload['agreement']['template_version']);
    }

    public function test_addendum_template_version_follows_parent_lineage_for_v1_and_v2(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$eventV2, $mouV2] = $this->completedMouEvent($tenant, $admin, [
            'event' => 'Konser Parent V2',
        ]);
        $eventV2->update(['venue_name' => 'Venue Addendum V2']);

        $addendumV2 = app(AgreementVersioningService::class)->checkForContractualChanges($eventV2->fresh(), $tenant->uid);

        $this->assertNotNull($addendumV2);
        $this->assertSame(AgreementVersioningService::TEMPLATE_VERSION, $addendumV2->template_version);
        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($eventV2->fresh(), $admin->uid, $addendumV2->uid)['ok']);
        $this->assertSame(AgreementVersioningService::TEMPLATE_VERSION, $addendumV2->fresh()->template_version);

        [$eventLegacy, $mouLegacy] = $this->completedLegacyMouEvent($tenant, $admin, [
            'event' => 'Konser Parent Legacy',
        ]);
        $eventLegacy->update(['venue_name' => 'Venue Addendum V1']);

        $addendumLegacy = app(AgreementVersioningService::class)->checkForContractualChanges($eventLegacy->fresh(), $tenant->uid);

        $this->assertNotNull($addendumLegacy);
        $this->assertSame(AgreementVersioningService::LEGACY_TEMPLATE_VERSION, $addendumLegacy->template_version);
        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($eventLegacy->fresh(), $admin->uid, $addendumLegacy->uid)['ok']);
        $this->assertSame(AgreementVersioningService::LEGACY_TEMPLATE_VERSION, $addendumLegacy->fresh()->template_version);
    }

    public function test_addendum_v2_template_uses_frozen_party_snapshots_without_hardcoded_platform_name(): void
    {
        $html = view('agreements.addendum-v2-pdf', ['payload' => [
            'agreement' => [
                'uid' => (string) Str::uuid(),
                'type' => Agreement::TYPE_ADDENDUM,
                'version' => 2,
                'status' => Agreement::STATUS_READY,
                'template_version' => AgreementVersioningService::TEMPLATE_VERSION,
                'document_number' => 'ADD/002',
            ],
            'parent_agreement' => [
                'uid' => (string) Str::uuid(),
                'type' => Agreement::TYPE_MOU,
                'version' => 1,
                'status' => Agreement::STATUS_COMPLETED,
                'template_version' => AgreementFinalizationService::TEMPLATE_VERSION,
                'document_number' => 'MOU/001',
                'completed_at' => now()->format('d-m-Y H:i'),
            ],
            'event' => [
                'event_name' => 'Konser Addendum V2',
            ],
            'platform_party' => [
                'company_name' => 'PT Platform Snapshot',
                'representative_name' => 'Rani Snapshot',
                'representative_position' => 'Direktur',
            ],
            'organizer' => [
                'organizer_name' => 'PT Organizer Snapshot',
                'responsible_name' => 'Budi Snapshot',
                'responsible_position' => 'Direktur Operasional',
            ],
            'diffs' => [[
                'section' => 'Event',
                'label' => 'Nama Venue',
                'before' => 'Venue Lama',
                'after' => 'Venue Baru',
            ]],
        ]])->render();

        $this->assertStringContainsString('PT Platform Snapshot', $html);
        $this->assertStringContainsString('Rani Snapshot', $html);
        $this->assertStringContainsString('PT Organizer Snapshot', $html);
        $this->assertStringContainsString('Budi Snapshot', $html);
        $this->assertStringNotContainsString('Tim Gotik Indonesia', $html);
    }

    private function admin(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Admin Cutover',
            'email' => 'admin-cutover-'.Str::random(6).'@example.test',
            'role' => 'admin',
        ], $overrides));
    }

    private function tenant(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Tenant Cutover',
            'email' => 'tenant-cutover-'.Str::random(6).'@example.test',
            'role' => 'penyewa',
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'User Cutover',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat User Cutover',
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
            'name' => 'Category '.Str::random(6),
            'slug' => 'category-'.Str::lower(Str::random(8)),
        ]);
        $uid = (string) Str::uuid();

        return Event::create(array_merge([
            'uid' => $uid,
            'category_id' => $category->id,
            'user_uid' => $tenant->uid,
            'event' => 'Konser '.Str::random(6),
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
            'slug' => 'event-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
        ], $overrides));
    }

    private function organizer(Event $event, array $overrides = []): EventOrganizer
    {
        return EventOrganizer::create(array_merge([
            'event_uid' => $event->uid,
            'organizer_name' => 'PT Organizer Cutover',
            'responsible_name' => 'Budi Santoso',
            'responsible_position' => 'Direktur',
            'phone' => '081234567890',
            'email' => 'organizer-cutover@example.test',
            'address' => 'Jl. Bisnis No. 1',
        ], $overrides));
    }

    private function bankAccount(Event $event, array $overrides = []): EventBankAccount
    {
        $account = EventBankAccount::create(array_merge([
            'event_uid' => $event->uid,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'PT Organizer Cutover',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/book.pdf',
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

    private function verifiedBankAccount(Event $event, array $overrides = []): EventBankAccount
    {
        return $this->bankAccount($event, array_merge([
            'status' => 'verified',
            'verified_by' => 'admin-existing',
            'verified_at' => now()->subDay(),
        ], $overrides));
    }

    private function organizerLetter(Event $event, array $overrides = []): EventDocument
    {
        $document = EventDocument::create(array_merge([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC/2026/001',
            'document_date' => '2026-08-20',
            'original_name' => 'surat.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/surat.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_by' => 'admin-existing',
            'verified_at' => now()->subDay(),
            'rejection_reason' => null,
        ], $overrides));

        Storage::disk('local')->put($document->file_path, 'organizer-letter');

        return $document;
    }

    private function verifiedOrganizerLetter(Event $event, array $overrides = []): EventDocument
    {
        return $this->organizerLetter($event, array_merge([
            'status' => 'verified',
            'verified_by' => 'admin-existing',
            'verified_at' => now()->subDay(),
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
            'slug' => 'gateway-'.Str::lower(Str::random(8)),
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

    private function completedMouEvent(User $tenant, User $admin, array $eventOverrides = []): array
    {
        $this->platformLegalProfile([
            'company_name' => 'PT Platform V2',
            'representative_name' => 'Rani Platform',
            'representative_position' => 'Direktur',
        ]);

        $event = $this->event($tenant, $eventOverrides);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event, ['document_number' => 'MOU/V2/001']);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid)['ok']);

        $agreement->refresh()->forceFill([
            'status' => Agreement::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();

        return [$event->fresh(), $agreement->fresh()];
    }

    private function completedLegacyMouEvent(User $tenant, User $admin, array $eventOverrides = []): array
    {
        $event = $this->event($tenant, $eventOverrides);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $agreement = $this->agreement($tenant, $event, [
            'created_by' => $admin->uid,
            'status' => Agreement::STATUS_COMPLETED,
            'template_version' => null,
            'document_number' => 'MOU/LEGACY/001',
            'event_snapshot' => $this->eventSnapshot($event, $event->event),
            'party_snapshot' => $this->partySnapshot(),
            'bank_snapshot' => $this->bankSnapshot(),
            'document_snapshot' => $this->documentSnapshot(),
            'commercial_snapshot' => $this->commercialSnapshot(),
            'completed_at' => now(),
        ]);

        return [$event->fresh(), $agreement->fresh()];
    }

    private function eventSnapshot(Event $event, string $eventName): array
    {
        return [
            'event_uid' => $event->uid,
            'event_name' => $eventName,
            'start' => '10-09-2026 19:00',
            'end' => '10-09-2026 22:00',
            'venue_name' => $event->venue_name,
            'venue_address' => $event->venue_address,
            'venue_city' => $event->venue_city,
            'venue_province' => $event->venue_province,
            'start_sale' => '01-09-2026 10:00',
            'buyer_fee' => [
                'mode' => 'fixed',
                'value' => 5000.0,
            ],
        ];
    }

    private function partySnapshot(): array
    {
        return [
            'organizer_name' => 'PT Organizer Cutover',
            'responsible_name' => 'Budi Santoso',
            'responsible_position' => 'Direktur',
            'phone' => '081234567890',
            'email' => 'organizer-cutover@example.test',
            'address' => 'Jl. Bisnis No. 1',
        ];
    }

    private function bankSnapshot(): array
    {
        return [
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'PT Organizer Cutover',
        ];
    }

    private function documentSnapshot(): array
    {
        return [
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC/2026/001',
            'document_date' => '20-08-2026',
            'original_name' => 'surat.pdf',
        ];
    }

    private function commercialSnapshot(): array
    {
        return [
            'buyer_fee' => [
                'mode' => 'fixed',
                'value' => 5000.0,
            ],
            'payment_otp_enabled' => false,
            'payment_gateways' => [[
                'payment_gateway_id' => 1,
                'payment' => 'BCA Virtual Account',
                'event_is_active' => true,
                'global_is_active' => true,
                'effective_is_active' => true,
                'fee_mode' => 'global',
                'resolved_fee_fixed' => '2000.00',
                'resolved_fee_percent' => '0',
            ]],
        ];
    }
}
