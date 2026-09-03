<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventDetail;
use App\Models\Cart;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventRegistrationField;
use App\Models\EventRegistrationMember;
use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationExportOperationalTest extends TestCase
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

    public function test_individual_participant_export_returns_authoritative_row_and_dynamic_fields(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);
        $institution = $this->field($event, ['label' => 'Institusi', 'type' => 'text', 'scope' => 'registration', 'sort_order' => 1]);
        $category = $this->field($event, ['label' => 'Kategori Umur', 'type' => 'select', 'scope' => 'registration', 'options' => ['U12', 'U15'], 'sort_order' => 2]);

        $buyer = $this->buyer(['name' => 'Maya Registran', 'email' => 'maya@example.test']);
        $gateway = $this->gateway('qris', 'QRIS');
        $cart = $this->cart($event, $buyer, [
            'payment_type' => 'qris',
            'payment_gateway_id' => $gateway->id,
            'status' => Cart::STATUS_SUCCESS,
        ]);
        $registration = $this->registration($event, $buyer, $cart, [
            'status' => EventRegistration::STATUS_SUCCESS,
            'answers' => [(int) $institution->id => 'Institut A', (int) $category->id => 'U15'],
        ]);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $response = $component->exportParticipants();
        $response->sendContent();
        $csv = ob_get_clean();

        $rows = $this->csvRows($csv);
        $this->assertStringStartsWith("\xEF\xBB\xBFsep=;\r\n", $csv);
        $header = $rows[1];
        $data = $rows[2];

        $this->assertContains('Registration UID', $header);
        $this->assertContains('Tipe', $header);
        $this->assertContains('Tim', $header);
        $this->assertContains('Invoice', $header);
        $this->assertContains('Institusi', $header);
        $this->assertContains('Kategori Umur', $header);

        $this->assertSame($registration->uid, $data[0]);
        $this->assertSame('Individu', $data[1]);
        $this->assertSame('', $data[2]);
        $this->assertSame('Maya Registran', $data[3]);
        $this->assertSame('maya@example.test', $data[4]);
        $this->assertSame($cart->invoice, $data[5]);
        $this->assertSame('QRIS', $data[6]);
        $this->assertSame(EventRegistration::STATUS_SUCCESS, $data[7]);
        $this->assertSame('', $data[8]);
        $this->assertSame('', $data[9]);
        $this->assertStringContainsString('2026', (string) $data[10]);
        $this->assertSame('Institut A', $data[11]);
        $this->assertSame('U15', $data[12]);
    }

    public function test_team_participant_export_emits_team_name_member_count_and_captain_label(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => 2,
            'team_max_members' => 5,
        ]);
        $division = $this->field($event, ['label' => 'Divisi', 'type' => 'text', 'scope' => 'registration', 'sort_order' => 1]);

        $buyer = $this->buyer(['name' => 'Doni Kapten', 'email' => 'doni@example.test']);
        $cart = $this->cart($event, $buyer, ['payment_type' => 'cash', 'status' => Cart::STATUS_SUCCESS]);
        $registration = $this->registration($event, $buyer, $cart, [
            'team_name' => 'Tim Garuda Esports',
            'answers' => [(int) $division->id => 'Competitive'],
        ]);
        $this->member($registration, ['is_captain' => true, 'sort_order' => 2]);
        $this->member($registration, ['is_captain' => false, 'sort_order' => 1]);
        $this->member($registration, ['is_captain' => false, 'sort_order' => 3]);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $component->exportParticipants()->sendContent();
        $csv = ob_get_clean();
        $rows = $this->csvRows($csv);
        $data = $rows[2];

        $this->assertSame('Tim', $data[1]);
        $this->assertSame('Tim Garuda Esports', $data[2]);
        $this->assertSame('Cash', $data[6]);
        $this->assertSame('3', $data[8]);
        $this->assertSame('Anggota 2', $data[9]);
        $this->assertStringContainsString('2026', (string) $data[10]);
        $this->assertSame('Competitive', $data[11]);
    }

    public function test_team_roster_export_emits_one_row_per_member_with_captain_flag(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => 2,
            'team_max_members' => 5,
        ]);
        $nickname = $this->field($event, ['label' => 'Nickname', 'type' => 'text', 'scope' => 'member', 'sort_order' => 1]);
        $position = $this->field($event, ['label' => 'Posisi', 'type' => 'text', 'scope' => 'member', 'sort_order' => 2]);

        $buyer = $this->buyer();
        $cart = $this->cart($event, $buyer);
        $registration = $this->registration($event, $buyer, $cart, ['team_name' => 'Tim Garuda']);
        $this->member($registration, ['is_captain' => false, 'sort_order' => 1, 'answers' => [(int) $nickname->id => 'Pertama', (int) $position->id => 'Support']]);
        $this->member($registration, ['is_captain' => true, 'sort_order' => 2, 'answers' => [(int) $nickname->id => 'Kapten', (int) $position->id => 'IGL']]);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $component->exportRoster()->sendContent();
        $csv = ob_get_clean();
        $rows = $this->csvRows($csv);

        $this->assertSame(['Registration UID', 'Nama Tim', 'Invoice', 'Status Registration', 'Urutan Anggota', 'Kapten', 'Nickname', 'Posisi'], $rows[1]);

        $this->assertSame($registration->uid, $rows[2][0]);
        $this->assertSame('Tim Garuda', $rows[2][1]);
        $this->assertSame('1', $rows[2][4]);
        $this->assertSame('Tidak', $rows[2][5]);
        $this->assertSame('Pertama', $rows[2][6]);
        $this->assertSame('Support', $rows[2][7]);

        $this->assertSame($registration->uid, $rows[3][0]);
        $this->assertSame('2', $rows[3][4]);
        $this->assertSame('Ya', $rows[3][5]);
        $this->assertSame('Kapten', $rows[3][6]);
        $this->assertSame('IGL', $rows[3][7]);
    }

    public function test_dynamic_registration_fields_are_emitted_in_sort_order(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);
        $second = $this->field($event, ['label' => 'Nomor Punggung', 'type' => 'number', 'scope' => 'registration', 'sort_order' => 5]);
        $first = $this->field($event, ['label' => 'Nama Lengkap', 'type' => 'text', 'scope' => 'registration', 'sort_order' => 1]);

        $buyer = $this->buyer();
        $cart = $this->cart($event, $buyer);
        $this->registration($event, $buyer, $cart, [
            'answers' => [(int) $second->id => 12, (int) $first->id => 'Budi Atlet'],
        ]);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $component->exportParticipants()->sendContent();
        $csv = ob_get_clean();
        $rows = $this->csvRows($csv);
        $header = $rows[1];
        $data = $rows[2];

        $this->assertSame('Tanggal Pendaftaran', $header[10]);
        $this->assertSame('Nama Lengkap', $header[11]);
        $this->assertSame('Nomor Punggung', $header[12]);
        $this->assertSame('Budi Atlet', $data[11]);
        $this->assertSame('12', $data[12]);
    }

    public function test_dynamic_member_fields_are_emitted_in_sort_order(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => 1,
            'team_max_members' => 3,
        ]);
        $second = $this->field($event, ['label' => 'Posisi', 'type' => 'text', 'scope' => 'member', 'sort_order' => 9]);
        $first = $this->field($event, ['label' => 'Nickname', 'type' => 'text', 'scope' => 'member', 'sort_order' => 1]);

        $buyer = $this->buyer();
        $cart = $this->cart($event, $buyer);
        $registration = $this->registration($event, $buyer, $cart, ['team_name' => 'Tim Z']);
        $this->member($registration, ['is_captain' => true, 'sort_order' => 1, 'answers' => [(int) $first->id => 'Alpha', (int) $second->id => 'Top']]);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $component->exportRoster()->sendContent();
        $csv = ob_get_clean();
        $rows = $this->csvRows($csv);

        $this->assertSame('Nickname', $rows[1][6]);
        $this->assertSame('Posisi', $rows[1][7]);
        $this->assertSame('Alpha', $rows[2][6]);
        $this->assertSame('Top', $rows[2][7]);
    }

    public function test_search_and_status_filters_apply_to_export_not_only_pagination(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        $matchBuyer = $this->buyer(['name' => 'Match Name', 'email' => 'match@example.test']);
        $matchCart = $this->cart($event, $matchBuyer, ['invoice' => 'INV-MATCH']);
        $this->registration($event, $matchBuyer, $matchCart, ['status' => EventRegistration::STATUS_SUCCESS]);

        $otherBuyer = $this->buyer(['name' => 'Other Name', 'email' => 'other@example.test']);
        $otherCart = $this->cart($event, $otherBuyer, ['invoice' => 'INV-OTHER']);
        $this->registration($event, $otherBuyer, $otherCart, ['status' => EventRegistration::STATUS_PENDING]);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;
        $component->searchParticipant = 'match';
        $component->filterParticipantStatus = EventRegistration::STATUS_SUCCESS;

        ob_start();
        $component->exportParticipants()->sendContent();
        $csv = ob_get_clean();
        $rows = $this->csvRows($csv);

        $this->assertCount(3, $rows);
        $this->assertStringContainsString('INV-MATCH', $csv);
        $this->assertStringNotContainsString('INV-OTHER', $csv);
    }

    public function test_export_does_not_apply_pagination_even_when_per_page_is_small(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        for ($index = 0; $index < 25; $index++) {
            $buyer = $this->buyer();
            $cart = $this->cart($event, $buyer, ['invoice' => 'INV-PAGE-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT)]);
            $this->registration($event, $buyer, $cart);
        }

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;
        $component->perPageParticipant = 10;

        ob_start();
        $component->exportParticipants()->sendContent();
        $csv = ob_get_clean();
        $rows = $this->csvRows($csv);

        $this->assertCount(27, $rows);
    }

    public function test_other_event_registration_does_not_leak_into_export(): void
    {
        $tenant = $this->tenant();
        $eventA = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);
        $eventB = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        $buyerA = $this->buyer();
        $cartA = $this->cart($eventA, $buyerA, ['invoice' => 'INV-EVENT-A']);
        $this->registration($eventA, $buyerA, $cartA);

        $buyerB = $this->buyer();
        $cartB = $this->cart($eventB, $buyerB, ['invoice' => 'INV-EVENT-B-SECRET']);
        $this->registration($eventB, $buyerB, $cartB);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $eventA->uid;

        ob_start();
        $component->exportParticipants()->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('INV-EVENT-A', $csv);
        $this->assertStringNotContainsString('INV-EVENT-B-SECRET', $csv);
    }

    public function test_foreign_dynamic_fields_do_not_leak_into_export(): void
    {
        $tenant = $this->tenant();
        $eventA = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);
        $eventB = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        $this->field($eventA, ['label' => 'FieldEventA', 'type' => 'text', 'scope' => 'registration', 'sort_order' => 1]);
        $foreign = $this->field($eventB, ['label' => 'FieldEventB', 'type' => 'text', 'scope' => 'registration', 'sort_order' => 1]);

        $buyer = $this->buyer();
        $cart = $this->cart($eventA, $buyer);
        $this->registration($eventA, $buyer, $cart, [
            'answers' => [(int) $foreign->id => 'Rahasia'],
        ]);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $eventA->uid;

        ob_start();
        $component->exportParticipants()->sendContent();
        $csv = ob_get_clean();
        $rows = $this->csvRows($csv);

        $this->assertNotContains('FieldEventB', $rows[1]);
        $this->assertStringNotContainsString('Rahasia', $csv);
    }

    public function test_ticketing_event_export_participants_is_rejected(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_TICKETING]);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;

        $this->assertNull($component->exportParticipants());
        $this->assertSame('Export peserta tidak tersedia untuk event ticketing.', session('error'));
    }

    public function test_individual_event_export_roster_is_rejected(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;

        $this->assertNull($component->exportRoster());
        $this->assertSame('Export roster hanya tersedia untuk event pendaftaran tim.', session('error'));
    }

    public function test_csv_injection_in_participant_export_is_sanitized(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);
        $notes = $this->field($event, ['label' => 'Catatan', 'type' => 'text', 'scope' => 'registration', 'sort_order' => 1]);

        $buyer = $this->buyer([
            'name' => '=cmd|"/c calc"!A1',
            'email' => '+evil@example.test',
        ]);
        $cart = $this->cart($event, $buyer, ['invoice' => "@evil\r\n=cmd"]);
        $this->registration($event, $buyer, $cart, [
            'answers' => [(int) $notes->id => "-SUM(1,1)\r\n=2+2"],
        ]);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $component->exportParticipants()->sendContent();
        $csv = ob_get_clean();
        $rows = $this->csvRows($csv);

        $this->assertStringStartsWith("'=cmd", $rows[2][3]);
        $this->assertStringStartsWith("'+evil", $rows[2][4]);
        $this->assertStringContainsString("'@evil", $csv);
        $this->assertStringContainsString("'-SUM(1,1)", $csv);
        $this->assertStringNotContainsString("\r\n=2+2", $csv);
        $this->assertStringContainsString('=cmd|', $csv);
    }

    public function test_csv_injection_in_roster_export_is_sanitized(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => 1,
            'team_max_members' => 3,
        ]);
        $nickname = $this->field($event, ['label' => 'Nickname', 'type' => 'text', 'scope' => 'member', 'sort_order' => 1]);

        $buyer = $this->buyer();
        $cart = $this->cart($event, $buyer);
        $registration = $this->registration($event, $buyer, $cart, ['team_name' => '=evil']);
        $this->member($registration, ['is_captain' => true, 'sort_order' => 1, 'answers' => [(int) $nickname->id => '+hacker']]);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $component->exportRoster()->sendContent();
        $csv = ob_get_clean();
        $rows = $this->csvRows($csv);

        $this->assertSame("'=evil", $rows[2][1]);
        $this->assertSame("'+hacker", $rows[2][6]);
    }

    public function test_missing_dynamic_answer_renders_empty_cell(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);
        $this->field($event, ['label' => 'Catatan', 'type' => 'text', 'scope' => 'registration', 'sort_order' => 1]);
        $this->field($event, ['label' => 'Panggilan', 'type' => 'text', 'scope' => 'registration', 'sort_order' => 2]);

        $buyer = $this->buyer();
        $cart = $this->cart($event, $buyer);
        $this->registration($event, $buyer, $cart, ['answers' => []]);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $component->exportParticipants()->sendContent();
        $csv = ob_get_clean();
        $rows = $this->csvRows($csv);
        $data = $rows[2];

        $this->assertSame('', $data[11]);
        $this->assertSame('', $data[12]);
    }

    public function test_existing_transaction_export_still_works_after_participant_export_added(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);
        $buyer = $this->buyer();
        $cart = $this->cart($event, $buyer, ['invoice' => 'INV-TX-EXISTING']);
        $this->registration($event, $buyer, $cart);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $component->exportExcel()->sendContent();
        $csv = ob_get_clean();

        $this->assertStringStartsWith("\xEF\xBB\xBFsep=;\r\n", $csv);
        $this->assertStringContainsString('Tanggal', $csv);
        $this->assertStringContainsString('Invoice', $csv);
    }

    public function test_filter_search_and_status_share_query_logic_between_list_and_export(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        $matchBuyer = $this->buyer(['name' => 'Match Person', 'email' => 'match@example.test']);
        $matchCart = $this->cart($event, $matchBuyer, ['invoice' => 'INV-FILTER-MATCH']);
        $this->registration($event, $matchBuyer, $matchCart, ['status' => EventRegistration::STATUS_SUCCESS]);

        $otherBuyer = $this->buyer(['name' => 'Other Person', 'email' => 'other@example.test']);
        $otherCart = $this->cart($event, $otherBuyer, ['invoice' => 'INV-FILTER-OTHER']);
        $this->registration($event, $otherBuyer, $otherCart, ['status' => EventRegistration::STATUS_PENDING]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('setTab', 'peserta')
            ->set('searchParticipant', 'Match')
            ->set('filterParticipantStatus', EventRegistration::STATUS_SUCCESS)
            ->assertSee('INV-FILTER-MATCH')
            ->assertDontSee('INV-FILTER-OTHER');

        $component = new EventDetail;
        $component->eventUid = $event->uid;
        $component->searchParticipant = 'Match';
        $component->filterParticipantStatus = EventRegistration::STATUS_SUCCESS;

        ob_start();
        $component->exportParticipants()->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('INV-FILTER-MATCH', $csv);
        $this->assertStringNotContainsString('INV-FILTER-OTHER', $csv);
    }

    public function test_roster_export_applies_same_search_and_status_filters(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => 1,
            'team_max_members' => 3,
        ]);

        $matchBuyer = $this->buyer(['name' => 'Match Roster', 'email' => 'match@example.test']);
        $matchCart = $this->cart($event, $matchBuyer, ['invoice' => 'INV-ROSTER-MATCH']);
        $matchReg = $this->registration($event, $matchBuyer, $matchCart, [
            'team_name' => 'Tim Match',
            'status' => EventRegistration::STATUS_SUCCESS,
        ]);
        $this->member($matchReg, ['is_captain' => true, 'sort_order' => 1]);

        $otherBuyer = $this->buyer(['name' => 'Other Roster', 'email' => 'other@example.test']);
        $otherCart = $this->cart($event, $otherBuyer, ['invoice' => 'INV-ROSTER-OTHER']);
        $otherReg = $this->registration($event, $otherBuyer, $otherCart, [
            'team_name' => 'Tim Other',
            'status' => EventRegistration::STATUS_PENDING,
        ]);
        $this->member($otherReg, ['is_captain' => true, 'sort_order' => 1]);

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;
        $component->searchParticipant = 'Match';
        $component->filterParticipantStatus = EventRegistration::STATUS_SUCCESS;

        ob_start();
        $component->exportRoster()->sendContent();
        $csv = ob_get_clean();
        $rows = $this->csvRows($csv);

        $this->assertCount(3, $rows);
        $this->assertStringContainsString('INV-ROSTER-MATCH', $csv);
        $this->assertStringNotContainsString('INV-ROSTER-OTHER', $csv);
    }

    public function test_roster_export_correct_with_many_registrations(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => 1,
            'team_max_members' => 3,
        ]);
        $nickname = $this->field($event, ['label' => 'Nickname', 'type' => 'text', 'scope' => 'member', 'sort_order' => 1]);

        for ($index = 0; $index < 30; $index++) {
            $buyer = $this->buyer();
            $cart = $this->cart($event, $buyer, ['invoice' => 'INV-ROSTER-MANY-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT)]);
            $registration = $this->registration($event, $buyer, $cart, ['team_name' => 'Tim '.$index]);
            $this->member($registration, ['is_captain' => true, 'sort_order' => 1, 'answers' => [(int) $nickname->id => 'Kapten '.$index]]);
            $this->member($registration, ['is_captain' => false, 'sort_order' => 2, 'answers' => [(int) $nickname->id => 'Anggota '.$index]]);
        }

        $this->actingAs($tenant);
        $component = new EventDetail;
        $component->eventUid = $event->uid;

        ob_start();
        $component->exportRoster()->sendContent();
        $csv = ob_get_clean();
        $rows = $this->csvRows($csv);

        $this->assertCount(62, $rows); // header + sep + 60 member rows
        $this->assertStringContainsString('INV-ROSTER-MANY-000', $csv);
        $this->assertStringContainsString('INV-ROSTER-MANY-029', $csv);
        $this->assertStringContainsString('Kapten 29', $csv);
    }

    public function test_roster_export_uses_registration_subquery_not_materialized_uid_list(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => 1,
            'team_max_members' => 3,
        ]);

        $buyer = $this->buyer();
        $cart = $this->cart($event, $buyer);
        $registration = $this->registration($event, $buyer, $cart, ['team_name' => 'Tim Subquery']);
        $this->member($registration, ['is_captain' => true, 'sort_order' => 1]);

        $component = new EventDetail;
        $component->eventUid = $event->uid;
        $component->searchParticipant = 'Subquery';
        $component->filterParticipantStatus = EventRegistration::STATUS_SUCCESS;

        $method = new \ReflectionMethod(EventDetail::class, 'rosterExportQuery');
        $method->setAccessible(true);
        $query = $method->invoke($component, $event);
        $sql = $query->toSql();

        $this->assertStringContainsString('in (select', strtolower($sql));
        $this->assertStringNotContainsString("'".$registration->uid."'", $sql);
        $this->assertStringContainsString('like', strtolower($sql));
        $this->assertStringContainsString('"event_registrations"."event_uid" = ?', $sql);

        $this->actingAs($tenant);
        ob_start();
        $component->exportRoster()->sendContent();
        $csv = ob_get_clean();
        $rows = $this->csvRows($csv);

        $this->assertSame($registration->uid, $rows[2][0]);
        $this->assertStringContainsString('INV', $csv);
    }

    public function test_other_tenant_event_participant_export_is_not_accessible(): void
    {
        $owner = $this->tenant();
        $event = $this->event($owner, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        $buyer = $this->buyer();
        $cart = $this->cart($event, $buyer, ['invoice' => 'INV-OWNED']);
        $this->registration($event, $buyer, $cart);

        $intruder = $this->tenant(['email' => 'intruder@example.test']);
        $this->actingAs($intruder);

        $component = new EventDetail;
        $component->eventUid = $event->uid;

        $this->expectException(ModelNotFoundException::class);
        $component->exportParticipants();
    }

    private function tenant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Tenant Export',
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

    private function buyer(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Pendaftar '.Str::random(4),
            'email' => fake()->unique()->safeEmail(),
            'role' => 'user',
            'gambar' => '-',
            'nomor' => '08120000000',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat Pendaftar',
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
            'event' => 'Registration Export Event '.$uid,
            'alamat' => 'Istora Senayan, Jakarta',
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
            'deskripsi' => 'Deskripsi',
            'map' => 'https://maps.google.com/?q=istora',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'registration-export-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
            'payment_otp_enabled' => false,
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ], $overrides));
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

    private function cart(Event $event, User $buyer, array $overrides = []): Cart
    {
        return Cart::create(array_merge([
            'uid' => (string) Str::uuid(),
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-'.Str::upper(Str::random(8)),
            'status' => Cart::STATUS_SUCCESS,
            'konfirmasi' => null,
            'payment_type' => 'cash',
            'internet_fee' => 0,
            'pajak' => 0,
            'pajak_persen' => 0,
            'gross_amount' => 150000,
        ], $overrides));
    }

    private function registration(Event $event, User $buyer, Cart $cart, array $overrides = []): EventRegistration
    {
        return EventRegistration::create(array_merge([
            'uid' => (string) Str::uuid(),
            'cart_uid' => $cart->uid,
            'invoice' => $cart->invoice,
            'event_uid' => $event->uid,
            'user_uid' => $buyer->uid,
            'registration_mode' => $event->registration_mode,
            'status' => EventRegistration::STATUS_SUCCESS,
            'team_name' => null,
            'answers' => [],
        ], $overrides));
    }

    private function member(EventRegistration $registration, array $overrides = []): EventRegistrationMember
    {
        return EventRegistrationMember::create(array_merge([
            'uid' => (string) Str::uuid(),
            'registration_uid' => $registration->uid,
            'is_captain' => false,
            'sort_order' => 1,
            'answers' => [],
        ], $overrides));
    }

    private function gateway(string $slug, string $payment): PaymentGateway
    {
        return PaymentGateway::create([
            'payment' => $payment,
            'category' => 'non-cash',
            'biaya' => 0,
            'biaya_type' => 'rupiah',
            'icon' => null,
            'is_active' => true,
            'slug' => $slug,
        ]);
    }

    private function csvRows(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);

        return array_values(array_filter(array_map(
            fn ($line) => str_getcsv($line, ';'),
            preg_split('/\r\n|\n|\r/', trim($csv))
        ), fn ($row) => $row !== [null] && $row !== false));
    }
}
