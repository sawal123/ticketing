<?php

namespace App\Livewire\Tutorials;

use App\Services\Tutorials\TutorialProgressService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class InteractiveTour extends Component
{
    #[Locked]
    public string $tutorialKey;

    #[Locked]
    public array $steps = [];

    public bool $canStart = false;

    public function mount(string $tutorialKey, array $steps = []): void
    {
        $this->tutorialKey = trim($tutorialKey);
        $this->steps = $this->normalizeSteps($steps);
        $this->refreshAvailability();
    }

    public function finish(): void
    {
        $user = auth()->user();

        if (! $user || ! $this->canStart) {
            return;
        }

        app(TutorialProgressService::class)->markCompleted($user, $this->tutorialKey);
        $this->canStart = false;
    }

    public function dismiss(): void
    {
        $user = auth()->user();

        if (! $user || ! $this->canStart) {
            return;
        }

        app(TutorialProgressService::class)->markDismissed($user, $this->tutorialKey);
        $this->canStart = false;
    }

    public function replay(): bool
    {
        $user = auth()->user();

        if (! $user || $this->tutorialKey === '' || $this->steps === []) {
            return false;
        }

        app(TutorialProgressService::class)->reset($user, $this->tutorialKey);
        $this->canStart = true;

        return true;
    }

    public function render()
    {
        return view('livewire.tutorials.interactive-tour');
    }

    private function refreshAvailability(): void
    {
        $user = auth()->user();

        if (! $user || $this->tutorialKey === '' || $this->steps === []) {
            $this->canStart = false;

            return;
        }

        $progress = app(TutorialProgressService::class);
        $this->canStart = ! $progress->isCompleted($user, $this->tutorialKey)
            && ! $progress->isDismissed($user, $this->tutorialKey);
    }

    private function normalizeSteps(array $steps): array
    {
        return collect($steps)
            ->filter(fn ($step) => is_array($step) && filled($step['target'] ?? null))
            ->map(fn (array $step) => [
                'target' => (string) $step['target'],
                'title' => (string) ($step['title'] ?? ''),
                'description' => (string) ($step['description'] ?? ''),
                'placement' => in_array($step['placement'] ?? null, ['top', 'right', 'bottom', 'left'], true)
                    ? $step['placement']
                    : 'bottom',
            ])
            ->values()
            ->all();
    }
}
