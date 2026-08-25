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
        $this->agreement($tenant, $event);

        $response = $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid).'?activeTab=mou');

        $response->assertOk()
            ->assertSeeText('MOU Event')
            ->assertSeeText('Konser Preview Tenant')
            ->assertSeeText('PT Preview Tenant')
            ->assertSeeText('Sawal Preview')
            ->assertSeeText('Istora Preview')
            ->assertSeeText('Bank Preview')
            ->assertSeeText('MOU-PREV-001')
            ->assertSeeText('20-08-2026')
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
            ->assertSeeText(Agreement::STATUS_COMPLETED)
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

    public function test_preview_distinguishes_ticket_tax_from_payment_gateway_fee_configuration(): void
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

        $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid).'?activeTab=mou')
            ->assertOk()
            ->assertSeeText('Pajak Tiket (Pembeli)')
            ->assertSeeText('11%')
            ->assertSeeText('Gateway Manual Preview')
            ->assertSeeText('MANUAL')
            ->assertSeeText('Rp 4.500')
            ->assertSeeText('1.5%')
            ->assertSeeText('Aktif');
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
}
