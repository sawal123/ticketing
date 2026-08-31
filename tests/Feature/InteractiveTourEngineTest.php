<?php

namespace Tests\Feature;

use App\Livewire\Tutorials\InteractiveTour;
use App\Models\TutorialProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class InteractiveTourEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_tour_renders_reusable_steps_and_accessible_controls(): void
    {
        $tour = Livewire::actingAs($this->user())
            ->test(InteractiveTour::class, [
                'tutorialKey' => 'dashboard.overview',
                'steps' => $this->steps(),
            ]);

        $tour->assertSet('canStart', true)
            ->assertSee('interactiveTour', false)
            ->assertSee('Ringkasan Statistik')
            ->assertSee('x-text="progressLabel"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('id="interactive-tour-', false)
            ->assertSee('aria-labelledby="interactive-tour-', false)
            ->assertSee('aria-describedby="interactive-tour-', false)
            ->assertSee('Kembali')
            ->assertSee('Lanjut')
            ->assertSee('Lewati');
    }

    public function test_finish_marks_only_the_active_tutorial_completed(): void
    {
        $user = $this->user();

        Livewire::actingAs($user)
            ->test(InteractiveTour::class, ['tutorialKey' => 'dashboard.overview', 'steps' => $this->steps()])
            ->call('finish')
            ->assertSet('canStart', false);

        $this->assertDatabaseHas('tutorial_progress', [
            'user_uid' => $user->uid,
            'tutorial_key' => 'dashboard.overview',
        ]);
        $this->assertNotNull(TutorialProgress::query()->where('tutorial_key', 'dashboard.overview')->value('completed_at'));

        Livewire::actingAs($user)
            ->test(InteractiveTour::class, ['tutorialKey' => 'event.tickets', 'steps' => $this->steps()])
            ->assertSet('canStart', true);
    }

    public function test_skip_marks_dismissed_and_blocks_future_starts(): void
    {
        $user = $this->user();

        Livewire::actingAs($user)
            ->test(InteractiveTour::class, ['tutorialKey' => 'dashboard.overview', 'steps' => $this->steps()])
            ->call('dismiss')
            ->assertSet('canStart', false);

        $progress = TutorialProgress::query()->where('tutorial_key', 'dashboard.overview')->firstOrFail();
        $this->assertNotNull($progress->dismissed_at);
        $this->assertNull($progress->completed_at);

        Livewire::actingAs($user)
            ->test(InteractiveTour::class, ['tutorialKey' => 'dashboard.overview', 'steps' => $this->steps()])
            ->assertSet('canStart', false);
    }

    public function test_progress_is_isolated_per_user_and_tutorial_key(): void
    {
        $firstUser = $this->user();
        $secondUser = $this->user(['email' => 'tour-second@example.test']);

        Livewire::actingAs($firstUser)
            ->test(InteractiveTour::class, ['tutorialKey' => 'dashboard.overview', 'steps' => $this->steps()])
            ->call('finish');

        Livewire::actingAs($secondUser)
            ->test(InteractiveTour::class, ['tutorialKey' => 'dashboard.overview', 'steps' => $this->steps()])
            ->assertSet('canStart', true);

        Livewire::actingAs($firstUser)
            ->test(InteractiveTour::class, ['tutorialKey' => 'event.tickets', 'steps' => $this->steps()])
            ->assertSet('canStart', true);
    }

    public function test_missing_target_handling_is_present_in_the_client_engine(): void
    {
        $engine = file_get_contents(resource_path('js/interactive-tour.js'));

        $this->assertStringContainsString('filter((step) => document.querySelector(step.target))', $engine);
        $this->assertStringContainsString('if (this.activeSteps.length === 0)', $engine);
        $this->assertStringContainsString('this.cleanup();', $engine);
        $this->assertStringNotContainsString('$wire.finish()', substr($engine, strpos($engine, 'if (this.activeSteps.length === 0)'), 160));
    }

    public function test_lifecycle_is_initialized_once_and_dialog_ids_are_instance_scoped(): void
    {
        $view = file_get_contents(resource_path('views/livewire/tutorials/interactive-tour.blade.php'));
        $engine = file_get_contents(resource_path('js/interactive-tour.js'));

        $this->assertStringNotContainsString('x-init="init()"', $view);
        $this->assertStringContainsString("'interactive-tour-' . \$this->getId()", $view);
        $this->assertStringNotContainsString('aria-labelledby="interactive-tour-title"', $view);
        $this->assertStringNotContainsString('aria-describedby="interactive-tour-description"', $view);
        $this->assertStringContainsString('if (this.initialized)', $engine);
        $this->assertStringContainsString("window.removeEventListener('start-tour', this.startListener);", $engine);
        $this->assertStringNotContainsString('fixed inset-0 z-[190] bg-slate-950/55', $view);
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'gambar' => '-',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function steps(): array
    {
        return [
            ['target' => '[data-tour="statistics"]', 'title' => 'Ringkasan Statistik', 'description' => 'Lihat statistik utama.', 'placement' => 'bottom'],
            ['target' => '[data-tour="events"]', 'title' => 'Event Aktif', 'description' => 'Lihat event aktif.', 'placement' => 'right'],
            ['target' => '[data-tour="chart"]', 'title' => 'Tren Event', 'description' => 'Lihat tren event.', 'placement' => 'top'],
        ];
    }
}
