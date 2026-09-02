<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventDetail;
use App\Models\Event;
use App\Models\EventRegistrationField;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DynamicRegistrationFieldsTest extends TestCase
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

        View::share('logo', [(object) ['logo' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
        Storage::fake('local');
        Storage::fake('public');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        $this->artisan('migrate:fresh', ['--database' => 'sqlite']);
    }

    public function test_registration_field_schema_and_allowed_values_are_available(): void
    {
        $this->assertTrue(Schema::hasTable('event_registration_fields'));
        $this->assertSame(['text', 'number', 'select', 'textarea'], EventRegistrationField::types());
        $this->assertSame(['registration', 'member'], EventRegistrationField::scopes());
    }

    public function test_ticketing_event_cannot_create_registration_field(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('registrationField', $this->fieldInput([
                'label' => 'Nama Peserta',
                'type' => 'text',
                'scope' => 'registration',
            ]))
            ->call('saveRegistrationField')
            ->assertHasErrors('registrationField');

        $this->assertDatabaseCount('event_registration_fields', 0);
    }

    public function test_individual_event_can_create_registration_scope_field(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('registrationField', $this->fieldInput([
                'label' => 'Nama Peserta',
                'type' => 'text',
                'scope' => 'registration',
            ]))
            ->call('saveRegistrationField')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_registration_fields', [
            'event_uid' => $event->uid,
            'label' => 'Nama Peserta',
            'scope' => 'registration',
        ]);
    }

    public function test_individual_event_cannot_create_member_scope_field(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('registrationField', $this->fieldInput([
                'label' => 'Nama Anggota',
                'type' => 'text',
                'scope' => 'member',
            ]))
            ->call('saveRegistrationField')
            ->assertHasErrors('registrationField.scope');

        $this->assertDatabaseCount('event_registration_fields', 0);
    }

    public function test_team_event_can_create_registration_scope_field(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('registrationField', $this->fieldInput([
                'label' => 'Nama Tim',
                'type' => 'text',
                'scope' => 'registration',
            ]))
            ->call('saveRegistrationField')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_registration_fields', [
            'event_uid' => $event->uid,
            'label' => 'Nama Tim',
            'scope' => 'registration',
        ]);
    }

    public function test_team_event_can_create_member_scope_field(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('registrationField', $this->fieldInput([
                'label' => 'Nama Anggota',
                'type' => 'text',
                'scope' => 'member',
            ]))
            ->call('saveRegistrationField')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_registration_fields', [
            'event_uid' => $event->uid,
            'label' => 'Nama Anggota',
            'scope' => 'member',
        ]);
    }

    public function test_invalid_type_is_rejected(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('registrationField', $this->fieldInput([
                'label' => 'Field Invalid',
                'type' => 'checkbox',
                'scope' => 'registration',
            ]))
            ->call('saveRegistrationField')
            ->assertHasErrors('registrationField.type');

        $this->assertDatabaseCount('event_registration_fields', 0);
    }

    public function test_invalid_scope_is_rejected(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('registrationField', $this->fieldInput([
                'label' => 'Field Invalid',
                'type' => 'text',
                'scope' => 'team',
            ]))
            ->call('saveRegistrationField')
            ->assertHasErrors('registrationField.scope');

        $this->assertDatabaseCount('event_registration_fields', 0);
    }

    public function test_select_with_less_than_two_options_is_rejected(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('registrationField', $this->fieldInput([
                'label' => 'Pilihan',
                'type' => 'select',
                'scope' => 'registration',
                'options' => "Satu\n",
            ]))
            ->call('saveRegistrationField')
            ->assertHasErrors('registrationField.options');

        $this->assertDatabaseCount('event_registration_fields', 0);
    }

    public function test_select_options_are_trimmed_deduplicated_and_stored_as_json(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('registrationField', $this->fieldInput([
                'label' => 'Pilihan',
                'type' => 'select',
                'scope' => 'registration',
                'options' => "  Alpha  \n\nAlpha\nBeta\nBeta\nGamma",
            ]))
            ->call('saveRegistrationField')
            ->assertHasNoErrors();

        $field = EventRegistrationField::where('event_uid', $event->uid)->sole();

        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $field->options);
        $this->assertDatabaseHas('event_registration_fields', [
            'id' => $field->id,
            'options' => json_encode(['Alpha', 'Beta', 'Gamma']),
        ]);
    }

    public function test_select_rejects_more_than_50_options(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);

        $options = implode("\n", array_map(fn ($i) => 'Option '.$i, range(1, 51)));

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('registrationField', $this->fieldInput([
                'label' => 'Pilihan Banyak',
                'type' => 'select',
                'scope' => 'registration',
                'options' => $options,
            ]))
            ->call('saveRegistrationField')
            ->assertHasErrors('registrationField.options');

        $this->assertDatabaseCount('event_registration_fields', 0);
    }

    public function test_select_rejects_option_longer_than_100_characters(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('registrationField', $this->fieldInput([
                'label' => 'Pilihan Panjang',
                'type' => 'select',
                'scope' => 'registration',
                'options' => str_repeat('A', 101)."\nB",
            ]))
            ->call('saveRegistrationField')
            ->assertHasErrors('registrationField.options');

        $this->assertDatabaseCount('event_registration_fields', 0);
    }

    public function test_non_select_fields_always_store_null_options(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);

        foreach (['text', 'number', 'textarea'] as $type) {
            Livewire::actingAs($tenant)
                ->test(EventDetail::class, ['uid' => $event->uid])
                ->set('registrationField', $this->fieldInput([
                    'label' => 'Field '.$type,
                    'type' => $type,
                    'scope' => 'registration',
                    'options' => "A\nB",
                ]))
                ->call('saveRegistrationField')
                ->assertHasNoErrors();
        }

        $this->assertSame(3, EventRegistrationField::where('event_uid', $event->uid)->whereNull('options')->count());
    }

    public function test_edit_field_succeeds(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);
        $field = $this->field($event, [
            'label' => 'Label Lama',
            'type' => 'text',
            'scope' => 'registration',
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('editRegistrationField', $field->id)
            ->set('registrationField.label', 'Label Baru')
            ->set('registrationField.is_required', true)
            ->call('saveRegistrationField')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_registration_fields', [
            'id' => $field->id,
            'label' => 'Label Baru',
            'is_required' => true,
        ]);
    }

    public function test_edit_field_from_other_event_is_rejected(): void
    {
        $tenant = $this->tenant();
        $eventA = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_TEAM]);
        $eventB = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_TEAM]);
        $fieldB = $this->field($eventB, ['label' => 'Field B']);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $eventA->uid])
            ->call('editRegistrationField', $fieldB->id);
    }

    public function test_delete_own_field_succeeds(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);
        $field = $this->field($event, ['label' => 'Field Dihapus']);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('deleteRegistrationField', $field->id);

        $this->assertDatabaseMissing('event_registration_fields', ['id' => $field->id]);
    }

    public function test_delete_field_from_other_event_is_rejected(): void
    {
        $tenant = $this->tenant();
        $eventA = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_TEAM]);
        $eventB = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_TEAM]);
        $fieldB = $this->field($eventB, ['label' => 'Field B']);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $eventA->uid])
            ->call('deleteRegistrationField', $fieldB->id);
    }

    public function test_move_up_succeeds(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);
        $first = $this->field($event, ['label' => 'First', 'sort_order' => 1]);
        $second = $this->field($event, ['label' => 'Second', 'sort_order' => 2]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('moveRegistrationField', $second->id, 'up');

        $this->assertSame(1, $second->fresh()->sort_order);
        $this->assertSame(2, $first->fresh()->sort_order);
    }

    public function test_move_down_succeeds(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);
        $first = $this->field($event, ['label' => 'First', 'sort_order' => 1]);
        $second = $this->field($event, ['label' => 'Second', 'sort_order' => 2]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('moveRegistrationField', $first->id, 'down');

        $this->assertSame(2, $first->fresh()->sort_order);
        $this->assertSame(1, $second->fresh()->sort_order);
    }

    public function test_reorder_stays_within_the_same_event(): void
    {
        $tenant = $this->tenant();
        $eventA = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_TEAM]);
        $eventB = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_TEAM]);

        $a1 = $this->field($eventA, ['label' => 'A1', 'sort_order' => 1]);
        $a2 = $this->field($eventA, ['label' => 'A2', 'sort_order' => 2]);
        $b1 = $this->field($eventB, ['label' => 'B1', 'sort_order' => 1]);
        $b2 = $this->field($eventB, ['label' => 'B2', 'sort_order' => 2]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $eventA->uid])
            ->call('moveRegistrationField', $a2->id, 'up');

        $this->assertSame(1, $a2->fresh()->sort_order);
        $this->assertSame(2, $a1->fresh()->sort_order);
        $this->assertSame(1, $b1->fresh()->sort_order);
        $this->assertSame(2, $b2->fresh()->sort_order);
    }

    public function test_sort_order_is_deterministic_after_reorder(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);
        $first = $this->field($event, ['label' => 'First', 'sort_order' => 1]);
        $second = $this->field($event, ['label' => 'Second', 'sort_order' => 2]);
        $third = $this->field($event, ['label' => 'Third', 'sort_order' => 3]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('moveRegistrationField', $second->id, 'up');

        $orders = EventRegistrationField::where('event_uid', $event->uid)
            ->orderBy('sort_order')
            ->pluck('id', 'sort_order')
            ->all();

        $this->assertSame([
            1 => $second->id,
            2 => $first->id,
            3 => $third->id,
        ], $orders);
    }

    public function test_registration_form_tab_is_visible_for_individual_event(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->assertSee('Form Pendaftaran');
    }

    public function test_registration_form_tab_is_visible_for_team_event(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->assertSee('Form Pendaftaran');
    }

    public function test_registration_form_tab_is_hidden_for_ticketing_event(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->assertDontSee('Form Pendaftaran');
    }

    public function test_livewire_cannot_bypass_event_mode_or_ownership_guard(): void
    {
        $tenant = $this->tenant();
        $ticketing = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);
        $eventA = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);
        $eventB = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
        ]);
        $otherField = $this->field($eventB, ['label' => 'Field Milik Event Lain']);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $ticketing->uid])
            ->set('registrationField', $this->fieldInput([
                'label' => 'Bypass Mode',
                'type' => 'text',
                'scope' => 'registration',
            ]))
            ->call('saveRegistrationField')
            ->assertHasErrors('registrationField');

        $this->assertDatabaseCount('event_registration_fields', 1);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $eventA->uid])
            ->call('deleteRegistrationField', $otherField->id);
    }

    private function fieldInput(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Field',
            'type' => 'text',
            'scope' => 'registration',
            'is_required' => false,
            'options' => '',
        ], $overrides);
    }

    private function field(Event $event, array $overrides = []): EventRegistrationField
    {
        return EventRegistrationField::create(array_merge([
            'event_uid' => $event->uid,
            'label' => 'Field '.Str::random(5),
            'type' => 'text',
            'scope' => 'registration',
            'is_required' => false,
            'options' => null,
            'sort_order' => 1,
        ], $overrides));
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

    private function event(User $tenant, array $overrides = []): Event
    {
        $uid = (string) Str::uuid();

        return Event::create(array_merge([
            'uid' => $uid,
            'user_uid' => $tenant->uid,
            'event' => 'Registration Field Event '.$uid,
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
            'deskripsi' => 'Deskripsi event registration field',
            'map' => 'https://maps.google.com/?q=istora',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'registration-field-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ], $overrides));
    }
}
