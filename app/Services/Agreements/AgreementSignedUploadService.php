<?php

namespace App\Services\Agreements;

use App\Models\Agreement;
use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;
use Throwable;

/**
 * Store / replace the tenant-uploaded signed MOU PDF.
 *
 * The authoritative signed file always lives at
 * private/agreements/{agreement_uid}/signed.pdf — the same agreement-scoped
 * directory M8 uses for unsigned.pdf. The uploaded file is first staged under
 * that agreement directory and verified to be actually present. Before staging
 * the Event is reloaded server-side and the current MOU Agreement is resolved
 * and validated (owner + READY); inside a DB transaction the Agreement row is
 * locked and re-validated again before the authoritative signed.pdf is
 * written. If the write or the DB update then fails, the transaction rolls
 * back and the previously accepted PDF is restored from a backup, so neither
 * the database state nor the old file is ever lost.
 */
class AgreementSignedUploadService
{
    private const SIGNED_FILENAME = 'signed.pdf';

    /**
     * @return array{ok: bool, agreement?: Agreement, reason?: string, message?: string}
     *
     * @throws Throwable When staging or persisting the file fails. In that
     *                   case the Agreement stays untouched and any staged or
     *                   backup file is cleaned up (or kept for recovery when
     *                   a restore fails, so the old file is never lost).
     */
    public function storeForEvent(Event $event, string $actorUid, UploadedFile $upload): array
    {
        $disk = Storage::disk('local');
        $stagedPath = null;
        $backupPath = null;
        $authoritativePath = null;
        $oldPath = null;

        try {
            // Pre-check before staging: reload the Event server-side, resolve
            // the current MOU Agreement and validate owner + READY. This is
            // NOT a substitute for the lock/revalidation inside the
            // transaction below.
            $agreement = $this->resolveReadyAgreement($event, $actorUid);

            $stagedPath = $this->stage($disk, $agreement, $upload);

            $result = DB::transaction(function () use ($event, $actorUid, $disk, $stagedPath, &$backupPath, &$authoritativePath, &$oldPath) {
                // Re-query and lock the Agreement inside the transaction.
                $lockedAgreement = Agreement::query()
                    ->where('event_uid', $event->uid)
                    ->where('type', Agreement::TYPE_MOU)
                    ->where('version', 1)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedAgreement) {
                    throw new LogicException('Agreement MOU tidak ditemukan untuk event ini.');
                }

                // Reload the Event server-side and re-validate owner + READY.
                $freshEvent = Event::query()
                    ->where('uid', $event->uid)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertOwner($freshEvent, $actorUid);
                $this->assertUploadAllowed($lockedAgreement);

                $path = $this->authoritativePath($lockedAgreement);
                $oldPath = $lockedAgreement->signed_pdf_path;

                // If a signed PDF already exists physically it MUST be backed
                // up before any overwrite attempt — even when $oldPath ===
                // $path, which is the normal re-upload case.
                if ($oldPath !== null && $disk->exists($oldPath)) {
                    $backupPath = $this->backupPath($lockedAgreement);

                    if (! $disk->copy($oldPath, $backupPath) || ! $disk->exists($backupPath)) {
                        throw new RuntimeException('Dokumen signed gagal dicadangkan.');
                    }
                }

                // Mark the authoritative path as being overwritten BEFORE the
                // attempt, so any later failure restores or removes that file.
                $authoritativePath = $path;

                // Write the new authoritative file from the verified staged
                // copy. The local disk uses 'throw' => false, so a false
                // return must be treated as failure.
                $stored = $disk->put($path, (string) $disk->get($stagedPath));

                if ($stored !== true || ! $disk->exists($path)) {
                    throw new RuntimeException('Dokumen signed gagal disimpan.');
                }

                $lockedAgreement->fill([
                    'status' => Agreement::STATUS_READY,
                    'signed_pdf_path' => $path,
                    'signed_review_status' => Agreement::SIGNED_REVIEW_PENDING,
                    'signed_verified_by' => null,
                    'signed_verified_at' => null,
                    'signed_rejection_reason' => null,
                    'signed_at' => now(),
                    'completed_at' => null,
                ])->save();

                $lockedAgreement->refresh();

                return [
                    'ok' => true,
                    'agreement' => $lockedAgreement,
                ];
            });

            // Transaction committed: only now remove staging and the backup.
            $this->deleteIfExists($disk, $stagedPath);
            $this->deleteIfExists($disk, $backupPath);

            return $result;
        } catch (Throwable $e) {
            $this->cleanupAfterFailure($disk, $stagedPath, $backupPath, $authoritativePath, $oldPath);

            throw $e;
        }
    }

    /**
     * @param  object  $disk  Filesystem adapter (FilesystemAdapter or test double).
     */
    private function stage($disk, Agreement $agreement, UploadedFile $upload): string
    {
        $stagedPath = $this->directory($agreement).'/staged-'.Str::uuid().'.pdf';

        $stored = $disk->put($stagedPath, (string) $upload->get());

        if ($stored !== true || ! $disk->exists($stagedPath)) {
            $this->deleteIfExists($disk, $stagedPath);

            throw new RuntimeException('Dokumen signed gagal disimpan sementara.');
        }

        return $stagedPath;
    }

    /**
     * Restore the previously accepted signed PDF after a failure. Only the
     * authoritative path is touched, and only when an overwrite was actually
     * attempted:
     *
     *  - if the previous signed file lived at the authoritative path (normal
     *    re-upload) it is restored from the backup;
     *  - otherwise the newly written / partial authoritative file is removed
     *    and the old file (wherever it lives) is left untouched, so the DB,
     *    which still points at the old path after rollback, stays consistent.
     *
     * The backup is never deleted until the restore has been verified, so the
     * old file cannot be lost.
     *
     * @param  object  $disk  Filesystem adapter (FilesystemAdapter or test double).
     */
    private function cleanupAfterFailure($disk, ?string $stagedPath, ?string $backupPath, ?string $authoritativePath, ?string $oldPath): void
    {
        try {
            if ($authoritativePath !== null) {
                $restored = false;

                if ($oldPath !== null && $oldPath === $authoritativePath && $backupPath !== null && $disk->exists($backupPath)) {
                    // Normal re-upload: the old file was overwritten in place;
                    // bring it back from the backup.
                    $restored = $disk->put($authoritativePath, (string) $disk->get($backupPath)) === true;
                    $restored = $restored && $disk->exists($authoritativePath);
                } else {
                    // No previous signed file at the authoritative path (or
                    // the old file lives elsewhere and stays untouched):
                    // remove the new / partial file.
                    $this->deleteIfExists($disk, $authoritativePath);
                    $restored = true;
                }

                if (! $restored) {
                    // Restore failed — keep the backup for manual recovery.
                    return;
                }
            }
        } catch (Throwable) {
            // Best effort only; keep the backup for manual recovery.
            return;
        }

        $this->deleteIfExists($disk, $stagedPath);
        $this->deleteIfExists($disk, $backupPath);
    }

    /**
     * Pre-flight check performed before staging. Resolves the current MOU
     * Agreement from a freshly reloaded Event and validates the owner and the
     * READY status. The transaction still re-locks and re-validates the row.
     */
    private function resolveReadyAgreement(Event $event, string $actorUid): Agreement
    {
        $freshEvent = Event::query()
            ->where('uid', $event->uid)
            ->firstOrFail();

        $this->assertOwner($freshEvent, $actorUid);

        $agreement = Agreement::query()
            ->where('event_uid', $event->uid)
            ->where('type', Agreement::TYPE_MOU)
            ->where('version', 1)
            ->first();

        if (! $agreement) {
            throw new LogicException('Agreement MOU tidak ditemukan untuk event ini.');
        }

        $this->assertUploadAllowed($agreement);

        return $agreement;
    }

    private function assertOwner(Event $event, string $actorUid): void
    {
        if ($event->user_uid !== $actorUid) {
            throw new LogicException('Event bukan milik penyewa ini.');
        }
    }

    private function assertUploadAllowed(Agreement $agreement): void
    {
        if (! $agreement->isReady()) {
            throw new LogicException('MOU hanya dapat diunggah saat berstatus READY.');
        }
    }

    private function directory(Agreement $agreement): string
    {
        return 'private/agreements/'.$agreement->uid;
    }

    private function authoritativePath(Agreement $agreement): string
    {
        return 'private/agreements/'.$agreement->uid.'/'.self::SIGNED_FILENAME;
    }

    private function backupPath(Agreement $agreement): string
    {
        return 'private/agreements/'.$agreement->uid.'/signed-backup-'.Str::uuid().'.pdf';
    }

    /**
     * @param  object  $disk  Filesystem adapter (FilesystemAdapter or test double).
     */
    private function deleteIfExists($disk, ?string $path): void
    {
        if ($path === null) {
            return;
        }

        try {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        } catch (Throwable) {
            // Best effort cleanup only; the original exception takes priority.
        }
    }
}
