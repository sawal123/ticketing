<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventDetail;
use App\Models\Agreement;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventBankAccount;
use App\Models\EventDocument;
use App\Models\EventOrganizer;
use App\Models\EventPaymentGateway;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\Agreements\AgreementSignedUploadService;
use Illuminate\Http\UploadedFile;
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

class AgreementManualSigningTest extends TestCase
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

    public function test_owner_can_download_unsigned_pdf_when_ready(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->readyAgreement($tenant, $event, [
            'unsigned_pdf_path' => self::DIR.'/'.$event->uid.'/unsigned.pdf',
        ]);

        Storage::disk('local')->put(
            self::DIR.'/'.$event->uid.'/unsigned.pdf',
            '%PDF-1.4 unsigned content'
        );

        $response = $this->actingAs($tenant)
            ->get(route('dashboard.event.mou.unsigned', $event->uid));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
        $this->assertSame('%PDF-1.4 unsigned content', $response->streamedContent());
    }

    public function test_draft_agreement_unsigned_pdf_is_not_downloadable(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->agreement($tenant, $event, [
            'status' => Agreement::STATUS_DRAFT,
            'unsigned_pdf_path' => self::DIR.'/'.$event->uid.'/unsigned.pdf',
        ]);

        // Even when a physical file exists, a DRAFT agreement's unsigned PDF
        // must not be served — the unsigned route is state-guarded on READY.
        Storage::disk('local')->put(self::DIR.'/'.$event->uid.'/unsigned.pdf', '%PDF-1.4 unsigned');

        $this->actingAs($tenant)
            ->get(route('dashboard.event.mou.unsigned', $event->uid))
            ->assertNotFound();
    }

    public function test_other_tenant_cannot_download_unsigned_pdf(): void
    {
        $tenantA = $this->tenant(['email' => 'owner-a@example.test']);
        $tenantB = $this->tenant(['email' => 'owner-b@example.test']);
        $event = $this->event($tenantA);
        $this->readyAgreement($tenantA, $event, [
            'unsigned_pdf_path' => self::DIR.'/'.$event->uid.'/unsigned.pdf',
        ]);

        Storage::disk('local')->put(self::DIR.'/'.$event->uid.'/unsigned.pdf', '%PDF-1.4');

        $this->actingAs($tenantB)
            ->get(route('dashboard.event.mou.unsigned', $event->uid))
            ->assertNotFound();
    }

    public function test_staff_cannot_download_or_upload_legal_mou(): void
    {
        $tenant = $this->tenant();
        $staff = $this->user([
            'email' => 'staff-m9@example.test',
            'role' => 'staff',
            'parent_uid' => $tenant->uid,
        ]);
        $event = $this->event($tenant);
        $this->readyAgreement($tenant, $event, [
            'unsigned_pdf_path' => self::DIR.'/'.$event->uid.'/unsigned.pdf',
        ]);

        Storage::disk('local')->put(self::DIR.'/'.$event->uid.'/unsigned.pdf', '%PDF-1.4');

        // Routes are penyewa-only; staff gets a 403 from the role middleware.
        $this->actingAs($staff)
            ->get(route('dashboard.event.mou.unsigned', $event->uid))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('dashboard.event.mou.signed', $event->uid))
            ->assertForbidden();

        // Even through the Livewire component, the upload action is forbidden.
        Livewire::actingAs($staff)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'))
            ->call('uploadSignedMou')
            ->assertForbidden();

        $agreement = $event->currentMouAgreement->refresh();
        $this->assertNull($agreement->signed_pdf_path);
        $this->assertNull($agreement->signed_at);
    }

    public function test_draft_agreement_cannot_upload_signed_pdf(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->agreement($tenant, $event); // DRAFT

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'))
            ->call('uploadSignedMou');

        $agreement = $event->currentMouAgreement->refresh();

        $this->assertNull($agreement->signed_pdf_path);
        $this->assertNull($agreement->signed_at);
        $this->assertSame(Agreement::STATUS_DRAFT, $agreement->status);
    }

    public function test_ready_agreement_can_upload_signed_pdf(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['status' => 'inactive']);
        $agreement = $this->readyAgreement($tenant, $event);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->create('signed.pdf', 200, 'application/pdf'))
            ->call('uploadSignedMou')
            ->assertHasNoErrors();

        $agreement->refresh();

        $this->assertSame($this->signedPath($agreement->uid), $agreement->signed_pdf_path);
        $this->assertTrue(Storage::disk('local')->exists($agreement->signed_pdf_path));
        $this->assertNotNull($agreement->signed_at);
        $this->assertSame(Agreement::STATUS_READY, $agreement->status);
        $this->assertSame('inactive', $event->fresh()->status);
    }

    public function test_non_pdf_upload_is_rejected(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $agreement = $this->readyAgreement($tenant, $event);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->create('signed.txt', 100, 'text/plain'))
            ->call('uploadSignedMou')
            ->assertHasErrors(['signedMou']);

        $agreement->refresh();

        $this->assertNull($agreement->signed_pdf_path);
        $this->assertNull($agreement->signed_at);
        $this->assertSame(Agreement::STATUS_READY, $agreement->status);
    }

    public function test_oversized_upload_is_rejected(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $agreement = $this->readyAgreement($tenant, $event);

        // 11 MB > 10 MB limit. createWithContent menghasilkan isi file nyata
        // sehingga ukuran benar-benar 11MB (fake create() hanya 0 byte).
        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->createWithContent('signed.pdf', str_repeat('x', 11 * 1024 * 1024)))
            ->call('uploadSignedMou')
            ->assertHasErrors(['signedMou']);

        $agreement->refresh();

        $this->assertNull($agreement->signed_pdf_path);
        $this->assertNull($agreement->signed_at);
    }

    public function test_cross_event_upload_isolation(): void
    {
        $tenantA = $this->tenant(['email' => 'iso-a@example.test']);
        $tenantB = $this->tenant(['email' => 'iso-b@example.test']);

        $eventA = $this->event($tenantA, ['event' => 'Event A']);
        $agreementA = $this->readyAgreement($tenantA, $eventA);

        $eventB = $this->event($tenantB, ['event' => 'Event B']);
        $uidB = (string) Str::uuid();
        $agreementB = $this->readyAgreement($tenantB, $eventB, [
            'uid' => $uidB,
            'signed_pdf_path' => $this->signedPath($uidB),
            'signed_at' => now()->subDay(),
        ]);

        Storage::disk('local')->put(
            $this->signedPath($uidB),
            'signed-B-original'
        );

        // Tenant A tries to upload to their own event, event B must stay intact.
        Livewire::actingAs($tenantA)
            ->test(EventDetail::class, ['uid' => $eventA->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->create('signed-a.pdf', 100, 'application/pdf'))
            ->call('uploadSignedMou')
            ->assertHasNoErrors();

        $agreementB = $agreementB->refresh();

        $this->assertSame(
            $this->signedPath($agreementB->uid),
            $agreementB->signed_pdf_path
        );
        $this->assertSame(
            'signed-B-original',
            Storage::disk('local')->get($agreementB->signed_pdf_path)
        );
        $this->assertNotEquals($agreementA->refresh()->uid, $agreementB->uid);
    }

    public function test_snapshots_and_unsigned_pdf_do_not_change_after_upload(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['event' => 'Snapshot Frozen M9']);
        $snapshots = [
            'event_snapshot' => ['event_name' => 'Snapshot Frozen M9'],
            'party_snapshot' => ['organizer_name' => 'PT Frozen M9'],
            'bank_snapshot' => ['bank_name' => 'Bank Frozen M9'],
            'document_snapshot' => ['document_number' => 'DOC-FROZEN'],
            'commercial_snapshot' => ['buyer_fee' => ['mode' => 'percent', 'value' => 10.0]],
        ];
        $agreement = $this->readyAgreement($tenant, $event, array_merge($snapshots, [
            'template_version' => 'mou-v1',
            'unsigned_pdf_path' => self::DIR.'/'.$event->uid.'/unsigned.pdf',
            'document_number' => 'MOU-2026-001',
            'version' => 1,
        ]));

        Storage::disk('local')->put(self::DIR.'/'.$event->uid.'/unsigned.pdf', '%PDF-1.4 unsigned');

        $before = $agreement->refresh()->toArray();

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'))
            ->call('uploadSignedMou')
            ->assertHasNoErrors();

        $after = $agreement->refresh()->toArray();

        $this->assertSame($before['event_snapshot'], $after['event_snapshot']);
        $this->assertSame($before['party_snapshot'], $after['party_snapshot']);
        $this->assertSame($before['bank_snapshot'], $after['bank_snapshot']);
        $this->assertSame($before['document_snapshot'], $after['document_snapshot']);
        $this->assertSame($before['commercial_snapshot'], $after['commercial_snapshot']);
        $this->assertSame($before['template_version'], $after['template_version']);
        $this->assertSame($before['unsigned_pdf_path'], $after['unsigned_pdf_path']);
        $this->assertSame($before['version'], $after['version']);
        $this->assertSame($before['document_number'], $after['document_number']);
        $this->assertSame('%PDF-1.4 unsigned', Storage::disk('local')->get($after['unsigned_pdf_path']));
    }

    public function test_privy_fields_stay_null_after_upload(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $agreement = $this->readyAgreement($tenant, $event);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'))
            ->call('uploadSignedMou')
            ->assertHasNoErrors();

        $agreement->refresh();

        $this->assertNull($agreement->privy_document_id);
        $this->assertNull($agreement->privy_status);
        $this->assertNull($agreement->privy_reference);
        $this->assertNull($agreement->sent_to_privy_at);
    }

    public function test_ui_shows_menunggu_tanda_tangan_when_ready_without_signed_pdf(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $this->readyAgreement($tenant, $event, [
            'unsigned_pdf_path' => self::DIR.'/'.$event->uid.'/unsigned.pdf',
        ]);

        Storage::disk('local')->put(self::DIR.'/'.$event->uid.'/unsigned.pdf', '%PDF-1.4');

        $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid).'?activeTab=mou')
            ->assertOk()
            ->assertSeeText('MOU Siap Ditandatangani')
            ->assertSeeText('Menunggu tanda tangan')
            ->assertSeeText('Download MOU Unsigned')
            ->assertDontSeeText('Menunggu verifikasi admin');
    }

    public function test_ui_shows_menunggu_verifikasi_admin_when_signed_pdf_available(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $uid = (string) Str::uuid();
        $this->readyAgreement($tenant, $event, [
            'uid' => $uid,
            'unsigned_pdf_path' => self::DIR.'/'.$event->uid.'/unsigned.pdf',
            'signed_pdf_path' => $this->signedPath($uid),
            'signed_at' => now(),
        ]);

        Storage::disk('local')->put(self::DIR.'/'.$event->uid.'/unsigned.pdf', '%PDF-1.4 unsigned');
        Storage::disk('local')->put($this->signedPath($uid), '%PDF-1.4 signed');

        $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid).'?activeTab=mou')
            ->assertOk()
            ->assertSeeText('Dokumen sudah diterima dan sedang menunggu verifikasi admin.')
            ->assertSeeText('Lihat MOU Unsigned')
            ->assertSeeText('Lihat MOU Bertanda Tangan')
            ->assertSeeText('Upload Ulang')
            ->assertDontSeeText('MOU Siap Ditandatangani');
    }

    public function test_owner_can_view_signed_pdf(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $uid = (string) Str::uuid();
        $this->readyAgreement($tenant, $event, [
            'uid' => $uid,
            'unsigned_pdf_path' => self::DIR.'/'.$event->uid.'/unsigned.pdf',
            'signed_pdf_path' => $this->signedPath($uid),
        ]);

        Storage::disk('local')->put(self::DIR.'/'.$event->uid.'/unsigned.pdf', '%PDF-1.4 unsigned');
        Storage::disk('local')->put($this->signedPath($uid), '%PDF-1.4 signed content');

        $response = $this->actingAs($tenant)
            ->get(route('dashboard.event.mou.signed', $event->uid));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
        $this->assertSame('%PDF-1.4 signed content', $response->streamedContent());
    }

    public function test_missing_physical_signed_pdf_returns_404(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $uid = (string) Str::uuid();
        $this->readyAgreement($tenant, $event, [
            'uid' => $uid,
            'signed_pdf_path' => $this->signedPath($uid),
        ]);

        // No physical file written.

        $this->actingAs($tenant)
            ->get(route('dashboard.event.mou.signed', $event->uid))
            ->assertNotFound();
    }

    public function test_private_path_is_not_exposed_in_html_or_url(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $uid = (string) Str::uuid();
        $agreement = $this->readyAgreement($tenant, $event, [
            'uid' => $uid,
            'unsigned_pdf_path' => self::DIR.'/'.$event->uid.'/unsigned.pdf',
            'signed_pdf_path' => $this->signedPath($uid),
        ]);

        Storage::disk('local')->put(self::DIR.'/'.$event->uid.'/unsigned.pdf', '%PDF-1.4 unsigned');
        Storage::disk('local')->put($this->signedPath($uid), '%PDF-1.4 signed');

        $this->actingAs($tenant)
            ->get(route('dashboard.event.detail', $event->uid).'?activeTab=mou')
            ->assertOk()
            ->assertDontSeeText($agreement->unsigned_pdf_path)
            ->assertDontSeeText($agreement->signed_pdf_path)
            ->assertDontSeeText('storage/app/private');

        $response = $this->actingAs($tenant)
            ->get(route('dashboard.event.mou.signed', $event->uid));
        $response->assertOk();
        $this->assertStringNotContainsString($agreement->signed_pdf_path, (string) $response->headers->get('content-disposition'));
        $this->assertStringNotContainsString('storage/app/private', $response->streamedContent());
    }

    public function test_reupload_while_ready_replaces_authoritative_file(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $agreement = $this->readyAgreement($tenant, $event);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'))
            ->call('uploadSignedMou')
            ->assertHasNoErrors();

        $agreement->refresh();
        $firstPath = $agreement->signed_pdf_path;
        $firstSignedAt = $agreement->signed_at;

        // Re-upload while still READY.
        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->create('second.pdf', 150, 'application/pdf'))
            ->call('uploadSignedMou')
            ->assertHasNoErrors();

        $agreement->refresh();

        $this->assertNotNull($agreement->signed_pdf_path);
        $this->assertTrue(Storage::disk('local')->exists($agreement->signed_pdf_path));
        $this->assertTrue($agreement->signed_at->gt($firstSignedAt) || $agreement->signed_at->equalTo($firstSignedAt));
        $this->assertSame(Agreement::STATUS_READY, $agreement->status);

        // No orphan staged/temporary files remain.
        $files = Storage::disk('local')->allFiles(self::DIR.'/'.$agreement->uid);
        foreach ($files as $file) {
            $this->assertStringNotContainsString('staged-', $file);
            $this->assertStringNotContainsString('signed-backup-', $file);
        }
        $this->assertTrue(Storage::disk('local')->exists($agreement->signed_pdf_path));
    }

    public function test_reupload_storage_failure_keeps_previous_signed_pdf(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $agreement = $this->readyAgreement($tenant, $event);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'))
            ->call('uploadSignedMou')
            ->assertHasNoErrors();

        $agreement->refresh();
        $oldPath = $agreement->signed_pdf_path;
        $oldSignedAt = $agreement->signed_at;
        $oldContent = Storage::disk('local')->get($oldPath);

        $realDisk = Storage::disk('local');
        $failing = new class($realDisk) {
            public bool $alreadyFailed = false;

            public function __construct(private $inner)
            {
            }

            public function exists(string $path): bool
            {
                return $this->inner->exists($path);
            }

            public function get(string $path): ?string
            {
                return $this->inner->get($path);
            }

            public function put(string $path, $contents, $options = []): bool
            {
                if (str_ends_with($path, '/signed.pdf') && ! $this->alreadyFailed) {
                    // Destructive failure: clobber/truncate the target first,
                    // then report failure — simulating a partial write that
                    // already destroyed the previously accepted file.
                    $this->alreadyFailed = true;
                    $this->inner->put($path, 'CORRUPTED PARTIAL WRITE');

                    return false;
                }

                return $this->inner->put($path, $contents, $options);
            }

            public function copy(string $from, string $to): bool
            {
                return $this->inner->copy($from, $to);
            }

            public function delete($paths): bool
            {
                return $this->inner->delete($paths);
            }

            public function move(string $from, string $to): bool
            {
                return $this->inner->move($from, $to);
            }

            public function readStream(string $path)
            {
                return $this->inner->readStream($path);
            }
        };

        Storage::shouldReceive('disk')->with('local')->andReturn($failing);

        try {
            app(AgreementSignedUploadService::class)
                ->storeForEvent($event, $tenant->uid, UploadedFile::fake()->create('second.pdf', 100, 'application/pdf'));
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Dokumen signed gagal disimpan.', $e->getMessage());
        }

        $agreement->refresh();

        $this->assertSame($oldPath, $agreement->signed_pdf_path);
        $this->assertSame($oldSignedAt->format('Y-m-d H:i:s'), $agreement->signed_at->format('Y-m-d H:i:s'));
        // Even though the target was corrupted before the failure was reported,
        // the service must restore the previous file from the backup.
        $this->assertSame($oldContent, $realDisk->get($oldPath));
        $this->assertSame(Agreement::STATUS_READY, $agreement->status);

        // Staging and backup are cleaned up afterwards; only signed.pdf remains.
        $files = $realDisk->allFiles(self::DIR.'/'.$agreement->uid);
        $this->assertNotEmpty($files);
        foreach ($files as $file) {
            $this->assertStringNotContainsString('staged-', $file);
            $this->assertStringNotContainsString('signed-backup-', $file);
        }
        $this->assertSame($oldContent, $realDisk->get($oldPath));
    }

    public function test_db_failure_after_file_staging_keeps_old_state_and_cleans_temp(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $agreement = $this->readyAgreement($tenant, $event);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'))
            ->call('uploadSignedMou')
            ->assertHasNoErrors();

        $agreement->refresh();
        $oldPath = $agreement->signed_pdf_path;
        $oldSignedAt = $agreement->signed_at;
        $oldContent = Storage::disk('local')->get($oldPath);

        // Break the owner match so the Agreement save throws inside the transaction.
        $otherTenant = $this->tenant(['email' => 'other-m9-owner@example.test']);
        $event->update(['user_uid' => $otherTenant->uid]);

        try {
            app(AgreementSignedUploadService::class)
                ->storeForEvent($event, $tenant->uid, UploadedFile::fake()->create('second.pdf', 100, 'application/pdf'));
            $this->fail('Expected LogicException was not thrown.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('Event bukan milik penyewa ini.', $e->getMessage());
        }

        $agreement->refresh();

        $this->assertSame($oldPath, $agreement->signed_pdf_path);
        $this->assertSame($oldSignedAt->format('Y-m-d H:i:s'), $agreement->signed_at->format('Y-m-d H:i:s'));
        $this->assertSame($oldContent, Storage::disk('local')->get($oldPath));
        $this->assertSame(Agreement::STATUS_READY, $agreement->status);

        // Temporary files cleaned up.
        $files = Storage::disk('local')->allFiles(self::DIR.'/'.$agreement->uid);
        foreach ($files as $file) {
            $this->assertStringNotContainsString('staged-', $file);
            $this->assertStringNotContainsString('signed-backup-', $file);
        }
    }

    public function test_db_failure_after_file_write_restores_previous_signed_pdf(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $agreement = $this->readyAgreement($tenant, $event);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'))
            ->call('uploadSignedMou')
            ->assertHasNoErrors();

        $agreement->refresh();
        try {
            // Make the stored timestamp older first so the next upload always
            // produces a dirty Agreement update even if both operations happen
            // within the same second.
            Agreement::query()
                ->whereKey($agreement->getKey())
                ->update(['signed_at' => now()->subMinute()]);

            $agreement->refresh();
            $oldPath = $agreement->signed_pdf_path;
            $oldSignedAt = $agreement->signed_at;
            $oldContent = Storage::disk('local')->get($oldPath);

            // Let the new authoritative signed.pdf be written, then force the
            // Agreement update to fail AFTER the file write has succeeded.
            Agreement::updating(function () {
                throw new RuntimeException('Simulated DB failure after file write');
            });

            try {
                app(AgreementSignedUploadService::class)
                    ->storeForEvent($event, $tenant->uid, UploadedFile::fake()->create('second.pdf', 100, 'application/pdf'));
                $this->fail('Expected RuntimeException was not thrown.');
            } catch (RuntimeException $e) {
                $this->assertSame('Simulated DB failure after file write', $e->getMessage());
            }

            $agreement->refresh();

            $this->assertSame(Agreement::STATUS_READY, $agreement->status);
            $this->assertSame($oldPath, $agreement->signed_pdf_path);
            $this->assertSame($oldSignedAt->format('Y-m-d H:i:s'), $agreement->signed_at->format('Y-m-d H:i:s'));
            // The physical signed PDF must still contain the OLD bytes.
            $this->assertSame($oldContent, Storage::disk('local')->get($oldPath));

            // No staged or backup orphans remain.
            $files = Storage::disk('local')->allFiles(self::DIR.'/'.$agreement->uid);
            foreach ($files as $file) {
                $this->assertStringNotContainsString('staged-', $file);
                $this->assertStringNotContainsString('signed-backup-', $file);
            }
        } finally {
            Agreement::flushEventListeners();
            Agreement::clearBootedModels();
        }
    }

    public function test_completed_agreement_cannot_upload_or_reupload(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $uid = (string) Str::uuid();
        $this->agreement($tenant, $event, [
            'uid' => $uid,
            'status' => Agreement::STATUS_COMPLETED,
            'signed_pdf_path' => $this->signedPath($uid),
            'signed_at' => now()->subDay(),
        ]);

        Storage::disk('local')->put($this->signedPath($uid), '%PDF-1.4 signed');

        try {
            app(AgreementSignedUploadService::class)
                ->storeForEvent($event, $tenant->uid, UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'));
            $this->fail('Expected LogicException was not thrown.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('MOU hanya dapat diunggah saat berstatus READY.', $e->getMessage());
        }

        $agreement = $event->currentMouAgreement->refresh();
        $this->assertSame(Agreement::STATUS_COMPLETED, $agreement->status);
        $this->assertSame(
            $this->signedPath($agreement->uid),
            $agreement->signed_pdf_path
        );
        $this->assertSame('%PDF-1.4 signed', Storage::disk('local')->get($agreement->signed_pdf_path));
    }

    public function test_upload_does_not_complete_agreement(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant);
        $agreement = $this->readyAgreement($tenant, $event);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'mou')
            ->set('signedMou', UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'))
            ->call('uploadSignedMou')
            ->assertHasNoErrors();

        $agreement->refresh();

        $this->assertSame(Agreement::STATUS_READY, $agreement->status);
        $this->assertNull($agreement->completed_at);
    }

    private function admin(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Admin M9',
            'email' => 'admin-m9@example.test',
            'role' => 'admin',
        ], $overrides));
    }

    private function tenant(array $overrides = []): User
    {
        return $this->user(array_merge([
            'name' => 'Tenant M9',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'M9 User',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat M9 User',
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
            'event' => 'M9 Event '.$uid,
            'alamat' => 'Alamat M9 Event',
            'tanggal' => '2026-09-10 19:00:00',
            'event_end' => '2026-09-10 22:00:00',
            'venue_name' => 'Venue M9',
            'venue_address' => 'Jl. M9',
            'venue_city' => 'Jakarta',
            'venue_province' => 'DKI Jakarta',
            'status' => 'inactive',
            'cover' => 'm9-cover.jpg',
            'fee' => 10,
            'pajak' => 0,
            'deskripsi' => 'Deskripsi M9',
            'map' => 'https://maps.google.com/?q=m9',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'm9-'.Str::lower(Str::random(8)),
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
            'privy_document_id' => null,
            'privy_status' => null,
            'privy_reference' => null,
            'sent_to_privy_at' => null,
            'signed_at' => null,
            'completed_at' => null,
        ], $overrides));
    }

    /**
     * Authoritative signed-PDF path for an Agreement, mirroring M8's
     * agreement-scoped unsigned.pdf path.
     */
    private function signedPath(string $agreementUid): string
    {
        return self::DIR.'/'.$agreementUid.'/signed.pdf';
    }

    /**
     * Create a READY agreement that mirrors an M8-finalized one without
     * depending on DomPDF rendering during the manual signing tests.
     */
    private function readyAgreement(User $tenant, Event $event, array $overrides = []): Agreement
    {
        return $this->agreement($tenant, $event, array_merge([
            'status' => Agreement::STATUS_READY,
            'template_version' => 'mou-v1',
            'event_snapshot' => ['event_name' => $event->event],
            'party_snapshot' => ['organizer_name' => 'PT M9 Organizer'],
            'bank_snapshot' => ['bank_name' => 'Bank M9'],
            'document_snapshot' => ['document_number' => 'DOC-M9-001'],
            'commercial_snapshot' => ['buyer_fee' => ['mode' => 'percent', 'value' => 10.0]],
            'document_number' => 'MOU-M9-001',
            'unsigned_pdf_path' => self::DIR.'/'.$event->uid.'/unsigned.pdf',
        ], $overrides));
    }
}
