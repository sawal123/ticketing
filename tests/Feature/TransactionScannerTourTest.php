<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\EventDetail;
use App\Livewire\Tutorials\InteractiveTour;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionScannerTourTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', env('DB_CONNECTION', 'mysql'));
        DB::setDefaultConnection('mysql');
        DB::purge('sqlite');
        DB::purge('mysql');

        Storage::fake('local');
        Storage::fake('public');
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
    }

    public function test_tenant_transaction_tour_only_renders_on_the_transaction_tab_without_transactions(): void
    {
        $tenant = $this->user('penyewa');
        $event = $this->event($tenant);

        Livewire::actingAs($tenant)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->assertDontSee('Tur Transaksi')
            ->assertDontSee('event.transactions', false)
            ->set('activeTab', 'transaksi')
            ->assertSee('Tur Transaksi')
            ->assertSee('event.transactions', false)
            ->assertSee('Tidak ada transaksi yang sesuai dengan filter.')
            ->assertSee('data-tour="transaction-tab"', false)
            ->assertSee('data-tour="transaction-filters"', false)
            ->assertSee('data-tour="transaction-list"', false)
            ->assertSee('data-tour="transaction-export"', false)
            ->assertSee('Flow Scanner dan Verifikasi')
            ->assertSee('petugas menekan Verifikasi');

        $component = file_get_contents(app_path('Livewire/Dashboard/EventDetail.php'));
        $method = substr(
            $component,
            strpos($component, 'private function transactionTourSteps'),
            strpos($component, 'public function confirmResendEmail') - strpos($component, 'private function transactionTourSteps')
        );
        $this->assertSame(5, substr_count($method, "'target' =>"));
    }

    public function test_admin_and_staff_do_not_receive_the_transaction_tour(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff', ['parent_uid' => $admin->uid]);
        $event = $this->event($admin);

        foreach ([$admin, $staff] as $user) {
            Livewire::actingAs($user)
                ->test(EventDetail::class, ['uid' => $event->uid])
                ->set('activeTab', 'transaksi')
                ->assertDontSee('Tur Transaksi')
                ->assertDontSee('event.transactions', false);
        }
    }

    public function test_transaction_tour_persistence_is_isolated_and_supports_finish_and_dismiss(): void
    {
        $tenant = $this->user('penyewa');
        $steps = [['target' => '[data-tour="fixture"]', 'title' => 'Fixture', 'description' => 'Fixture']];

        Livewire::actingAs($tenant)
            ->test(InteractiveTour::class, ['tutorialKey' => 'event.tickets', 'steps' => $steps])
            ->call('finish')
            ->assertSet('canStart', false);

        Livewire::actingAs($tenant)
            ->test(InteractiveTour::class, ['tutorialKey' => 'event.transactions', 'steps' => $steps])
            ->assertSet('canStart', true)
            ->call('finish')
            ->assertSet('canStart', false);

        Livewire::actingAs($tenant)
            ->test(InteractiveTour::class, ['tutorialKey' => 'dashboard.overview', 'steps' => $steps])
            ->assertSet('canStart', true);

        $otherTenant = $this->user('penyewa');

        Livewire::actingAs($otherTenant)
            ->test(InteractiveTour::class, ['tutorialKey' => 'event.transactions', 'steps' => $steps])
            ->call('dismiss')
            ->assertSet('canStart', false);

        Livewire::actingAs($otherTenant)
            ->test(InteractiveTour::class, ['tutorialKey' => 'event.transactions', 'steps' => $steps])
            ->assertSet('canStart', false);
    }

    private function user(string $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Transaction Tour User',
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
            'event' => 'Transaction Tour '.$uid,
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
            'deskripsi' => 'Deskripsi transaction tour',
            'map' => 'https://maps.google.com/?q=istora',
            'start_sale' => '2026-09-01 10:00:00',
            'slug' => 'transaction-tour-'.Str::lower(Str::random(8)),
            'konfirmasi' => null,
        ]);
    }
}
