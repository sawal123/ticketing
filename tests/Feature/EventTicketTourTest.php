<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventCreate;
use App\Livewire\Dashboard\EventDetail;
use App\Livewire\Tutorials\InteractiveTour;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class EventTicketTourTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_tenant_event_create_and_edit_render_the_same_six_step_event_tour(): void
    {
        $tenant = $this->user('penyewa');
        $event = $this->event($tenant);

        foreach ([null, $event->uid] as $eventUid) {
            $tour = Livewire::actingAs($tenant)
                ->test(EventCreate::class, $eventUid ? ['uid' => $eventUid] : [])
                ->assertSee('Tur Event')
                ->assertSee('event.setup', false)
                ->assertSee('Informasi Event')
                ->assertSee('Dokumen Penyelenggara');

            foreach ([
                'event-info',
                'event-organizer',
                'event-schedule',
                'event-bank-account',
                'event-documents',
                'event-location',
            ] as $target) {
                $tour->assertSee('data-tour="'.$target.'"', false);
            }
        }

        $component = file_get_contents(app_path('Livewire/Dashboard/EventCreate.php'));
        $this->assertSame(6, substr_count($component, "'target' =>"));
    }

    public function test_ticket_tour_is_only_rendered_on_ticket_tab_and_remains_valid_without_tickets(): void
    {
        $tenant = $this->user('penyewa');
        $event = $this->event($tenant);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->assertDontSee('Tur Tiket')
            ->assertDontSee('event.tickets', false)
            ->set('activeTab', 'tiket')
            ->assertSee('Tur Tiket')
            ->assertSee('event.tickets', false)
            ->assertSee('Belum ada kategori tiket.')
            ->assertSee('data-tour="ticket-tab"', false)
            ->assertSee('data-tour="ticket-list"', false)
            ->assertSee('data-tour="ticket-add"', false);

        $component = file_get_contents(app_path('Livewire/Dashboard/EventDetail.php'));
        $this->assertSame(3, substr_count($component, "'target' =>"));
    }

    public function test_admin_and_staff_do_not_receive_tenant_tours(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff', ['parent_uid' => $admin->uid]);
        $event = $this->event($admin);

        Livewire::actingAs($admin)
            ->test(EventCreate::class)
            ->assertDontSee('Tur Event')
            ->assertDontSee('event.setup', false);

        Livewire::actingAs($staff)
            ->test(EventCreate::class)
            ->assertDontSee('Tur Event')
            ->assertDontSee('event.setup', false);

        Livewire::actingAs($staff)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'tiket')
            ->assertDontSee('Tur Tiket')
            ->assertDontSee('event.tickets', false);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->set('activeTab', 'tiket')
            ->assertDontSee('Tur Tiket')
            ->assertDontSee('event.tickets', false);
    }

    public function test_event_setup_and_ticket_tutorial_progress_are_isolated(): void
    {
        $tenant = $this->user('penyewa');
        $steps = [['target' => '[data-tour="fixture"]', 'title' => 'Fixture', 'description' => 'Fixture']];

        Livewire::actingAs($tenant)
            ->test(InteractiveTour::class, ['tutorialKey' => 'event.setup', 'steps' => $steps])
            ->call('finish')
            ->assertSet('canStart', false);

        Livewire::actingAs($tenant)
            ->test(InteractiveTour::class, ['tutorialKey' => 'event.tickets', 'steps' => $steps])
            ->assertSet('canStart', true)
            ->call('dismiss')
            ->assertSet('canStart', false);
    }

    private function user(string $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Event Tour User',
            'email' => fake()->unique()->safeEmail(),
            'role' => $role,
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Jl. Sudirman No. 1',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function event(User $owner): Event
    {
        $uid = (string) Str::uuid();

        return Event::create([
            'uid' => $uid,
            'user_uid' => $owner->uid,
            'event' => 'Event Tour '.$uid,
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
            'deskripsi' => 'Deskripsi event tour',
            'map' => 'https://maps.google.com/?q=istora',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'event-tour-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
        ]);
    }
}
