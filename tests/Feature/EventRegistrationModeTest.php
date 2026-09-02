<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventCreate;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventBankAccount;
use App\Models\EventDocument;
use App\Models\EventOrganizer;
use App\Models\Harga;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class EventRegistrationModeTest extends TestCase
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

    public function test_database_defaults_registration_mode_to_ticketing(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Music', 'slug' => 'music']);
        $event = $this->event($tenant, $category);

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);
    }

    public function test_event_model_falls_back_to_ticketing_when_registration_mode_is_null(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Legacy', 'slug' => 'legacy']);
        $event = $this->event($tenant, $category, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);

        DB::table('events')
            ->where('id', $event->id)
            ->update(['registration_mode' => null]);

        $event->refresh();

        $this->assertNull($event->getRawOriginal('registration_mode'));
        $this->assertSame(Event::REGISTRATION_MODE_TICKETING, $event->registration_mode);
    }

    public function test_create_event_via_livewire_defaults_to_ticketing(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Seminar', 'slug' => 'seminar']);

        $this->fillCreateForm(
            Livewire::actingAs($tenant)->test(EventCreate::class),
            $category
        )->call('save');

        $event = Event::where('event', 'Create Default Event')->firstOrFail();

        $this->assertSame(Event::REGISTRATION_MODE_TICKETING, $event->registration_mode);
        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);
    }

    public function test_create_event_via_livewire_can_store_individual_and_team_modes(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Competition', 'slug' => 'competition']);

        foreach ([Event::REGISTRATION_MODE_INDIVIDUAL, Event::REGISTRATION_MODE_TEAM] as $index => $mode) {
            $this->fillCreateForm(
                Livewire::actingAs($tenant)->test(EventCreate::class),
                $category,
                [
                    'event' => 'Registration Mode Event '.($index + 1),
                    'registration_mode' => $mode,
                ]
            )->call('save');
        }

        $this->assertDatabaseHas('events', [
            'event' => 'Registration Mode Event 1',
            'registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL,
        ]);
        $this->assertDatabaseHas('events', [
            'event' => 'Registration Mode Event 2',
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);
    }

    public function test_invalid_registration_mode_is_rejected(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Workshop', 'slug' => 'workshop']);

        $this->fillCreateForm(
            Livewire::actingAs($tenant)->test(EventCreate::class),
            $category,
            ['registration_mode' => 'vip-only']
        )->call('save')
            ->assertHasErrors(['registration_mode']);

        $this->assertDatabaseMissing('events', [
            'event' => 'Create Default Event',
        ]);
    }

    public function test_edit_event_loads_existing_registration_mode_with_legacy_fallback(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Talk Show', 'slug' => 'talk-show']);
        $teamEvent = $this->editableEvent($tenant, $category, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);
        $legacyEvent = $this->editableEvent($tenant, $category, [
            'registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL,
        ]);

        DB::table('events')
            ->where('id', $legacyEvent->id)
            ->update(['registration_mode' => null]);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $teamEvent->uid])
            ->assertSet('registration_mode', Event::REGISTRATION_MODE_TEAM);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $legacyEvent->uid])
            ->assertSet('registration_mode', Event::REGISTRATION_MODE_TICKETING);
    }

    public function test_event_without_ticket_or_cart_can_change_mode_from_ticketing_to_team(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Expo', 'slug' => 'expo']);
        $event = $this->editableEvent($tenant, $category);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('registration_mode', Event::REGISTRATION_MODE_TEAM)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);
    }

    public function test_event_with_ticket_category_rejects_registration_mode_change(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Concert', 'slug' => 'concert']);
        $event = $this->editableEvent($tenant, $category);
        $this->harga($event);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('registration_mode', Event::REGISTRATION_MODE_TEAM)
            ->call('save')
            ->assertHasErrors(['registration_mode']);

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);
    }

    public function test_event_with_cart_rejects_registration_mode_change(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Race', 'slug' => 'race']);
        $event = $this->editableEvent($tenant, $category);
        $this->cart($event, $tenant);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('registration_mode', Event::REGISTRATION_MODE_INDIVIDUAL)
            ->call('save')
            ->assertHasErrors(['registration_mode']);

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);
    }

    public function test_event_with_soft_deleted_cart_rejects_registration_mode_change(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Trail Run', 'slug' => 'trail-run']);
        $event = $this->editableEvent($tenant, $category);
        $cart = $this->cart($event, $tenant);
        $cart->delete();

        $event->load('carts');

        $this->assertTrue($event->carts->isEmpty());
        $this->assertTrue($event->registrationModeLocked());

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('registration_mode', Event::REGISTRATION_MODE_TEAM)
            ->call('save')
            ->assertHasErrors(['registration_mode']);

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);
    }

    public function test_locked_mode_cannot_be_bypassed_by_direct_livewire_manipulation(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Forum', 'slug' => 'forum']);
        $event = $this->editableEvent($tenant, $category);
        $this->harga($event);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->assertSet('registrationModeLocked', true)
            ->set('registrationModeLocked', false)
            ->set('registration_mode', Event::REGISTRATION_MODE_TEAM)
            ->call('save')
            ->assertHasErrors(['registration_mode'])
            ->assertSet('registration_mode', Event::REGISTRATION_MODE_TICKETING);

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);
    }

    public function test_rejected_change_keeps_existing_ticket_cart_and_registration_mode(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Festival', 'slug' => 'festival']);
        $event = $this->editableEvent($tenant, $category);
        $ticket = $this->harga($event);
        $cart = $this->cart($event, $tenant);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->set('registration_mode', Event::REGISTRATION_MODE_TEAM)
            ->call('save')
            ->assertHasErrors(['registration_mode']);

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);
        $this->assertDatabaseHas('hargas', [
            'id' => $ticket->id,
            'uid' => $event->uid,
        ]);
        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'event_uid' => $event->uid,
        ]);
    }

    public function test_locked_edit_ui_shows_current_mode_and_lock_message(): void
    {
        $tenant = $this->tenant();
        $category = Category::create(['name' => 'Team Event', 'slug' => 'team-event']);
        $event = $this->editableEvent($tenant, $category, [
            'registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL,
        ]);
        $this->cart($event, $tenant);

        Livewire::actingAs($tenant)
            ->test(EventCreate::class, ['uid' => $event->uid])
            ->assertSee('Jenis Pendaftaran')
            ->assertSee('Pendaftaran Individu')
            ->assertSee('Jenis pendaftaran saat ini: Pendaftaran Individu')
            ->assertSee('Jenis pendaftaran tidak dapat diubah karena event sudah memiliki data operasional.')
            ->assertSeeHtml('disabled');
    }

    private function fillCreateForm(Testable $component, Category $category, array $overrides = []): Testable
    {
        foreach ($this->createFormData($category, $overrides) as $field => $value) {
            $component->set($field, $value);
        }

        return $component;
    }

    private function createFormData(Category $category, array $overrides = []): array
    {
        return array_merge([
            'event' => 'Create Default Event',
            'fee' => 10,
            'start_sale' => '2026-09-01 10:00',
            'event_start' => '2026-09-10 19:00',
            'event_end' => '2026-09-10 22:00',
            'venue_name' => 'Istora Senayan',
            'venue_address' => 'Jl. Pintu Satu Senayan',
            'venue_city' => 'Jakarta Pusat',
            'venue_province' => 'DKI Jakarta',
            'map' => 'https://maps.google.com/?q=istora',
            'cover' => UploadedFile::fake()->image('cover.jpg'),
            'deskripsi' => 'Deskripsi event utama',
            'category_id' => $category->id,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
            'organizer_name' => 'PT Event Nusantara',
            'responsible_name' => 'Sawalinto',
            'responsible_position' => 'Project Manager',
            'phone' => '081234567890',
            'email' => 'organizer@example.test',
            'address' => 'Alamat penyelenggara lengkap',
            'bank_name' => 'Bank Central Asia',
            'account_number' => '1234567890',
            'account_holder_name' => 'PT Event Nusantara',
            'bank_book' => UploadedFile::fake()->create('bank-book.pdf', 256, 'application/pdf'),
            'document_number' => '001/SP-EVENT/IX/2026',
            'document_date' => '2026-08-20',
            'organizer_letter' => UploadedFile::fake()->create('organizer-letter.pdf', 256, 'application/pdf'),
            'responsible_identity' => UploadedFile::fake()->create('responsible-identity.pdf', 128, 'application/pdf'),
        ], $overrides);
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

    private function event(User $tenant, Category $category, array $overrides = []): Event
    {
        $uid = (string) Str::uuid();

        return Event::create(array_merge([
            'uid' => $uid,
            'user_uid' => $tenant->uid,
            'category_id' => $category->id,
            'event' => 'Existing Event '.$uid,
            'alamat' => 'Istora Senayan, Jl. Pintu Satu Senayan, Jakarta Pusat, DKI Jakarta',
            'tanggal' => '2026-09-10 19:00:00',
            'event_end' => '2026-09-10 22:00:00',
            'venue_name' => 'Istora Senayan',
            'venue_address' => 'Jl. Pintu Satu Senayan',
            'venue_city' => 'Jakarta Pusat',
            'venue_province' => 'DKI Jakarta',
            'status' => 'inactive',
            'cover' => 'cover.jpg',
            'fee' => 10,
            'pajak' => 0,
            'deskripsi' => 'Deskripsi existing event',
            'map' => 'https://maps.google.com/?q=existing',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'existing-event-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
        ], $overrides));
    }

    private function editableEvent(User $tenant, Category $category, array $overrides = []): Event
    {
        $event = $this->event($tenant, $category, $overrides);

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
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/bank/book.pdf', 'bank-book');

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
        ]);
        Storage::disk('local')->put('private/events/'.$event->uid.'/documents/organizer-letter.pdf', 'organizer-letter');

        return $event;
    }

    private function harga(Event $event, array $overrides = []): Harga
    {
        return Harga::create(array_merge([
            'uid' => $event->uid,
            'kategori' => 'VIP '.Str::random(5),
            'qty' => 10,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => 150000,
            'status' => 'active',
            'max_order_qty' => 5,
        ], $overrides));
    }

    private function cart(Event $event, User $buyer, array $overrides = []): Cart
    {
        return Cart::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.Str::upper(Str::random(8)),
            'status' => Cart::STATUS_PENDING,
            'konfirmasi' => null,
            'payment_type' => 'qris',
            'internet_fee' => 0,
            'pajak' => 0,
            'pajak_persen' => 0,
            'gross_amount' => 150000,
        ], $overrides));
    }
}
