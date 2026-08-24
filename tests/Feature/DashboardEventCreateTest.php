<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventCreate;
use App\Models\Category;
use App\Models\Event;
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
        Storage::fake('public');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        $this->artisan('migrate:fresh', ['--database' => 'sqlite']);
    }

    public function test_create_event_saves_new_schedule_and_location_fields_with_legacy_compatibility(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Music', 'slug' => 'music']);

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
            ->call('save');

        $event->refresh();
        $organizer = $event->organizer()->first();

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

        EventOrganizer::create([
            'event_uid' => $eventB->uid,
            'organizer_name' => 'Organizer B',
            'responsible_name' => 'PJ B',
            'responsible_position' => 'Manager B',
            'phone' => '081000000002',
            'email' => 'b@example.test',
            'address' => 'Alamat B',
        ]);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $eventA->uid])
            ->set('organizer_name', 'Organizer A Revisi')
            ->set('responsible_name', 'PJ A Revisi')
            ->set('responsible_position', 'Director A')
            ->set('phone', '081999999999')
            ->set('email', 'a-revisi@example.test')
            ->set('address', 'Alamat A Revisi')
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
    }

    public function test_create_event_and_organizer_are_saved_atomically(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Atomic', 'slug' => 'atomic']);

        EventOrganizer::creating(function () {
            throw new \RuntimeException('forced organizer failure');
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
                ->call('save');

            $this->fail('Expected organizer creation to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('forced organizer failure', $exception->getMessage());
        } finally {
            EventOrganizer::flushEventListeners();
        }

        $this->assertDatabaseMissing('events', [
            'event' => 'Atomic Event',
        ]);

        $this->assertDatabaseCount('event_organizers', 0);
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
