<?php

namespace App\Services\Agreements;

use App\Models\Agreement;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;

class AgreementSignedVerificationService
{
    /**
     * @return array{ok: bool, agreement: Agreement}
     */
    public function approveForEvent(Event $event, string $actorUid, ?string $agreementUid = null): array
    {
        $result = DB::transaction(function () use ($event, $actorUid, $agreementUid) {
            $admin = $this->resolveAdmin($actorUid);
            $lockedAgreement = $this->resolveLockedAgreement($event, $agreementUid);

            $this->assertReadySignedAgreement($lockedAgreement);
            $this->assertReviewPending($lockedAgreement);

            $lockedAgreement->fill([
                'signed_review_status' => Agreement::SIGNED_REVIEW_VERIFIED,
                'signed_verified_by' => $admin->uid,
                'signed_verified_at' => now(),
                'signed_rejection_reason' => null,
                'status' => Agreement::STATUS_COMPLETED,
                'completed_at' => now(),
            ])->saveOrFail();

            return [
                'ok' => true,
                'agreement' => $lockedAgreement->fresh(),
            ];
        });

        app(AgreementVersioningService::class)->checkForContractualChanges(
            Event::query()->where('uid', $event->uid)->firstOrFail(),
            $actorUid
        );

        return $result;
    }

    /**
     * @return array{ok: bool, agreement: Agreement}
     */
    public function rejectForEvent(Event $event, string $actorUid, string $reason, ?string $agreementUid = null): array
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new LogicException('Alasan penolakan wajib diisi.');
        }

        if (mb_strlen($reason) > 1000) {
            throw new LogicException('Alasan penolakan maksimal 1000 karakter.');
        }

        return DB::transaction(function () use ($event, $actorUid, $reason, $agreementUid) {
            $admin = $this->resolveAdmin($actorUid);
            $lockedAgreement = $this->resolveLockedAgreement($event, $agreementUid);

            $this->assertReadySignedAgreement($lockedAgreement);
            $this->assertReviewPending($lockedAgreement);

            $lockedAgreement->fill([
                'signed_review_status' => Agreement::SIGNED_REVIEW_REJECTED,
                'signed_verified_by' => $admin->uid,
                'signed_verified_at' => null,
                'signed_rejection_reason' => $reason,
                'status' => Agreement::STATUS_READY,
                'completed_at' => null,
            ])->saveOrFail();

            return [
                'ok' => true,
                'agreement' => $lockedAgreement->fresh(),
            ];
        });
    }

    private function resolveAdmin(string $actorUid): User
    {
        $admin = User::query()
            ->where('uid', $actorUid)
            ->first();

        if (! $admin || strtolower((string) $admin->role) !== 'admin') {
            throw new LogicException('Hanya admin yang dapat memverifikasi MOU.');
        }

        return $admin;
    }

    private function resolveLockedAgreement(Event $event, ?string $agreementUid = null): Agreement
    {
        Event::query()
            ->where('uid', $event->uid)
            ->lockForUpdate()
            ->firstOrFail();

        $agreementQuery = Agreement::query()
            ->where('event_uid', $event->uid)
            ->lockForUpdate();

        if ($agreementUid) {
            $agreementQuery->where('uid', $agreementUid);
        } else {
            $agreementQuery
                ->where('status', Agreement::STATUS_READY)
                ->orderByRaw("CASE WHEN type = 'addendum' THEN 2 ELSE 1 END DESC")
                ->orderByDesc('version')
                ->latest('id');
        }

        $agreement = $agreementQuery->first();

        if (! $agreement && ! $agreementUid) {
            $agreement = Agreement::query()
                ->where('event_uid', $event->uid)
                ->where('type', Agreement::TYPE_MOU)
                ->where('version', 1)
                ->lockForUpdate()
                ->first();
        }

        if (! $agreement) {
            throw new LogicException('Agreement MOU tidak ditemukan untuk event ini.');
        }

        return $agreement;
    }

    private function assertReadySignedAgreement(Agreement $agreement): void
    {
        if (! $agreement->isReady()) {
            throw new LogicException('Verifikasi signed MOU hanya tersedia saat agreement berstatus READY.');
        }

        $path = $agreement->signed_pdf_path;
        $disk = Storage::disk('local');

        if (! filled($path) || ! $disk->exists($path)) {
            throw new LogicException('Dokumen MOU bertanda tangan belum tersedia.');
        }
    }

    private function assertReviewPending(Agreement $agreement): void
    {
        if ($agreement->signed_review_status !== null && $agreement->signed_review_status !== Agreement::SIGNED_REVIEW_PENDING) {
            throw new LogicException('Review signed MOU sudah diproses sebelumnya.');
        }
    }
}
