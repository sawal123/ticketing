<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventCreate;
use App\Models\Agreement;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventBankAccount;
use App\Models\EventDocument;
use App\Models\EventOrganizer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardEventCreateTest extends TestCase
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

    public function test_create_event_saves_new_schedule_and_location_fields_with_legacy_compatibility(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Music', 'slug' => 'music']);
        $bankBook = UploadedFile::fake()->create('bank-book.pdf', 256, 'application/pdf');
        $organizerLetter = UploadedFile::fake()->create('organizer-letter.pdf', 256, 'application/pdf');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class)
            ->set('event', 'Festival Nusantara')
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
            ->set('deskripsi', 'Deskripsi event utama')
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
            ->set('bank_book', $bankBook)
            ->set('document_number', '001/SP-EVENT/VIII/2026')
            ->set('document_date', '2026-08-20')
            ->set('organizer_letter', $organizerLetter)
            ->set('responsible_identity', UploadedFile::fake()->create('responsible-identity.pdf', 128, 'application/pdf'))
            ->call('save');

        $event = Event::where('event', 'Festival Nusantara')->firstOrFail();

        $this->assertSame('2026-09-01 10:00:00', substr((string) $event->start_sale, 0, 19));
        $this->assertSame('2026-09-10 19:00:00', substr((string) $event->tanggal, 0, 19));
        $this->assertSame('2026-09-10 22:00:00', substr((string) $event->event_end, 0, 19));
        $this->assertSame('Istora Senayan', $event->venue_name);
        $this->assertSame('Jl. Pintu Satu Senayan', $event->venue_address);
        $this->assertSame('Jakarta Pusat', $event->venue_city);
        $this->assertSame('DKI Jakarta', $event->venue_province);
        $this->assertSame('Istora Senayan, Jl. Pintu Satu Senayan, Jakarta Pusat, DKI Jakarta', $event->alamat);
        $this->assertSame(10, (int) $event->fee);
        $this->assertSame(0, (int) $event->pajak);
        $this->assertNotNull($event->organizer);
        $this->assertSame('PT Event Nusantara', $event->organizer->organizer_name);
        $this->assertSame('Sawalinto', $event->organizer->responsible_name);
        $this->assertSame('Project Manager', $event->organizer->responsible_position);
        $this->assertSame('081234567890', $event->organizer->phone);
        $this->assertSame('organizer@example.test', $event->organizer->email);
        $this->assertSame('Alamat penyelenggara lengkap', $event->organizer->address);
        $this->assertNotNull($event->bankAccount);
        $this->assertSame('Bank Central Asia', $event->bankAccount->bank_name);
        $this->assertSame('1234567890', $event->bankAccount->account_number);
        $this->assertSame('PT Event Nusantara', $event->bankAccount->account_holder_name);
        $this->assertSame('pending', $event->bankAccount->status);
        $this->assertSame('bank-book.pdf', $event->bankAccount->bank_book_original_name);
        $this->assertSame('application/pdf', $event->bankAccount->bank_book_mime);
        $this->assertStringStartsWith('private/events/'.$event->uid.'/bank/', $event->bankAccount->bank_book_path);
        Storage::disk('local')->assertExists($event->bankAccount->bank_book_path);
        $this->assertNotNull($event->organizerLetter);
        $this->assertSame(EventDocument::TYPE_ORGANIZER_LETTER, $event->organizerLetter->document_type);
        $this->assertNotEmpty($event->organizerLetter->uid);
        $this->assertSame('001/SP-EVENT/VIII/2026', $event->organizerLetter->document_number);
        $this->assertSame('2026-08-20', $event->organizerLetter->document_date?->format('Y-m-d'));
        $this->assertSame('pending', $event->organizerLetter->status);
        $this->assertSame('organizer-letter.pdf', $event->organizerLetter->original_name);
        $this->assertStringStartsWith('private/events/'.$event->uid.'/documents/', $event->organizerLetter->file_path);
        Storage::disk('local')->assertExists($event->organizerLetter->file_path);

        $agreements = Agreement::where('event_uid', $event->uid)
            ->where('type', Agreement::TYPE_MOU)
            ->where('version', 1)
            ->get();
        $agreement = $agreements->sole();

        $this->assertCount(1, $agreements);
        $this->assertNotEmpty($agreement->uid);
        $this->assertSame($event->uid, $agreement->event_uid);
        $this->assertSame($event->user_uid, $agreement->tenant_user_uid);
        $this->assertSame($tenant->uid, $agreement->created_by);
        $this->assertSame(Agreement::TYPE_MOU, $agreement->type);
        $this->assertSame(1, $agreement->version);
        $this->assertSame(Agreement::STATUS_DRAFT, $agreement->status);
        $this->assertNull($agreement->event_snapshot);
        $this->assertNull($agreement->party_snapshot);
        $this->assertNull($agreement->bank_snapshot);
        $this->assertNull($agreement->document_snapshot);
        $this->assertNull($agreement->commercial_snapshot);
        $this->assertNull($agreement->document_number);
        $this->assertNull($agreement->template_version);
        $this->assertNull($agreement->unsigned_pdf_path);
        $this->assertNull($agreement->signed_pdf_path);
        $this->assertNull($agreement->privy_document_id);
        $this->assertNull($agreement->privy_status);
        $this->assertNull($agreement->privy_reference);
        $this->assertNull($agreement->sent_to_privy_at);
        $this->assertNull($agreement->signed_at);
        $this->assertNull($agreement->completed_at);
        $this->assertTrue($event->fresh()->agreements->contains(fn (Agreement $item) => $item->id === $agreement->id));
        $this->assertSame($event->uid, $agreement->event->uid);
        $this->assertSame($tenant->uid, $agreement->tenant->uid);
    }

    public function test_staff_created_event_uses_parent_owner_for_agreement_tenant_and_staff_for_created_by(): void
    {
        $owner = $this->tenant(['email' => 'owner-m5@example.test']);
        $staff = $this->user([
            'email' => 'staff-m5@example.test',
            'role' => 'staff',
            'parent_uid' => $owner->uid,
        ]);
        $category = Category::create(['name' => 'Music Staff', 'slug' => 'music-staff']);

        Livewire::actingAs($staff)
            ->test(EventCreate::class)
            ->set('event', 'Festival Staff')
            ->set('fee', 10)
            ->set('start_sale', '2026-09-01 10:00')
            ->set('event_start', '2026-09-10 19:00')
            ->set('event_end', '2026-09-10 22:00')
            ->set('venue_name', 'Venue Staff')
            ->set('venue_address', 'Alamat Venue Staff')
            ->set('venue_city', 'Jakarta')
            ->set('venue_province', 'DKI Jakarta')
            ->set('map', 'https://maps.google.com/?q=staff')
            ->set('cover', UploadedFile::fake()->image('staff-cover.jpg'))
            ->set('deskripsi', 'Deskripsi event staff')
            ->set('category_id', $category->id)
            ->set('organizer_name', 'Organizer Staff')
            ->set('responsible_name', 'PJ Staff')
            ->set('responsible_position', 'Manager Staff')
            ->set('phone', '081234567890')
            ->set('email', 'staff-organizer@example.test')
            ->set('address', 'Alamat organizer staff')
            ->set('bank_name', 'Bank Staff')
            ->set('account_number', '987654321')
            ->set('account_holder_name', 'Organizer Staff')
            ->set('bank_book', UploadedFile::fake()->create('staff-book.pdf', 128, 'application/pdf'))
            ->set('document_number', 'STF-001')
            ->set('document_date', '2026-08-20')
            ->set('organizer_letter', UploadedFile::fake()->create('staff-letter.pdf', 128, 'application/pdf'))
            ->set('responsible_identity', UploadedFile::fake()->create('responsible-identity.pdf', 128, 'application/pdf'))
            ->call('save');

        $event = Event::where('event', 'Festival Staff')->firstOrFail();
        $agreement = Agreement::where('event_uid', $event->uid)
            ->where('type', Agreement::TYPE_MOU)
            ->where('version', 1)
            ->sole();

        $this->assertSame($owner->uid, $event->user_uid);
        $this->assertSame($owner->uid, $agreement->tenant_user_uid);
        $this->assertSame($staff->uid, $agreement->created_by);
        $this->assertSame($event->user_uid, $agreement->tenant_user_uid);
    }

    public function test_staff_can_open_event_routes_over_http(): void
    {
        $owner = $this->tenant(['email' => 'owner-m5-http@example.test']);
        $staff = $this->user([
            'email' => 'staff-m5-http@example.test',
            'role' => 'staff',
            'parent_uid' => $owner->uid,
        ]);
        $ownerEvent = $this->event($owner, ['event' => 'Festival Owner Staff Http']);

        $this->actingAs($staff)
            ->get(route('dashboard.event'))
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('dashboard.event.create'))
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('dashboard.event.edit', $ownerEvent->uid))
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('dashboard.event.detail', $ownerEvent->uid))
            ->assertOk();
    }

    public function test_legacy_dashboard_add_event_is_closed_and_does_not_create_event(): void
    {
        $tenant = $this->tenant(['email' => 'legacy-dashboard@example.test']);
        $initialEventCount = Event::count();
        $initialAgreementCount = Agreement::count();

        $response = $this->actingAs($tenant)->post(route('dashboard.old.addEvent'), [
            'event' => 'Legacy Dashboard Event',
            'fee' => 7,
            'alamat' => 'Alamat Legacy Dashboard',
            'start' => '2026-09-15 19:00:00',
            'end' => '2026-09-15 22:00:00',
            'map' => 'https://maps.google.com/?q=legacy-dashboard',
            'deskripsi' => 'Deskripsi legacy dashboard',
            'cover' => UploadedFile::fake()->image('legacy-dashboard.jpg'),
        ]);

        $response->assertRedirect(route('dashboard.event.create'));
        $response->assertSessionHas('error', 'Form event lama sudah ditutup. Gunakan form event baru.');
        $this->assertSame($initialEventCount, Event::count());
        $this->assertSame($initialAgreementCount, Agreement::count());
        $this->assertDatabaseMissing('events', ['event' => 'Legacy Dashboard Event']);
    }

    public function test_legacy_admin_add_event_is_closed_and_does_not_create_event(): void
    {
        $admin = $this->user([
            'email' => 'legacy-admin@example.test',
            'role' => 'admin',
        ]);
        $initialEventCount = Event::count();
        $initialAgreementCount = Agreement::count();

        $response = $this->actingAs($admin)->post('/admin/old/addEvents', [
            'event' => 'Legacy Admin Event',
            'fee' => 9,
            'alamat' => 'Alamat Legacy Admin',
            'tanggal' => '2026-09-20 19:00:00',
            'map' => 'https://maps.google.com/?q=legacy-admin',
            'deskripsi' => 'Deskripsi legacy admin',
            'cover' => UploadedFile::fake()->image('legacy-admin.jpg'),
        ]);

        $response->assertRedirect(route('admin.event'));
        $response->assertSessionHas('error', 'Form event legacy admin sudah ditutup. Event baru harus diajukan oleh penyewa melalui form event baru.');
        $this->assertSame($initialEventCount, Event::count());
        $this->assertSame($initialAgreementCount, Agreement::count());
        $this->assertDatabaseMissing('events', ['event' => 'Legacy Admin Event']);
    }

    public function test_edit_event_updates_new_fields_and_keeps_existing_fee_column(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Talk Show', 'slug' => 'talk-show']);
        $event = $this->event($tenant, ['category_id' => $category->id, 'fee' => 5]);
        $agreement = Agreement::createDraftForEvent($event, $tenant->uid);
        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Lama',
            'responsible_name' => 'Penanggung Jawab Lama',
            'responsible_position' => 'Koordinator Lama',
            'phone' => '081111111111',
            'email' => 'lama@example.test',
            'address' => 'Alamat lama organizer',
        ]);
        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Lama',
            'account_number' => '000111222',
            'account_holder_name' => 'Pemilik Lama',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/old-book.pdf',
            'bank_book_original_name' => 'old-book.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/old-book.pdf', 'legacy');
        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'OLD-001',
            'document_date' => '2026-08-10',
            'original_name' => 'old-letter.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/old-letter.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/old-letter.pdf', 'old-letter');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('event', 'Festival Nusantara Revisi')
            ->set('fee', 12)
            ->set('event_end', '2026-09-10 23:00')
            ->set('venue_name', 'ICE BSD Hall 1')
            ->set('venue_address', 'Jl. BSD Grand Boulevard')
            ->set('venue_city', 'Tangerang')
            ->set('venue_province', 'Banten')
            ->set('organizer_name', 'Organizer Baru')
            ->set('responsible_name', 'Penanggung Jawab Baru')
            ->set('responsible_position', 'Event Director')
            ->set('phone', '082222222222')
            ->set('email', 'baru@example.test')
            ->set('address', 'Alamat baru organizer')
            ->set('bank_name', 'Bank Negara Indonesia')
            ->set('account_number', '9876543210')
            ->set('account_holder_name', 'Organizer Baru')
            ->set('document_number', 'NEW-001')
            ->set('document_date', '2026-08-25')
            ->call('save');

        $event->refresh();
        $organizer = $event->organizer()->first();
        $bankAccount = $event->bankAccount()->first();
        $organizerLetter = $event->organizerLetter()->first();

        $this->assertSame('Festival Nusantara Revisi', $event->event);
        $this->assertSame('2026-09-10 23:00:00', substr((string) $event->event_end, 0, 19));
        $this->assertSame('ICE BSD Hall 1', $event->venue_name);
        $this->assertSame('Jl. BSD Grand Boulevard', $event->venue_address);
        $this->assertSame('Tangerang', $event->venue_city);
        $this->assertSame('Banten', $event->venue_province);
        $this->assertSame('ICE BSD Hall 1, Jl. BSD Grand Boulevard, Tangerang, Banten', $event->alamat);
        $this->assertSame(12, (int) $event->fee);
        $this->assertSame('Organizer Baru', $organizer->organizer_name);
        $this->assertSame('Penanggung Jawab Baru', $organizer->responsible_name);
        $this->assertSame('Event Director', $organizer->responsible_position);
        $this->assertSame('082222222222', $organizer->phone);
        $this->assertSame('baru@example.test', $organizer->email);
        $this->assertSame('Alamat baru organizer', $organizer->address);
        $this->assertSame('Bank Negara Indonesia', $bankAccount->bank_name);
        $this->assertSame('9876543210', $bankAccount->account_number);
        $this->assertSame('Organizer Baru', $bankAccount->account_holder_name);
        $this->assertSame('private/events/'.$event->uid.'/bank/old-book.pdf', $bankAccount->bank_book_path);
        Storage::disk('local')->assertExists($bankAccount->bank_book_path);
        $this->assertSame('NEW-001', $organizerLetter->document_number);
        $this->assertSame('2026-08-25', $organizerLetter->document_date?->format('Y-m-d'));
        $this->assertSame('private/events/'.$event->uid.'/documents/old-letter.pdf', $organizerLetter->file_path);
        Storage::disk('local')->assertExists($organizerLetter->file_path);

        $agreements = Agreement::where('event_uid', $event->uid)->orderBy('id')->get();
        $this->assertCount(1, $agreements);
        $this->assertSame($agreement->uid, $agreements->first()->uid);
        $this->assertSame(1, $agreements->first()->version);
        $this->assertSame(Agreement::STATUS_DRAFT, $agreements->first()->status);
        $this->assertNull($agreements->first()->event_snapshot);
        $this->assertNull($agreements->first()->party_snapshot);
        $this->assertNull($agreements->first()->bank_snapshot);
        $this->assertNull($agreements->first()->document_snapshot);
        $this->assertNull($agreements->first()->commercial_snapshot);
    }

    public function test_validation_rejects_invalid_date_order(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Expo', 'slug' => 'expo']);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class)
            ->set('event', 'Invalid Date Event')
            ->set('fee', 10)
            ->set('start_sale', '2026-09-10 10:00')
            ->set('event_start', '2026-09-09 19:00')
            ->set('event_end', '2026-09-09 18:00')
            ->set('venue_name', 'Venue A')
            ->set('venue_address', 'Alamat Venue A')
            ->set('venue_city', 'Bandung')
            ->set('venue_province', 'Jawa Barat')
            ->set('map', 'https://example.test/map')
            ->set('cover', UploadedFile::fake()->image('cover.jpg'))
            ->set('deskripsi', 'Deskripsi event invalid')
            ->set('organizer_name', 'Organizer Uji')
            ->set('responsible_name', 'PJ Uji')
            ->set('responsible_position', 'Koordinator')
            ->set('phone', '081234567890')
            ->set('email', 'uji@example.test')
            ->set('address', 'Alamat organizer uji')
            ->set('bank_name', 'Bank Uji')
            ->set('account_number', '123456789')
            ->set('account_holder_name', 'Organizer Uji')
            ->set('bank_book', UploadedFile::fake()->create('bank-book.pdf', 128, 'application/pdf'))
            ->set('document_number', 'EXP-001')
            ->set('document_date', '2026-08-20')
            ->set('organizer_letter', UploadedFile::fake()->create('organizer-letter.pdf', 128, 'application/pdf'))
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasErrors([
                'event_start' => ['after'],
                'event_end' => ['after'],
            ]);
    }

    public function test_validation_requires_complete_location_and_valid_map_url(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Community', 'slug' => 'community']);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class)
            ->set('event', 'Invalid Location Event')
            ->set('fee', 5)
            ->set('start_sale', '2026-09-01 10:00')
            ->set('event_start', '2026-09-02 19:00')
            ->set('event_end', '2026-09-02 21:00')
            ->set('venue_name', '')
            ->set('venue_address', '')
            ->set('venue_city', '')
            ->set('venue_province', '')
            ->set('map', 'not-a-valid-url')
            ->set('cover', UploadedFile::fake()->image('cover.jpg'))
            ->set('deskripsi', 'Deskripsi event invalid location')
            ->set('organizer_name', 'Organizer Uji')
            ->set('responsible_name', 'PJ Uji')
            ->set('responsible_position', 'Koordinator')
            ->set('phone', '081234567890')
            ->set('email', 'uji@example.test')
            ->set('address', 'Alamat organizer uji')
            ->set('bank_name', 'Bank Uji')
            ->set('account_number', '123456789')
            ->set('account_holder_name', 'Organizer Uji')
            ->set('bank_book', UploadedFile::fake()->create('bank-book.pdf', 128, 'application/pdf'))
            ->set('document_number', 'COM-001')
            ->set('document_date', '2026-08-20')
            ->set('organizer_letter', UploadedFile::fake()->create('organizer-letter.pdf', 128, 'application/pdf'))
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasErrors([
                'venue_name' => ['required'],
                'venue_address' => ['required'],
                'venue_city' => ['required'],
                'venue_province' => ['required'],
                'map' => ['url'],
            ]);
    }

    public function test_update_organizer_only_affects_selected_event(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Festival', 'slug' => 'festival']);
        $eventA = $this->event($tenant, ['event' => 'Event A', 'category_id' => $category->id]);
        $eventB = $this->event($tenant, ['event' => 'Event B', 'category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $eventA->uid,
            'organizer_name' => 'Organizer A',
            'responsible_name' => 'PJ A',
            'responsible_position' => 'Manager A',
            'phone' => '081000000001',
            'email' => 'a@example.test',
            'address' => 'Alamat A',
        ]);
        EventBankAccount::create([
            'event_uid' => $eventA->uid,
            'bank_name' => 'Bank A',
            'account_number' => '111111',
            'account_holder_name' => 'Pemilik A',
            'bank_book_path' => 'private/events/'.$eventA->uid.'/bank/a.pdf',
            'bank_book_original_name' => 'a.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$eventA->uid.'/bank/a.pdf', 'a');
        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $eventA->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-A',
            'document_date' => '2026-08-01',
            'original_name' => 'a-letter.pdf',
            'file_path' => 'private/events/'.$eventA->uid.'/documents/a-letter.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$eventA->uid.'/documents/a-letter.pdf', 'doc-a');

        EventOrganizer::create([
            'event_uid' => $eventB->uid,
            'organizer_name' => 'Organizer B',
            'responsible_name' => 'PJ B',
            'responsible_position' => 'Manager B',
            'phone' => '081000000002',
            'email' => 'b@example.test',
            'address' => 'Alamat B',
        ]);
        EventBankAccount::create([
            'event_uid' => $eventB->uid,
            'bank_name' => 'Bank B',
            'account_number' => '222222',
            'account_holder_name' => 'Pemilik B',
            'bank_book_path' => 'private/events/'.$eventB->uid.'/bank/b.pdf',
            'bank_book_original_name' => 'b.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$eventB->uid.'/bank/b.pdf', 'b');
        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $eventB->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-B',
            'document_date' => '2026-08-02',
            'original_name' => 'b-letter.pdf',
            'file_path' => 'private/events/'.$eventB->uid.'/documents/b-letter.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$eventB->uid.'/documents/b-letter.pdf', 'doc-b');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $eventA->uid])
            ->set('organizer_name', 'Organizer A Revisi')
            ->set('responsible_name', 'PJ A Revisi')
            ->set('responsible_position', 'Director A')
            ->set('phone', '081999999999')
            ->set('email', 'a-revisi@example.test')
            ->set('address', 'Alamat A Revisi')
            ->set('bank_name', 'Bank A Revisi')
            ->set('account_number', '333333')
            ->set('account_holder_name', 'Pemilik A Revisi')
            ->set('document_number', 'DOC-A-REVISI')
            ->set('document_date', '2026-08-15')
            ->call('save');

        $this->assertDatabaseHas('event_organizers', [
            'event_uid' => $eventA->uid,
            'organizer_name' => 'Organizer A Revisi',
            'responsible_name' => 'PJ A Revisi',
            'responsible_position' => 'Director A',
            'phone' => '081999999999',
            'email' => 'a-revisi@example.test',
            'address' => 'Alamat A Revisi',
        ]);

        $this->assertDatabaseHas('event_organizers', [
            'event_uid' => $eventB->uid,
            'organizer_name' => 'Organizer B',
            'responsible_name' => 'PJ B',
            'responsible_position' => 'Manager B',
            'phone' => '081000000002',
            'email' => 'b@example.test',
            'address' => 'Alamat B',
        ]);

        $this->assertDatabaseHas('event_bank_accounts', [
            'event_uid' => $eventA->uid,
            'bank_name' => 'Bank A Revisi',
            'account_number' => '333333',
            'account_holder_name' => 'Pemilik A Revisi',
        ]);

        $this->assertDatabaseHas('event_bank_accounts', [
            'event_uid' => $eventB->uid,
            'bank_name' => 'Bank B',
            'account_number' => '222222',
            'account_holder_name' => 'Pemilik B',
        ]);

        $this->assertDatabaseHas('event_documents', [
            'event_uid' => $eventA->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-A-REVISI',
        ]);

        $this->assertDatabaseHas('event_documents', [
            'event_uid' => $eventB->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-B',
        ]);
    }

    public function test_legacy_event_mount_does_not_invent_new_fields_and_requires_explicit_completion_before_save(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Legacy', 'slug' => 'legacy']);
        $event = $this->event($tenant, [
            'category_id' => $category->id,
            'alamat' => 'Alamat Venue Lama',
            'tanggal' => '2026-09-10 19:00:00',
            'event_end' => null,
            'venue_name' => null,
            'venue_address' => null,
            'venue_city' => null,
            'venue_province' => null,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->assertSet('event_start', '2026-09-10 19:00')
            ->assertSet('event_end', null)
            ->assertSet('venue_address', 'Alamat Venue Lama')
            ->assertSet('venue_name', null)
            ->assertSet('venue_city', null)
            ->assertSet('venue_province', null)
            ->call('save')
            ->assertHasErrors([
                'event_end' => ['required'],
                'organizer_name' => ['required'],
                'responsible_name' => ['required'],
                'responsible_position' => ['required'],
                'phone' => ['required'],
                'email' => ['required'],
                'address' => ['required'],
                'bank_name' => ['required'],
                'account_number' => ['required'],
                'account_holder_name' => ['required'],
                'bank_book' => ['required'],
                'document_number' => ['required'],
                'document_date' => ['required'],
                'organizer_letter' => ['required'],
                'venue_name' => ['required'],
                'venue_city' => ['required'],
                'venue_province' => ['required'],
            ]);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('event_end', '2026-09-10 22:00')
            ->set('venue_name', 'Venue Legacy Baru')
            ->set('venue_city', 'Bandung')
            ->set('venue_province', 'Jawa Barat')
            ->set('organizer_name', 'Organizer Legacy')
            ->set('responsible_name', 'PJ Legacy')
            ->set('responsible_position', 'Supervisor')
            ->set('phone', '081111111111')
            ->set('email', 'legacy@example.test')
            ->set('address', 'Alamat organizer legacy')
            ->set('bank_name', 'Bank Legacy')
            ->set('account_number', '456789123')
            ->set('account_holder_name', 'Organizer Legacy')
            ->set('bank_book', UploadedFile::fake()->create('legacy-book.pdf', 128, 'application/pdf'))
            ->set('document_number', 'LEG-001')
            ->set('document_date', '2026-08-21')
            ->set('organizer_letter', UploadedFile::fake()->create('legacy-letter.pdf', 128, 'application/pdf'))
            ->call('save');

        $event->refresh();

        $this->assertSame('2026-09-10 22:00:00', substr((string) $event->event_end, 0, 19));
        $this->assertSame('Venue Legacy Baru', $event->venue_name);
        $this->assertSame('Alamat Venue Lama', $event->venue_address);
        $this->assertSame('Bandung', $event->venue_city);
        $this->assertSame('Jawa Barat', $event->venue_province);
        $this->assertSame('Venue Legacy Baru, Alamat Venue Lama, Bandung, Jawa Barat', $event->alamat);
        $this->assertDatabaseHas('event_organizers', [
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Legacy',
            'responsible_name' => 'PJ Legacy',
            'responsible_position' => 'Supervisor',
            'phone' => '081111111111',
            'email' => 'legacy@example.test',
            'address' => 'Alamat organizer legacy',
        ]);
        $this->assertDatabaseHas('event_bank_accounts', [
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Legacy',
            'account_number' => '456789123',
            'account_holder_name' => 'Organizer Legacy',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('event_documents', [
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'LEG-001',
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('agreements', 0);
    }

    public function test_replace_bank_book_updates_database_and_deletes_old_file_after_success(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Replacement', 'slug' => 'replacement']);
        $event = $this->event($tenant, ['category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Replace',
            'responsible_name' => 'PJ Replace',
            'responsible_position' => 'Manager',
            'phone' => '081234567800',
            'email' => 'replace@example.test',
            'address' => 'Alamat replace',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Replace',
            'account_number' => '111222333',
            'account_holder_name' => 'Pemilik Replace',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/old-book.pdf',
            'bank_book_original_name' => 'old-book.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/old-book.pdf', 'old');
        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-OLD',
            'document_date' => '2026-08-10',
            'original_name' => 'old-organizer-letter.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/old-organizer-letter.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/old-organizer-letter.pdf', 'old-doc');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('bank_book', UploadedFile::fake()->create('new-book.pdf', 256, 'application/pdf'))
            ->call('save');

        $event->refresh();
        $bankAccount = $event->bankAccount()->firstOrFail();

        $this->assertNotSame('private/events/'.$event->uid.'/bank/old-book.pdf', $bankAccount->bank_book_path);
        $this->assertSame('new-book.pdf', $bankAccount->bank_book_original_name);
        Storage::disk('local')->assertMissing('private/events/'.$event->uid.'/bank/old-book.pdf');
        Storage::disk('local')->assertExists($bankAccount->bank_book_path);
    }

    public function test_replace_organizer_letter_updates_database_and_deletes_old_file_after_success(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Document Replacement', 'slug' => 'document-replacement']);
        $event = $this->event($tenant, ['category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Doc Replace',
            'responsible_name' => 'PJ Doc Replace',
            'responsible_position' => 'Manager',
            'phone' => '081234567801',
            'email' => 'doc-replace@example.test',
            'address' => 'Alamat doc replace',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Doc Replace',
            'account_number' => '121212',
            'account_holder_name' => 'Pemilik Doc Replace',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/doc-replace-bank.pdf',
            'bank_book_original_name' => 'doc-replace-bank.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/doc-replace-bank.pdf', 'bank');

        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-REPLACE',
            'document_date' => '2026-08-11',
            'original_name' => 'old-doc.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/old-doc.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/old-doc.pdf', 'old-doc');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('organizer_letter', UploadedFile::fake()->create('new-doc.pdf', 256, 'application/pdf'))
            ->call('save');

        $event->refresh();
        $organizerLetter = $event->organizerLetter()->firstOrFail();

        $this->assertNotSame('private/events/'.$event->uid.'/documents/old-doc.pdf', $organizerLetter->file_path);
        $this->assertSame('new-doc.pdf', $organizerLetter->original_name);
        Storage::disk('local')->assertMissing('private/events/'.$event->uid.'/documents/old-doc.pdf');
        Storage::disk('local')->assertExists($organizerLetter->file_path);
    }

    public function test_tampering_bank_book_display_properties_cannot_override_authoritative_database_path(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Tamper', 'slug' => 'tamper']);
        $event = $this->event($tenant, ['category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Tamper',
            'responsible_name' => 'PJ Tamper',
            'responsible_position' => 'Manager',
            'phone' => '081234567811',
            'email' => 'tamper@example.test',
            'address' => 'Alamat tamper',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Aman',
            'account_number' => '777888999',
            'account_holder_name' => 'Pemilik Aman',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/real-book.pdf',
            'bank_book_original_name' => 'real-book.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-20 10:00:00',
            'verified_by' => 'admin-verified',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/real-book.pdf', 'real');
        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-SECURE',
            'document_date' => '2026-08-12',
            'original_name' => 'real-doc.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/real-doc.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-20 10:00:00',
            'verified_by' => 'admin-doc',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/real-doc.pdf', 'real-doc');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('existingBankBookPath', 'private/events/hacked/bank/evil.pdf')
            ->set('existingBankBookOriginalName', 'evil.pdf')
            ->call('save');

        $bankAccount = $event->fresh()->bankAccount()->firstOrFail();

        $this->assertSame('private/events/'.$event->uid.'/bank/real-book.pdf', $bankAccount->bank_book_path);
        $this->assertSame('real-book.pdf', $bankAccount->bank_book_original_name);
        $this->assertSame('verified', $bankAccount->status);
        $this->assertSame('2026-08-20 10:00:00', $bankAccount->verified_at?->format('Y-m-d H:i:s'));
        $this->assertSame('admin-verified', $bankAccount->verified_by);
    }

    public function test_tampering_organizer_letter_display_properties_cannot_override_authoritative_database_path(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Document Tamper', 'slug' => 'document-tamper']);
        $event = $this->event($tenant, ['category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Document Tamper',
            'responsible_name' => 'PJ Document Tamper',
            'responsible_position' => 'Manager',
            'phone' => '081234567812',
            'email' => 'document-tamper@example.test',
            'address' => 'Alamat document tamper',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Doc Tamper',
            'account_number' => '555666777',
            'account_holder_name' => 'Pemilik Doc Tamper',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/doc-tamper-bank.pdf',
            'bank_book_original_name' => 'doc-tamper-bank.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/doc-tamper-bank.pdf', 'bank');

        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-TAMPER',
            'document_date' => '2026-08-13',
            'original_name' => 'real-organizer-doc.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/real-organizer-doc.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-20 11:00:00',
            'verified_by' => 'admin-doc',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/real-organizer-doc.pdf', 'real-organizer-doc');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('existingOrganizerLetterPath', 'private/events/hacked/documents/evil.pdf')
            ->set('existingOrganizerLetterOriginalName', 'evil.pdf')
            ->call('save');

        $organizerLetter = $event->fresh()->organizerLetter()->firstOrFail();

        $this->assertSame('private/events/'.$event->uid.'/documents/real-organizer-doc.pdf', $organizerLetter->file_path);
        $this->assertSame('real-organizer-doc.pdf', $organizerLetter->original_name);
        $this->assertSame('verified', $organizerLetter->status);
        $this->assertSame('2026-08-20 11:00:00', $organizerLetter->verified_at?->format('Y-m-d H:i:s'));
        $this->assertSame('admin-doc', $organizerLetter->verified_by);
    }

    public function test_tampering_organizer_letter_display_properties_cannot_bypass_required_upload_when_database_has_no_file(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Document Legacy Tamper', 'slug' => 'document-legacy-tamper']);
        $event = $this->event($tenant, ['category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Legacy Document',
            'responsible_name' => 'PJ Legacy Document',
            'responsible_position' => 'Manager',
            'phone' => '081234567823',
            'email' => 'legacy-doc@example.test',
            'address' => 'Alamat legacy doc',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Legacy Doc',
            'account_number' => '123123999',
            'account_holder_name' => 'Pemilik Legacy Doc',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/legacy-doc-bank.pdf',
            'bank_book_original_name' => 'legacy-doc-bank.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/legacy-doc-bank.pdf', 'bank');

        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-EMPTY',
            'document_date' => '2026-08-14',
            'original_name' => '',
            'file_path' => '',
            'mime_type' => '',
            'status' => 'pending',
        ]);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('existingOrganizerLetterPath', 'private/events/fake/documents/fake.pdf')
            ->set('existingOrganizerLetterOriginalName', 'fake.pdf')
            ->call('save')
            ->assertHasErrors([
                'organizer_letter' => ['required'],
            ]);

        $organizerLetter = $event->fresh()->organizerLetter()->firstOrFail();

        $this->assertSame('', $organizerLetter->file_path);
        $this->assertSame('', $organizerLetter->original_name);
    }

    public function test_missing_existing_organizer_letter_file_requires_reupload_and_resets_verification_state(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Missing Organizer Letter', 'slug' => 'missing-organizer-letter']);
        $event = $this->event($tenant, ['category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Missing File',
            'responsible_name' => 'PJ Missing File',
            'responsible_position' => 'Manager',
            'phone' => '081234567824',
            'email' => 'missing-file@example.test',
            'address' => 'Alamat missing file',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Missing File',
            'account_number' => '456456456',
            'account_holder_name' => 'Pemilik Missing File',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/missing-file-bank.pdf',
            'bank_book_original_name' => 'missing-file-bank.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/missing-file-bank.pdf', 'bank');

        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-MISSING',
            'document_date' => '2026-08-19',
            'original_name' => 'missing-file-doc.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/missing-file-doc.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-22 12:00:00',
            'verified_by' => 'admin-doc',
            'rejection_reason' => 'old reason',
        ]);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('existingOrganizerLetterPath', 'private/events/hacked/documents/evil.pdf')
            ->set('existingOrganizerLetterOriginalName', 'evil.pdf')
            ->call('save')
            ->assertHasErrors([
                'organizer_letter' => ['required'],
            ]);

        $organizerLetter = $event->fresh()->organizerLetter()->firstOrFail();

        $this->assertSame('private/events/'.$event->uid.'/documents/missing-file-doc.pdf', $organizerLetter->file_path);
        $this->assertSame('missing-file-doc.pdf', $organizerLetter->original_name);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('existingOrganizerLetterPath', 'private/events/hacked/documents/evil.pdf')
            ->set('existingOrganizerLetterOriginalName', 'evil.pdf')
            ->set('organizer_letter', UploadedFile::fake()->create('replacement-organizer-letter.pdf', 128, 'application/pdf'))
            ->call('save');

        $organizerLetter = $event->fresh()->organizerLetter()->firstOrFail();

        $this->assertNotSame('private/events/'.$event->uid.'/documents/missing-file-doc.pdf', $organizerLetter->file_path);
        $this->assertSame('replacement-organizer-letter.pdf', $organizerLetter->original_name);
        $this->assertSame('pending', $organizerLetter->status);
        $this->assertNull($organizerLetter->verified_at);
        $this->assertNull($organizerLetter->verified_by);
        $this->assertNull($organizerLetter->rejection_reason);
        Storage::disk('local')->assertExists($organizerLetter->file_path);
    }

    public function test_tampering_bank_book_display_properties_cannot_bypass_required_upload_when_database_has_no_bank_book(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Tamper Legacy', 'slug' => 'tamper-legacy']);
        $event = $this->event($tenant, ['category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Legacy Bank',
            'responsible_name' => 'PJ Legacy Bank',
            'responsible_position' => 'Manager',
            'phone' => '081234567822',
            'email' => 'legacy-bank@example.test',
            'address' => 'Alamat legacy bank',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Legacy',
            'account_number' => '123123123',
            'account_holder_name' => 'Pemilik Legacy',
            'bank_book_path' => '',
            'bank_book_original_name' => null,
            'bank_book_mime' => null,
            'status' => 'pending',
        ]);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('existingBankBookPath', 'private/events/fake/bank/fake.pdf')
            ->set('existingBankBookOriginalName', 'fake.pdf')
            ->call('save')
            ->assertHasErrors([
                'bank_book' => ['required'],
            ]);

        $bankAccount = $event->fresh()->bankAccount()->firstOrFail();

        $this->assertSame('', $bankAccount->bank_book_path);
        $this->assertNull($bankAccount->bank_book_original_name);
    }

    public function test_updating_account_number_resets_bank_account_verification_state(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Verification Reset', 'slug' => 'verification-reset']);
        $event = $this->event($tenant, ['category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Verified',
            'responsible_name' => 'PJ Verified',
            'responsible_position' => 'Manager',
            'phone' => '081234567833',
            'email' => 'verified@example.test',
            'address' => 'Alamat verified',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Verified',
            'account_number' => '111222333',
            'account_holder_name' => 'Pemilik Verified',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/verified.pdf',
            'bank_book_original_name' => 'verified.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-20 10:00:00',
            'verified_by' => 'admin-verified',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/verified.pdf', 'verified');
        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-BANK-VERIFY',
            'document_date' => '2026-08-18',
            'original_name' => 'bank-verify-doc.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/bank-verify-doc.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-20 10:30:00',
            'verified_by' => 'admin-doc',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/bank-verify-doc.pdf', 'doc');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('account_number', '999888777')
            ->call('save');

        $bankAccount = $event->fresh()->bankAccount()->firstOrFail();

        $this->assertSame('999888777', $bankAccount->account_number);
        $this->assertSame('pending', $bankAccount->status);
        $this->assertNull($bankAccount->verified_at);
        $this->assertNull($bankAccount->verified_by);
        $this->assertNull($bankAccount->rejection_reason);
        $this->assertSame('private/events/'.$event->uid.'/bank/verified.pdf', $bankAccount->bank_book_path);
    }

    public function test_replacing_bank_book_resets_bank_account_verification_state(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Verification Replace', 'slug' => 'verification-replace']);
        $event = $this->event($tenant, ['category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Replace Verified',
            'responsible_name' => 'PJ Replace Verified',
            'responsible_position' => 'Manager',
            'phone' => '081234567844',
            'email' => 'replace-verified@example.test',
            'address' => 'Alamat replace verified',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Replace Verified',
            'account_number' => '222333444',
            'account_holder_name' => 'Pemilik Replace Verified',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/replace-old.pdf',
            'bank_book_original_name' => 'replace-old.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-21 09:00:00',
            'verified_by' => 'admin-verified',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/replace-old.pdf', 'old');
        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-BANK-REPLACE',
            'document_date' => '2026-08-18',
            'original_name' => 'bank-replace-doc.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/bank-replace-doc.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-20 10:45:00',
            'verified_by' => 'admin-doc',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/bank-replace-doc.pdf', 'doc');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('bank_book', UploadedFile::fake()->create('replace-new.pdf', 128, 'application/pdf'))
            ->call('save');

        $bankAccount = $event->fresh()->bankAccount()->firstOrFail();

        $this->assertSame('pending', $bankAccount->status);
        $this->assertNull($bankAccount->verified_at);
        $this->assertNull($bankAccount->verified_by);
        $this->assertNull($bankAccount->rejection_reason);
        $this->assertSame('replace-new.pdf', $bankAccount->bank_book_original_name);
        Storage::disk('local')->assertMissing('private/events/'.$event->uid.'/bank/replace-old.pdf');
        Storage::disk('local')->assertExists($bankAccount->bank_book_path);
    }

    public function test_saving_without_bank_account_changes_keeps_existing_verification_state(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Verification Keep', 'slug' => 'verification-keep']);
        $event = $this->event($tenant, ['category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Keep',
            'responsible_name' => 'PJ Keep',
            'responsible_position' => 'Manager',
            'phone' => '081234567855',
            'email' => 'keep@example.test',
            'address' => 'Alamat keep',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Keep',
            'account_number' => '333444555',
            'account_holder_name' => 'Pemilik Keep',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/keep.pdf',
            'bank_book_original_name' => 'keep.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-22 08:00:00',
            'verified_by' => 'admin-keep',
            'rejection_reason' => 'Periksa nomor rekening',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/keep.pdf', 'keep');
        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-BANK-KEEP',
            'document_date' => '2026-08-18',
            'original_name' => 'bank-keep-doc.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/bank-keep-doc.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-20 11:00:00',
            'verified_by' => 'admin-doc',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/bank-keep-doc.pdf', 'doc');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->call('save');

        $bankAccount = $event->fresh()->bankAccount()->firstOrFail();

        $this->assertSame('verified', $bankAccount->status);
        $this->assertSame('2026-08-22 08:00:00', $bankAccount->verified_at?->format('Y-m-d H:i:s'));
        $this->assertSame('admin-keep', $bankAccount->verified_by);
        $this->assertSame('Periksa nomor rekening', $bankAccount->rejection_reason);
        $this->assertSame('private/events/'.$event->uid.'/bank/keep.pdf', $bankAccount->bank_book_path);
    }

    public function test_updating_document_number_resets_organizer_letter_verification_state(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Document Verification Reset', 'slug' => 'document-verification-reset']);
        $event = $this->event($tenant, ['category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Doc Verified',
            'responsible_name' => 'PJ Doc Verified',
            'responsible_position' => 'Manager',
            'phone' => '081234567866',
            'email' => 'doc-verified@example.test',
            'address' => 'Alamat doc verified',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Doc Verified',
            'account_number' => '444555666',
            'account_holder_name' => 'Pemilik Doc Verified',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/doc-verified-bank.pdf',
            'bank_book_original_name' => 'doc-verified-bank.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/doc-verified-bank.pdf', 'bank');

        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-VERIFY-1',
            'document_date' => '2026-08-15',
            'original_name' => 'doc-verified.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/doc-verified.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-22 08:00:00',
            'verified_by' => 'admin-doc',
            'rejection_reason' => 'old',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/doc-verified.pdf', 'doc');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('document_number', 'DOC-VERIFY-2')
            ->call('save');

        $organizerLetter = $event->fresh()->organizerLetter()->firstOrFail();

        $this->assertSame('DOC-VERIFY-2', $organizerLetter->document_number);
        $this->assertSame('pending', $organizerLetter->status);
        $this->assertNull($organizerLetter->verified_at);
        $this->assertNull($organizerLetter->verified_by);
        $this->assertNull($organizerLetter->rejection_reason);
    }

    public function test_replacing_organizer_letter_resets_verification_state(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Document Verification Replace', 'slug' => 'document-verification-replace']);
        $event = $this->event($tenant, ['category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Doc Replace Verify',
            'responsible_name' => 'PJ Doc Replace Verify',
            'responsible_position' => 'Manager',
            'phone' => '081234567877',
            'email' => 'doc-replace-verify@example.test',
            'address' => 'Alamat doc replace verify',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Doc Replace Verify',
            'account_number' => '777111222',
            'account_holder_name' => 'Pemilik Doc Replace Verify',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/doc-replace-verify-bank.pdf',
            'bank_book_original_name' => 'doc-replace-verify-bank.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/doc-replace-verify-bank.pdf', 'bank');

        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-REPLACE-VERIFY',
            'document_date' => '2026-08-16',
            'original_name' => 'doc-old-verify.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/doc-old-verify.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-22 09:00:00',
            'verified_by' => 'admin-doc',
            'rejection_reason' => 'old',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/doc-old-verify.pdf', 'doc-old');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('organizer_letter', UploadedFile::fake()->create('doc-new-verify.pdf', 128, 'application/pdf'))
            ->call('save');

        $organizerLetter = $event->fresh()->organizerLetter()->firstOrFail();

        $this->assertSame('pending', $organizerLetter->status);
        $this->assertNull($organizerLetter->verified_at);
        $this->assertNull($organizerLetter->verified_by);
        $this->assertNull($organizerLetter->rejection_reason);
        $this->assertSame('doc-new-verify.pdf', $organizerLetter->original_name);
        Storage::disk('local')->assertMissing('private/events/'.$event->uid.'/documents/doc-old-verify.pdf');
        Storage::disk('local')->assertExists($organizerLetter->file_path);
    }

    public function test_saving_without_document_changes_keeps_existing_document_verification_state(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Document Verification Keep', 'slug' => 'document-verification-keep']);
        $event = $this->event($tenant, ['category_id' => $category->id]);

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'Organizer Doc Keep',
            'responsible_name' => 'PJ Doc Keep',
            'responsible_position' => 'Manager',
            'phone' => '081234567888',
            'email' => 'doc-keep@example.test',
            'address' => 'Alamat doc keep',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Doc Keep',
            'account_number' => '999111333',
            'account_holder_name' => 'Pemilik Doc Keep',
            'bank_book_path' => 'private/events/'.$event->uid.'/bank/doc-keep-bank.pdf',
            'bank_book_original_name' => 'doc-keep-bank.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/doc-keep-bank.pdf', 'bank');

        EventDocument::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-KEEP',
            'document_date' => '2026-08-17',
            'original_name' => 'doc-keep.pdf',
            'file_path' => 'private/events/'.$event->uid.'/documents/doc-keep.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_at' => '2026-08-22 10:00:00',
            'verified_by' => 'admin-doc',
            'rejection_reason' => 'keep',
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/doc-keep.pdf', 'doc');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->call('save');

        $organizerLetter = $event->fresh()->organizerLetter()->firstOrFail();

        $this->assertSame('verified', $organizerLetter->status);
        $this->assertSame('2026-08-22 10:00:00', $organizerLetter->verified_at?->format('Y-m-d H:i:s'));
        $this->assertSame('admin-doc', $organizerLetter->verified_by);
        $this->assertSame('keep', $organizerLetter->rejection_reason);
        $this->assertSame('private/events/'.$event->uid.'/documents/doc-keep.pdf', $organizerLetter->file_path);
    }

    public function test_validation_rejects_invalid_bank_book_and_missing_bank_fields(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Validation', 'slug' => 'validation']);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class)
            ->set('event', 'Invalid Bank Event')
            ->set('fee', 10)
            ->set('start_sale', '2026-09-01 10:00')
            ->set('event_start', '2026-09-10 19:00')
            ->set('event_end', '2026-09-10 22:00')
            ->set('venue_name', 'Venue Validation')
            ->set('venue_address', 'Alamat Validation')
            ->set('venue_city', 'Jakarta')
            ->set('venue_province', 'DKI Jakarta')
            ->set('map', 'https://maps.google.com/?q=validation')
            ->set('cover', UploadedFile::fake()->image('cover.jpg'))
            ->set('deskripsi', 'Deskripsi validation')
            ->set('organizer_name', 'Organizer Validation')
            ->set('responsible_name', 'PJ Validation')
            ->set('responsible_position', 'Manager Validation')
            ->set('phone', '081234567890')
            ->set('email', 'validation@example.test')
            ->set('address', 'Alamat organizer validation')
            ->set('bank_name', '')
            ->set('account_number', '')
            ->set('account_holder_name', '')
            ->set('bank_book', UploadedFile::fake()->create('bank-book.svg', 10, 'image/svg+xml'))
            ->set('document_number', '')
            ->set('document_date', '')
            ->set('organizer_letter', UploadedFile::fake()->create('organizer-letter.svg', 10, 'image/svg+xml'))
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasErrors([
                'bank_name' => ['required'],
                'account_number' => ['required'],
                'account_holder_name' => ['required'],
                'bank_book' => ['mimes'],
                'document_number' => ['required'],
                'document_date' => ['required'],
                'organizer_letter' => ['mimes'],
            ]);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class)
            ->set('event', 'Large Bank Event')
            ->set('fee', 10)
            ->set('start_sale', '2026-09-01 10:00')
            ->set('event_start', '2026-09-10 19:00')
            ->set('event_end', '2026-09-10 22:00')
            ->set('venue_name', 'Venue Validation')
            ->set('venue_address', 'Alamat Validation')
            ->set('venue_city', 'Jakarta')
            ->set('venue_province', 'DKI Jakarta')
            ->set('map', 'https://maps.google.com/?q=validation')
            ->set('cover', UploadedFile::fake()->image('cover.jpg'))
            ->set('deskripsi', 'Deskripsi validation')
            ->set('organizer_name', 'Organizer Validation')
            ->set('responsible_name', 'PJ Validation')
            ->set('responsible_position', 'Manager Validation')
            ->set('phone', '081234567890')
            ->set('email', 'validation@example.test')
            ->set('address', 'Alamat organizer validation')
            ->set('bank_name', 'Bank Validation')
            ->set('account_number', '123456789')
            ->set('account_holder_name', 'Organizer Validation')
            ->set('bank_book', UploadedFile::fake()->create('large-book.pdf', 6000, 'application/pdf'))
            ->set('document_number', 'VAL-001')
            ->set('document_date', '2026-08-20')
            ->set('organizer_letter', UploadedFile::fake()->create('large-organizer-letter.pdf', 6000, 'application/pdf'))
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasErrors([
                'bank_book' => ['max'],
                'organizer_letter' => ['max'],
            ]);
    }

    public function test_create_event_organizer_bank_account_and_document_are_saved_atomically(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Atomic', 'slug' => 'atomic']);
        $initialLocalFiles = Storage::disk('local')->allFiles('private/events');
        $initialCoverFiles = Storage::disk('public')->allFiles('cover');

        EventDocument::creating(function () {
            throw new \RuntimeException('forced organizer letter failure');
        });

        try {
            Livewire::actingAs($tenant)
                ->test(EventCreate::class)
                ->set('event', 'Atomic Event')
                ->set('fee', 10)
                ->set('start_sale', '2026-09-01 10:00')
                ->set('event_start', '2026-09-10 19:00')
                ->set('event_end', '2026-09-10 22:00')
                ->set('venue_name', 'Venue Atomic')
                ->set('venue_address', 'Alamat Venue Atomic')
                ->set('venue_city', 'Jakarta')
                ->set('venue_province', 'DKI Jakarta')
                ->set('map', 'https://maps.google.com/?q=atomic')
                ->set('cover', UploadedFile::fake()->image('cover.jpg'))
                ->set('deskripsi', 'Deskripsi atomic')
                ->set('category_id', $category->id)
                ->set('organizer_name', 'Organizer Atomic')
                ->set('responsible_name', 'PJ Atomic')
                ->set('responsible_position', 'Manager Atomic')
                ->set('phone', '081234567890')
                ->set('email', 'atomic@example.test')
                ->set('address', 'Alamat organizer atomic')
                ->set('bank_name', 'Bank Atomic')
                ->set('account_number', '123123123')
                ->set('account_holder_name', 'Organizer Atomic')
                ->set('bank_book', UploadedFile::fake()->create('atomic-book.pdf', 128, 'application/pdf'))
                ->set('document_number', 'AT-001')
                ->set('document_date', '2026-08-20')
                ->set('organizer_letter', UploadedFile::fake()->create('atomic-organizer-letter.pdf', 128, 'application/pdf'))
                ->set('responsible_identity', UploadedFile::fake()->create('responsible-identity.pdf', 128, 'application/pdf'))
                ->call('save');

            $this->fail('Expected organizer letter creation to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('forced organizer letter failure', $exception->getMessage());
        } finally {
            EventDocument::flushEventListeners();
        }

        $this->assertDatabaseMissing('events', [
            'event' => 'Atomic Event',
        ]);

        $this->assertDatabaseCount('event_organizers', 0);
        $this->assertDatabaseCount('event_bank_accounts', 0);
        $this->assertDatabaseCount('event_documents', 0);
        $this->assertSame($initialLocalFiles, Storage::disk('local')->allFiles('private/events'));
        $this->assertSame($initialCoverFiles, Storage::disk('public')->allFiles('cover'));
    }

    public function test_create_event_rolls_back_everything_when_agreement_creation_fails(): void
    {
        $tenant = $this->tenant(['email' => 'atomic-agreement@example.test']);
        $category = Category::create(['name' => 'Atomic Agreement', 'slug' => 'atomic-agreement']);
        $initialLocalFiles = Storage::disk('local')->allFiles('private/events');
        $initialCoverFiles = Storage::disk('public')->allFiles('cover');

        Agreement::creating(function () {
            throw new \RuntimeException('forced agreement failure');
        });

        try {
            Livewire::actingAs($tenant)
                ->test(EventCreate::class)
                ->set('event', 'Atomic Agreement Event')
                ->set('fee', 10)
                ->set('start_sale', '2026-09-01 10:00')
                ->set('event_start', '2026-09-10 19:00')
                ->set('event_end', '2026-09-10 22:00')
                ->set('venue_name', 'Venue Agreement Atomic')
                ->set('venue_address', 'Alamat Venue Agreement Atomic')
                ->set('venue_city', 'Jakarta')
                ->set('venue_province', 'DKI Jakarta')
                ->set('map', 'https://maps.google.com/?q=agreement-atomic')
                ->set('cover', UploadedFile::fake()->image('agreement-atomic-cover.jpg'))
                ->set('deskripsi', 'Deskripsi atomic agreement')
                ->set('category_id', $category->id)
                ->set('organizer_name', 'Organizer Agreement Atomic')
                ->set('responsible_name', 'PJ Agreement Atomic')
                ->set('responsible_position', 'Manager Agreement Atomic')
                ->set('phone', '081234567890')
                ->set('email', 'agreement-atomic@example.test')
                ->set('address', 'Alamat organizer agreement atomic')
                ->set('bank_name', 'Bank Agreement Atomic')
                ->set('account_number', '999888777')
                ->set('account_holder_name', 'Organizer Agreement Atomic')
                ->set('bank_book', UploadedFile::fake()->create('agreement-atomic-book.pdf', 128, 'application/pdf'))
                ->set('document_number', 'AT-AGR-001')
                ->set('document_date', '2026-08-20')
                ->set('organizer_letter', UploadedFile::fake()->create('agreement-atomic-letter.pdf', 128, 'application/pdf'))
                ->set('responsible_identity', UploadedFile::fake()->create('responsible-identity.pdf', 128, 'application/pdf'))
                ->call('save');

            $this->fail('Expected agreement creation to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('forced agreement failure', $exception->getMessage());
        } finally {
            Agreement::flushEventListeners();
            Agreement::clearBootedModels();
        }

        $this->assertDatabaseMissing('events', [
            'event' => 'Atomic Agreement Event',
        ]);
        $this->assertDatabaseCount('event_organizers', 0);
        $this->assertDatabaseCount('event_bank_accounts', 0);
        $this->assertDatabaseCount('event_documents', 0);
        $this->assertDatabaseCount('agreements', 0);
        $this->assertSame($initialLocalFiles, Storage::disk('local')->allFiles('private/events'));
        $this->assertSame($initialCoverFiles, Storage::disk('public')->allFiles('cover'));
    }

    private function tenant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Tenant Event',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat Tenant',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Generic Event User',
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

    private function event(User $tenant, array $overrides = []): Event
    {
        $uid = (string) Str::uuid();

        return Event::create(array_merge([
            'uid' => $uid,
            'user_uid' => $tenant->uid,
            'event' => 'Festival Lama '.$uid,
            'alamat' => 'Istora Senayan, Jl. Pintu Satu Senayan, Jakarta Pusat, DKI Jakarta',
            'tanggal' => '2026-09-10 19:00:00',
            'event_end' => '2026-09-10 22:00:00',
            'venue_name' => 'Istora Senayan',
            'venue_address' => 'Jl. Pintu Satu Senayan',
            'venue_city' => 'Jakarta Pusat',
            'venue_province' => 'DKI Jakarta',
            'status' => 'inactive',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'pajak' => 0,
            'deskripsi' => 'Deskripsi event lama',
            'map' => 'https://maps.google.com/?q=istora',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'festival-lama-'.$uid,
            'konfirmasi' => null,
        ], $overrides));
    }
}
