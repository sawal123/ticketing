<?php

namespace App\Services\Tutorials;

use App\Models\Agreement;
use App\Models\Event;
use App\Models\User;
use App\Services\Agreements\AgreementReviewService;
use Illuminate\Support\Collection;

class GettingStartedChecklistService
{
    public const TUTORIAL_KEY = 'dashboard.getting-started';

    public function __construct(
        private readonly AgreementReviewService $agreementReviewService,
        private readonly TutorialProgressService $tutorialProgressService,
    ) {
    }

    public function buildForUser(User $user): array
    {
        if (strtolower((string) $user->role) !== 'penyewa') {
            return $this->hiddenState();
        }

        if ($this->tutorialProgressService->isDismissed($user, self::TUTORIAL_KEY)) {
            return $this->hiddenState(['dismissed' => true]);
        }

        if ($this->tutorialProgressService->isCompleted($user, self::TUTORIAL_KEY)) {
            return $this->hiddenState(['completed' => true]);
        }

        $events = Event::query()
            ->where('user_uid', $user->uid)
            ->with([
                'agreements',
                'bankAccount',
                'currentMouAgreement',
                'eventPaymentGateways.paymentGateway',
                'hargas',
                'organizer',
                'organizerLetter',
                'responsibleIdentityDocument',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $selectedEvent = $this->selectOnboardingEvent($events);
        $reviewItems = $selectedEvent ? $this->reviewItemsFor($selectedEvent) : collect();

        $steps = [
            $this->makeStep(
                key: 'profile',
                label: 'Lengkapi profil penyewa',
                completed: $this->profileIsComplete($user),
                url: route('dashboard.settings')
            ),
            $this->makeStep(
                key: 'event',
                label: 'Buat event',
                completed: $events->isNotEmpty(),
                url: route('dashboard.event.create')
            ),
            $this->makeStep(
                key: 'bank-account',
                label: 'Lengkapi rekening pencairan event',
                completed: $selectedEvent !== null
                    && $this->itemPassed($reviewItems, 'bank_account_available')
                    && $this->itemPassed($reviewItems, 'physical_bank_book_available'),
                url: $this->eventEditUrl($selectedEvent)
            ),
            $this->makeStep(
                key: 'mou',
                label: 'Selesaikan MOU',
                completed: $selectedEvent !== null && $selectedEvent->agreements->contains(
                    fn (Agreement $agreement) => $agreement->type === Agreement::TYPE_MOU
                        && $agreement->status === Agreement::STATUS_COMPLETED
                ),
                url: $this->eventDetailUrl($selectedEvent, 'mou')
            ),
            $this->makeStep(
                key: 'ticket',
                label: 'Buat tiket',
                completed: $selectedEvent !== null && $selectedEvent->hargas->isNotEmpty(),
                url: $this->eventDetailUrl($selectedEvent, 'tiket')
            ),
            $this->makeStep(
                key: 'payment-methods',
                label: 'Atur metode pembayaran',
                completed: $selectedEvent !== null
                    && $this->itemPassed($reviewItems, 'payment_configuration_valid')
                    && $this->itemPassed($reviewItems, 'effective_active_gateway'),
                url: $this->eventDetailUrl($selectedEvent, 'mou')
            ),
        ];

        $completedCount = collect($steps)->where('completed', true)->count();
        $totalSteps = count($steps);
        $progressPercentage = $totalSteps > 0 ? (int) round(($completedCount / $totalSteps) * 100) : 0;
        $nextStep = collect($steps)->firstWhere('completed', false);

        if ($completedCount === $totalSteps) {
            $this->tutorialProgressService->markCompleted($user, self::TUTORIAL_KEY);

            return [
                'visible' => false,
                'dismissed' => false,
                'completed' => true,
                'tutorial_key' => self::TUTORIAL_KEY,
                'completed_count' => $completedCount,
                'total_steps' => $totalSteps,
                'progress_percentage' => $progressPercentage,
                'steps' => $steps,
                'next_step' => null,
                'primary_url' => null,
                'event_uid' => $selectedEvent?->uid,
                'event_name' => $selectedEvent?->event,
            ];
        }

        return [
            'visible' => true,
            'dismissed' => false,
            'completed' => false,
            'tutorial_key' => self::TUTORIAL_KEY,
            'completed_count' => $completedCount,
            'total_steps' => $totalSteps,
            'progress_percentage' => $progressPercentage,
            'steps' => $steps,
            'next_step' => $nextStep,
            'primary_url' => $nextStep['url'] ?? route('dashboard.settings'),
            'event_uid' => $selectedEvent?->uid,
            'event_name' => $selectedEvent?->event,
        ];
    }

    public function dismiss(User $user): void
    {
        if (strtolower((string) $user->role) !== 'penyewa') {
            return;
        }

        $this->tutorialProgressService->markDismissed($user, self::TUTORIAL_KEY);
    }

    private function selectOnboardingEvent(Collection $events): ?Event
    {
        if ($events->isEmpty()) {
            return null;
        }

        return $events->first(function (Event $event): bool {
            return ! $this->eventChecklistIsComplete($event)
                || strtolower((string) $event->status) !== 'active'
                || (string) $event->konfirmasi !== '1';
        }) ?? $events->first();
    }

    private function eventChecklistIsComplete(Event $event): bool
    {
        $reviewItems = $this->reviewItemsFor($event);

        return $this->itemPassed($reviewItems, 'bank_account_available')
            && $this->itemPassed($reviewItems, 'physical_bank_book_available')
            && $event->agreements->contains(
                fn (Agreement $agreement) => $agreement->type === Agreement::TYPE_MOU
                    && $agreement->status === Agreement::STATUS_COMPLETED
            )
            && $event->hargas->isNotEmpty()
            && $this->itemPassed($reviewItems, 'payment_configuration_valid')
            && $this->itemPassed($reviewItems, 'effective_active_gateway');
    }

    private function reviewItemsFor(Event $event): Collection
    {
        return collect($this->agreementReviewService->buildForEvent($event)['items'] ?? [])
            ->keyBy('key');
    }

    private function itemPassed(Collection $items, string $key): bool
    {
        return (bool) data_get($items->get($key), 'passed', false);
    }

    private function profileIsComplete(User $user): bool
    {
        return collect([
            $user->name,
            $user->email,
            $user->nomor,
            $user->kota,
            $user->alamat,
        ])->every(fn ($value) => $this->hasMeaningfulValue($value));
    }

    private function hasMeaningfulValue(mixed $value): bool
    {
        $value = trim((string) $value);

        return $value !== '' && $value !== '-';
    }

    private function makeStep(string $key, string $label, bool $completed, string $url): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'completed' => $completed,
            'url' => $url,
        ];
    }

    private function eventEditUrl(?Event $event): string
    {
        if (! $event) {
            return route('dashboard.event.create');
        }

        return route('dashboard.event.edit', ['uid' => $event->uid]);
    }

    private function eventDetailUrl(?Event $event, string $activeTab): string
    {
        if (! $event) {
            return route('dashboard.event.create');
        }

        return route('dashboard.event.detail', ['uid' => $event->uid, 'activeTab' => $activeTab]);
    }

    private function hiddenState(array $overrides = []): array
    {
        return array_merge([
            'visible' => false,
            'dismissed' => false,
            'completed' => false,
            'tutorial_key' => self::TUTORIAL_KEY,
            'completed_count' => 0,
            'total_steps' => 6,
            'progress_percentage' => 0,
            'steps' => [],
            'next_step' => null,
            'primary_url' => null,
            'event_uid' => null,
            'event_name' => null,
        ], $overrides);
    }
}
