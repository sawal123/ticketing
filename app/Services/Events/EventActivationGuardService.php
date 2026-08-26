<?php

namespace App\Services\Events;

use App\Models\Agreement;
use App\Models\Event;
use App\Models\User;
use App\Services\Agreements\AgreementReviewService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;

class EventActivationGuardService
{
    /**
     * @return array{can_activate: bool, message: string, blocking_reasons: array<int, string>}
     */
    public function evaluateForEvent(Event $event): array
    {
        $event->loadMissing('currentMouAgreement');

        $review = app(AgreementReviewService::class)->activationPrerequisiteItemsForEvent($event);
        $failedReviewReason = collect($review)
            ->firstWhere('passed', false)['reason'] ?? null;

        if ($failedReviewReason) {
            return $this->blocked($failedReviewReason);
        }

        $agreement = $event->currentMouAgreement;

        if (! $agreement || ! $agreement->isCompleted()) {
            return $this->blocked('MOU belum selesai.');
        }

        if ($agreement->signed_review_status !== Agreement::SIGNED_REVIEW_VERIFIED) {
            return $this->blocked('MOU bertanda tangan belum diverifikasi.');
        }

        $path = $agreement->signed_pdf_path;
        $disk = Storage::disk('local');

        if (! filled($path) || ! $disk->exists($path)) {
            return $this->blocked('Dokumen MOU bertanda tangan belum tersedia.');
        }

        $uncompletedAddendum = Agreement::query()
            ->where('event_uid', $event->uid)
            ->where('type', Agreement::TYPE_ADDENDUM)
            ->where('status', '!=', Agreement::STATUS_COMPLETED)
            ->first();

        if ($uncompletedAddendum) {
            return $this->blocked('Terdapat addendum yang belum selesai.');
        }

        $unverifiedAddendum = Agreement::query()
            ->where('event_uid', $event->uid)
            ->where('type', Agreement::TYPE_ADDENDUM)
            ->where('status', Agreement::STATUS_COMPLETED)
            ->where(function ($q) {
                $q->where('signed_review_status', '!=', Agreement::SIGNED_REVIEW_VERIFIED)
                    ->orWhereNull('signed_review_status');
            })
            ->first();

        if ($unverifiedAddendum) {
            return $this->blocked('Addendum bertanda tangan belum diverifikasi.');
        }

        return [
            'can_activate' => true,
            'message' => 'Event dapat diaktifkan.',
            'blocking_reasons' => [],
        ];
    }

    public function activateForEvent(Event $event, string $actorUid, bool $confirm = false): Event
    {
        return DB::transaction(function () use ($event, $actorUid, $confirm) {
            $admin = $this->resolveAdmin($actorUid);
            $lockedEvent = Event::query()
                ->whereKey($event->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedEvent && filled($event->uid)) {
                $lockedEvent = Event::query()
                    ->where('uid', $event->uid)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $lockedEvent) {
                throw new LogicException('Event tidak ditemukan.');
            }

            if (strtolower((string) $lockedEvent->status) === 'active') {
                return $lockedEvent;
            }

            $evaluation = $this->evaluateForEvent($lockedEvent);

            if (! $evaluation['can_activate']) {
                throw new LogicException($evaluation['message']);
            }

            $lockedEvent->status = 'active';

            if ($confirm) {
                $lockedEvent->konfirmasi = '1';
            }

            $lockedEvent->save();

            return $lockedEvent->fresh();
        });
    }

    private function resolveAdmin(string $actorUid): User
    {
        $admin = User::query()
            ->where('uid', $actorUid)
            ->first();

        if (! $admin || strtolower((string) $admin->role) !== 'admin') {
            throw new LogicException('Hanya admin yang dapat mengaktifkan event.');
        }

        return $admin;
    }

    /**
     * @return array{can_activate: bool, message: string, blocking_reasons: array<int, string>}
     */
    private function blocked(string $reason): array
    {
        return [
            'can_activate' => false,
            'message' => 'Event belum dapat diaktifkan: '.$reason,
            'blocking_reasons' => [$reason],
        ];
    }
}
