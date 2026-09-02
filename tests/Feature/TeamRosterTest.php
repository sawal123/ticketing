<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventDetail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventRegistrationField;
use App\Models\EventRegistrationMember;
use App\Models\User;
use App\Services\Registrations\TeamRosterValidator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TeamRosterTest extends TestCase
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

    private function validator(): TeamRosterValidator
    {
        return app(TeamRosterValidator::class);
    }

    // ---------------------------------------------------------------- #1

    public function test_registration_schema_models_casts_and_relations_are_valid(): void
    {
        $this->assertTrue(Schema::hasTable('event_registrations'));
        $this->assertTrue(Schema::hasTable('event_registration_members'));
        $this->assertTrue(Schema::hasColumn('events', 'team_min_members'));
        $this->assertTrue(Schema::hasColumn('events', 'team_max_members'));

        $tenant = $this->tenant();
        $event = $this->teamEvent($tenant, 2, 5);

        $registration = EventRegistration::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'user_uid' => $tenant->uid,
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_name' => 'Tim Utama',
            'answers' => ['registration' => 'value'],
        ]);

        $this->assertSame(['registration' => 'value'], $registration->fresh()->answers);
        $this->assertSame($event->uid, $registration->event->uid);
        $this->assertSame($tenant->uid, $registration->user->uid);

        $member = EventRegistrationMember::create([
            'uid' => (string) Str::uuid(),
            'registration_uid' => $registration->uid,
            'is_captain' => true,
            'sort_order' => 1,
            'answers' => ['member' => 'value'],
        ]);

        $fresh = $member->fresh();
        $this->assertTrue($fresh->is_captain);
        $this->assertIsInt($fresh->sort_order);
        $this->assertSame(['member' => 'value'], $fresh->answers);
        $this->assertSame($registration->uid, $fresh->registration->uid);
        $this->assertSame([$member->uid], $registration->members->pluck('uid')->all());
        $this->assertSame([$registration->uid], $event->registrations->pluck('uid')->all());
    }

    // ------------------------------------------------------- #2 livewire

    public function test_team_event_can_save_min_and_max_via_livewire(): void
    {
        $tenant = $this->tenant();
        $event = $this->teamEvent($tenant);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('team_min_members', 3)
            ->set('team_max_members', 7)
            ->call('saveTeamSettings')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'team_min_members' => 3,
            'team_max_members' => 7,
        ]);
    }

    public function test_individual_event_cannot_save_team_settings(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('team_min_members', 2)
            ->set('team_max_members', 5)
            ->call('saveTeamSettings')
            ->assertHasErrors('team_min_members');

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'team_min_members' => null,
            'team_max_members' => null,
        ]);
    }

    public function test_ticketing_event_cannot_save_team_settings(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_TICKETING]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('team_min_members', 2)
            ->set('team_max_members', 5)
            ->call('saveTeamSettings')
            ->assertHasErrors('team_min_members');

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'team_min_members' => null,
            'team_max_members' => null,
        ]);
    }

    public function test_livewire_manipulation_cannot_bypass_team_settings_mode_guard(): void
    {
        $tenant = $this->tenant();

        foreach ([Event::REGISTRATION_MODE_INDIVIDUAL, Event::REGISTRATION_MODE_TICKETING] as $mode) {
            $event = $this->event($tenant, ['registration_mode' => $mode]);

            Livewire::actingAs($tenant)
                ->test(EventDetail::class, ['uid' => $event->uid])
                ->set('team_min_members', 1)
                ->set('team_max_members', 1)
                ->call('saveTeamSettings')
                ->assertHasErrors('team_min_members');

            $this->assertDatabaseHas('events', [
                'uid' => $event->uid,
                'team_min_members' => null,
                'team_max_members' => null,
            ]);
        }
    }

    public function test_max_smaller_than_min_is_rejected(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_TEAM]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('team_min_members', 6)
            ->set('team_max_members', 2)
            ->call('saveTeamSettings')
            ->assertHasErrors('team_max_members');

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'team_min_members' => null,
            'team_max_members' => null,
        ]);
    }

    public function test_team_settings_card_is_visible_only_for_team_mode(): void
    {
        $tenant = $this->tenant();
        $teamEvent = $this->teamEvent($tenant, 2, 5);
        $individualEvent = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $teamEvent->uid])
            ->set('activeTab', 'form-pendaftaran')
            ->assertSee('Pengaturan Tim')
            ->assertSee('Minimum Anggota')
            ->assertSee('Maksimum Anggota');

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $individualEvent->uid])
            ->set('activeTab', 'form-pendaftaran')
            ->assertDontSee('Pengaturan Tim');
    }

    // ------------------------------------------------ isolation #7 & #8

    public function test_registration_is_isolated_per_event_and_user(): void
    {
        $tenantA = $this->tenant();
        $tenantB = $this->tenant();
        $eventA = $this->teamEvent($tenantA, 1, 5);
        $eventB = $this->teamEvent($tenantB, 1, 5);

        $regA = $this->registration($eventA, $tenantA, 'Tim A');
        $regB = $this->registration($eventB, $tenantB, 'Tim B');

        $this->assertSame($eventA->uid, $regA->event->uid);
        $this->assertSame($tenantA->uid, $regA->user->uid);
        $this->assertSame($eventB->uid, $regB->event->uid);
        $this->assertSame($tenantB->uid, $regB->user->uid);

        $this->assertSame([$regA->uid], $eventA->registrations->pluck('uid')->all());
        $this->assertSame([$regB->uid], $eventB->registrations->pluck('uid')->all());
        $this->assertFalse($eventA->registrations->contains('uid', $regB->uid));
    }

    public function test_members_are_isolated_per_registration(): void
    {
        $tenant = $this->tenant();
        $eventA = $this->teamEvent($tenant, 1, 5);
        $eventB = $this->teamEvent($tenant, 1, 5);
        $regA = $this->registration($eventA, $tenant, 'Tim A');
        $regB = $this->registration($eventB, $tenant, 'Tim B');

        $memberA1 = $this->member($regA, true, 1);
        $memberA2 = $this->member($regA, false, 2);
        $memberB1 = $this->member($regB, true, 1);

        $this->assertSame([$memberA1->uid, $memberA2->uid], $regA->members->pluck('uid')->all());
        $this->assertSame([$memberB1->uid], $regB->members->pluck('uid')->all());
        $this->assertSame($regA->uid, $memberA1->registration->uid);
        $this->assertSame($regA->uid, $memberA2->registration->uid);
        $this->assertSame($regB->uid, $memberB1->registration->uid);
    }

    // -------------------------------------------- validator roster size

    public function test_roster_below_minimum_is_rejected(): void
    {
        $event = $this->teamEvent($this->tenant(), 2, 5);

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true),
        ]));

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('members', $result['errors']);
        $this->assertNull($result['data']);
    }

    public function test_roster_above_maximum_is_rejected(): void
    {
        $event = $this->teamEvent($this->tenant(), 2, 3);

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true),
            $this->rosterMember(false),
            $this->rosterMember(false),
            $this->rosterMember(false),
        ]));

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('members', $result['errors']);
    }

    public function test_roster_at_exact_minimum_is_accepted(): void
    {
        $event = $this->teamEvent($this->tenant(), 2, 5);

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true),
            $this->rosterMember(false),
        ]));

        $this->assertTrue($result['valid']);
        $this->assertCount(2, $result['data']['members']);
    }

    public function test_roster_at_exact_maximum_is_accepted(): void
    {
        $event = $this->teamEvent($this->tenant(), 2, 3);

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true),
            $this->rosterMember(false),
            $this->rosterMember(false),
        ]));

        $this->assertTrue($result['valid']);
        $this->assertCount(3, $result['data']['members']);
    }

    public function test_roster_without_captain_is_rejected(): void
    {
        $event = $this->teamEvent($this->tenant(), 1, 3);

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(false),
        ]));

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('captain', $result['errors']);
    }

    public function test_roster_with_multiple_captains_is_rejected(): void
    {
        $event = $this->teamEvent($this->tenant(), 2, 3);

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true),
            $this->rosterMember(true),
        ]));

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('captain', $result['errors']);
    }

    public function test_roster_with_exactly_one_captain_is_accepted(): void
    {
        $event = $this->teamEvent($this->tenant(), 1, 3);

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true),
        ]));

        $this->assertTrue($result['valid']);
        $this->assertTrue($result['data']['members'][0]['is_captain']);
    }

    public function test_team_name_is_required(): void
    {
        $event = $this->teamEvent($this->tenant(), 1, 3);

        $result = $this->validator()->validateAndNormalize($event, $this->roster('', [
            $this->rosterMember(true),
        ]));

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('team_name', $result['errors']);
    }

    // ---------------------------------------------- dynamic member fields

    private function dynamicFieldEvent(): Event
    {
        $tenant = $this->tenant();
        $event = $this->teamEvent($tenant, 1, 5);

        $this->registrationField($event, ['label' => 'Nickname', 'type' => 'text', 'scope' => 'member', 'is_required' => true, 'sort_order' => 1]);
        $this->registrationField($event, ['label' => 'Bio', 'type' => 'textarea', 'scope' => 'member', 'is_required' => false, 'sort_order' => 2]);
        $this->registrationField($event, ['label' => 'Umur', 'type' => 'number', 'scope' => 'member', 'is_required' => false, 'sort_order' => 3]);
        $this->registrationField($event, ['label' => 'Role', 'type' => 'select', 'scope' => 'member', 'is_required' => false, 'sort_order' => 4, 'options' => ['A', 'B']]);

        return $event;
    }

    private function memberField(Event $event, string $label): EventRegistrationField
    {
        return EventRegistrationField::where('event_uid', $event->uid)->where('label', $label)->sole();
    }

    public function test_required_member_field_empty_is_rejected(): void
    {
        $event = $this->dynamicFieldEvent();
        $nick = $this->memberField($event, 'Nickname');

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true, [(string) $nick->id => '']),
        ]));

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey("members.0.answers.{$nick->id}", $result['errors']);
    }

    public function test_optional_member_field_may_be_empty(): void
    {
        $event = $this->dynamicFieldEvent();
        $nick = $this->memberField($event, 'Nickname');

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true, [(string) $nick->id => 'Captain']),
        ]));

        $this->assertTrue($result['valid']);
        $this->assertArrayNotHasKey('members.0.answers', $result['errors']);
    }

    public function test_number_field_non_numeric_is_rejected(): void
    {
        $event = $this->dynamicFieldEvent();
        $nick = $this->memberField($event, 'Nickname');
        $age = $this->memberField($event, 'Umur');

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true, [(string) $nick->id => 'Captain', (string) $age->id => 'abc']),
        ]));

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey("members.0.answers.{$age->id}", $result['errors']);
    }

    public function test_select_value_outside_options_is_rejected(): void
    {
        $event = $this->dynamicFieldEvent();
        $nick = $this->memberField($event, 'Nickname');
        $role = $this->memberField($event, 'Role');

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true, [(string) $nick->id => 'Captain', (string) $role->id => 'C']),
        ]));

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey("members.0.answers.{$role->id}", $result['errors']);
    }

    public function test_select_valid_value_is_accepted_and_normalized(): void
    {
        $event = $this->dynamicFieldEvent();
        $nick = $this->memberField($event, 'Nickname');
        $role = $this->memberField($event, 'Role');
        $age = $this->memberField($event, 'Umur');

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true, [
                (string) $nick->id => 'Captain',
                (string) $role->id => 'B',
                (string) $age->id => '18',
            ]),
        ]));

        $this->assertTrue($result['valid']);
        $this->assertSame('B', $result['data']['members'][0]['answers'][$role->id]);
        $this->assertSame(18, $result['data']['members'][0]['answers'][$age->id]);
        $this->assertSame('Captain', $result['data']['members'][0]['answers'][$nick->id]);
    }

    public function test_field_from_another_event_is_rejected(): void
    {
        $event = $this->dynamicFieldEvent();
        $nick = $this->memberField($event, 'Nickname');

        $otherEvent = $this->teamEvent($this->tenant(), 1, 5);
        $foreign = $this->registrationField($otherEvent, ['label' => 'Foreign', 'type' => 'text', 'scope' => 'member', 'is_required' => false, 'sort_order' => 1]);

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true, [(string) $nick->id => 'Captain', (string) $foreign->id => 'x']),
        ]));

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey("members.0.answers.{$foreign->id}", $result['errors']);
    }

    public function test_registration_scope_field_as_member_answer_is_rejected(): void
    {
        $tenant = $this->tenant();
        $event = $this->teamEvent($tenant, 1, 5);
        $registrationScopeField = $this->registrationField($event, ['label' => 'Nama Tim', 'type' => 'text', 'scope' => 'registration', 'is_required' => false, 'sort_order' => 1]);

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true, [(string) $registrationScopeField->id => 'Tim X']),
        ]));

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey("members.0.answers.{$registrationScopeField->id}", $result['errors']);
    }

    public function test_unknown_field_id_is_rejected(): void
    {
        $event = $this->dynamicFieldEvent();
        $nick = $this->memberField($event, 'Nickname');

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true, [(string) $nick->id => 'Captain', '999999' => 'x']),
        ]));

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('members.0.answers.999999', $result['errors']);
    }

    public function test_normalized_answers_only_contain_authorized_fields(): void
    {
        $event = $this->dynamicFieldEvent();
        $nick = $this->memberField($event, 'Nickname');
        $role = $this->memberField($event, 'Role');
        $age = $this->memberField($event, 'Umur');

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true, [
                (string) $nick->id => 'Captain',
                (string) $role->id => 'A',
                (string) $age->id => '20',
                '123456' => 'stray',
            ]),
        ]));

        $this->assertFalse($result['valid']);
        $this->assertNull($result['data']);

        // A fully valid payload must only expose authorized member fields.
        $valid = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true, [
                (string) $nick->id => 'Captain',
                (string) $role->id => 'A',
                (string) $age->id => '20',
            ]),
        ]));

        $this->assertTrue($valid['valid']);
        $this->assertSame([$nick->id, $age->id, $role->id], array_keys($valid['data']['members'][0]['answers']));
    }

    // --------------------------------------------- validator side effects

    public function test_validator_does_not_create_carts_or_transactions(): void
    {
        $tenant = $this->tenant();
        $event = $this->teamEvent($tenant, 1, 5);

        $beforeCarts = DB::table('carts')->count();

        $invalid = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(false),
        ]));

        $valid = $this->validator()->validateAndNormalize($event, $this->roster('Tim', [
            $this->rosterMember(true),
        ]));

        $this->assertFalse($invalid['valid']);
        $this->assertTrue($valid['valid']);
        $this->assertSame($beforeCarts, DB::table('carts')->count());
        $this->assertSame(0, DB::table('event_registrations')->count());
        $this->assertSame(0, DB::table('event_registration_members')->count());
    }

    public function test_many_members_stay_one_event_registration_not_one_per_member(): void
    {
        $tenant = $this->tenant();
        $event = $this->teamEvent($tenant, 3, 3);

        $result = $this->validator()->validateAndNormalize($event, $this->roster('Tim Satu', [
            $this->rosterMember(true),
            $this->rosterMember(false),
            $this->rosterMember(false),
        ]));

        $this->assertTrue($result['valid']);
        $this->assertSame('Tim Satu', $result['data']['team_name']);
        $this->assertCount(3, $result['data']['members']);
        $this->assertSame(0, DB::table('event_registrations')->count());
        $this->assertSame(0, DB::table('event_registration_members')->count());

        // Foundation: one EventRegistration row may carry many member rows.
        $registration = $this->registration($event, $tenant, 'Tim Satu');
        $this->member($registration, true, 1);
        $this->member($registration, false, 2);
        $this->member($registration, false, 3);

        $this->assertSame(1, DB::table('event_registrations')->count());
        $this->assertSame(3, DB::table('event_registration_members')->count());
        $this->assertSame(3, $registration->fresh()->members->count());
    }

    // ------------------------------------------------------------- helpers

    private function roster(string $teamName, array $members): array
    {
        return ['team_name' => $teamName, 'members' => $members];
    }

    private function rosterMember(bool $isCaptain, array $answers = []): array
    {
        return ['is_captain' => $isCaptain, 'answers' => $answers];
    }

    private function registration(Event $event, User $user, string $teamName): EventRegistration
    {
        return EventRegistration::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'user_uid' => $user->uid,
            'registration_mode' => $event->registration_mode,
            'team_name' => $teamName,
            'answers' => null,
        ]);
    }

    private function member(EventRegistration $registration, bool $isCaptain, int $sortOrder): EventRegistrationMember
    {
        return EventRegistrationMember::create([
            'uid' => (string) Str::uuid(),
            'registration_uid' => $registration->uid,
            'is_captain' => $isCaptain,
            'sort_order' => $sortOrder,
            'answers' => null,
        ]);
    }

    private function registrationField(Event $event, array $overrides = []): EventRegistrationField
    {
        return EventRegistrationField::create(array_merge([
            'event_uid' => $event->uid,
            'label' => 'Field '.Str::random(5),
            'type' => 'text',
            'scope' => 'member',
            'is_required' => false,
            'options' => null,
            'sort_order' => 1,
        ], $overrides));
    }

    private function teamEvent(User $tenant, int $min = 2, int $max = 5): Event
    {
        return $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => $min,
            'team_max_members' => $max,
        ]);
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
            'event' => 'Team Roster Event '.$uid,
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
            'deskripsi' => 'Deskripsi event team roster',
            'map' => 'https://maps.google.com/?q=istora',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'team-roster-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
            'team_min_members' => null,
            'team_max_members' => null,
        ], $overrides));
    }
}
