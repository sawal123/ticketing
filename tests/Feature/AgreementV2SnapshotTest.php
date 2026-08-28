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
use App\Services\Agreements\AgreementVersioningService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgreementV2SnapshotTest extends TestCase
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

    public function test_finalization_freezes_platform_party_snapshot_and_payload_uses_frozen_values(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $this->platformLegalProfile([
            'company_name' => 'PT Gotik A',
            'legal_id' => 'NIB-A',
            'address' => 'Alamat A',
            'representative_name' => 'Rani',
            'representative_position' => 'Direktur',
            'email' => 'legal-a@example.test',
            'phone' => '0811111111',
            'website' => 'https://a.example.test',
        ]);

        [$event, $agreement] = $this->draftMouEvent($tenant, $admin);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid);
        $this->assertTrue($result['ok']);

        $agreement->refresh();
        $expected = $this->expectedPlatformSnapshot([
            'company_name' => 'PT Gotik A',
            'legal_id' => 'NIB-A',
            'address' => 'Alamat A',
            'representative_name' => 'Rani',
            'representative_position' => 'Direktur',
            'email' => 'legal-a@example.test',
            'phone' => '0811111111',
            'website' => 'https://a.example.test',
        ]);

        $this->assertSame($expected, $agreement->platform_party_snapshot);
        $this->assertArrayNotHasKey('id', $agreement->platform_party_snapshot);
        $this->assertArrayNotHasKey('profile_key', $agreement->platform_party_snapshot);
        $this->assertArrayNotHasKey('created_at', $agreement->platform_party_snapshot);
        $this->assertArrayNotHasKey('updated_at', $agreement->platform_party_snapshot);

        PlatformLegalProfile::query()
            ->where('profile_key', PlatformLegalProfile::DEFAULT_KEY)
            ->update([
                'company_name' => 'PT Gotik B',
                'email' => 'legal-b@example.test',
            ]);

        $agreement->refresh();
        $payload = app(AgreementFinalizationService::class)->pdfPayloadForAgreement($agreement);

        $this->assertSame($expected, $agreement->platform_party_snapshot);
        $this->assertSame($expected, $payload['platform_party']);
    }

    public function test_finalization_allows_missing_platform_profile_and_keeps_snapshot_null_for_legacy_compatibility(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        [$event, $agreement] = $this->draftMouEvent($tenant, $admin);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid);
        $this->assertTrue($result['ok']);

        $agreement->refresh();

        $this->assertNull($agreement->platform_party_snapshot);
        $this->assertNull(app(AgreementFinalizationService::class)->pdfPayloadForAgreement($agreement)['platform_party']);
    }

    public function test_global_platform_profile_change_does_not_auto_create_addendum_for_legacy_completed_agreement_with_null_snapshot(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        [$event, $agreement] = $this->draftMouEvent($tenant, $admin);

        $agreement->forceFill([
            'status' => Agreement::STATUS_COMPLETED,
            'template_version' => AgreementFinalizationService::TEMPLATE_VERSION,
            'event_snapshot' => [
                'event_uid' => $event->uid,
                'event_name' => $event->event,
                'start' => '10-09-2026 19:00',
                'end' => '10-09-2026 22:00',
                'venue_name' => $event->venue_name,
                'venue_address' => $event->venue_address,
                'venue_city' => $event->venue_city,
                'venue_province' => $event->venue_province,
                'start_sale' => '01-09-2026 10:00',
                'buyer_fee' => ['mode' => 'fixed', 'value' => 5000.0],
            ],
            'party_snapshot' => [
                'organizer_name' => 'PT Musik Indonesia',
                'responsible_name' => 'Budi Santoso',
                'responsible_position' => 'Direktur',
                'phone' => '081234567890',
                'email' => 'organizer@example.test',
                'address' => 'Jl. Bisnis No. 1',
            ],
            'bank_snapshot' => [
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_holder_name' => 'PT Musik Indonesia',
            ],
            'document_snapshot' => [
                'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
                'document_number' => 'DOC/2026/001',
                'document_date' => now()->format('d-m-Y'),
                'original_name' => 'surat.pdf',
            ],
            'commercial_snapshot' => [
                'buyer_fee' => ['mode' => 'fixed', 'value' => 5000.0],
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
            ],
            'platform_party_snapshot' => null,
            'completed_at' => now(),
        ])->save();

        $this->platformLegalProfile(['company_name' => 'PT Global Baru']);

        $result = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($result);
        $this->assertDatabaseCount('agreements', 1);
    }

    public function test_addendum_inherits_parent_platform_party_snapshot_not_latest_global_profile(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $this->platformLegalProfile([
            'company_name' => 'PT Gotik A',
            'legal_id' => 'NIB-A',
            'address' => 'Alamat A',
            'representative_name' => 'Rani',
            'representative_position' => 'Direktur',
            'email' => 'legal-a@example.test',
            'phone' => '0811111111',
            'website' => 'https://a.example.test',
        ]);

        [$event, $mou] = $this->completedMouEvent($tenant, $admin);
        $parentSnapshot = $mou->refresh()->platform_party_snapshot;

        PlatformLegalProfile::query()
            ->where('profile_key', PlatformLegalProfile::DEFAULT_KEY)
            ->update([
                'company_name' => 'PT Gotik B',
                'legal_id' => 'NIB-B',
                'address' => 'Alamat B',
                'representative_name' => 'Sinta',
            ]);

        $event->update(['venue_name' => 'Venue Baru']);

        $addendum = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);
        $this->assertNotNull($addendum);
        $this->assertSame($parentSnapshot, $addendum->platform_party_snapshot);

        $finalized = app(AgreementFinalizationService::class)->finalizeForEvent($event->fresh(), $admin->uid, $addendum->uid);
        $this->assertTrue($finalized['ok']);

        $addendum->refresh();
        $this->assertSame($parentSnapshot, $addendum->platform_party_snapshot);
        $this->assertSame($parentSnapshot, app(AgreementFinalizationService::class)->pdfPayloadForAgreement($addendum)['platform_party']);
    }

    public function test_compute_diffs_detects_explicit_platform_party_snapshot_change_and_keeps_existing_diff_behavior(): void
    {
        $parent = new Agreement([
            'event_snapshot' => [
                'event_name' => 'Event Lama',
                'start' => '01-09-2026 10:00',
                'end' => '01-09-2026 12:00',
                'venue_name' => 'Venue Lama',
                'venue_address' => 'Alamat Lama',
                'venue_city' => 'Jakarta',
                'venue_province' => 'DKI Jakarta',
                'start_sale' => '25-08-2026 10:00',
                'buyer_fee' => ['mode' => 'fixed', 'value' => 5000.0],
            ],
            'platform_party_snapshot' => $this->expectedPlatformSnapshot([
                'company_name' => 'PT Gotik A',
                'legal_id' => 'NIB-A',
                'address' => 'Alamat A',
                'representative_name' => 'Rani',
                'representative_position' => 'Direktur',
                'email' => 'legal-a@example.test',
                'phone' => '0811111111',
                'website' => 'https://a.example.test',
            ]),
            'party_snapshot' => [
                'organizer_name' => 'Organizer A',
                'responsible_name' => 'Budi',
                'responsible_position' => 'Direktur',
                'phone' => '0812',
                'email' => 'organizer@example.test',
                'address' => 'Alamat Org',
            ],
            'bank_snapshot' => [
                'bank_name' => 'BCA',
                'account_number' => '123',
                'account_holder_name' => 'Organizer A',
            ],
            'document_snapshot' => [
                'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
                'document_number' => 'DOC-1',
                'document_date' => '01-09-2026',
                'original_name' => 'surat.pdf',
            ],
            'commercial_snapshot' => [
                'buyer_fee' => ['mode' => 'fixed', 'value' => 5000.0],
                'payment_otp_enabled' => false,
                'payment_gateways' => [],
            ],
        ]);

        $diffs = app(AgreementVersioningService::class)->computeDiffs([
            'event_snapshot' => [
                'event_name' => 'Event Lama',
                'start' => '01-09-2026 10:00',
                'end' => '01-09-2026 12:00',
                'venue_name' => 'Venue Baru',
                'venue_address' => 'Alamat Lama',
                'venue_city' => 'Jakarta',
                'venue_province' => 'DKI Jakarta',
                'start_sale' => '25-08-2026 10:00',
                'buyer_fee' => ['mode' => 'percent', 'value' => 10.0],
            ],
            'platform_party_snapshot' => $this->expectedPlatformSnapshot([
                'company_name' => 'PT Gotik B',
                'legal_id' => 'NIB-B',
                'address' => 'Alamat B',
                'representative_name' => 'Sinta',
                'representative_position' => 'Komisaris',
                'email' => 'legal-b@example.test',
                'phone' => '0822',
                'website' => 'https://b.example.test',
            ]),
            'party_snapshot' => $parent->party_snapshot,
            'bank_snapshot' => $parent->bank_snapshot,
            'document_snapshot' => $parent->document_snapshot,
            'commercial_snapshot' => $parent->commercial_snapshot,
        ], $parent);

        $this->assertTrue(collect($diffs)->contains(fn ($diff) => $diff['section'] === 'PIHAK PERTAMA' && $diff['field'] === 'platform_company_name'));
        $this->assertTrue(collect($diffs)->contains(fn ($diff) => $diff['section'] === 'Event' && $diff['field'] === 'venue_name'));
        $this->assertTrue(collect($diffs)->contains(fn ($diff) => $diff['field'] === 'buyer_fee' && $diff['label'] === 'Biaya Pembeli / Event Fee'));
    }

    public function test_ticket_changes_do_not_create_contractual_diff_or_addendum(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        [$event, $mou] = $this->completedMouEvent($tenant, $admin);

        $ticket = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Presale',
            'qty' => 100,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => 150000,
            'status' => 'active',
        ]);

        $ticket->update([
            'kategori' => 'VIP',
            'qty' => 50,
            'harga' => 300000,
        ]);

        $result = app(AgreementVersioningService::class)->checkForContractualChanges($event->fresh(), $tenant->uid);

        $this->assertNull($result);
        $this->assertDatabaseCount('agreements', 1);
        $this->assertSame($mou->uid, $mou->refresh()->uid);
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
            $table->string('parent_agreement_uid')->nullable();
            $table->string('document_number')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('DRAFT');
            $table->string('template_version')->nullable();
            $table->text('event_snapshot')->nullable();
            $table->text('platform_party_snapshot')->nullable();
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

        Schema::create('platform_legal_profiles', function ($table) {
            $table->id();
            $table->string('profile_key')->default(PlatformLegalProfile::DEFAULT_KEY)->unique();
            $table->string('company_name')->nullable();
            $table->string('legal_id', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('representative_name')->nullable();
            $table->string('representative_position')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website')->nullable();
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
    }

    private function user(array $overrides = []): User
    {
        return User::query()->create(array_merge([
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
            'email' => 'admin-'.Str::random(6).'@example.test',
            'role' => 'admin',
        ], $overrides));
    }

    private function tenant(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Tenant User',
            'email' => 'tenant-'.Str::random(6).'@example.test',
            'role' => 'penyewa',
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
            'ticket_tax_mode' => 'fixed',
            'ticket_tax_fixed' => 0,
            'ticket_tax_percent' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => 'https://maps.google.com/?q=venue',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'event-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
        ], $overrides));
    }

    private function draftMouEvent(User $tenant, User $admin): array
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
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/book.pdf',
            'status' => 'verified',
            'verified_by' => $admin->uid,
            'verified_at' => now(),
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/book.pdf', 'dummy-bank-book');

        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC/2026/001',
            'document_date' => now()->toDateString(),
            'original_name' => 'surat.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/surat.pdf',
            'status' => 'verified',
            'verified_by' => $admin->uid,
            'verified_at' => now(),
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/surat.pdf', 'dummy-letter');

        $gateway = PaymentGateway::create([
            'payment' => 'BCA Virtual Account',
            'category' => 'bank_transfer',
            'biaya' => 2000,
            'biaya_type' => 'fixed',
            'default_fee_fixed' => 2000,
            'default_fee_percent' => 0,
            'is_active' => true,
            'slug' => 'bca-va',
        ]);

        EventPaymentGateway::create([
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => true,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
        ]);

        $agreement = Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'created_by' => $admin->uid,
            'status' => Agreement::STATUS_DRAFT,
            'type' => Agreement::TYPE_MOU,
            'version' => 1,
            'document_number' => 'MOU/001',
        ]);

        return [$event, $agreement];
    }

    private function completedMouEvent(User $tenant, User $admin): array
    {
        [$event, $agreement] = $this->draftMouEvent($tenant, $admin);

        $result = app(AgreementFinalizationService::class)->finalizeForEvent($event, $admin->uid, $agreement->uid);
        $this->assertTrue($result['ok']);

        $agreement->forceFill([
            'status' => Agreement::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();

        return [$event->fresh(), $agreement->fresh()];
    }

    private function expectedPlatformSnapshot(array $values): array
    {
        return [
            'company_name' => $values['company_name'] ?? null,
            'legal_id' => $values['legal_id'] ?? null,
            'address' => $values['address'] ?? null,
            'representative_name' => $values['representative_name'] ?? null,
            'representative_position' => $values['representative_position'] ?? null,
            'email' => $values['email'] ?? null,
            'phone' => $values['phone'] ?? null,
            'website' => $values['website'] ?? null,
        ];
    }
}
