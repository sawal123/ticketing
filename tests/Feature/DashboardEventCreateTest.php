<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventCreate;
use App\Models\Category;
use App\Models\Event;
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
    }

    public function test_edit_event_updates_new_fields_and_keeps_existing_fee_column(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Talk Show', 'slug' => 'talk-show']);
        $event = $this->event($tenant, ['category_id' => $category->id, 'fee' => 5]);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('event', 'Festival Nusantara Revisi')
            ->set('fee', 12)
            ->set('event_end', '2026-09-10 23:00')
            ->set('venue_name', 'ICE BSD Hall 1')
            ->set('venue_address', 'Jl. BSD Grand Boulevard')
            ->set('venue_city', 'Tangerang')
            ->set('venue_province', 'Banten')
            ->call('save');

        $event->refresh();

        $this->assertSame('Festival Nusantara Revisi', $event->event);
        $this->assertSame('2026-09-10 23:00:00', substr((string) $event->event_end, 0, 19));
        $this->assertSame('ICE BSD Hall 1', $event->venue_name);
        $this->assertSame('Jl. BSD Grand Boulevard', $event->venue_address);
        $this->assertSame('Tangerang', $event->venue_city);
        $this->assertSame('Banten', $event->venue_province);
        $this->assertSame('ICE BSD Hall 1, Jl. BSD Grand Boulevard, Tangerang, Banten', $event->alamat);
        $this->assertSame(12, (int) $event->fee);
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
