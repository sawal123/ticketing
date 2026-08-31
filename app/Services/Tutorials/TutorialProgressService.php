<?php

namespace App\Services\Tutorials;

use App\Models\TutorialProgress;
use App\Models\User;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class TutorialProgressService
{
    public function isCompleted(User|string $user, string $tutorialKey): bool
    {
        return $this->progressFor($user, $tutorialKey)?->completed_at !== null;
    }

    public function isDismissed(User|string $user, string $tutorialKey): bool
    {
        return $this->progressFor($user, $tutorialKey)?->dismissed_at !== null;
    }

    public function markCompleted(User|string $user, string $tutorialKey): TutorialProgress
    {
        $progress = $this->firstOrNew($user, $tutorialKey);

        if ($progress->completed_at === null || $progress->dismissed_at !== null) {
            $progress->completed_at ??= Carbon::now();
            $progress->dismissed_at = null;
            $progress->save();
        }

        return $progress->fresh();
    }

    public function markDismissed(User|string $user, string $tutorialKey): TutorialProgress
    {
        $progress = $this->firstOrNew($user, $tutorialKey);

        if ($progress->dismissed_at === null || $progress->completed_at !== null) {
            $progress->dismissed_at ??= Carbon::now();
            $progress->completed_at = null;
            $progress->save();
        }

        return $progress->fresh();
    }

    public function reset(User|string $user, string $tutorialKey): void
    {
        TutorialProgress::query()
            ->where('user_uid', $this->resolveUserUid($user))
            ->where('tutorial_key', $this->normalizeTutorialKey($tutorialKey))
            ->delete();
    }

    private function progressFor(User|string $user, string $tutorialKey): ?TutorialProgress
    {
        return TutorialProgress::query()
            ->where('user_uid', $this->resolveUserUid($user))
            ->where('tutorial_key', $this->normalizeTutorialKey($tutorialKey))
            ->first();
    }

    private function firstOrNew(User|string $user, string $tutorialKey): TutorialProgress
    {
        return TutorialProgress::firstOrNew([
            'user_uid' => $this->resolveUserUid($user),
            'tutorial_key' => $this->normalizeTutorialKey($tutorialKey),
        ]);
    }

    private function resolveUserUid(User|string $user): string
    {
        $userUid = $user instanceof User ? $user->uid : $user;
        $userUid = trim((string) $userUid);

        if ($userUid === '') {
            throw new InvalidArgumentException('User UID is required.');
        }

        return $userUid;
    }

    private function normalizeTutorialKey(string $tutorialKey): string
    {
        $tutorialKey = trim($tutorialKey);

        if ($tutorialKey === '') {
            throw new InvalidArgumentException('Tutorial key is required.');
        }

        return $tutorialKey;
    }
}
