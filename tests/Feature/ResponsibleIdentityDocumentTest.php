<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventCreate;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventBankAccount;
use App\Models\EventDocument;
use App\Models\EventOrganizer;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\View\ViewException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ResponsibleIdentityDocumentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        View::share('logo', [(object) ['logo' => '']]);
        Storage::fake('local');
        Storage::fake('public');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        $this->artisan('migrate:fresh', ['--database' => 'sqlite']);
    }

    public function test_create_event_saves_responsible_identity_document_in_private_storage(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Music Identity', 'slug' => 'music-identity']);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class)
            ->set('event', 'Festival Identitas')
            ->set('fee', 10)
            ->set('start_sale', '2026-09-01 10:00')
            ->set('event_start', '2026-09-10 19:00')
            ->set('event_end', '2026-09-10 22:00')
            ->set('venue_name', 'Istora Senayan')
            ->set('venue_address', 'Jl. Pintu Satu Senayan')
            ->set('venue_city', 'Jakarta Pusat')
            ->set('venue_province', 'DKI Jakarta')
            ->set('map', 'https://maps.google.com/?q=istora')
            ->set('cover', UploadedFile::fake()->image('cover.jpg'))
            ->set('deskripsi', 'Deskripsi event identitas')
            ->set('category_id', $category->id)
            ->set('organizer_name', 'PT Event Nusantara')
            ->set('responsible_name', 'Sawalinto')
            ->set('responsible_position', 'Project Manager')
            ->set('phone', '081234567890')
            ->set('email', 'organizer@example.test')
            ->set('address', 'Alamat penyelenggara lengkap')
            ->set('bank_name', 'Bank Central Asia')
            ->set('account_number', '1234567890')
            ->set('account_holder_name', 'PT Event Nusantara')
            ->set('bank_book', UploadedFile::fake()->create('bank-book.pdf', 256, 'application/pdf'))
            ->set('document_number', '001/SP-EVENT/VIII/2026')
            ->set('document_date', '2026-08-20')
            ->set('organizer_letter', UploadedFile::fake()->create('organizer-letter.pdf', 256, 'application/pdf'))
            ->set('responsible_identity', UploadedFile::fake()->create('ktp-pj.pdf', 256, 'application/pdf'))
            ->call('save');

        $event = Event::where('event', 'Festival Identitas')
            ->with('responsibleIdentityDocument')
            ->firstOrFail();
        $document = $event->responsibleIdentityDocument;

        $this->assertNotNull($document);
        $this->assertSame(EventDocument::TYPE_RESPONSIBLE_IDENTITY, $document->document_type);
        $this->assertNull($document->document_number);
        $this->assertNull($document->document_date);
        $this->assertSame('pending', $document->status);
        $this->assertSame('ktp-pj.pdf', $document->original_name);
        $this->assertSame('application/pdf', $document->mime_type);
        $this->assertStringStartsWith('private/events/'.$event->uid.'/responsible-identity/', $document->file_path);
        $this->assertStringNotContainsString('/storage/', $document->file_path);
        $this->assertFalse(Str::startsWith($document->file_path, ['http://', 'https://']));
        Storage::disk('local')->assertExists($document->file_path);
        $this->assertSame(
            1,
            EventDocument::query()
                ->where('event_uid', $event->uid)
                ->where('document_type', EventDocument::TYPE_RESPONSIBLE_IDENTITY)
                ->count()
        );
    }

    public function test_edit_without_replacement_preserves_existing_responsible_identity_file_and_status(): void
    {
        $tenant = $this->tenant(['email' => 'preserve@example.test']);
        $category = Category::create(['name' => 'Preserve Identity', 'slug' => 'preserve-identity']);
        $event = $this->event($tenant, $category, ['event' => 'Preserve Responsible Identity']);
        $existing = $this->seedResponsibleIdentity($event, [
            'file_path' => 'private/events/'.$event->uid.'/responsible-identity/existing-identity.pdf',
            'original_name' => 'existing-identity.pdf',
            'status' => 'verified',
            'verified_by' => 'admin-doc',
            'verified_at' => '2026-08-20 11:00:00',
            'rejection_reason' => 'keep',
        ]);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->call('save');

        $document = $event->fresh()->responsibleIdentityDocument()->firstOrFail();

        $this->assertSame($existing->uid, $document->uid);
        $this->assertSame('private/events/'.$event->uid.'/responsible-identity/existing-identity.pdf', $document->file_path);
        $this->assertSame('existing-identity.pdf', $document->original_name);
        $this->assertSame('verified', $document->status);
        $this->assertSame('admin-doc', $document->verified_by);
        $this->assertSame('2026-08-20 11:00:00', $document->verified_at?->format('Y-m-d H:i:s'));
        $this->assertSame('keep', $document->rejection_reason);
        Storage::disk('local')->assertExists($document->file_path);
        $this->assertSame(
            1,
            EventDocument::query()
                ->where('event_uid', $event->uid)
                ->where('document_type', EventDocument::TYPE_RESPONSIBLE_IDENTITY)
                ->count()
        );
    }

    public function test_legacy_edit_without_responsible_identity_still_saves_other_event_changes(): void
    {
        $tenant = $this->tenant(['email' => 'legacy-no-identity@example.test']);
        $category = Category::create(['name' => 'Legacy No Identity', 'slug' => 'legacy-no-identity']);
        $event = $this->event($tenant, $category, ['event' => 'Legacy Event Without Identity']);

        $this->assertNull($event->responsibleIdentityDocument);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('event', 'Legacy Event Updated')
            ->call('save');

        $event->refresh();

        $this->assertSame('Legacy Event Updated', $event->event);
        $this->assertNull($event->responsibleIdentityDocument()->first());
        $this->assertSame(
            0,
            EventDocument::query()
                ->where('event_uid', $event->uid)
                ->where('document_type', EventDocument::TYPE_RESPONSIBLE_IDENTITY)
                ->count()
        );
    }

    public function test_replacement_resets_verification_and_deletes_old_responsible_identity_after_success(): void
    {
        $tenant = $this->tenant(['email' => 'replace-success@example.test']);
        $category = Category::create(['name' => 'Replace Identity', 'slug' => 'replace-identity']);
        $event = $this->event($tenant, $category, ['event' => 'Replace Responsible Identity']);
        $existing = $this->seedResponsibleIdentity($event, [
            'file_path' => 'private/events/'.$event->uid.'/responsible-identity/old-identity.pdf',
            'original_name' => 'old-identity.pdf',
            'status' => 'verified',
            'verified_by' => 'admin-doc',
            'verified_at' => '2026-08-21 09:00:00',
            'rejection_reason' => 'old note',
        ]);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('responsible_identity', UploadedFile::fake()->create('new-identity.pdf', 256, 'application/pdf'))
            ->call('save');

        $document = $event->fresh()->responsibleIdentityDocument()->firstOrFail();

        $this->assertSame($existing->uid, $document->uid);
        $this->assertNotSame('private/events/'.$event->uid.'/responsible-identity/old-identity.pdf', $document->file_path);
        $this->assertSame('new-identity.pdf', $document->original_name);
        $this->assertSame('pending', $document->status);
        $this->assertNull($document->verified_by);
        $this->assertNull($document->verified_at);
        $this->assertNull($document->rejection_reason);
        Storage::disk('local')->assertMissing('private/events/'.$event->uid.'/responsible-identity/old-identity.pdf');
        Storage::disk('local')->assertExists($document->file_path);
        $this->assertSame(
            1,
            EventDocument::query()
                ->where('event_uid', $event->uid)
                ->where('document_type', EventDocument::TYPE_RESPONSIBLE_IDENTITY)
                ->count()
        );
    }

    public function test_replacement_failure_rolls_back_without_deleting_old_responsible_identity_file(): void
    {
        $tenant = $this->tenant(['email' => 'replace-fail@example.test']);
        $category = Category::create(['name' => 'Rollback Identity', 'slug' => 'rollback-identity']);
        $event = $this->event($tenant, $category, ['event' => 'Rollback Responsible Identity']);
        $this->seedResponsibleIdentity($event, [
            'file_path' => 'private/events/'.$event->uid.'/responsible-identity/rollback-identity.pdf',
            'original_name' => 'rollback-identity.pdf',
            'status' => 'verified',
            'verified_by' => 'admin-doc',
            'verified_at' => '2026-08-22 10:00:00',
            'rejection_reason' => 'keep safe',
        ]);
        $initialFiles = Storage::disk('local')->allFiles('private/events/'.$event->uid);

        EventDocument::saving(function (EventDocument $document) use ($event) {
            if (
                $document->event_uid === $event->uid
                && $document->document_type === EventDocument::TYPE_RESPONSIBLE_IDENTITY
                && $document->file_path !== 'private/events/'.$event->uid.'/responsible-identity/rollback-identity.pdf'
            ) {
                throw new \RuntimeException('forced responsible identity failure');
            }
        });

        try {
            Livewire::actingAs($tenant)
                ->test(EventCreate::class, ['uid' => $event->uid])
                ->set('responsible_identity', UploadedFile::fake()->create('replacement-identity.pdf', 256, 'application/pdf'))
                ->call('save');

            $this->fail('Expected responsible identity replacement to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('forced responsible identity failure', $exception->getMessage());
        } finally {
            EventDocument::flushEventListeners();
        }

        $document = $event->fresh()->responsibleIdentityDocument()->firstOrFail();

        $this->assertSame('private/events/'.$event->uid.'/responsible-identity/rollback-identity.pdf', $document->file_path);
        $this->assertSame('rollback-identity.pdf', $document->original_name);
        $this->assertSame('verified', $document->status);
        $this->assertSame('admin-doc', $document->verified_by);
        $this->assertSame('2026-08-22 10:00:00', $document->verified_at?->format('Y-m-d H:i:s'));
        $this->assertSame('keep safe', $document->rejection_reason);
        Storage::disk('local')->assertExists($document->file_path);
        $this->assertSame($initialFiles, Storage::disk('local')->allFiles('private/events/'.$event->uid));
        $this->assertSame(
            1,
            EventDocument::query()
                ->where('event_uid', $event->uid)
                ->where('document_type', EventDocument::TYPE_RESPONSIBLE_IDENTITY)
                ->count()
        );
    }

    public function test_responsible_identity_rejects_invalid_mime_upload(): void
    {
        $tenant = $this->tenant(['email' => 'invalid-mime@example.test']);
        $category = Category::create(['name' => 'Invalid Mime Identity', 'slug' => 'invalid-mime-identity']);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class)
            ->set('event', 'Invalid Mime Event')
            ->set('fee', 10)
            ->set('start_sale', '2026-09-01 10:00')
            ->set('event_start', '2026-09-10 19:00')
            ->set('event_end', '2026-09-10 22:00')
            ->set('venue_name', 'Venue Invalid Mime')
            ->set('venue_address', 'Alamat Venue Invalid Mime')
            ->set('venue_city', 'Jakarta')
            ->set('venue_province', 'DKI Jakarta')
            ->set('map', 'https://maps.google.com/?q=invalid-mime')
            ->set('cover', UploadedFile::fake()->image('cover.jpg'))
            ->set('deskripsi', 'Deskripsi invalid mime')
            ->set('category_id', $category->id)
            ->set('organizer_name', 'Organizer Invalid Mime')
            ->set('responsible_name', 'PJ Invalid Mime')
            ->set('responsible_position', 'Manager Invalid Mime')
            ->set('phone', '081234567890')
            ->set('email', 'invalid-mime@example.test')
            ->set('address', 'Alamat organizer invalid mime')
            ->set('bank_name', 'Bank Invalid Mime')
            ->set('account_number', '111222333')
            ->set('account_holder_name', 'Organizer Invalid Mime')
            ->set('bank_book', UploadedFile::fake()->create('bank-book.pdf', 256, 'application/pdf'))
            ->set('document_number', 'INV-001')
            ->set('document_date', '2026-08-20')
            ->set('organizer_letter', UploadedFile::fake()->create('organizer-letter.pdf', 256, 'application/pdf'))
            ->set('responsible_identity', UploadedFile::fake()->create('identity.txt', 10, 'text/plain'))
            ->call('save')
            ->assertHasErrors(['responsible_identity' => ['mimes']]);
    }

    public function test_responsible_identity_rejects_files_larger_than_five_mb(): void
    {
        $tenant = $this->tenant(['email' => 'invalid-size@example.test']);
        $category = Category::create(['name' => 'Invalid Size Identity', 'slug' => 'invalid-size-identity']);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class)
            ->set('event', 'Invalid Size Event')
            ->set('fee', 10)
            ->set('start_sale', '2026-09-01 10:00')
            ->set('event_start', '2026-09-10 19:00')
            ->set('event_end', '2026-09-10 22:00')
            ->set('venue_name', 'Venue Invalid Size')
            ->set('venue_address', 'Alamat Venue Invalid Size')
            ->set('venue_city', 'Jakarta')
            ->set('venue_province', 'DKI Jakarta')
            ->set('map', 'https://maps.google.com/?q=invalid-size')
            ->set('cover', UploadedFile::fake()->image('cover.jpg'))
            ->set('deskripsi', 'Deskripsi invalid size')
            ->set('category_id', $category->id)
            ->set('organizer_name', 'Organizer Invalid Size')
            ->set('responsible_name', 'PJ Invalid Size')
            ->set('responsible_position', 'Manager Invalid Size')
            ->set('phone', '081234567890')
            ->set('email', 'invalid-size@example.test')
            ->set('address', 'Alamat organizer invalid size')
            ->set('bank_name', 'Bank Invalid Size')
            ->set('account_number', '444555666')
            ->set('account_holder_name', 'Organizer Invalid Size')
            ->set('bank_book', UploadedFile::fake()->create('bank-book.pdf', 256, 'application/pdf'))
            ->set('document_number', 'INV-002')
            ->set('document_date', '2026-08-20')
            ->set('organizer_letter', UploadedFile::fake()->create('organizer-letter.pdf', 256, 'application/pdf'))
            ->set('responsible_identity', UploadedFile::fake()->create('identity.pdf', 6000, 'application/pdf'))
            ->call('save')
            ->assertHasErrors(['responsible_identity' => ['max']]);
    }

    public function test_cross_tenant_edit_is_denied(): void
    {
        $owner = $this->tenant(['email' => 'owner-cross@example.test']);
        $otherTenant = $this->tenant(['email' => 'other-cross@example.test']);
        $category = Category::create(['name' => 'Cross Tenant Identity', 'slug' => 'cross-tenant-identity']);
        $event = $this->event($owner, $category, ['event' => 'Cross Tenant Event']);
        $this->seedResponsibleIdentity($event);

        try {
            Livewire::actingAs($otherTenant)
                ->test(EventCreate::class, ['uid' => $event->uid]);

            $this->fail('Expected cross-tenant edit to be denied.');
        } catch (ViewException $exception) {
            $this->assertInstanceOf(ModelNotFoundException::class, $exception->getPrevious());
        }
    }

    private function tenant(array $overrides = []): User
    {
        return User::create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Tenant User',
            'email' => 'tenant-'.Str::lower(Str::random(8)).'@example.test',
            'password' => Hash::make('password'),
            'role' => 'user',
            'parent_uid' => null,
            'nomor' => '081234567890',
            'alamat' => 'Alamat tenant',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'birthday' => '2000-01-01',
        ], $overrides));
    }

    private function event(User $tenant, Category $category, array $overrides = []): Event
    {
        $event = Event::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $tenant->uid,
            'category_id' => $category->id,
            'event' => 'Existing Event',
            'alamat' => 'Istora Senayan, Jl. Pintu Satu Senayan, Jakarta Pusat, DKI Jakarta',
            'tanggal' => '2026-09-10 19:00:00',
            'event_end' => '2026-09-10 22:00:00',
            'venue_name' => 'Istora Senayan',
            'venue_address' => 'Jl. Pintu Satu Senayan',
            'venue_city' => 'Jakarta Pusat',
            'venue_province' => 'DKI Jakarta',
            'status' => 'inactive',
            'cover' => 'existing-cover.jpg',
            'fee' => 10,
            'deskripsi' => 'Deskripsi existing event',
            'map' => 'https://maps.google.com/?q=existing',
            'pajak' => 0,
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'existing-event-'.Str::lower(Str::random(5)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
        ], $overrides));

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'PT Event Nusantara',
            'responsible_name' => 'Sawalinto',
            'responsible_position' => 'Project Manager',
            'phone' => '081234567890',
            'email' => 'organizer@example.test',
            'address' => 'Alamat penyelenggara lengkap',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Central Asia',
            'account_number' => '1234567890',
            'account_holder_name' => 'PT Event Nusantara',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/book.pdf',
            'bank_book_original_name' => 'book.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'verified',
            'verified_by' => 'admin-bank',
            'verified_at' => '2026-08-20 10:00:00',
            'rejection_reason' => null,
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/book.pdf', '%PDF-bank');

        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-001',
            'document_date' => '2026-08-20',
            'original_name' => 'organizer-letter.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/organizer-letter.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_by' => 'admin-doc',
            'verified_at' => '2026-08-20 10:30:00',
            'rejection_reason' => null,
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/organizer-letter.pdf', '%PDF-letter');

        return $event;
    }

    private function seedResponsibleIdentity(Event $event, array $overrides = []): EventDocument
    {
        $document = EventDocument::create(array_merge([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_RESPONSIBLE_IDENTITY,
            'document_number' => null,
            'document_date' => null,
            'original_name' => 'responsible-identity.pdf',
            'file_path' => 'private/events/'.$event->uid.'/responsible-identity/responsible-identity.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
            'rejection_reason' => null,
        ], $overrides));

        Storage::disk('local')->put($document->file_path, '%PDF-identity');

        return $document;
    }
}
