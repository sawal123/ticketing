<?php

namespace Tests\Feature;

use App\Livewire\Tutorials\InteractiveTour;
use App\Livewire\Tutorials\TutorialManager;
use App\Models\User;
use App\Services\Tutorials\TutorialProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TutorialReplayResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('users', fn ($table) => $table->unique('uid'));
        }
    }

    public function test_tenant_can_reset_only_an_allowed_tutorial_without_affecting_other_users_or_keys(): void
    {
        $tenant = $this->user('penyewa');
        $other = $this->user('penyewa');
        $service = app(TutorialProgressService::class);
        $service->markCompleted($tenant, 'dashboard.overview');
        $service->markDismissed($tenant, 'event.setup');
        $service->markCompleted($other, 'dashboard.overview');
        Livewire::actingAs($tenant)->test(TutorialManager::class)->assertSee('Getting Started')->assertSee('Withdrawal Tour')->call('resetTutorial', 'dashboard.overview');
        $this->assertFalse($service->isCompleted($tenant, 'dashboard.overview'));
        $this->assertTrue($service->isDismissed($tenant, 'event.setup'));
        $this->assertTrue($service->isCompleted($other, 'dashboard.overview'));
        Livewire::actingAs($tenant)->test(TutorialManager::class)->call('resetTutorial', 'unknown.key');
    }

    public function test_replay_resets_locked_key_and_finish_or_dismiss_persists_again(): void
    {
        $tenant = $this->user('penyewa');
        $steps = [['target' => '[data-tour="fixture"]']];
        $service = app(TutorialProgressService::class);
        $service->markCompleted($tenant, 'dashboard.overview');
        Livewire::actingAs($tenant)->test(InteractiveTour::class, ['tutorialKey' => 'dashboard.overview', 'steps' => $steps])->call('replay')->assertSet('canStart', true)->call('finish')->assertSet('canStart', false);
        $this->assertTrue($service->isCompleted($tenant, 'dashboard.overview'));
        Livewire::actingAs($tenant)->test(InteractiveTour::class, ['tutorialKey' => 'dashboard.overview', 'steps' => $steps])->call('replay')->call('dismiss');
        $this->assertTrue($service->isDismissed($tenant, 'dashboard.overview'));
    }

    public function test_staff_and_admin_cannot_mount_manager_and_manual_triggers_use_replay(): void
    {
        foreach (['staff', 'admin'] as $role) {
            Livewire::actingAs($this->user($role))->test(TutorialManager::class)->assertForbidden();
        }
        foreach (['demo-index.blade.php', 'event-create.blade.php', 'event-detail.blade.php', 'penarikan-index.blade.php'] as $view) {
            $this->assertStringContainsString('replay-tour', file_get_contents(resource_path('views/livewire/dashboard/'.$view)));
        }
        $this->assertStringContainsString('start-tour', file_get_contents(resource_path('js/interactive-tour.js')));
    }

    private function user(string $role): User
    {
        return User::factory()->create(['uid' => (string) Str::uuid(), 'email' => fake()->unique()->safeEmail(), 'role' => $role, 'gambar' => '-', 'nomor' => '08123456789', 'birthday' => '2000-01-01', 'alamat' => 'Jl', 'kota' => 'Jakarta', 'gender' => 'pria', 'password' => Hash::make('Password123')]);
    }
}
