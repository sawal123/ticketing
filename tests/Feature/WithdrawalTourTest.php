<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\PenarikanIndex;
use App\Livewire\Tutorials\InteractiveTour;
use App\Models\User;
use App\Services\Tutorials\TutorialProgressService;
use App\Services\Withdrawals\WithdrawalBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class WithdrawalTourTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        View::share('logo', [(object) ['logo' => '']]);
        View::share('seo', [(object) ['keyword' => 'Test', 'description' => 'Test']]);
        $this->mock(WithdrawalBalanceService::class, function ($mock) {
            $mock->shouldReceive('grossEarningsFor')->zeroOrMoreTimes()->andReturn(0);
            $mock->shouldReceive('deductedWithdrawalsFor')->zeroOrMoreTimes()->andReturn(0);
        });
    }

    public function test_tenant_renders_a_five_step_withdrawal_tour_without_history(): void
    {
        $tenant = $this->user('penyewa');

        Livewire::actingAs($tenant)
            ->test(PenarikanIndex::class)
            ->assertSee('Tur Penarikan')
            ->assertSee('withdrawal.overview', false)
            ->assertSee('Belum ada riwayat penarikan.')
            ->assertSee('data-tour="withdrawal-summary"', false)
            ->assertSee('data-tour="withdrawal-create"', false)
            ->assertSee('data-tour="withdrawal-history"', false)
            ->assertSee('PENDING. Setelah admin mulai memproses, status menjadi PROCESSING, lalu SUCCESS')
            ->assertSee('bukti transfer dapat dibuka jika admin sudah mengunggahnya');

        $component = file_get_contents(app_path('Livewire/Dashboard/PenarikanIndex.php'));
        $method = substr($component, strpos($component, 'private function withdrawalTourSteps'), strpos($component, 'public function canViewTransferProof') - strpos($component, 'private function withdrawalTourSteps'));
        $this->assertSame(5, substr_count($method, "'target' =>"));
        $this->assertStringNotContainsString('1x24', $method);
    }

    public function test_admin_and_staff_do_not_receive_the_tenant_withdrawal_tour(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff', ['parent_uid' => $admin->uid]);

        foreach ([$admin, $staff] as $user) {
            Livewire::actingAs($user)
                ->test(PenarikanIndex::class)
                ->assertDontSee('Tur Penarikan')
                ->assertDontSee('withdrawal.overview', false);
        }
    }

    public function test_withdrawal_persistence_uses_its_own_tutorial_key(): void
    {
        $tenant = $this->user('penyewa');
        $steps = [['target' => '[data-tour="fixture"]']];

        $this->mock(TutorialProgressService::class, function ($mock) {
            $mock->shouldReceive('isCompleted')->zeroOrMoreTimes()->andReturnFalse();
            $mock->shouldReceive('isDismissed')->zeroOrMoreTimes()->andReturnFalse();
            $mock->shouldReceive('markCompleted')->once()->withArgs(fn (User $user, string $key) => $key === 'withdrawal.overview');
            $mock->shouldReceive('markDismissed')->once()->withArgs(fn (User $user, string $key) => $key === 'withdrawal.overview');
        });

        Livewire::actingAs($tenant)
            ->test(InteractiveTour::class, ['tutorialKey' => 'event.transactions', 'steps' => $steps])
            ->assertSet('canStart', true);

        Livewire::actingAs($tenant)
            ->test(InteractiveTour::class, ['tutorialKey' => 'withdrawal.overview', 'steps' => $steps])
            ->call('finish')
            ->assertSet('canStart', false);

        Livewire::actingAs($tenant)
            ->test(InteractiveTour::class, ['tutorialKey' => 'withdrawal.overview', 'steps' => $steps])
            ->call('dismiss')
            ->assertSet('canStart', false);
    }

    private function user(string $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Withdrawal Tour User',
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
}
