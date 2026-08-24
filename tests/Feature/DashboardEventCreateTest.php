<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventCreate;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventBankAccount;
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
        Storage::persistentFake('local');
        Storage::persistentFake('public');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        $this->artisan('migrate:fresh', ['--database' => 'sqlite']);
    }

    public function test_create_event_saves_new_schedule_and_location_fields_with_legacy_compatibility(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Music', 'slug' => 'music']);
        $bankBook = UploadedFile::fake()->create('bank-book.pdf', 256, 'application/pdf');

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
    }

    public function test_edit_event_updates_new_fields_and_keeps_existing_fee_column(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Talk Show', 'slug' => 'talk-show']);
        $event = $this->event($tenant, ['category_id' => $category->id, 'fee' => 5]);
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
            ->call('save');

        $event->refresh();
        $organizer = $event->organizer()->first();
        $bankAccount = $event->bankAccount()->first();

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

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('account_number', '999888777')
            ->call('save');

        $bankAccount = $event->fresh()->bankAccount()->firstOrFail();

        $this->assertSame('999888777', $bankAccount->account_number);
        $this->assertSame('pending', $bankAccount->status);
        $this->assertNull($bankAccount->verified_at);
        $this->assertNull($bankAccount->verified_by);
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

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('bank_book', UploadedFile::fake()->create('replace-new.pdf', 128, 'application/pdf'))
            ->call('save');

        $bankAccount = $event->fresh()->bankAccount()->firstOrFail();

        $this->assertSame('pending', $bankAccount->status);
        $this->assertNull($bankAccount->verified_at);
        $this->assertNull($bankAccount->verified_by);
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
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/keep.pdf', 'keep');

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->call('save');

        $bankAccount = $event->fresh()->bankAccount()->firstOrFail();

        $this->assertSame('verified', $bankAccount->status);
        $this->assertSame('2026-08-22 08:00:00', $bankAccount->verified_at?->format('Y-m-d H:i:s'));
        $this->assertSame('admin-keep', $bankAccount->verified_by);
        $this->assertSame('private/events/'.$event->uid.'/bank/keep.pdf', $bankAccount->bank_book_path);
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
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasErrors([
                'bank_name' => ['required'],
                'account_number' => ['required'],
                'account_holder_name' => ['required'],
                'bank_book' => ['mimes'],
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
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasErrors([
                'bank_book' => ['max'],
            ]);
    }

    public function test_create_event_organizer_and_bank_account_are_saved_atomically(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Atomic', 'slug' => 'atomic']);

        EventBankAccount::creating(function () {
            throw new \RuntimeException('forced bank account failure');
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
                ->call('save');

            $this->fail('Expected bank account creation to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('forced bank account failure', $exception->getMessage());
        } finally {
            EventBankAccount::flushEventListeners();
        }

        $this->assertDatabaseMissing('events', [
            'event' => 'Atomic Event',
        ]);

        $this->assertDatabaseCount('event_organizers', 0);
        $this->assertDatabaseCount('event_bank_accounts', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('private/events'));
        $this->assertSame([], Storage::disk('public')->allFiles('cover'));
    }

    private function tenant(): User
    {
        return User::factory()->create([
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
        ]);
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
