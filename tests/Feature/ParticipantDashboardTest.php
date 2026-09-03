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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ParticipantDashboardTest extends TestCase
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

    public function test_ticketing_event_does_not_show_peserta_tab_or_participant_modal(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->assertDontSee('Peserta');
    }

    public function test_active_tab_peserta_manipulation_on_ticketing_event_is_sanitized(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('setTab', 'peserta')
            ->assertSet('activeTab', 'umum');
    }

    public function test_direct_active_tab_property_manipulation_on_ticketing_event_is_sanitized(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TICKETING,
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'peserta')
            ->assertSet('activeTab', 'umum');
    }

    public function test_individual_registration_list_and_detail_show_authoritative_data(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);
        $institution = $this->field($event, ['label' => 'Institusi', 'type' => 'text', 'scope' => 'registration', 'sort_order' => 1]);
        $division = $this->field($event, ['label' => 'Divisi', 'type' => 'select', 'scope' => 'registration', 'options' => ['A', 'B'], 'sort_order' => 2]);

        $buyer = $this->buyer(['name' => 'Maya Registran', 'email' => 'maya@example.test']);
        $gateway = $this->gateway('qris', 'QRIS');
        $cart = $this->cart($event, $buyer, [
            'payment_type' => 'qris',
            'payment_gateway_id' => $gateway->id,
            'status' => Cart::STATUS_SUCCESS,
            'gross_amount' => 150000,
        ]);
        $registration = $this->registration($event, $buyer, $cart, [
            'answers' => [(int) $institution->id => 'Institut Teknologi Nusantara', (int) $division->id => 'A'],
        ]);

        $component = Livewire::actingAs($tenant)->test(EventDetail::class, ['uid' => $event->uid]);

        $component->call('setTab', 'peserta')
            ->assertSet('activeTab', 'peserta')
            ->assertSee('Maya Registran')
            ->assertSee('maya@example.test')
            ->assertSee($cart->invoice)
            ->assertSee('QRIS')
            ->assertSee('Peserta individu');

        $component->call('showParticipantDetail', $registration->uid)
            ->assertSet('selectedRegistrationUid', $registration->uid)
            ->assertSee('Detail Peserta')
            ->assertSee('Akun Pendaftar')
            ->assertSee('Institusi')
            ->assertSee('Institut Teknologi Nusantara')
            ->assertSee('Divisi')
            ->assertSee('Maya Registran')
            ->assertSee('QRIS');
    }

    public function test_individual_registration_answers_render_by_field_definition_not_name_assumption(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);
        $badgeNumber = $this->field($event, ['label' => 'Nomor Punggung', 'type' => 'number', 'scope' => 'registration', 'sort_order' => 1]);
        $category = $this->field($event, ['label' => 'Kategori Umur', 'type' => 'select', 'scope' => 'registration', 'options' => ['U12', 'U15'], 'sort_order' => 2]);
        $notes = $this->field($event, ['label' => 'Catatan Kesehatan', 'type' => 'textarea', 'scope' => 'registration', 'sort_order' => 3]);

        $buyer = $this->buyer(['name' => 'Rina Atlet', 'email' => 'rina@example.test']);
        $cart = $this->cart($event, $buyer);
        $registration = $this->registration($event, $buyer, $cart, [
            'answers' => [(int) $badgeNumber->id => 12, (int) $category->id => 'U15', (int) $notes->id => 'Sehat walafiat'],
        ]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('setTab', 'peserta')
            ->call('showParticipantDetail', $registration->uid)
            ->assertSee('Nomor Punggung')
            ->assertSee('12')
            ->assertSee('Kategori Umur')
            ->assertSee('U15')
            ->assertSee('Catatan Kesehatan')
            ->assertSee('Sehat walafiat');
    }

    public function test_team_registration_list_and_detail_show_roster_and_captain(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => 2,
            'team_max_members' => 5,
        ]);
        $division = $this->field($event, ['label' => 'Divisi', 'type' => 'text', 'scope' => 'registration', 'sort_order' => 1]);
        $nickname = $this->field($event, ['label' => 'Nickname', 'type' => 'text', 'scope' => 'member', 'sort_order' => 1]);
        $position = $this->field($event, ['label' => 'Posisi', 'type' => 'text', 'scope' => 'member', 'sort_order' => 2]);

        $buyer = $this->buyer(['name' => 'Doni Kapten', 'email' => 'doni@example.test']);
        $cart = $this->cart($event, $buyer, ['payment_type' => 'cash', 'status' => Cart::STATUS_SUCCESS]);
        $registration = $this->registration($event, $buyer, $cart, [
            'team_name' => 'Tim Garuda Esports',
            'answers' => [(int) $division->id => 'Competitive'],
            'status' => EventRegistration::STATUS_SUCCESS,
        ]);
        $this->member($registration, ['is_captain' => true, 'sort_order' => 2, 'answers' => [(int) $nickname->id => 'SiKapten', (int) $position->id => 'IGL']]);
        $this->member($registration, ['is_captain' => false, 'sort_order' => 1, 'answers' => [(int) $nickname->id => 'SiPertama', (int) $position->id => 'Support']]);
        $this->member($registration, ['is_captain' => false, 'sort_order' => 3, 'answers' => [(int) $nickname->id => 'SiKetiga', (int) $position->id => 'Duelist']]);

        $component = Livewire::actingAs($tenant)->test(EventDetail::class, ['uid' => $event->uid]);

        $component->call('setTab', 'peserta')
            ->assertSee('Tim Garuda Esports')
            ->assertSee('3 anggota')
            ->assertSee('Cash')
            ->assertSee('doni@example.test');

        $component->call('showParticipantDetail', $registration->uid)
            ->assertSet('selectedRegistrationUid', $registration->uid)
            ->assertSee('Detail Peserta')
            ->assertSee('Tim Garuda Esports')
            ->assertSee('Divisi')
            ->assertSee('Competitive')
            ->assertSee('Daftar Anggota (3)')
            ->assertSee('Nickname')
            ->assertSee('Posisi')
            ->assertSee('SiKapten')
            ->assertSee('SiPertama')
            ->assertSee('SiKetiga')
            ->assertSee('Kapten')
            ->assertSee('Cash');
    }

    public function test_registration_from_another_event_does_not_leak_into_list(): void
    {
        $tenant = $this->tenant();
        $eventA = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);
        $eventB = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        $buyerA = $this->buyer();
        $cartA = $this->cart($eventA, $buyerA);
        $this->registration($eventA, $buyerA, $cartA);

        $buyerB = $this->buyer();
        $cartB = $this->cart($eventB, $buyerB, ['invoice' => 'INV-SECRET-B']);
        $this->registration($eventB, $buyerB, $cartB);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $eventA->uid])
            ->call('setTab', 'peserta')
            ->assertSee($cartA->invoice)
            ->assertDontSee('INV-SECRET-B');
    }

    public function test_manipulated_detail_uid_from_another_event_is_rejected(): void
    {
        $tenant = $this->tenant();
        $eventA = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);
        $eventB = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        $buyerB = $this->buyer();
        $cartB = $this->cart($eventB, $buyerB);
        $registrationB = $this->registration($eventB, $buyerB, $cartB);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $eventA->uid])
            ->call('showParticipantDetail', $registrationB->uid)
            ->assertSet('selectedRegistrationUid', null)
            ->assertNotDispatched('open-modal');
    }

    public function test_participant_search_matches_uid_invoice_team_and_user(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => 1,
            'team_max_members' => 3,
        ]);

        $buyerAlpha = $this->buyer(['name' => 'Udin Alpha', 'email' => 'alpha@example.test']);
        $cartAlpha = $this->cart($event, $buyerAlpha, ['invoice' => 'INV-ALPHA']);
        $regAlpha = $this->registration($event, $buyerAlpha, $cartAlpha, ['team_name' => 'Tim Alpha']);

        $buyerBeta = $this->buyer(['name' => 'Euis Beta', 'email' => 'beta@example.test']);
        $cartBeta = $this->cart($event, $buyerBeta, ['invoice' => 'INV-BETA']);
        $this->registration($event, $buyerBeta, $cartBeta, ['team_name' => 'Tim Beta']);

        $component = Livewire::actingAs($tenant)->test(EventDetail::class, ['uid' => $event->uid]);
        $component->call('setTab', 'peserta')->assertSee('INV-ALPHA')->assertSee('INV-BETA');

        // search by team name
        $component->set('searchParticipant', 'Tim Beta')
            ->assertSee('INV-BETA')
            ->assertDontSee('INV-ALPHA');

        // search by invoice
        $component->set('searchParticipant', 'INV-ALPHA')
            ->assertSee('INV-ALPHA')
            ->assertSee('Udin Alpha')
            ->assertDontSee('INV-BETA');

        // search by registration uid
        $component->set('searchParticipant', $regAlpha->uid)
            ->assertSee('INV-ALPHA')
            ->assertDontSee('INV-BETA');

        // search by user email
        $component->set('searchParticipant', 'beta@example.test')
            ->assertSee('INV-BETA')
            ->assertSee('Tim Beta')
            ->assertDontSee('INV-ALPHA');
    }

    public function test_participant_status_filter_is_whitelisted_and_invalid_values_reset(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        foreach ([
            EventRegistration::STATUS_SUCCESS => 'INV-STATUS-S',
            EventRegistration::STATUS_PENDING => 'INV-STATUS-P',
            EventRegistration::STATUS_CANCELLED => 'INV-STATUS-C',
            EventRegistration::STATUS_EXPIRED => 'INV-STATUS-E',
        ] as $status => $invoice) {
            $buyer = $this->buyer();
            $cart = $this->cart($event, $buyer, ['invoice' => $invoice]);
            $this->registration($event, $buyer, $cart, ['status' => $status]);
        }

        $component = Livewire::actingAs($tenant)->test(EventDetail::class, ['uid' => $event->uid]);
        $component->call('setTab', 'peserta')
            ->assertSee('INV-STATUS-S')
            ->assertSee('INV-STATUS-P')
            ->assertSee('INV-STATUS-C')
            ->assertSee('INV-STATUS-E');

        $component->set('filterParticipantStatus', EventRegistration::STATUS_SUCCESS)
            ->assertSee('INV-STATUS-S')
            ->assertDontSee('INV-STATUS-P')
            ->assertDontSee('INV-STATUS-C')
            ->assertDontSee('INV-STATUS-E');

        // arbitrary value must be rejected server-side and reset to all.
        $component->set('filterParticipantStatus', 'HACKED')
            ->assertSet('filterParticipantStatus', 'all')
            ->assertSee('INV-STATUS-S')
            ->assertSee('INV-STATUS-P')
            ->assertSee('INV-STATUS-C')
            ->assertSee('INV-STATUS-E');
    }

    public function test_participant_pagination_uses_separate_state_from_transactions(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        for ($index = 0; $index < 12; $index++) {
            $buyer = $this->buyer();
            $cart = $this->cart($event, $buyer, ['invoice' => 'INV-PAGE-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT)]);
            $this->registration($event, $buyer, $cart);
        }

        $component = Livewire::actingAs($tenant)->test(EventDetail::class, ['uid' => $event->uid]);

        // Participants paginate on their own page name.
        $component->call('setTab', 'peserta');
        $component->call('nextPage', 'pesertaPage');
        $component->assertSet('paginators.pesertaPage', 2);

        // Changing a transaction filter must not reset the participant page state.
        $component->set('filterPayment', 'cash')
            ->assertSet('paginators.pesertaPage', 2);

        // Transaction pagination uses the default page and stays isolated.
        $component->call('setTab', 'transaksi');
        $component->call('gotoPage', 2);
        $component->assertSet('paginators.page', 2);

        // Changing a participant filter must not reset the transaction page state.
        $component->set('searchParticipant', 'noop')
            ->assertSet('paginators.page', 2);
    }

    public function test_payment_method_and_status_come_from_authoritative_cart_and_registration(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        // Cash registration -> Cash
        $cashBuyer = $this->buyer();
        $cashCart = $this->cart($event, $cashBuyer, ['invoice' => 'INV-PAY-CASH', 'payment_type' => 'cash']);
        $this->registration($event, $cashBuyer, $cashCart, ['status' => EventRegistration::STATUS_PENDING]);

        // Online gateway registration -> gateway label from cart
        $gateway = $this->gateway('bank_transfer', 'Bank Transfer');
        $onlineBuyer = $this->buyer();
        $onlineCart = $this->cart($event, $onlineBuyer, [
            'invoice' => 'INV-PAY-ONLINE',
            'payment_type' => 'bank_transfer',
            'payment_gateway_id' => $gateway->id,
            'status' => Cart::STATUS_SUCCESS,
            'gross_amount' => 200000,
        ]);
        $onlineRegistration = $this->registration($event, $onlineBuyer, $onlineCart);

        $component = Livewire::actingAs($tenant)->test(EventDetail::class, ['uid' => $event->uid]);
        $component->call('setTab', 'peserta')
            ->assertSee('INV-PAY-CASH')
            ->assertSee('Cash')
            ->assertSee('PENDING')
            ->assertSee('INV-PAY-ONLINE')
            ->assertSee('Bank Transfer')
            ->assertSee('SUCCESS');

        $component->call('showParticipantDetail', $onlineRegistration->uid)
            ->assertSee('Rp 200.000')
            ->assertSee('Bank Transfer')
            ->assertSee('SUCCESS');
    }

    public function test_team_registration_list_shows_captain_from_authoritative_members(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => 2,
            'team_max_members' => 5,
        ]);
        $nickname = $this->field($event, ['label' => 'Nickname', 'type' => 'text', 'scope' => 'member', 'sort_order' => 1]);

        $buyer = $this->buyer();
        $cart = $this->cart($event, $buyer);
        $registration = $this->registration($event, $buyer, $cart, ['team_name' => 'Tim Kapten List']);
        $this->member($registration, ['is_captain' => false, 'sort_order' => 1, 'answers' => [(int) $nickname->id => 'Pertama']]);
        $this->member($registration, ['is_captain' => true, 'sort_order' => 2, 'answers' => [(int) $nickname->id => 'SangKapten']]);
        $this->member($registration, ['is_captain' => false, 'sort_order' => 3, 'answers' => [(int) $nickname->id => 'Ketiga']]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('setTab', 'peserta')
            ->assertSee('Tim Kapten List')
            ->assertSeeHtml('data-captain="member"')
            ->assertSee('Kapten: Anggota 2')
            ->assertSee('Nickname: SangKapten');
    }

    public function test_individual_registration_list_has_no_captain(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, ['registration_mode' => Event::REGISTRATION_MODE_INDIVIDUAL]);

        $buyer = $this->buyer(['name' => 'Individu Tanpa Tim', 'email' => 'individu@example.test']);
        $cart = $this->cart($event, $buyer);
        $this->registration($event, $buyer, $cart);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('setTab', 'peserta')
            ->assertSeeHtml('data-captain="none"')
            ->assertDontSeeHtml('data-captain="member"')
            ->assertDontSee('Kapten: Anggota');
    }

    public function test_team_without_captain_registration_list_shows_dash(): void
    {
        $tenant = $this->tenant();
        $event = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => 1,
            'team_max_members' => 3,
        ]);

        $buyer = $this->buyer();
        $cart = $this->cart($event, $buyer);
        $registration = $this->registration($event, $buyer, $cart, ['team_name' => 'Tim Tanpa Kapten']);
        $this->member($registration, ['is_captain' => false, 'sort_order' => 1]);
        $this->member($registration, ['is_captain' => false, 'sort_order' => 2]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('setTab', 'peserta')
            ->assertSee('Tim Tanpa Kapten')
            ->assertSeeHtml('data-captain="none"')
            ->assertDontSee('Kapten: Anggota');
    }

    public function test_captain_from_registration_of_another_event_does_not_leak(): void
    {
        $tenant = $this->tenant();
        $eventA = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => 1,
            'team_max_members' => 3,
        ]);
        $eventB = $this->event($tenant, [
            'registration_mode' => Event::REGISTRATION_MODE_TEAM,
            'team_min_members' => 1,
            'team_max_members' => 3,
        ]);
        $nicknameA = $this->field($eventA, ['label' => 'Nickname', 'type' => 'text', 'scope' => 'member', 'sort_order' => 1]);
        $nicknameB = $this->field($eventB, ['label' => 'Nickname', 'type' => 'text', 'scope' => 'member', 'sort_order' => 1]);

        $buyerA = $this->buyer();
        $cartA = $this->cart($eventA, $buyerA);
        $registrationA = $this->registration($eventA, $buyerA, $cartA, ['team_name' => 'Tim Event A']);
        $this->member($registrationA, ['is_captain' => true, 'sort_order' => 1, 'answers' => [(int) $nicknameA->id => 'KaptenEventA']]);

        $buyerB = $this->buyer();
        $cartB = $this->cart($eventB, $buyerB);
        $registrationB = $this->registration($eventB, $buyerB, $cartB, ['team_name' => 'Tim Rahasia B']);
        $this->member($registrationB, ['is_captain' => true, 'sort_order' => 1, 'answers' => [(int) $nicknameB->id => 'KaptenRahasiaB']]);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $eventA->uid])
            ->call('setTab', 'peserta')
            ->assertSee('Tim Event A')
            ->assertSee('KaptenEventA')
            ->assertDontSee('Tim Rahasia B')
            ->assertDontSee('KaptenRahasiaB');
    }

    private function tenant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Tenant Peserta',
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
            'event' => 'Participant Dashboard Event '.$uid,
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
            'deskripsi' => 'Deskripsi event peserta',
            'map' => 'https://maps.google.com/?q=istora',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'participant-dashboard-'.Str::lower(Str::random(8)),
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
}
