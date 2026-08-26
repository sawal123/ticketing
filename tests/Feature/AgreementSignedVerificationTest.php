<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Admin\EventDetail as AdminEventDetail;
use App\Models\Agreement;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Services\Agreements\AgreementSignedUploadService;
use App\Services\Agreements\AgreementSignedVerificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class AgreementSignedVerificationTest extends TestCase
{
    private const DIR = 'private/agreements';

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

    public function test_admin_approves_signed_mou_and_completes_agreement(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $event = $this->event($tenant, ['status' => 'inactive']);

        $uid = (string) Str::uuid();
        $agreement = $this->readyAgreement($tenant, $event, [
            'uid' => $uid,
            'signed_pdf_path' => $this->signedPath($uid),
            'signed_review_status' => Agreement::SIGNED_REVIEW_PENDING,
        ]);
        Storage::disk('local')->put($this->signedPath($uid), '%PDF-1.4 signed');

        Livewire::actingAs($admin)
            ->test(AdminEventDetail::class, ['uid' => $event->uid])
            ->call('approveSignedMou')
            ->assertHasNoErrors();

        $agreement->refresh();

        $this->assertSame(Agreement::STATUS_COMPLETED, $agreement->status);
        $this->assertSame(Agreement::SIGNED_REVIEW_VERIFIED, $agreement->signed_review_status);
        $this->assertSame($admin->uid, $agreement->signed_verified_by);
        $this->assertNotNull($agreement->signed_verified_at);
        $this->assertNotNull($agreement->completed_at);
        $this->assertNull($agreement->signed_rejection_reason);
    }

    public function test_admin_rejects_signed_mou_and_keeps_ready_state(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $event = $this->event($tenant, ['status' => 'inactive']);

        $uid = (string) Str::uuid();
        $agreement = $this->readyAgreement($tenant, $event, [
            'uid' => $uid,
            'signed_pdf_path' => $this->signedPath($uid),
            'signed_review_status' => Agreement::SIGNED_REVIEW_PENDING,
        ]);
        Storage::disk('local')->put($this->signedPath($uid), '%PDF-1.4 signed');

        Livewire::actingAs($admin)
            ->test(AdminEventDetail::class, ['uid' => $event->uid])
            ->set('signedMouRejectionReason', 'Tanda tangan tidak sesuai dengan dokumen.')
            ->call('rejectSignedMou')
            ->assertHasNoErrors();

        $agreement->refresh();

        $this->assertSame(Agreement::STATUS_READY, $agreement->status);
        $this->assertSame(Agreement::SIGNED_REVIEW_REJECTED, $agreement->signed_review_status);
        $this->assertSame('Tanda tangan tidak sesuai dengan dokumen.', $agreement->signed_rejection_reason);
        $this->assertSame($admin->uid, $agreement->signed_verified_by);
        $this->assertNull($agreement->signed_verified_at);
        $this->assertNull($agreement->completed_at);
    }

    public function test_reject_requires_non_empty_reason(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $event = $this->event($tenant);

        $uid = (string) Str::uuid();
        $agreement = $this->readyAgreement($tenant, $event, [
            'uid' => $uid,
            'signed_pdf_path' => $this->signedPath($uid),
            'signed_review_status' => Agreement::SIGNED_REVIEW_PENDING,
        ]);
        Storage::disk('local')->put($this->signedPath($uid), '%PDF-1.4 signed');

        try {
            app(AgreementSignedVerificationService::class)->rejectForEvent($event, $admin->uid, '   ');
            $this->fail('Expected LogicException was not thrown.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('Alasan penolakan wajib diisi.', $e->getMessage());
        }

        $agreement->refresh();
        $this->assertSame(Agreement::SIGNED_REVIEW_PENDING, $agreement->signed_review_status);
        $this->assertSame(Agreement::STATUS_READY, $agreement->status);
    }

    public function test_non_admin_cannot_verify_signed_mou(): void
    {
        $tenant = $this->tenant();
        $staff = $this->user(['role' => 'staff', 'parent_uid' => $tenant->uid]);
        $event = $this->event($tenant);

        $uid = (string) Str::uuid();
        $this->readyAgreement($tenant, $event, [
            'uid' => $uid,
            'signed_pdf_path' => $this->signedPath($uid),
            'signed_review_status' => Agreement::SIGNED_REVIEW_PENDING,
        ]);
        Storage::disk('local')->put($this->signedPath($uid), '%PDF-1.4 signed');

        foreach ([$tenant, $staff] as $actor) {
            try {
                app(AgreementSignedVerificationService::class)->approveForEvent($event, $actor->uid);
                $this->fail('Expected LogicException was not thrown.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Hanya admin yang dapat memverifikasi MOU.', $e->getMessage());
            }
        }
    }

    public function test_approve_requires_ready_agreement(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $event = $this->event($tenant);
        $this->agreement($tenant, $event); // DRAFT

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Verifikasi signed MOU hanya tersedia saat agreement berstatus READY.');

        app(AgreementSignedVerificationService::class)->approveForEvent($event, $admin->uid);
    }

    public function test_approve_requires_signed_file_to_be_present(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $event = $this->event($tenant);

        $this->readyAgreement($tenant, $event, [
            'signed_review_status' => Agreement::SIGNED_REVIEW_PENDING,
        ]); // READY but no signed_pdf_path / physical file

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Dokumen MOU bertanda tangan belum tersedia.');

        app(AgreementSignedVerificationService::class)->approveForEvent($event, $admin->uid);
    }

    public function test_already_processed_review_cannot_be_reprocessed(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $event = $this->event($tenant);

        $uid = (string) Str::uuid();
        $this->readyAgreement($tenant, $event, [
            'uid' => $uid,
            'signed_pdf_path' => $this->signedPath($uid),
            'signed_review_status' => Agreement::SIGNED_REVIEW_VERIFIED,
        ]);
        Storage::disk('local')->put($this->signedPath($uid), '%PDF-1.4 signed');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Review signed MOU sudah diproses sebelumnya.');

        app(AgreementSignedVerificationService::class)->approveForEvent($event, $admin->uid);
    }

    public function test_tenant_reupload_after_rejection_resets_review_to_pending(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);

        $uid = (string) Str::uuid();
        $agreement = $this->readyAgreement($tenant, $event, [
            'uid' => $uid,
            'signed_pdf_path' => $this->signedPath($uid),
            'signed_review_status' => Agreement::SIGNED_REVIEW_REJECTED,
            'signed_verified_by' => $this->admin()->uid,
            'signed_rejection_reason' => 'Alasan lama.',
        ]);
        Storage::disk('local')->put($this->signedPath($uid), '%PDF-1.4 signed lama');

        app(AgreementSignedUploadService::class)
            ->storeForEvent($event, $tenant->uid, UploadedFile::fake()->create('signed.pdf', 120, 'application/pdf'));

        $agreement->refresh();

        $this->assertSame(Agreement::STATUS_READY, $agreement->status);
        $this->assertSame(Agreement::SIGNED_REVIEW_PENDING, $agreement->signed_review_status);
        $this->assertNull($agreement->signed_verified_by);
        $this->assertNull($agreement->signed_verified_at);
        $this->assertNull($agreement->signed_rejection_reason);
        $this->assertNotNull($agreement->signed_at);
        $this->assertSame($this->signedPath($uid), $agreement->signed_pdf_path);
    }

    public function test_signed_route_is_admin_only_and_state_guarded(): void
    {
        $tenant = $this->tenant();
        $admin = $this->admin();
        $event = $this->event($tenant);

        $uid = (string) Str::uuid();
        $this->readyAgreement($tenant, $event, [
            'uid' => $uid,
            'signed_pdf_path' => $this->signedPath($uid),
        ]);
        Storage::disk('local')->put($this->signedPath($uid), '%PDF-1.4 signed');

        // Non-admin cannot reach the admin signed-PDF route; the admin
        // middleware bounces them back to the homepage.
        $this->actingAs($tenant)
            ->get(route('admin.event.review.mou.signed', $event->uid))
            ->assertRedirect('/');

        // Admin with no signed file -> 404.
        $otherEvent = $this->event($tenant, ['event' => 'Other Event']);
        $this->readyAgreement($tenant, $otherEvent);
        $this->actingAs($admin)
            ->get(route('admin.event.review.mou.signed', $otherEvent->uid))
            ->assertNotFound();

        // Admin with signed file -> streams the PDF.
        $response = $this->actingAs($admin)
            ->get(route('admin.event.review.mou.signed', $event->uid));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('mou-signed.pdf', (string) $response->headers->get('content-disposition'));
        $this->assertSame('%PDF-1.4 signed', $response->streamedContent());
    }

    private function admin(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Admin M10',
            'email' => 'admin-m10@example.test',
            'role' => 'admin',
        ], $overrides));
    }

    private function tenant(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Tenant M10',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'M10 User',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat M10 User',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
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
            'event' => 'M10 Event ' . $uid,
            'alamat' => 'Alamat M10 Event',
            'tanggal' => '2026-09-10 19:00:00',
            'event_end' => '2026-09-10 22:00:00',
            'venue_name' => 'Venue M10',
            'venue_address' => 'Jl. M10',
            'venue_city' => 'Jakarta',
            'venue_province' => 'DKI Jakarta',
            'status' => 'inactive',
            'cover' => 'm10-cover.jpg',
            'fee' => 10,
            'pajak' => 0,
            'deskripsi' => 'Deskripsi M10',
            'map' => 'https://maps.google.com/?q=m10',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'm10-' . Str::lower(Str::random(8)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
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
            'signed_review_status' => null,
            'signed_verified_by' => null,
            'signed_verified_at' => null,
            'signed_rejection_reason' => null,
            'privy_document_id' => null,
            'privy_status' => null,
            'privy_reference' => null,
            'sent_to_privy_at' => null,
            'signed_at' => null,
            'completed_at' => null,
        ], $overrides));
    }

    private function readyAgreement(User $tenant, Event $event, array $overrides = []): Agreement
    {
        $agreementUid = $overrides['uid'] ?? (string) Str::uuid();

        return $this->agreement($tenant, $event, array_merge([
            'uid' => $agreementUid,
            'status' => Agreement::STATUS_READY,
            'template_version' => 'mou-v1',
            'event_snapshot' => ['event_name' => $event->event],
            'party_snapshot' => ['organizer_name' => 'PT M10 Organizer'],
            'bank_snapshot' => ['bank_name' => 'Bank M10'],
            'document_snapshot' => ['document_number' => 'DOC-M10-001'],
            'commercial_snapshot' => ['buyer_fee' => ['mode' => 'percent', 'value' => 10.0]],
            'document_number' => 'MOU-M10-001',
            'unsigned_pdf_path' => $this->unsignedPath($agreementUid),
        ], $overrides));
    }

    private function unsignedPath(string $agreementUid): string
    {
        return self::DIR . '/' . $agreementUid . '/unsigned.pdf';
    }

    private function signedPath(string $agreementUid): string
    {
        return self::DIR . '/' . $agreementUid . '/signed.pdf';
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
}
