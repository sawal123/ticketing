<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\DemoIndex;
use App\Models\Agreement;
use App\Models\Event;
use App\Models\EventBankAccount;
use App\Models\EventPaymentGateway;
use App\Models\Harga;
use App\Models\PaymentGateway;
use App\Models\TutorialProgress;
use App\Models\User;
use App\Services\Tutorials\GettingStartedChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GettingStartedChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_user_without_data_has_zero_of_six_progress(): void
    {
        $tenant = $this->tenant();

        $checklist = app(GettingStartedChecklistService::class)->buildForUser($tenant);

        $this->assertTrue($checklist['visible']);
        $this->assertSame(0, $checklist['completed_count']);
        $this->assertSame(0, $checklist['progress_percentage']);
        $this->assertSame('profile', $checklist['next_step']['key']);
        $this->assertSame(route('dashboard.settings'), $checklist['primary_url']);
    }

    public function test_progress_uses_authoritative_data_and_cta_points_to_first_incomplete_step(): void
    {
        $tenant = $this->tenant([
            'nomor' => '08123456789',
            'kota' => 'Jakarta',
            'alamat' => 'Jl. Sudirman No. 1',
        ]);
        $event = $this->event($tenant);
        $this->bankAccount($event);

        $checklist = app(GettingStartedChecklistService::class)->buildForUser($tenant);
        $steps = collect($checklist['steps'])->keyBy('key');

        $this->assertSame(3, $checklist['completed_count']);
        $this->assertTrue($steps['profile']['completed']);
        $this->assertTrue($steps['event']['completed']);
        $this->assertTrue($steps['bank-account']['completed']);
        $this->assertFalse($steps['mou']['completed']);
        $this->assertFalse($steps['ticket']['completed']);
        $this->assertFalse($steps['payment-methods']['completed']);
        $this->assertSame('mou', $checklist['next_step']['key']);
        $this->assertSame(
            route('dashboard.event.detail', ['uid' => $event->uid, 'activeTab' => 'mou']),
            $checklist['primary_url']
        );
    }

    public function test_data_from_other_users_does_not_affect_progress(): void
    {
        $tenantA = $this->tenant(['email' => 'tenant-a@example.test']);
        $tenantB = $this->tenant([
            'email' => 'tenant-b@example.test',
            'nomor' => '081111111111',
            'kota' => 'Bandung',
            'alamat' => 'Jl. Braga No. 2',
        ]);
        $eventB = $this->event($tenantB);
        $this->bankAccount($eventB);
        $this->completedMou($eventB);
        $this->ticket($eventB);
        $this->activePaymentMethod($eventB);

        $checklist = app(GettingStartedChecklistService::class)->buildForUser($tenantA);

        $this->assertTrue($checklist['visible']);
        $this->assertSame(0, $checklist['completed_count']);
        $this->assertNull($checklist['event_uid']);
    }

    public function test_event_specific_steps_do_not_mix_data_from_different_events(): void
    {
        $tenant = $this->tenant([
            'nomor' => '08123456789',
            'kota' => 'Jakarta',
            'alamat' => 'Jl. Sudirman No. 1',
        ]);

        $completeOlderEvent = $this->event($tenant, ['event' => 'Event Lama']);
        $this->bankAccount($completeOlderEvent);
        $this->completedMou($completeOlderEvent);
        $this->ticket($completeOlderEvent);
        $this->activePaymentMethod($completeOlderEvent);

        $currentOnboardingEvent = $this->event($tenant, ['event' => 'Event Baru']);
        $this->bankAccount($currentOnboardingEvent);

        $checklist = app(GettingStartedChecklistService::class)->buildForUser($tenant);
        $steps = collect($checklist['steps'])->keyBy('key');

        $this->assertSame($currentOnboardingEvent->uid, $checklist['event_uid']);
        $this->assertSame(3, $checklist['completed_count']);
        $this->assertTrue($steps['bank-account']['completed']);
        $this->assertFalse($steps['mou']['completed']);
        $this->assertFalse($steps['ticket']['completed']);
        $this->assertFalse($steps['payment-methods']['completed']);
    }

    public function test_all_steps_complete_marks_tutorial_completed_and_hides_card(): void
    {
        $tenant = $this->tenant([
            'nomor' => '08123456789',
            'kota' => 'Jakarta',
            'alamat' => 'Jl. Sudirman No. 1',
        ]);
        $event = $this->event($tenant);
        $this->bankAccount($event);
        $this->completedMou($event);
        $this->ticket($event);
        $this->activePaymentMethod($event);

        $checklist = app(GettingStartedChecklistService::class)->buildForUser($tenant);

        $this->assertFalse($checklist['visible']);
        $this->assertTrue($checklist['completed']);
        $this->assertSame(6, $checklist['completed_count']);
        $this->assertDatabaseHas('tutorial_progress', [
            'user_uid' => $tenant->uid,
            'tutorial_key' => GettingStartedChecklistService::TUTORIAL_KEY,
        ]);
        $this->assertNotNull(TutorialProgress::query()
            ->where('user_uid', $tenant->uid)
            ->where('tutorial_key', GettingStartedChecklistService::TUTORIAL_KEY)
            ->value('completed_at'));

        Livewire::actingAs($tenant)
            ->test(DemoIndex::class)
            ->assertDontSee('Mulai Menggunakan Gotik')
            ->assertSee('Dashboard');
    }

    public function test_dismiss_hides_card_on_dashboard(): void
    {
        $tenant = $this->tenant();

        Livewire::actingAs($tenant)
            ->test(DemoIndex::class)
            ->assertSee('Mulai Menggunakan Gotik')
            ->call('dismissGettingStartedChecklist')
            ->assertDontSee('Mulai Menggunakan Gotik')
            ->assertSee('Dashboard');

        $this->assertDatabaseHas('tutorial_progress', [
            'user_uid' => $tenant->uid,
            'tutorial_key' => GettingStartedChecklistService::TUTORIAL_KEY,
        ]);
        $this->assertNotNull(TutorialProgress::query()
            ->where('user_uid', $tenant->uid)
            ->where('tutorial_key', GettingStartedChecklistService::TUTORIAL_KEY)
            ->value('dismissed_at'));
    }

    private function tenant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Tenant Checklist',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'nomor' => '-',
            'birthday' => '2000-01-01',
            'alamat' => '-',
            'kota' => '-',
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
            'event' => 'Checklist Event '.$uid,
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'status' => 'draft',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Deskripsi event',
            'map' => 'https://example.test/map',
            'pajak' => 0,
            'start_sale' => now()->addDay()->format('Y-m-d H:i:s'),
            'slug' => 'checklist-event-'.$uid,
            'konfirmasi' => null,
        ], $overrides));
    }

    private function bankAccount(Event $event, array $overrides = []): EventBankAccount
    {
        $path = 'private/bank-books/'.$event->uid.'.pdf';
        Storage::disk('local')->put($path, 'bank-book');

        return EventBankAccount::create(array_merge([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Central Asia',
            'account_number' => '1234567890',
            'account_holder_name' => 'PT Checklist',
            'bank_book_path' => $path,
            'bank_book_original_name' => 'bank-book.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'pending',
        ], $overrides));
    }

    private function completedMou(Event $event, array $overrides = []): Agreement
    {
        return Agreement::create(array_merge([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $event->user_uid,
            'type' => Agreement::TYPE_MOU,
            'version' => 1,
            'status' => Agreement::STATUS_COMPLETED,
            'created_by' => $event->user_uid,
            'completed_at' => now(),
        ], $overrides));
    }

    private function ticket(Event $event, array $overrides = []): Harga
    {
        return Harga::create(array_merge([
            'uid' => $event->uid,
            'kategori' => 'Regular',
            'qty' => 100,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => 100000,
            'status' => 'active',
        ], $overrides));
    }

    private function activePaymentMethod(Event $event, array $gatewayOverrides = [], array $configOverrides = []): EventPaymentGateway
    {
        $gateway = PaymentGateway::create(array_merge([
            'payment' => 'Virtual Account',
            'category' => 'bank_transfer',
            'biaya' => 0,
            'biaya_type' => 'rupiah',
            'default_fee_fixed' => 4000,
            'default_fee_percent' => 0,
            'midtrans_code' => 'bca_va',
            'icon' => 'bank.svg',
            'is_active' => true,
            'slug' => 'virtual-account-'.Str::random(5),
        ], $gatewayOverrides));

        return EventPaymentGateway::create(array_merge([
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => true,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
            'fee_fixed' => null,
            'fee_percent' => null,
        ], $configOverrides));
    }
}
