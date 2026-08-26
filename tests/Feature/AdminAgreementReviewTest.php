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
use App\Services\Agreements\AgreementReviewService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAgreementReviewTest extends TestCase
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

    public function test_admin_can_open_review_mou_tab(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['event' => 'Review MOU Admin Event']);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.event.detail', $event->uid).'?activeTab=review-mou')
            ->assertOk()
            ->assertSeeText('Review MOU')
            ->assertSeeText('Readiness Checklist')
            ->assertSeeText('Preview MOU M6')
            ->assertSeeText('Ringkasan Konfigurasi Komersial');
    }

    public function test_non_admin_cannot_access_admin_review_page_and_private_file(): void
    {
        $tenant = $this->tenant(['email' => 'tenant-review-access@example.test']);
        $event = $this->event($tenant);
        $this->verifiedBankAccount($event);

        $this->actingAs($tenant)
            ->get(route('admin.event.detail', $event->uid).'?activeTab=review-mou')
            ->assertRedirect('/');

        $this->actingAs($tenant)
            ->get(route('admin.event.review.bank-book', $event->uid))
            ->assertForbidden();
    }

    public function test_approve_bank_account_succeeds(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $bankAccount = $this->bankAccount($event, ['status' => 'pending']);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'review-mou')
            ->call('approveBankAccount');

        $bankAccount->refresh();

        $this->assertSame('verified', $bankAccount->status);
        $this->assertSame($admin->uid, $bankAccount->verified_by);
        $this->assertNotNull($bankAccount->verified_at);
        $this->assertNull($bankAccount->rejection_reason);
    }

    public function test_reject_bank_account_requires_reason(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->bankAccount($event);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'review-mou')
            ->call('rejectBankAccount')
            ->assertHasErrors(['bankRejectionReason' => 'required']);
    }

    public function test_missing_bank_file_cannot_be_verified(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $bankAccount = EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Missing',
            'account_number' => '99887766',
            'account_holder_name' => 'Missing File',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/missing-bank.pdf',
            'bank_book_original_name' => 'missing-bank.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'review-mou')
            ->call('approveBankAccount');

        $bankAccount->refresh();

        $this->assertSame('pending', $bankAccount->status);
        $this->assertNull($bankAccount->verified_at);
        $this->assertNull($bankAccount->verified_by);
    }

    public function test_approve_organizer_letter_succeeds(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $document = $this->organizerLetter($event, ['status' => 'pending']);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'review-mou')
            ->call('approveOrganizerLetter');

        $document->refresh();

        $this->assertSame('verified', $document->status);
        $this->assertSame($admin->uid, $document->verified_by);
        $this->assertNotNull($document->verified_at);
        $this->assertNull($document->rejection_reason);
    }

    public function test_reject_organizer_letter_requires_reason(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizerLetter($event);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'review-mou')
            ->call('rejectOrganizerLetter')
            ->assertHasErrors(['organizerLetterRejectionReason' => 'required']);
    }

    public function test_cross_event_isolation_is_enforced_for_review_actions(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $eventA = $this->event($tenant, ['event' => 'Event A Review']);
        $eventB = $this->event($tenant, ['event' => 'Event B Review']);
        $bankA = $this->bankAccount($eventA, ['status' => 'pending']);
        $bankB = $this->bankAccount($eventB, ['status' => 'pending']);
        $letterA = $this->organizerLetter($eventA, ['status' => 'pending']);
        $letterB = $this->organizerLetter($eventB, ['status' => 'pending']);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $eventA->uid])
            ->set('activeTab', 'review-mou')
            ->call('approveBankAccount')
            ->call('approveOrganizerLetter');

        $bankA->refresh();
        $bankB->refresh();
        $letterA->refresh();
        $letterB->refresh();

        $this->assertSame('verified', $bankA->status);
        $this->assertSame('pending', $bankB->status);
        $this->assertSame('verified', $letterA->status);
        $this->assertSame('pending', $letterB->status);
    }

    public function test_admin_can_open_private_bank_book(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $bankAccount = $this->bankAccount($event, [
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/review-bank-book.pdf',
            'bank_book_original_name' => 'review-bank-book.pdf',
        ]);
        Storage::disk('local')->put($bankAccount->bank_book_path, 'bank-book-review-content');

        $response = $this->actingAs($admin)
            ->get(route('admin.event.review.bank-book', $event->uid));

        $response->assertOk();
        $this->assertSame('bank-book-review-content', $response->streamedContent());
        $this->assertStringContainsString('review-bank-book.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_admin_can_open_private_organizer_letter(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $document = $this->organizerLetter($event, [
            'file_path' => 'private/events/'.$event->uid.'/documents/review-letter.pdf',
            'original_name' => 'review-letter.pdf',
        ]);
        Storage::disk('local')->put($document->file_path, 'organizer-letter-review-content');

        $response = $this->actingAs($admin)
            ->get(route('admin.event.review.organizer-letter', $event->uid));

        $response->assertOk();
        $this->assertSame('organizer-letter-review-content', $response->streamedContent());
        $this->assertStringContainsString('review-letter.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_missing_private_review_file_returns_404(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->bankAccount($event, [
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/missing-review.pdf',
            'bank_book_original_name' => 'missing-review.pdf',
            'skip_storage' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.event.review.bank-book', $event->uid))
            ->assertNotFound();
    }

    public function test_review_html_does_not_expose_private_paths(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $bankAccount = $this->verifiedBankAccount($event);
        $document = $this->verifiedOrganizerLetter($event);
        $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.event.detail', $event->uid).'?activeTab=review-mou')
            ->assertOk()
            ->assertDontSeeText($bankAccount->bank_book_path)
            ->assertDontSeeText($document->file_path)
            ->assertDontSeeText('storage/app/private');
    }

    public function test_readiness_is_false_when_bank_and_document_are_pending(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->bankAccount($event, ['status' => 'pending']);
        $this->organizerLetter($event, ['status' => 'pending']);
        $this->agreement($tenant, $event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $review = app(AgreementReviewService::class)->buildForEvent($event->fresh());

        $this->assertFalse($review['is_ready']);
        $this->assertSame('BELUM SIAP FINALISASI', $review['status_label']);

        $this->actingAs($admin)
            ->get(route('admin.event.detail', $event->uid).'?activeTab=review-mou')
            ->assertOk()
            ->assertSeeText('BELUM SIAP FINALISASI')
            ->assertSeeText('Rekening event belum diverifikasi.')
            ->assertSeeText('Surat penyelenggara belum diverifikasi.');
    }

    public function test_readiness_is_true_when_all_prerequisites_are_valid_and_agreement_stays_draft(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['status' => 'inactive']);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $agreement = $this->agreement($tenant, $event, ['status' => Agreement::STATUS_DRAFT]);
        $gateway = $this->gateway(['is_active' => true]);
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $review = app(AgreementReviewService::class)->buildForEvent($event->fresh());

        $this->assertTrue($review['is_ready']);
        $this->assertSame('SIAP FINALISASI', $review['status_label']);

        $this->actingAs($admin)
            ->get(route('admin.event.detail', $event->uid).'?activeTab=review-mou')
            ->assertOk()
            ->assertSeeText('SIAP FINALISASI');

        $agreement->refresh();
        $event->refresh();

        $this->assertSame(Agreement::STATUS_DRAFT, $agreement->status);
        $this->assertSame('inactive', $event->status);
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

    public function test_globally_inactive_gateway_is_not_counted_as_effectively_active(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $this->agreement($tenant, $event);
        $gateway = $this->gateway(['is_active' => false, 'payment' => 'Gateway Inactive Global']);
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $review = app(AgreementReviewService::class)->buildForEvent($event->fresh());
        $effectiveGatewayItem = collect($review['items'])->firstWhere('key', 'effective_active_gateway');

        $this->assertFalse($review['is_ready']);
        $this->assertFalse($effectiveGatewayItem['passed']);
        $this->assertSame('Belum ada payment gateway event yang efektif aktif.', $effectiveGatewayItem['reason']);
    }

    public function test_legacy_event_without_agreement_is_safe_and_does_not_auto_create_agreement(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant(['email' => 'legacy-admin-review@example.test']);
        $event = $this->event($tenant, ['event' => 'Legacy No Agreement']);
        $this->organizer($event);
        $this->verifiedBankAccount($event);
        $this->verifiedOrganizerLetter($event);
        $gateway = $this->gateway();
        $this->eventGateway($event, $gateway, ['is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.event.detail', $event->uid).'?activeTab=review-mou')
            ->assertOk()
            ->assertSeeText('MOU belum tersedia untuk event ini.')
            ->assertSeeText('BELUM SIAP FINALISASI');

        $this->assertDatabaseCount('agreements', 0);
    }

    private function admin(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Admin Review',
            'email' => 'admin-review@example.test',
            'role' => 'admin',
        ], $overrides));
    }

    private function tenant(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Tenant Review',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Review User',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat Review User',
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
            $table->decimal('default_fee_fixed', 15, 2)->default(0);
            $table->decimal('default_fee_percent', 8, 4)->default(0);
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
            'event' => 'Admin Review Event '.$uid,
            'alamat' => 'Alamat Review Event',
            'tanggal' => '2026-09-10 19:00:00',
            'event_end' => '2026-09-10 22:00:00',
            'venue_name' => 'Venue Review',
            'venue_address' => 'Jl. Review',
            'venue_city' => 'Jakarta',
            'venue_province' => 'DKI Jakarta',
            'status' => 'inactive',
            'cover' => 'review-cover.jpg',
            'fee' => 10,
            'pajak' => 0,
            'deskripsi' => 'Deskripsi review admin',
            'map' => 'https://maps.google.com/?q=review',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'admin-review-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
        ], $overrides));
    }

    private function organizer(Event $event, array $overrides = []): EventOrganizer
    {
        return EventOrganizer::create(array_merge([
            'event_uid' => $event->uid,
            'organizer_name' => 'PT Organizer Review',
            'responsible_name' => 'Responsible Review',
            'responsible_position' => 'Director',
            'phone' => '081234567890',
            'email' => 'organizer-review@example.test',
            'address' => 'Alamat organizer review',
        ], $overrides));
    }

    private function bankAccount(Event $event, array $overrides = []): EventBankAccount
    {
        $data = array_merge([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Review',
            'account_number' => '1234567890',
            'account_holder_name' => 'Organizer Review',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/book-review.pdf',
            'bank_book_original_name' => 'book-review.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
            'verified_at' => null,
            'verified_by' => null,
            'rejection_reason' => null,
        ], $overrides);

        $bankAccount = EventBankAccount::create($data);

        if (filled($bankAccount->bank_book_path) && empty($overrides['skip_storage'])) {
            Storage::disk('local')->put($bankAccount->bank_book_path, 'bank-review-file');
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
            'document_number' => 'DOC-REVIEW-001',
            'document_date' => '2026-08-20',
            'original_name' => 'organizer-review.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/organizer-review.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
            'rejection_reason' => null,
        ], $overrides);

        $document = EventDocument::create($data);

        if (filled($document->file_path) && empty($overrides['skip_storage'])) {
            Storage::disk('local')->put($document->file_path, 'organizer-review-file');
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
            'payment' => 'Gateway Review '.Str::random(5),
            'category' => 'bank_transfer',
            'biaya' => 0,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 2,
            'midtrans_code' => null,
            'icon' => null,
            'is_active' => true,
            'slug' => 'gateway-review-'.Str::lower(Str::random(8)),
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
