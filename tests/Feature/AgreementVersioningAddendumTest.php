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
use App\Models\User;
use App\Services\Agreements\AgreementFinalizationService;
use App\Services\Agreements\AgreementSignedUploadService;
use App\Services\Agreements\AgreementSignedVerificationService;
use App\Services\Agreements\AgreementVersioningService;
use App\Services\Events\EventActivationGuardService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AgreementVersioningAddendumTest extends TestCase
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
            $table->string('ticket_tax_mode')->default('fixed');
            $table->unsignedBigInteger('ticket_tax_fixed')->default(0);
            $table->decimal('ticket_tax_percent', 8, 4)->default(0);
            $table->string('start_sale')->nullable();
            $table->string('slug')->nullable();
            $table->string('konfirmasi')->nullable();
            $table->boolean('payment_otp_enabled')->default(false);
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
            $table->string('document_number')->nullable();
            $table->date('document_date')->nullable();
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
            $table->string('parent_agreement_uid')->nullable();
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
            $table->boolean('is_active')->default(false);
            $table->string('fee_mode')->default('global');
            $table->decimal('fee_fixed', 15, 2)->nullable();
            $table->decimal('fee_percent', 8, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('talent', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('talent')->nullable();
            $table->string('gambar')->nullable();
            $table->string('link')->nullable();
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
            $table->string('user_uid')->nullable();
            $table->string('event_uid');
            $table->string('invoice')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_type')->nullable();
            $table->unsignedBigInteger('internet_fee')->default(0);
            $table->unsignedBigInteger('pajak')->default(0);
            $table->unsignedBigInteger('total')->default(0);
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
    }

    private function user(array $overrides = []): User
    {
        return User::create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat User',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function admin(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Admin User',
            'email' => 'admin-' . Str::random(6) . '@example.test',
            'role' => 'admin',
        ], $overrides));
    }

    private function tenant(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Tenant User',
            'email' => 'tenant-' . Str::random(6) . '@example.test',
            'role' => 'penyewa',
        ], $overrides));
    }

    private function event(User $tenant, array $overrides = []): Event
    {
        $category = Category::create([
            'name' => 'Category ' . Str::random(6),
            'slug' => 'category-' . Str::lower(Str::random(8)),
        ]);
        $uid = (string) Str::uuid();

        return Event::create(array_merge([
            'uid' => $uid,
            'category_id' => $category->id,
            'user_uid' => $tenant->uid,
            'event' => 'Konser Rock Merdeka ' . $uid,
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
            'ticket_tax_mode' => 'fixed',
            'ticket_tax_fixed' => 2000,
            'ticket_tax_percent' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => 'https://maps.google.com/?q=venue',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'event-' . Str::lower(Str::random(8)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
        ], $overrides));
    }

    private function createDraftMouEvent(User $tenant, User $admin): array
    {
        $event = $this->event($tenant);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'PT Musik Indonesia',
            'responsible_name' => 'Budi Santoso',
            'responsible_position' => 'Direktur',
            'phone' => '081234567890',
            'email' => 'organizer@example.test',
            'address' => 'Jl. Bisnis No. 1',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'PT Musik Indonesia',
            'bank_book_path' => 'private/events/' . $event->uid . '/bank/book.pdf',
            'status' => 'verified',
            'verified_by' => $admin->uid,
            'verified_at' => now(),
        ]);
        Storage::disk('local')->put('private/events/' . $event->uid . '/bank/book.pdf', 'dummy-bank-book');

        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC/2026/001',
            'document_date' => now()->toDateString(),
            'original_name' => 'surat.pdf',
            'file_path' => 'private/events/' . $event->uid . '/documents/surat.pdf',
            'status' => 'verified',
            'verified_by' => $admin->uid,
            'verified_at' => now(),
        ]);
        Storage::disk('local')->put('private/events/' . $event->uid . '/documents/surat.pdf', 'dummy-letter');

        $this->verifiedResponsibleIdentity($event, $admin->uid);

        $gateway = PaymentGateway::create([
            'payment' => 'BCA Virtual Account',
            'category' => 'bank_transfer',
            'biaya' => 2000,
            'biaya_type' => 'fixed',
            'is_active' => true,
            'slug' => 'bca-va',
        ]);

        EventPaymentGateway::create([
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => true,
            'fee_mode' => 'global',
        ]);

        $mou = Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'created_by' => $admin->uid,
            'status' => Agreement::STATUS_DRAFT,
            'type' => Agreement::TYPE_MOU,
            'version' => 1,
            'document_number' => 'MOU/001',
        ]);

        return [$event, $mou];
    }

    private function createCompletedMouEvent(User $tenant, User $admin): array
    {
        [$event, $mou] = $this->createDraftMouEvent($tenant, $admin);

        // Finalize MOU
        $finResult = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $mou->uid);
        $this->assertTrue($finResult['ok']);
        $mou->refresh();

        // Upload signed MOU
        $dummyPdf = UploadedFile::fake()->create('signed-mou.pdf', 100, 'application/pdf');
        $upResult = app(AgreementSignedUploadService::class)->storeForEvent($event, $tenant->uid, $dummyPdf, $mou->uid);
        $this->assertTrue($upResult['ok']);
        $mou->refresh();

        // Admin approve signed MOU
        $appResult = app(AgreementSignedVerificationService::class)->approveForEvent($event, $admin->uid, $mou->uid);
        $this->assertTrue($appResult['ok']);
        $mou->refresh();

        $this->assertTrue($mou->isCompleted());
        $this->assertEquals(Agreement::SIGNED_REVIEW_VERIFIED, $mou->signed_review_status);

        return [$event, $mou];
    }

    private function verifiedResponsibleIdentity(Event $event, string $verifiedBy, array $overrides = []): EventDocument
    {
        $document = EventDocument::updateOrCreate(
            [
                'event_uid' => $event->uid,
                'document_type' => EventDocument::TYPE_RESPONSIBLE_IDENTITY,
            ],
            array_merge([
                'uid' => (string) Str::uuid(),
                'document_number' => null,
                'document_date' => null,
                'original_name' => 'responsible-identity.pdf',
                'file_path' => 'private/events/' . $event->uid . '/responsible-identity/responsible-identity.pdf',
                'mime_type' => 'application/pdf',
                'status' => 'verified',
                'verified_by' => $verifiedBy,
                'verified_at' => now(),
                'rejection_reason' => null,
            ], $overrides)
        );

        Storage::disk('local')->put($document->file_path, 'dummy-responsible-identity');

        return $document;
    }

    private function createCompletedMouEventWithGatewayConfig(User $tenant, User $admin, array $gatewayConfigOverrides): array
    {
        [$event, $mou] = $this->createDraftMouEvent($tenant, $admin);

        EventPaymentGateway::query()->where('event_id', $event->id)->firstOrFail()->update(array_merge([
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 2000,
            'fee_percent' => 0,
        ], $gatewayConfigOverrides));

        $finResult = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $mou->uid);
        $this->assertTrue($finResult['ok']);
        $mou->refresh();

        $dummyPdf = UploadedFile::fake()->create('signed-mou-custom-gateway.pdf', 100, 'application/pdf');
        $upResult = app(AgreementSignedUploadService::class)->storeForEvent($event, $tenant->uid, $dummyPdf, $mou->uid);
        $this->assertTrue($upResult['ok']);
        $mou->refresh();

        $appResult = app(AgreementSignedVerificationService::class)->approveForEvent($event, $admin->uid, $mou->uid);
        $this->assertTrue($appResult['ok']);
        $mou->refresh();

        return [$event->fresh(), $mou->fresh()];
    }

    private function completeAddendum(Event $event, User $tenant, User $admin, Agreement $addendum, ?string $filename = null): Agreement
    {
        $filename ??= 'signed-addendum-v'.$addendum->version.'.pdf';

        $this->assertTrue(
            app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $addendum->uid)['ok']
        );

        $dummyPdf = UploadedFile::fake()->create($filename, 150, 'application/pdf');

        $this->assertTrue(
            app(AgreementSignedUploadService::class)->storeForEvent($event, $tenant->uid, $dummyPdf, $addendum->uid)['ok']
        );

        $this->assertTrue(
            app(AgreementSignedVerificationService::class)->approveForEvent($event, $admin->uid, $addendum->uid)['ok']
        );

        return $addendum->fresh();
    }

    public function test_new_event_starts_with_mou_v1_and_no_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        $event = $this->event($tenant);

        $mou = Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'created_by' => $admin->uid,
            'status' => Agreement::STATUS_DRAFT,
            'type' => Agreement::TYPE_MOU,
            'version' => 1,
            'document_number' => 'MOU/001',
        ]);

        $this->assertEquals(1, $mou->version);
        $this->assertEquals(Agreement::TYPE_MOU, $mou->type);
        $this->assertFalse($mou->isAddendum());
        $this->assertNull($mou->parent_agreement_uid);
        $this->assertCount(1, $event->agreements);
    }

    public function test_editing_event_with_completed_mou_creates_draft_addendum_v1(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        // Modify contractual field (e.g. event name)
        $event->event = 'Konser Rock Merdeka Revised';
        $event->save();

        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        $this->assertNotNull($addendum);
        $this->assertEquals(Agreement::TYPE_ADDENDUM, $addendum->type);
        $this->assertEquals(1, $addendum->version);
        $this->assertEquals(Agreement::STATUS_DRAFT, $addendum->status);
        $this->assertEquals($mou->uid, $addendum->parent_agreement_uid);

        // Assert completed MOU remains untouched (immutable)
        $mou->refresh();
        $this->assertEquals(Agreement::STATUS_COMPLETED, $mou->status);
    }

    public function test_no_changes_does_not_create_new_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);
        $this->assertNull($addendum);
        $this->assertEquals(1, Agreement::where('event_uid', $event->uid)->count());
    }

    public function test_subsequent_changes_update_existing_draft_addendum_without_duplicate(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $event->event = 'Perubahan Pertama';
        $event->save();
        $addendum1 = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        $event->venue_name = 'Venue Baru Lapangan Merdeka';
        $event->save();
        $addendum2 = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        $this->assertEquals($addendum1->id, $addendum2->id);
        $this->assertEquals(1, $addendum2->version);
        $this->assertEquals(2, Agreement::where('event_uid', $event->uid)->count());
    }

    public function test_event_gateway_active_to_inactive_does_not_create_draft_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $gatewayConfig = EventPaymentGateway::query()->where('event_id', $event->id)->firstOrFail();
        $gatewayConfig->update(['is_active' => false]);

        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($addendum);
        $this->assertSame($mou->uid, Agreement::query()->where('event_uid', $event->uid)->sole()->uid);
    }

    public function test_global_gateway_active_to_inactive_does_not_create_draft_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        PaymentGateway::query()->where('payment', 'BCA Virtual Account')->firstOrFail()->update(['is_active' => false]);

        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($addendum);
        $this->assertSame($mou->uid, Agreement::query()->where('event_uid', $event->uid)->sole()->uid);
    }

    public function test_event_fee_change_only_does_not_create_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $event->update(['fee' => 12]);

        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($addendum);
        $this->assertSame($mou->uid, Agreement::query()->where('event_uid', $event->uid)->sole()->uid);
    }

    public function test_gateway_fee_mode_change_only_does_not_create_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        EventPaymentGateway::query()->where('event_id', $event->id)->firstOrFail()->update([
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 2000,
            'fee_percent' => 0,
        ]);

        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($addendum);
        $this->assertSame($mou->uid, Agreement::query()->where('event_uid', $event->uid)->sole()->uid);
    }

    public function test_gateway_fixed_fee_change_only_does_not_create_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEventWithGatewayConfig($tenant, $admin, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 2000,
            'fee_percent' => 0,
        ]);

        EventPaymentGateway::query()->where('event_id', $event->id)->firstOrFail()->update([
            'fee_fixed' => 4500,
        ]);

        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($addendum);
        $this->assertSame($mou->uid, Agreement::query()->where('event_uid', $event->uid)->sole()->uid);
    }

    public function test_gateway_percent_fee_change_only_does_not_create_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEventWithGatewayConfig($tenant, $admin, [
            'fee_mode' => EventPaymentGateway::FEE_MODE_MANUAL,
            'fee_fixed' => 0,
            'fee_percent' => 1.5,
        ]);

        EventPaymentGateway::query()->where('event_id', $event->id)->firstOrFail()->update([
            'fee_percent' => 3.25,
        ]);

        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($addendum);
        $this->assertSame($mou->uid, Agreement::query()->where('event_uid', $event->uid)->sole()->uid);
    }

    public function test_adding_or_removing_gateway_only_does_not_create_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $extraGateway = PaymentGateway::create([
            'payment' => 'QRIS Tambahan',
            'category' => 'qris',
            'biaya' => 0,
            'biaya_type' => 'fixed',
            'default_fee_fixed' => 0,
            'default_fee_percent' => 0,
            'is_active' => true,
            'slug' => 'qris-tambahan',
        ]);

        EventPaymentGateway::create([
            'event_id' => $event->id,
            'payment_gateway_id' => $extraGateway->id,
            'is_active' => true,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
        ]);

        $afterAdd = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($afterAdd);
        $this->assertSame($mou->uid, Agreement::query()->where('event_uid', $event->uid)->sole()->uid);

        EventPaymentGateway::query()->where('event_id', $event->id)->firstOrFail()->delete();

        $afterRemove = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($afterRemove);
        $this->assertSame($mou->uid, Agreement::query()->where('event_uid', $event->uid)->sole()->uid);
    }

    public function test_payment_otp_change_still_creates_draft_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $event->update(['payment_otp_enabled' => true]);

        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);
        $preview = app(AgreementVersioningService::class)->buildAddendumPreview($event->fresh(), $addendum);

        $this->assertNotNull($addendum);
        $this->assertSame(Agreement::STATUS_DRAFT, $addendum->status);
        $this->assertSame($mou->uid, $addendum->parent_agreement_uid);
        $this->assertTrue(collect($preview['diffs'])->contains(fn (array $diff) => $diff['field'] === 'payment_otp_enabled'));
    }

    public function test_payment_otp_disable_still_creates_draft_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createDraftMouEvent($tenant, $admin);
        $event->update(['payment_otp_enabled' => true]);

        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($event->fresh(), $admin->uid, $mou->uid)['ok']);
        $this->assertTrue(app(AgreementSignedUploadService::class)->storeForEvent(
            $event->fresh(),
            $tenant->uid,
            UploadedFile::fake()->create('signed-mou-otp-enabled.pdf', 100, 'application/pdf'),
            $mou->uid
        )['ok']);
        $this->assertTrue(app(AgreementSignedVerificationService::class)->approveForEvent(
            $event->fresh(),
            $admin->uid,
            $mou->uid
        )['ok']);

        $event->update(['payment_otp_enabled' => false]);

        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);
        $preview = app(AgreementVersioningService::class)->buildAddendumPreview($event->fresh(), $addendum);

        $this->assertNotNull($addendum);
        $this->assertSame($mou->uid, $addendum->parent_agreement_uid);
        $this->assertTrue(collect($preview['diffs'])->contains(fn (array $diff) => $diff['field'] === 'payment_otp_enabled'
            && $diff['before'] === 'Aktif'
            && $diff['after'] === 'Nonaktif'));
    }

    public function test_responsible_identity_review_changes_do_not_create_addendum_or_mutate_completed_parent(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $originalDocumentSnapshot = $mou->document_snapshot;
        $originalCommercialSnapshot = $mou->commercial_snapshot;
        $originalUnsignedPdf = $mou->unsigned_pdf_path;
        $originalSignedPdf = $mou->signed_pdf_path;

        $replacementPath = 'private/events/' . $event->uid . '/responsible-identity/replacement-r5.pdf';
        Storage::disk('local')->put($replacementPath, 'replacement-r5');

        EventDocument::query()
            ->where('event_uid', $event->uid)
            ->where('document_type', EventDocument::TYPE_RESPONSIBLE_IDENTITY)
            ->firstOrFail()
            ->update([
                'original_name' => 'replacement-r5.pdf',
                'file_path' => $replacementPath,
                'status' => 'pending',
                'verified_by' => null,
                'verified_at' => null,
                'rejection_reason' => null,
            ]);

        $liveSnapshots = app(AgreementVersioningService::class)->buildLiveSnapshots($event->fresh(), $mou->fresh());

        $this->assertSame([], app(AgreementVersioningService::class)->computeDiffs($liveSnapshots, $mou->fresh()));
        $this->assertNull(app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid));

        $rejectedPath = 'private/events/' . $event->uid . '/responsible-identity/rejected-r5.pdf';
        Storage::disk('local')->put($rejectedPath, 'rejected-r5');

        EventDocument::query()
            ->where('event_uid', $event->uid)
            ->where('document_type', EventDocument::TYPE_RESPONSIBLE_IDENTITY)
            ->firstOrFail()
            ->update([
                'original_name' => 'rejected-r5.pdf',
                'file_path' => $rejectedPath,
                'status' => 'rejected',
                'verified_by' => $admin->uid,
                'verified_at' => now()->addMinute(),
                'rejection_reason' => 'Dokumen blur',
            ]);

        $liveSnapshotsAfterReview = app(AgreementVersioningService::class)->buildLiveSnapshots($event->fresh(), $mou->fresh());

        $this->assertSame([], app(AgreementVersioningService::class)->computeDiffs($liveSnapshotsAfterReview, $mou->fresh()));
        $this->assertNull(app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid));

        $mou->refresh();

        $this->assertSame($originalDocumentSnapshot, $mou->document_snapshot);
        $this->assertSame($originalCommercialSnapshot, $mou->commercial_snapshot);
        $this->assertSame($originalUnsignedPdf, $mou->unsigned_pdf_path);
        $this->assertSame($originalSignedPdf, $mou->signed_pdf_path);
        $this->assertSame('responsible-identity.pdf', $mou->document_snapshot['responsible_identity']['original_name'] ?? null);
        $this->assertSame('verified', $mou->document_snapshot['responsible_identity']['verification_status'] ?? null);
        $this->assertSame(1, Agreement::query()->where('event_uid', $event->uid)->count());
    }

    public function test_responsible_name_change_still_creates_draft_addendum_without_leaking_identity_metadata(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $sentinelPath = 'private/events/' . $event->uid . '/responsible-identity/ktp-r5-sentinel.pdf';
        Storage::disk('local')->put($sentinelPath, 'ktp-r5-sentinel');

        EventDocument::query()
            ->where('event_uid', $event->uid)
            ->where('document_type', EventDocument::TYPE_RESPONSIBLE_IDENTITY)
            ->firstOrFail()
            ->update([
                'original_name' => 'ktp-r5-sentinel.pdf',
                'file_path' => $sentinelPath,
                'status' => 'rejected',
                'verified_by' => $admin->uid,
                'verified_at' => null,
                'rejection_reason' => 'Foto buram',
            ]);

        $event->organizer()->update([
            'responsible_name' => 'Siti Responsible R5',
        ]);

        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);
        $preview = app(AgreementVersioningService::class)->buildAddendumPreview($event->fresh(), $addendum);
        $diffsJson = (string) json_encode($preview['diffs']);

        $this->assertNotNull($addendum);
        $this->assertSame($mou->uid, $addendum->parent_agreement_uid);
        $this->assertTrue(collect($preview['diffs'])->contains(fn (array $diff) => $diff['field'] === 'responsible_name'
            && $diff['before'] === 'Budi Santoso'
            && $diff['after'] === 'Siti Responsible R5'));
        $this->assertStringNotContainsString('ktp-r5-sentinel.pdf', $diffsJson);
        $this->assertStringNotContainsString('private/events/', $diffsJson);
    }

    public function test_reverting_live_data_removes_stale_draft_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $originalName = $event->event;
        $event->update(['event' => 'Perubahan Draft Addendum']);

        $draftAddendum = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        $this->assertNotNull($draftAddendum);
        $this->assertDatabaseHas('agreements', ['uid' => $draftAddendum->uid, 'status' => Agreement::STATUS_DRAFT]);

        $event->update(['event' => $originalName]);

        $result = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($result);
        $this->assertDatabaseMissing('agreements', ['uid' => $draftAddendum->uid]);
        $this->assertSame(1, Agreement::where('event_uid', $event->uid)->count());
        $this->assertSame($mou->uid, Agreement::where('event_uid', $event->uid)->sole()->uid);
    }

    public function test_ready_addendum_does_not_create_parallel_until_parent_completes_then_auto_creates_next_version(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $event->update(['event' => 'Konser Addendum V1']);
        $addendumV1 = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        $this->assertTrue(
            app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $addendumV1->uid)['ok']
        );

        $event->update(['venue_name' => 'Venue Berubah Saat READY']);

        $parallelAttempt = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($parallelAttempt);
        $this->assertSame(1, Agreement::where('event_uid', $event->uid)->where('type', Agreement::TYPE_ADDENDUM)->count());

        $dummyPdf = UploadedFile::fake()->create('signed-ready-v1.pdf', 150, 'application/pdf');
        $this->assertTrue(
            app(AgreementSignedUploadService::class)->storeForEvent($event, $tenant->uid, $dummyPdf, $addendumV1->uid)['ok']
        );

        $approval = app(AgreementSignedVerificationService::class)->approveForEvent($event->fresh(), $admin->uid, $addendumV1->uid);

        $this->assertTrue($approval['ok']);

        $addendumV2 = Agreement::query()
            ->where('event_uid', $event->uid)
            ->where('type', Agreement::TYPE_ADDENDUM)
            ->where('version', 2)
            ->first();

        $this->assertNotNull($addendumV2);
        $this->assertSame(Agreement::STATUS_DRAFT, $addendumV2->status);
        $this->assertSame($addendumV1->uid, $addendumV2->parent_agreement_uid);
        $this->assertTrue($addendumV1->fresh()->isCompleted());
        $this->assertSame($mou->uid, $addendumV1->parent_agreement_uid);
    }

    public function test_changes_before_completed_mou_do_not_create_addendum_but_post_completion_diff_does(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createDraftMouEvent($tenant, $admin);

        $event->update(['event' => 'Perubahan Sebelum MOU Completed']);

        $beforeCompletion = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($beforeCompletion);
        $this->assertSame(1, Agreement::where('event_uid', $event->uid)->count());

        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $mou->uid)['ok']);
        $signedMou = UploadedFile::fake()->create('signed-mou-prechange.pdf', 150, 'application/pdf');
        $this->assertTrue(app(AgreementSignedUploadService::class)->storeForEvent($event, $tenant->uid, $signedMou, $mou->uid)['ok']);
        $this->assertTrue(app(AgreementSignedVerificationService::class)->approveForEvent($event->fresh(), $admin->uid, $mou->uid)['ok']);

        $event->update(['venue_name' => 'Perubahan Setelah MOU Completed']);

        $afterCompletion = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNotNull($afterCompletion);
        $this->assertSame(Agreement::TYPE_ADDENDUM, $afterCompletion->type);
        $this->assertSame(1, $afterCompletion->version);
        $this->assertSame($mou->uid, $afterCompletion->parent_agreement_uid);
    }

    public function test_review_metadata_changes_only_do_not_create_addendum(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event] = $this->createCompletedMouEvent($tenant, $admin);

        $event->bankAccount()->firstOrFail()->update([
            'status' => 'rejected',
            'verified_by' => $tenant->uid,
            'verified_at' => null,
        ]);

        $event->organizerLetter()->firstOrFail()->update([
            'status' => 'pending',
            'verified_by' => $tenant->uid,
            'verified_at' => null,
        ]);

        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($addendum);
        $this->assertSame(1, Agreement::where('event_uid', $event->uid)->count());
    }

    public function test_addendum_finalization_generates_unsigned_pdf_and_readies_it(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $event->event = 'Konser Finalisasi Addendum';
        $event->save();
        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        $finResult = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $addendum->uid);

        $this->assertTrue($finResult['ok']);
        $addendum->refresh();
        $this->assertTrue($addendum->isReady());
        $this->assertNotNull($addendum->unsigned_pdf_path);
        Storage::disk('local')->assertExists($addendum->unsigned_pdf_path);
    }

    public function test_tenant_upload_signed_addendum_sets_pending_review(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $event->event = 'Konser Signed Addendum';
        $event->save();
        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $addendum->uid);
        $addendum->refresh();

        $dummyPdf = UploadedFile::fake()->create('signed-addendum.pdf', 150, 'application/pdf');
        $upResult = app(AgreementSignedUploadService::class)->storeForEvent($event, $tenant->uid, $dummyPdf, $addendum->uid);

        $this->assertTrue($upResult['ok']);
        $addendum->refresh();
        $this->assertTrue($addendum->isReady());
        $this->assertEquals(Agreement::SIGNED_REVIEW_PENDING, $addendum->signed_review_status);
        $this->assertNotNull($addendum->signed_pdf_path);
        Storage::disk('local')->assertExists($addendum->signed_pdf_path);
    }

    public function test_admin_approve_signed_addendum_completes_it(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $event->event = 'Konser Approved Addendum';
        $event->save();
        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $addendum->uid);
        $dummyPdf = UploadedFile::fake()->create('signed-addendum.pdf', 150, 'application/pdf');
        app(AgreementSignedUploadService::class)->storeForEvent($event, $tenant->uid, $dummyPdf, $addendum->uid);

        $appResult = app(AgreementSignedVerificationService::class)->approveForEvent($event, $admin->uid, $addendum->uid);

        $this->assertTrue($appResult['ok']);
        $addendum->refresh();
        $this->assertTrue($addendum->isCompleted());
        $this->assertEquals(Agreement::SIGNED_REVIEW_VERIFIED, $addendum->signed_review_status);
        $this->assertNotNull($addendum->completed_at);
        $this->assertEquals($admin->uid, $addendum->signed_verified_by);
    }

    public function test_new_contractual_change_after_completed_addendum_v1_creates_addendum_v2(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        // Addendum v1
        $event->event = 'Konser V1';
        $event->save();
        $addendum1 = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);
        $addendum1 = $this->completeAddendum($event, $tenant, $admin, $addendum1, 'signed-addendum1.pdf');
        $this->assertTrue($addendum1->isCompleted());

        // Now modify again -> Addendum v2
        $event->event = 'Konser V2 Super';
        $event->save();
        $addendum2 = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        $this->assertNotNull($addendum2);
        $this->assertEquals(Agreement::TYPE_ADDENDUM, $addendum2->type);
        $this->assertEquals(2, $addendum2->version);
        $this->assertEquals(Agreement::STATUS_DRAFT, $addendum2->status);
        $this->assertEquals($addendum1->uid, $addendum2->parent_agreement_uid);
        $this->assertEquals(3, Agreement::where('event_uid', $event->uid)->count());
    }

    public function test_cross_event_addendum_changes_remain_isolated(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$eventA] = $this->createCompletedMouEvent($tenant, $admin);
        [$eventB] = $this->createCompletedMouEvent($tenant, $admin);

        $eventA->update(['event' => 'Perubahan Event A']);

        $addendumA = app(AgreementVersioningService::class)->checkForContractualChanges($eventA->fresh(), $tenant->uid);

        $this->assertNotNull($addendumA);
        $this->assertSame(1, Agreement::where('event_uid', $eventA->uid)->where('type', Agreement::TYPE_ADDENDUM)->count());
        $this->assertSame(0, Agreement::where('event_uid', $eventB->uid)->where('type', Agreement::TYPE_ADDENDUM)->count());
    }

    public function test_admin_reject_signed_addendum_allows_reupload(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $event->event = 'Konser Rejected Addendum';
        $event->save();
        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $addendum->uid);
        $dummyPdf = UploadedFile::fake()->create('signed-bad.pdf', 150, 'application/pdf');
        app(AgreementSignedUploadService::class)->storeForEvent($event, $tenant->uid, $dummyPdf, $addendum->uid);

        $rejResult = app(AgreementSignedVerificationService::class)->rejectForEvent($event, $admin->uid, 'Tanda tangan tidak cocok', $addendum->uid);

        $this->assertTrue($rejResult['ok']);
        $addendum->refresh();
        $this->assertTrue($addendum->isReady());
        $this->assertEquals(Agreement::SIGNED_REVIEW_REJECTED, $addendum->signed_review_status);
        $this->assertEquals('Tanda tangan tidak cocok', $addendum->signed_rejection_reason);

        // Tenant re-uploads
        $fixedPdf = UploadedFile::fake()->create('signed-fixed.pdf', 150, 'application/pdf');
        $reupResult = app(AgreementSignedUploadService::class)->storeForEvent($event, $tenant->uid, $fixedPdf, $addendum->uid);
        $this->assertTrue($reupResult['ok']);
        $addendum->refresh();
        $this->assertEquals(Agreement::SIGNED_REVIEW_PENDING, $addendum->signed_review_status);
    }

    public function test_event_activation_guard_blocks_activation_if_uncompleted_addendum_exists(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $event->event = 'Konser Blocked Activation';
        $event->save();
        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        // Addendum is DRAFT -> Activation MUST fail
        $guardResult = app(EventActivationGuardService::class)->evaluateForEvent($event);
        $this->assertFalse($guardResult['can_activate']);
        $this->assertContains('Terdapat addendum yang belum selesai.', $guardResult['blocking_reasons']);

        // Finalize -> READY (still uncompleted) -> Activation MUST fail
        app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $addendum->uid);
        $event->refresh();
        $guardResult = app(EventActivationGuardService::class)->evaluateForEvent($event);
        $this->assertFalse($guardResult['can_activate']);

        // Complete addendum -> Activation allowed
        $dummyPdf = UploadedFile::fake()->create('signed-addendum.pdf', 150, 'application/pdf');
        app(AgreementSignedUploadService::class)->storeForEvent($event, $tenant->uid, $dummyPdf, $addendum->uid);
        app(AgreementSignedVerificationService::class)->approveForEvent($event, $admin->uid, $addendum->uid);
        $event->refresh();

        $guardResult = app(EventActivationGuardService::class)->evaluateForEvent($event);
        $this->assertTrue($guardResult['can_activate']);
    }

    public function test_active_event_is_not_deactivated_when_draft_addendum_is_created(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        // Activate event
        $event->status = 'active';
        $event->konfirmasi = '1';
        $event->save();

        // Edit event -> creates DRAFT addendum
        $event->event = 'Konser Aktif Diedit';
        $event->save();
        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        $event->refresh();
        $this->assertEquals('active', $event->status);
        $this->assertEquals('1', (string) $event->konfirmasi);
        $this->assertNotNull($addendum);
    }

    public function test_addendum_preview_and_diff_calculation(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $event->event = 'Konser Nama Baru';
        $event->venue_name = 'Venue Baru';
        $event->save();
        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        $preview = app(AgreementVersioningService::class)->buildAddendumPreview($event, $addendum);

        $this->assertEquals('addendum', $preview['agreement']['type']);
        $this->assertEquals(1, $preview['agreement']['version']);
        $this->assertCount(2, $preview['diffs']);

        $diffKeys = collect($preview['diffs'])->pluck('field')->all();
        $this->assertContains('event_name', $diffKeys);
        $this->assertContains('venue_name', $diffKeys);
    }

    public function test_file_download_routes_stream_correct_addendum_pdf(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event, $mou] = $this->createCompletedMouEvent($tenant, $admin);

        $event->event = 'Konser Streaming Test';
        $event->save();
        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event, $tenant->uid);

        app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $addendum->uid);
        $dummyPdf = UploadedFile::fake()->create('signed-addendum.pdf', 150, 'application/pdf');
        app(AgreementSignedUploadService::class)->storeForEvent($event, $tenant->uid, $dummyPdf, $addendum->uid);

        // Tenant can download unsigned & signed
        $this->actingAs($tenant)
            ->get(route('dashboard.event.mou.unsigned', ['uid' => $event->uid, 'agreementUid' => $addendum->uid]))
            ->assertOk();

        $this->actingAs($tenant)
            ->get(route('dashboard.event.mou.signed', ['uid' => $event->uid, 'agreementUid' => $addendum->uid]))
            ->assertOk();

        // Admin can review unsigned & signed
        $this->actingAs($admin)
            ->get(route('admin.event.review.mou.unsigned', ['uid' => $event->uid, 'agreementUid' => $addendum->uid]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.event.review.mou.signed', ['uid' => $event->uid, 'agreementUid' => $addendum->uid]))
            ->assertOk();
    }

    public function test_other_tenant_cannot_download_addendum_files(): void
    {
        $tenant = $this->tenant();
        $otherTenant = $this->tenant(['email' => 'other-tenant@example.test']);
        $admin = $this->admin();

        [$event] = $this->createCompletedMouEvent($tenant, $admin);

        $event->update(['event' => 'Konser Private Addendum']);
        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertTrue(app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $addendum->uid)['ok']);

        $dummyPdf = UploadedFile::fake()->create('private-signed-addendum.pdf', 150, 'application/pdf');
        $this->assertTrue(app(AgreementSignedUploadService::class)->storeForEvent($event, $tenant->uid, $dummyPdf, $addendum->uid)['ok']);

        $this->actingAs($otherTenant)
            ->get(route('dashboard.event.mou.unsigned', ['uid' => $event->uid, 'agreementUid' => $addendum->uid]))
            ->assertNotFound();

        $this->actingAs($otherTenant)
            ->get(route('dashboard.event.mou.signed', ['uid' => $event->uid, 'agreementUid' => $addendum->uid]))
            ->assertNotFound();
    }

    public function test_completed_addendum_is_immutable_including_parent_snapshot_and_pdf_fields(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event] = $this->createCompletedMouEvent($tenant, $admin);

        $event->update(['event' => 'Konser Immutable Addendum']);
        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);
        $addendum = $this->completeAddendum($event, $tenant, $admin, $addendum);

        $originalParent = $addendum->parent_agreement_uid;
        $originalSnapshot = $addendum->event_snapshot;
        $originalSignedPdf = $addendum->signed_pdf_path;

        try {
            $addendum->forceFill([
                'parent_agreement_uid' => (string) Str::uuid(),
                'event_snapshot' => ['event_name' => 'Mutated Snapshot'],
                'signed_pdf_path' => 'private/agreements/mutated/signed.pdf',
            ])->save();
            $this->fail('Expected completed addendum immutability exception was not thrown.');
        } catch (\LogicException $e) {
            $this->assertSame('Completed agreement is immutable.', $e->getMessage());
        }

        $addendum->refresh();

        $this->assertSame($originalParent, $addendum->parent_agreement_uid);
        $this->assertSame($originalSnapshot, $addendum->event_snapshot);
        $this->assertSame($originalSignedPdf, $addendum->signed_pdf_path);
    }

    public function test_addendum_history_orders_mou_then_v1_then_v2(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event] = $this->createCompletedMouEvent($tenant, $admin);

        $event->update(['event' => 'Konser History V1']);
        $addendumV1 = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);
        $this->completeAddendum($event, $tenant, $admin, $addendumV1, 'history-v1.pdf');

        $event->update(['event' => 'Konser History V2']);
        $addendumV2 = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);
        $this->completeAddendum($event, $tenant, $admin, $addendumV2, 'history-v2.pdf');

        $history = Agreement::query()
            ->where('event_uid', $event->uid)
            ->orderByRaw("CASE WHEN type = 'mou' THEN 1 ELSE 2 END ASC")
            ->orderBy('version')
            ->get()
            ->map(fn (Agreement $agreement) => $agreement->type.':'.$agreement->version)
            ->all();

        $this->assertSame(['mou:1', 'addendum:1', 'addendum:2'], $history);
    }

    public function test_admin_review_mou_tab_still_receives_commercial_review_summary(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();

        [$event] = $this->createCompletedMouEvent($tenant, $admin);

        Livewire::actingAs($admin)
            ->test(AdminEventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'review-mou')
            ->assertViewHas('commercialReview', function ($commercialReview) {
                return is_array($commercialReview)
                    && is_array($commercialReview['ticket_tax'] ?? null)
                    && collect($commercialReview['payment_gateways'] ?? [])->isNotEmpty()
                    && collect($commercialReview['payment_gateways'] ?? [])->contains(
                        fn (array $gateway) => ($gateway['payment'] ?? null) === 'BCA Virtual Account'
                    );
            });
    }
}
