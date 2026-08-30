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
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\Agreements\AgreementPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgreementPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '', 'icon' => '']]);
    }

    public function test_tenant_can_preview_mou_from_live_event_data(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'event' => 'Konser Preview Tenant',
            'venue_name' => 'Istora Preview',
            'venue_address' => 'Jl. Preview No. 1',
            'venue_city' => 'Jakarta Pusat',
            'venue_province' => 'DKI Jakarta',
        ]);
        $this->organizer($event, [
            'organizer_name' => 'PT Preview Tenant',
            'responsible_name' => 'Sawal Preview',
            'responsible_position' => 'Direktur Program',
        ]);
        $bankAccount = $this->bankAccount($event, [
            'bank_name' => 'Bank Preview',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/internal-preview.pdf',
        ]);
        $document = $this->organizerLetter($event, [
            'document_number' => 'MOU-PREV-001',
            'document_date' => '2026-08-20',
            'file_path' => 'private/events/'.$event->uid.'/documents/internal-letter.pdf',
        ]);
        $this->responsibleIdentity($event, [
            'original_name' => 'responsible-identity-preview.pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-22 10:00:00',
        ]);
        $this->agreement($tenant, $event);

        $response = $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid).'?activeTab=mou');

        $response->assertOk()
            ->assertSeeText('Preview MOU V2')
            ->assertSeeText('Comprehensive MOU Body')
            ->assertSeeText('Konser Preview Tenant')
            ->assertSeeText('PT Preview Tenant')
            ->assertSeeText('Sawal Preview')
            ->assertSeeText('Istora Preview')
            ->assertSeeText('Bank Preview')
            ->assertSeeText('MOU-PREV-001')
            ->assertSeeText('20-08-2026')
            ->assertSeeText('Identitas Penanggung Jawab')
            ->assertSeeText('TERVERIFIKASI')
            ->assertSeeText(Agreement::STATUS_DRAFT)
            ->assertDontSeeText($bankAccount->bank_book_path)
            ->assertDontSeeText($document->file_path)
            ->assertDontSeeText('storage/app/private');
    }

    public function test_staff_can_preview_parent_tenant_mou(): void
    {
        $tenant = $this->tenant(['email' => 'preview-parent@example.test']);
        $staff = $this->user([
            'email' => 'preview-staff@example.test',
            'role' => 'staff',
            'parent_uid' => $tenant->uid,
        ]);
        $event = $this->event($tenant, ['event' => 'Konser Staff Preview']);
        $this->agreement($tenant, $event);

        $this->actingAs($staff)
            ->get(route('dashboard.event.detail', $event->uid).'?activeTab=mou')
            ->assertOk()
            ->assertSeeText('Konser Staff Preview')
            ->assertSeeText(Agreement::STATUS_DRAFT);
    }

    public function test_cross_tenant_and_cross_staff_preview_isolation_is_enforced(): void
    {
        $tenantA = $this->tenant(['email' => 'preview-a@example.test']);
        $staffA = $this->user([
            'email' => 'preview-staff-a@example.test',
            'role' => 'staff',
            'parent_uid' => $tenantA->uid,
        ]);
        $tenantB = $this->tenant(['email' => 'preview-b@example.test']);
        $eventB = $this->event($tenantB, ['event' => 'Konser Tenant B']);
        $this->agreement($tenantB, $eventB);

        $this->actingAs($tenantA)
            ->get(route('dashboard.event.detail', $eventB->uid).'?activeTab=mou')
            ->assertNotFound();

        $this->actingAs($staffA)
            ->get(route('dashboard.event.detail', $eventB->uid).'?activeTab=mou')
            ->assertNotFound();
    }

    public function test_legacy_event_without_agreement_shows_empty_state_without_creating_new_agreement(): void
    {
        $tenant = $this->tenant(['email' => 'legacy-preview@example.test']);
        $event = $this->event($tenant, ['event' => 'Legacy Tanpa Agreement']);

        $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid).'?activeTab=mou')
            ->assertOk()
            ->assertSeeText('MOU belum tersedia untuk event ini.');

        $this->assertDatabaseCount('agreements', 0);
    }

    public function test_preview_stays_live_when_event_related_data_changes_and_keeps_snapshots_null(): void
    {
        $tenant = $this->tenant(['email' => 'live-preview@example.test']);
        $event = $this->event($tenant, ['event' => 'Nama Live Lama']);
        $organizer = $this->organizer($event, ['organizer_name' => 'Organizer Live Lama']);
        $bankAccount = $this->bankAccount($event, ['bank_name' => 'Bank Live Lama']);
        $agreement = $this->agreement($tenant, $event);

        $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid).'?activeTab=mou')
            ->assertOk()
            ->assertSeeText('Nama Live Lama')
            ->assertSeeText('Organizer Live Lama')
            ->assertSeeText('Bank Live Lama');

        $event->update(['event' => 'Nama Live Baru']);
        $organizer->update(['organizer_name' => 'Organizer Live Baru']);
        $bankAccount->update(['bank_name' => 'Bank Live Baru']);

        $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid).'?activeTab=mou')
            ->assertOk()
            ->assertSeeText('Nama Live Baru')
            ->assertSeeText('Organizer Live Baru')
            ->assertSeeText('Bank Live Baru')
            ->assertDontSeeText('Nama Live Lama')
            ->assertDontSeeText('Organizer Live Lama')
            ->assertDontSeeText('Bank Live Lama');

        $agreement->refresh();

        $this->assertSame(Agreement::STATUS_DRAFT, $agreement->status);
        $this->assertSame(1, (int) $agreement->version);
        $this->assertNull($agreement->event_snapshot);
        $this->assertNull($agreement->party_snapshot);
        $this->assertNull($agreement->bank_snapshot);
        $this->assertNull($agreement->document_snapshot);
        $this->assertNull($agreement->commercial_snapshot);
        $this->assertNull($agreement->unsigned_pdf_path);
        $this->assertNull($agreement->signed_pdf_path);
        $this->assertNull($agreement->privy_document_id);
        $this->assertNull($agreement->privy_reference);
    }

    public function test_preview_of_completed_agreement_is_read_only_and_hides_sensitive_paths(): void
    {
        $tenant = $this->tenant(['email' => 'completed-preview@example.test']);
        $event = $this->event($tenant, ['event' => 'Konser Completed Preview']);
        $this->organizer($event);
        $this->bankAccount($event, [
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/secret-bank.pdf',
        ]);
        $this->organizerLetter($event, [
            'file_path' => 'private/events/'.$event->uid.'/documents/secret-letter.pdf',
        ]);
        $agreement = $this->agreement($tenant, $event, [
            'status' => Agreement::STATUS_COMPLETED,
            'unsigned_pdf_path' => 'private/agreements/unsigned-secret.pdf',
            'signed_pdf_path' => 'private/agreements/signed-secret.pdf',
            'privy_reference' => 'PRIVY-SECRET-REF',
        ]);

        $response = $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid).'?activeTab=mou');

        $response->assertOk()
            ->assertSeeText('MOU Terverifikasi/Selesai')
            ->assertDontSeeText('private/agreements/unsigned-secret.pdf')
            ->assertDontSeeText('private/agreements/signed-secret.pdf')
            ->assertDontSeeText('PRIVY-SECRET-REF')
            ->assertDontSeeText('storage/app/private');

        $agreement->refresh();

        $this->assertSame(Agreement::STATUS_COMPLETED, $agreement->status);
        $this->assertSame('private/agreements/unsigned-secret.pdf', $agreement->unsigned_pdf_path);
        $this->assertSame('private/agreements/signed-secret.pdf', $agreement->signed_pdf_path);
        $this->assertSame('PRIVY-SECRET-REF', $agreement->privy_reference);
    }

    public function test_preview_keeps_commercial_data_internal_without_rendering_it_in_mou_html(): void
    {
        $tenant = $this->tenant(['email' => 'commercial-preview@example.test']);
        $event = $this->event($tenant, [
            'event' => 'Konser Komersial Preview',
            'fee' => 11,
            'payment_otp_enabled' => true,
        ]);
        $this->agreement($tenant, $event);

        $gateway = PaymentGateway::create([
            'payment' => 'Gateway Manual Preview',
            'category' => 'bank_transfer',
            'biaya' => 0,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 3,
            'midtrans_code' => null,
            'icon' => null,
            'is_active' => true,
            'slug' => 'gateway-manual-preview',
        ]);

        EventPaymentGateway::create([
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => true,
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 4500,
            'fee_percent' => 1.5,
        ]);

        $preview = app(AgreementPreviewService::class)->buildForEvent($event->fresh());

        $this->assertSame('percent', $preview['commercial']['buyer_fee']['mode']);
        $this->assertEquals(11.0, $preview['commercial']['buyer_fee']['value']);
        $this->assertSame('11%', $preview['commercial']['ticket_tax']['value']);
        $this->assertTrue($preview['commercial']['payment_otp_enabled']);
        $this->assertSame('Gateway Manual Preview', $preview['commercial']['payment_gateways'][0]['payment']);
        $this->assertSame('4500.00', $preview['commercial']['payment_gateways'][0]['resolved_fee_fixed']);
        $this->assertSame('1.5', $preview['commercial']['payment_gateways'][0]['resolved_fee_percent']);

        $html = view('agreements.mou-v2-preview', [
            'payload' => $preview,
        ])->render();

        $this->assertStringNotContainsString('Biaya Pembeli / Event Fee', $html);
        $this->assertStringNotContainsString('11%', $html);
        $this->assertStringNotContainsString('Gateway Manual Preview', $html);
        $this->assertStringNotContainsString('Rp 4.500', $html);
        $this->assertStringNotContainsString('1.5%', $html);
    }

    public function test_preview_keeps_percent_buyer_fee_internal_without_rendering_it_in_mou_html(): void
    {
        $tenant = $this->tenant(['email' => 'buyer-percent@example.test']);
        $event = $this->event($tenant, [
            'event' => 'Konser Buyer Percent',
            'fee' => 10,
        ]);
        $this->agreement($tenant, $event);

        $preview = app(AgreementPreviewService::class)->buildForEvent($event->fresh());

        $this->assertSame('percent', $preview['commercial']['buyer_fee']['mode']);
        $this->assertEquals(10.0, $preview['commercial']['buyer_fee']['value']);
        $this->assertSame('10%', $preview['commercial']['ticket_tax']['value']);

        $html = view('agreements.mou-v2-preview', [
            'payload' => $preview,
        ])->render();

        $this->assertStringNotContainsString('Biaya Pembeli / Event Fee', $html);
        $this->assertStringNotContainsString('10%', $html);
        $this->assertStringNotContainsString('Rp 10', $html);
    }

    public function test_preview_keeps_fixed_buyer_fee_internal_without_rendering_it_in_mou_html(): void
    {
        $tenant = $this->tenant(['email' => 'buyer-fixed@example.test']);
        $event = $this->event($tenant, [
            'event' => 'Konser Buyer Fixed',
            'fee' => 5000,
        ]);
        $this->agreement($tenant, $event);

        $preview = app(AgreementPreviewService::class)->buildForEvent($event->fresh());

        $this->assertSame('fixed', $preview['commercial']['buyer_fee']['mode']);
        $this->assertEquals(5000.0, $preview['commercial']['buyer_fee']['value']);
        $this->assertSame('Rp 5.000', $preview['commercial']['ticket_tax']['value']);

        $html = view('agreements.mou-v2-preview', [
            'payload' => $preview,
        ])->render();

        $this->assertStringNotContainsString('Biaya Pembeli / Event Fee', $html);
        $this->assertStringNotContainsString('Rp 5.000', $html);
        $this->assertStringNotContainsString('5000%', $html);
    }

    public function test_preview_keeps_global_gateway_new_defaults_internal_without_rendering_gateway_details(): void
    {
        $tenant = $this->tenant(['email' => 'gateway-global-new@example.test']);
        $event = $this->event($tenant, ['event' => 'Konser Gateway Global New']);
        $this->agreement($tenant, $event);
        $gateway = $this->gateway([
            'payment' => 'Gateway Global New',
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 3,
        ]);
        $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
            'is_active' => true,
        ]);

        $preview = app(AgreementPreviewService::class)->buildForEvent($event->fresh());

        $this->assertContains('Gateway Global New', $preview['commercial']['active_payment_methods']);
        $this->assertSame('Gateway Global New', $preview['commercial']['payment_gateways'][0]['payment']);
        $this->assertSame('2000.00', $preview['commercial']['payment_gateways'][0]['resolved_fee_fixed']);
        $this->assertSame('3', $preview['commercial']['payment_gateways'][0]['resolved_fee_percent']);

        $html = view('agreements.mou-v2-preview', [
            'payload' => $preview,
        ])->render();

        $this->assertStringNotContainsString('Gateway Global New', $html);
        $this->assertStringNotContainsString('Biaya Kanal Pembayaran', $html);
        $this->assertStringNotContainsString('Rp 2.000', $html);
        $this->assertStringNotContainsString('3%', $html);
    }

    public function test_preview_keeps_explicit_zero_global_defaults_internal_without_rendering_gateway_details(): void
    {
        $tenant = $this->tenant(['email' => 'gateway-global-zero@example.test']);
        $event = $this->event($tenant, ['event' => 'Konser Gateway Zero']);
        $this->agreement($tenant, $event);
        $gateway = $this->gateway([
            'payment' => 'Gateway Zero Default',
            'biaya' => 4000,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => 0,
            'default_fee_percent' => 0,
        ]);
        $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
            'is_active' => true,
        ]);

        $preview = app(AgreementPreviewService::class)->buildForEvent($event->fresh());

        $this->assertContains('Gateway Zero Default', $preview['commercial']['active_payment_methods']);
        $this->assertSame('0.00', $preview['commercial']['payment_gateways'][0]['resolved_fee_fixed']);
        $this->assertSame('0', $preview['commercial']['payment_gateways'][0]['resolved_fee_percent']);

        $html = view('agreements.mou-v2-preview', [
            'payload' => $preview,
        ])->render();

        $this->assertStringNotContainsString('Gateway Zero Default', $html);
        $this->assertStringNotContainsString('Rp 0', $html);
        $this->assertStringNotContainsString('0%', $html);
        $this->assertStringNotContainsString('Rp 4.000', $html);
    }

    public function test_preview_keeps_legacy_gateway_rupiah_fallback_internal_without_rendering_gateway_details(): void
    {
        $tenant = $this->tenant(['email' => 'gateway-legacy-rupiah@example.test']);
        $event = $this->event($tenant, ['event' => 'Konser Gateway Legacy Rupiah']);
        $this->agreement($tenant, $event);
        $gateway = $this->gateway([
            'payment' => 'Gateway Legacy Rupiah',
            'biaya' => 4000,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => 0,
            'default_fee_percent' => 0,
        ]);
        $eventGateway = $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
            'is_active' => true,
        ]);
        $gateway->setAttribute('default_fee_fixed', null);
        $gateway->setAttribute('default_fee_percent', null);
        $eventGateway->setRelation('paymentGateway', $gateway);
        $event->setRelation('eventPaymentGateways', collect([$eventGateway]));

        $preview = app(AgreementPreviewService::class)->buildForEvent($event);
        $html = view('agreements.mou-v2-preview', ['payload' => $preview])->render();

        $this->assertSame('Gateway Legacy Rupiah', $preview['commercial']['payment_gateways'][0]['payment']);
        $this->assertSame('4000.00', $preview['commercial']['payment_gateways'][0]['resolved_fee_fixed']);
        $this->assertSame('0', $preview['commercial']['payment_gateways'][0]['resolved_fee_percent']);
        $this->assertStringNotContainsString('Gateway Legacy Rupiah', $html);
        $this->assertStringNotContainsString('Rp 4.000', $html);
    }

    public function test_draft_preview_includes_safe_responsible_identity_metadata(): void
    {
        $tenant = $this->tenant(['email' => 'preview-identity@example.test']);
        $event = $this->event($tenant, ['event' => 'Konser Preview Identity']);
        $this->agreement($tenant, $event);
        $this->responsibleIdentity($event, [
            'original_name' => 'preview-identity.pdf',
            'status' => 'pending',
            'verified_at' => null,
        ]);

        $preview = app(AgreementPreviewService::class)->buildForEvent($event);

        $this->assertSame(EventDocument::TYPE_RESPONSIBLE_IDENTITY, $preview['responsible_identity']['document_type']);
        $this->assertSame('preview-identity.pdf', $preview['responsible_identity']['original_name']);
        $this->assertSame('pending', $preview['responsible_identity']['verification_status']);
        $this->assertArrayNotHasKey('file_path', $preview['responsible_identity']);
        $this->assertArrayNotHasKey('nik', $preview['responsible_identity']);
    }

    public function test_preview_keeps_legacy_gateway_percent_fallback_internal_without_rendering_gateway_details(): void
    {
        $tenant = $this->tenant(['email' => 'gateway-legacy-percent@example.test']);
        $event = $this->event($tenant, ['event' => 'Konser Gateway Legacy Percent']);
        $this->agreement($tenant, $event);
        $gateway = $this->gateway([
            'payment' => 'Gateway Legacy Percent',
            'biaya' => 3,
            'biaya_type' => 'persen',
            'default_fee_fixed' => 0,
            'default_fee_percent' => 0,
        ]);
        $eventGateway = $this->eventGateway($event, $gateway, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
            'is_active' => true,
        ]);
        $gateway->setAttribute('default_fee_fixed', null);
        $gateway->setAttribute('default_fee_percent', null);
        $eventGateway->setRelation('paymentGateway', $gateway);
        $event->setRelation('eventPaymentGateways', collect([$eventGateway]));

        $preview = app(AgreementPreviewService::class)->buildForEvent($event);
        $html = view('agreements.mou-v2-preview', ['payload' => $preview])->render();

        $this->assertSame('Gateway Legacy Percent', $preview['commercial']['payment_gateways'][0]['payment']);
        $this->assertSame('0.00', $preview['commercial']['payment_gateways'][0]['resolved_fee_fixed']);
        $this->assertSame('3', $preview['commercial']['payment_gateways'][0]['resolved_fee_percent']);
        $this->assertStringNotContainsString('Gateway Legacy Percent', $html);
        $this->assertStringNotContainsString('3%', $html);
    }

    public function test_preview_keeps_globally_inactive_gateway_internal_without_rendering_gateway_details(): void
    {
        $tenant = $this->tenant(['email' => 'gateway-global-inactive@example.test']);
        $event = $this->event($tenant, ['event' => 'Konser Gateway Global Inactive']);
        $this->agreement($tenant, $event);
        $gateway = $this->gateway([
            'payment' => 'Gateway Global Inactive',
            'is_active' => false,
        ]);
        $this->eventGateway($event, $gateway, [
            'is_active' => true,
        ]);

        $preview = app(AgreementPreviewService::class)->buildForEvent($event->fresh());

        $this->assertSame('Gateway Global Inactive', $preview['commercial']['payment_gateways'][0]['payment']);
        $this->assertFalse($preview['commercial']['payment_gateways'][0]['effective_is_active']);
        $this->assertNotContains('Gateway Global Inactive', $preview['commercial']['active_payment_methods']);

        $html = view('agreements.mou-v2-preview', [
            'payload' => $preview,
        ])->render();

        $this->assertStringNotContainsString('Gateway Global Inactive', $html);
    }

    public function test_preview_keeps_event_inactive_gateway_internal_without_rendering_gateway_details(): void
    {
        $tenant = $this->tenant(['email' => 'gateway-event-inactive@example.test']);
        $event = $this->event($tenant, ['event' => 'Konser Gateway Event Inactive']);
        $this->agreement($tenant, $event);
        $gateway = $this->gateway([
            'payment' => 'Gateway Event Inactive',
            'is_active' => true,
        ]);
        $this->eventGateway($event, $gateway, [
            'is_active' => false,
        ]);

        $preview = app(AgreementPreviewService::class)->buildForEvent($event->fresh());

        $this->assertSame('Gateway Event Inactive', $preview['commercial']['payment_gateways'][0]['payment']);
        $this->assertFalse($preview['commercial']['payment_gateways'][0]['effective_is_active']);
        $this->assertNotContains('Gateway Event Inactive', $preview['commercial']['active_payment_methods']);

        $html = view('agreements.mou-v2-preview', [
            'payload' => $preview,
        ])->render();

        $this->assertStringNotContainsString('Gateway Event Inactive', $html);
    }

    private function tenant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Tenant Preview',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat Tenant Preview',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'User Preview',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat User Preview',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
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
            'event' => 'Event Preview '.$uid,
            'alamat' => 'Alamat Legacy Preview',
            'tanggal' => '2026-09-10 19:00:00',
            'event_end' => '2026-09-10 22:00:00',
            'venue_name' => 'Venue Preview',
            'venue_address' => 'Jl. Preview',
            'venue_city' => 'Jakarta',
            'venue_province' => 'DKI Jakarta',
            'status' => 'inactive',
            'cover' => 'preview-cover.jpg',
            'fee' => 10,
            'pajak' => 0,
            'deskripsi' => 'Deskripsi preview',
            'map' => 'https://maps.google.com/?q=preview',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'event-preview-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
        ], $overrides));
    }

    private function organizer(Event $event, array $overrides = []): EventOrganizer
    {
        return EventOrganizer::create(array_merge([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Preview',
            'responsible_name' => 'Responsible Preview',
            'responsible_position' => 'Project Lead',
            'phone' => '081234567890',
            'email' => 'organizer-preview@example.test',
            'address' => 'Alamat Organizer Preview',
        ], $overrides));
    }

    private function bankAccount(Event $event, array $overrides = []): EventBankAccount
    {
        return EventBankAccount::create(array_merge([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Preview Default',
            'account_number' => '1234567890',
            'account_holder_name' => 'Organizer Preview',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/book.pdf',
            'bank_book_original_name' => 'book.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'verified',
        ], $overrides));
    }

    private function organizerLetter(Event $event, array $overrides = []): EventDocument
    {
        return EventDocument::create(array_merge([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-PREVIEW-001',
            'document_date' => '2026-08-10',
            'original_name' => 'organizer-letter.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/organizer-letter.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
        ], $overrides));
    }

    private function responsibleIdentity(Event $event, array $overrides = []): EventDocument
    {
        return EventDocument::create(array_merge([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_RESPONSIBLE_IDENTITY,
            'document_number' => null,
            'document_date' => null,
            'original_name' => 'responsible-identity-preview.pdf',
            'file_path' => 'private/events/'.$event->uid.'/responsible-identity/responsible-identity-preview.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-22 10:00:00',
            'verified_by' => 'admin-preview',
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
            'payment' => 'Gateway '.Str::random(6),
            'category' => 'bank_transfer',
            'biaya' => 0,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => 0,
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
}
