<?php

namespace Tests\Feature;

use App\Http\Controllers\TransactionController;
use App\Models\Cart;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventRegistrationField;
use App\Models\HargaCart;
use App\Models\User;
use App\Services\Registrations\CheckoutRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckoutRegistrationIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('services.midtrans.serverKey', 'test-server-key');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
    }

    public function test_ticketing_flow_does_not_create_registration(): void
    {
        $cart = $this->cart($this->event(Event::REGISTRATION_MODE_TICKETING));
        $this->cartItem($cart, 2);

        $this->assertNull($this->service()->persist($cart, ['registration_answers' => ['forged' => 'value']]));
        $this->assertDatabaseCount('event_registrations', 0);
    }

    public function test_individual_registration_persists_strictly_normalized_answers(): void
    {
        $event = $this->event(Event::REGISTRATION_MODE_INDIVIDUAL);
        $name = $this->field($event, 'Name', 'text', 'registration', true);
        $role = $this->field($event, 'Role', 'select', 'registration', false, ['A', 'B']);
        $cart = $this->cart($event);
        $this->cartItem($cart, 1);

        $registration = $this->service()->persist($cart, [
            'event_uid' => 'forged-event',
            'user_uid' => 'forged-user',
            'registration_answers' => [(string) $name->id => ' <b>Alice</b> ', (string) $role->id => 'A'],
        ]);

        $this->assertSame($cart->uid, $registration->cart_uid);
        $this->assertSame($cart->invoice, $registration->invoice);
        $this->assertSame($cart->event_uid, $registration->event_uid);
        $this->assertSame($cart->user_uid, $registration->user_uid);
        $this->assertSame(EventRegistration::STATUS_PENDING, $registration->status);
        $this->assertSame('Alice', $registration->answers[$name->id]);
        $this->assertSame('A', $registration->answers[$role->id]);
        $this->assertNull($registration->team_name);
        $this->assertCount(0, $registration->members);
    }

    public function test_individual_registration_rejects_required_unknown_foreign_and_member_fields(): void
    {
        $event = $this->event(Event::REGISTRATION_MODE_INDIVIDUAL);
        $required = $this->field($event, 'Name', 'text', 'registration', true);
        $member = $this->field($event, 'Member only', 'text', 'member');
        $foreign = $this->field($this->event(Event::REGISTRATION_MODE_INDIVIDUAL), 'Foreign', 'text', 'registration');
        $cart = $this->cart($event);
        $this->cartItem($cart, 1);

        try {
            $this->service()->persist($cart, ['registration_answers' => [
                (string) $required->id => '',
                (string) $member->id => 'nope',
                (string) $foreign->id => 'nope',
                'not-an-id' => 'nope',
            ]]);
            $this->fail('Expected validation failure.');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $this->assertArrayHasKey("registration_answers.{$required->id}", $errors);
            $this->assertArrayHasKey("registration_answers.{$member->id}", $errors);
            $this->assertArrayHasKey("registration_answers.{$foreign->id}", $errors);
            $this->assertArrayHasKey('registration_answers.not-an-id', $errors);
        }
    }

    public function test_individual_registration_rejects_wrong_field_types(): void
    {
        $event = $this->event(Event::REGISTRATION_MODE_INDIVIDUAL);
        $text = $this->field($event, 'Text', 'text', 'registration');
        $textarea = $this->field($event, 'Textarea', 'textarea', 'registration');
        $number = $this->field($event, 'Number', 'number', 'registration');
        $select = $this->field($event, 'Select', 'select', 'registration', false, ['A']);
        $cart = $this->cart($event);
        $this->cartItem($cart, 1);

        $this->expectException(ValidationException::class);
        $this->service()->persist($cart, ['registration_answers' => [
            (string) $text->id => false,
            (string) $textarea->id => [],
            (string) $number->id => false,
            (string) $select->id => [],
        ]]);
    }

    public function test_team_registration_persists_roster_and_registration_answers(): void
    {
        $event = $this->event(Event::REGISTRATION_MODE_TEAM, 2, 3);
        $division = $this->field($event, 'Division', 'text', 'registration', true);
        $nickname = $this->field($event, 'Nickname', 'text', 'member', true);
        $cart = $this->cart($event);
        $this->cartItem($cart, 1);

        $registration = $this->service()->persist($cart, [
            'registration_answers' => [(string) $division->id => 'Open'],
            'team_name' => 'Team Alpha',
            'members' => [
                ['is_captain' => true, 'answers' => [(string) $nickname->id => 'Captain']],
                ['is_captain' => false, 'answers' => [(string) $nickname->id => 'Member']],
            ],
        ]);

        $this->assertSame('Team Alpha', $registration->team_name);
        $this->assertSame('Open', $registration->answers[$division->id]);
        $this->assertCount(2, $registration->fresh()->members);
    }

    public function test_team_registration_accepts_members_above_minimum_up_to_maximum(): void
    {
        $event = $this->event(Event::REGISTRATION_MODE_TEAM, 2, 5);
        $nickname = $this->field($event, 'Nickname', 'text', 'member', true);
        $cart = $this->cart($event);
        $this->cartItem($cart, 1);

        $registration = $this->service()->persist($cart, [
            'team_name' => 'Team Five',
            'members' => [
                ['is_captain' => true, 'answers' => [(string) $nickname->id => 'One']],
                ['is_captain' => false, 'answers' => [(string) $nickname->id => 'Two']],
                ['is_captain' => false, 'answers' => [(string) $nickname->id => 'Three']],
                ['is_captain' => false, 'answers' => [(string) $nickname->id => 'Four']],
                ['is_captain' => false, 'answers' => [(string) $nickname->id => 'Five']],
            ],
        ]);

        $this->assertCount(5, $registration->fresh()->members);
    }

    public function test_team_registration_rejects_invalid_roster(): void
    {
        $event = $this->event(Event::REGISTRATION_MODE_TEAM, 2, 3);
        $cart = $this->cart($event);
        $this->cartItem($cart, 1);

        $this->expectException(ValidationException::class);
        $this->service()->persist($cart, [
            'team_name' => 'Team Alpha',
            'members' => [['is_captain' => true, 'answers' => []]],
        ]);
    }

    public function test_team_registration_rejects_roster_above_maximum(): void
    {
        $event = $this->event(Event::REGISTRATION_MODE_TEAM, 2, 5);
        $cart = $this->cart($event);
        $this->cartItem($cart, 1);

        $this->expectException(ValidationException::class);
        $this->service()->persist($cart, [
            'team_name' => 'Too Many',
            'members' => [
                ['is_captain' => true, 'answers' => []],
                ['is_captain' => false, 'answers' => []],
                ['is_captain' => false, 'answers' => []],
                ['is_captain' => false, 'answers' => []],
                ['is_captain' => false, 'answers' => []],
                ['is_captain' => false, 'answers' => []],
            ],
        ]);
    }

    public function test_registration_mode_rejects_quantity_above_one(): void
    {
        $cart = $this->cart($this->event(Event::REGISTRATION_MODE_INDIVIDUAL));
        $this->cartItem($cart, 2);

        $this->expectException(ValidationException::class);
        $this->service()->persist($cart, ['registration_answers' => []]);
    }

    public function test_retry_updates_existing_registration_without_duplication(): void
    {
        $event = $this->event(Event::REGISTRATION_MODE_TEAM, 1, 2);
        $member = $this->field($event, 'Name', 'text', 'member', true);
        $cart = $this->cart($event);
        $this->cartItem($cart, 1);

        $first = $this->service()->persist($cart, [
            'team_name' => 'First',
            'members' => [['is_captain' => true, 'answers' => [(string) $member->id => 'One']]],
        ]);
        $second = $this->service()->persist($cart, [
            'team_name' => 'Second',
            'members' => [['is_captain' => true, 'answers' => [(string) $member->id => 'Two']]],
        ]);

        $this->assertSame($first->uid, $second->uid);
        $this->assertDatabaseCount('event_registrations', 1);
        $this->assertDatabaseCount('event_registration_members', 1);
        $this->assertSame('Second', $second->fresh()->team_name);
    }

    public function test_registration_status_follows_payment_status_without_creating_another_registration(): void
    {
        $cart = $this->cart($this->event(Event::REGISTRATION_MODE_INDIVIDUAL));
        $this->cartItem($cart, 1);
        $this->service()->persist($cart, ['registration_answers' => []]);

        foreach ([EventRegistration::STATUS_PENDING, EventRegistration::STATUS_SUCCESS, EventRegistration::STATUS_CANCELLED, EventRegistration::STATUS_EXPIRED] as $status) {
            $this->service()->syncStatus($cart, $status);
            $this->assertSame($status, EventRegistration::where('cart_uid', $cart->uid)->value('status'));
        }

        $this->assertDatabaseCount('event_registrations', 1);
    }

    public function test_pending_callback_retry_keeps_registration_unique_and_pending(): void
    {
        $cart = $this->cart($this->event(Event::REGISTRATION_MODE_INDIVIDUAL), [
            'status' => Cart::STATUS_PENDING,
            'gross_amount' => 100000,
        ]);
        $this->cartItem($cart, 1);
        $this->service()->persist($cart, ['registration_answers' => []]);
        DB::table('transactions')->insert([
            'uid' => $cart->uid, 'user_uid' => $cart->user_uid, 'event_uid' => $cart->event_uid,
            'amount' => '100000', 'gross_amount' => 100000, 'invoice' => $cart->invoice,
            'status_transaksi' => Cart::STATUS_PENDING, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $payload = [
            'transaction_status' => 'pending', 'payment_type' => 'bca_va', 'fraud_status' => 'accept',
            'order_id' => $cart->invoice, 'status_code' => '201', 'gross_amount' => '100000.00',
            'signature_key' => hash('sha512', $cart->invoice.'201100000.00test-server-key'),
        ];

        $this->postJson('/api/callback', $payload)->assertOk();
        $this->postJson('/api/callback', $payload)->assertOk();

        $this->assertDatabaseCount('event_registrations', 1);
        $this->assertSame(EventRegistration::STATUS_PENDING, EventRegistration::where('cart_uid', $cart->uid)->value('status'));
    }

    public function test_active_payment_link_locks_registration_changes(): void
    {
        $cart = $this->cart($this->event(Event::REGISTRATION_MODE_INDIVIDUAL), [
            'link' => 'https://pay.example.test/snap',
            'payment_link_expires_at' => now()->addMinute(),
        ]);
        $this->cartItem($cart, 1);

        $this->expectException(ValidationException::class);
        $this->service()->persist($cart, ['registration_answers' => []]);
    }

    public function test_registration_validation_error_keeps_registration_team_and_member_input_only(): void
    {
        $request = Request::create('/paynow', 'POST', [
            'ticket_holder_name' => 'Buyer',
            'registration_answers' => ['10' => 'Open'],
            'team_name' => 'Team Alpha',
            'members' => [['is_captain' => '1', 'answers' => ['11' => 'Captain']]],
            'payment_gateway_id' => 99,
            'cart_uid' => 'cart-secret',
        ]);
        $method = new \ReflectionMethod(TransactionController::class, 'recipientInput');
        $method->setAccessible(true);

        session()->flashInput($method->invoke(new TransactionController, $request));

        $this->assertSame(['10' => 'Open'], session()->getOldInput('registration_answers'));
        $this->assertSame('Team Alpha', session()->getOldInput('team_name'));
        $this->assertSame([['is_captain' => '1', 'answers' => ['11' => 'Captain']]], session()->getOldInput('members'));
        $this->assertNull(session()->getOldInput('payment_gateway_id'));
        $this->assertNull(session()->getOldInput('cart_uid'));
    }

    private function service(): CheckoutRegistrationService
    {
        return app(CheckoutRegistrationService::class);
    }

    private function event(string $mode, int $min = 1, int $max = 1): Event
    {
        return Event::create([
            'uid' => (string) Str::uuid(), 'event' => 'Registration Event', 'registration_mode' => $mode,
            'team_min_members' => $min, 'team_max_members' => $max,
        ]);
    }

    private function cart(Event $event, array $overrides = []): Cart
    {
        $user = User::first() ?? User::create(['uid' => (string) Str::uuid(), 'name' => 'Buyer', 'email' => 'buyer@example.test', 'password' => 'secret']);

        return Cart::create(array_merge([
            'uid' => (string) Str::uuid(), 'user_uid' => $user->uid, 'event_uid' => $event->uid,
            'invoice' => 'INV-'.Str::upper(Str::random(8)), 'status' => Cart::STATUS_RESERVED,
        ], $overrides));
    }

    private function cartItem(Cart $cart, int $quantity): void
    {
        HargaCart::create(['uid' => $cart->uid, 'event_uid' => $cart->event_uid, 'quantity' => $quantity, 'harga_ticket' => 100000]);
    }

    private function field(Event $event, string $label, string $type, string $scope, bool $required = false, ?array $options = null): EventRegistrationField
    {
        return EventRegistrationField::create([
            'event_uid' => $event->uid, 'label' => $label, 'type' => $type, 'scope' => $scope,
            'is_required' => $required, 'options' => $options, 'sort_order' => 1,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', fn ($table) => $table->id());
        Schema::table('users', function ($table) {
            $table->string('uid');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('events', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('event');
            $table->string('registration_mode')->nullable();
            $table->unsignedInteger('team_min_members')->nullable();
            $table->unsignedInteger('team_max_members')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('invoice');
            $table->string('status');
            $table->text('link')->nullable();
            $table->timestamp('payment_link_expires_at')->nullable();
            $table->unsignedBigInteger('gross_amount')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('midtrans_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('event_uid');
            $table->unsignedInteger('quantity');
            $table->integer('harga_ticket');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('event_registration_fields', function ($table) {
            $table->id();
            $table->string('event_uid');
            $table->string('label');
            $table->string('type');
            $table->string('scope');
            $table->boolean('is_required')->default(false);
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('event_registrations', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('cart_uid')->unique();
            $table->string('invoice')->index();
            $table->string('event_uid');
            $table->string('user_uid');
            $table->string('registration_mode');
            $table->string('status');
            $table->string('team_name')->nullable();
            $table->json('answers')->nullable();
            $table->timestamps();
        });
        Schema::create('event_registration_members', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('registration_uid');
            $table->boolean('is_captain');
            $table->unsignedInteger('sort_order');
            $table->json('answers')->nullable();
            $table->timestamps();
        });
        Schema::create('transactions', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('amount');
            $table->unsignedBigInteger('gross_amount')->nullable();
            $table->string('invoice');
            $table->string('status_transaksi');
            $table->string('payment_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
