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
 * The uploaded file is first staged under the agreement's private directory
 * and verified to be actually present. Inside a DB transaction the Agreement
 * row is locked and re-validated (owner + READY) before the authoritative
 * signed.pdf is written. If the DB update then fails, the transaction rolls
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
     *                   backup file is cleaned up.
     */
    public function storeForEvent(Event $event, string $actorUid, UploadedFile $upload): array
    {
        $disk = Storage::disk('local');
        $stagedPath = null;
        $backupPath = null;
        $authoritativePath = null;

        try {
            $stagedPath = $this->stage($disk, $event, $upload);

            $result = DB::transaction(function () use ($event, $actorUid, $disk, $stagedPath, &$backupPath, &$authoritativePath) {
                $agreement = Agreement::query()
                    ->where('event_uid', $event->uid)
                    ->where('type', Agreement::TYPE_MOU)
                    ->where('version', 1)
                    ->lockForUpdate()
                    ->first();

                if (! $agreement) {
                    throw new LogicException('Agreement MOU tidak ditemukan untuk event ini.');
                }

                $freshEvent = Event::query()
                    ->where('uid', $event->uid)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertOwner($freshEvent, $actorUid);
                $this->assertUploadAllowed($agreement);

                $path = $this->authoritativePath($agreement);
                $oldPath = $agreement->signed_pdf_path;

                // Keep a backup of the current authoritative file so a later
                // DB failure can restore it.
                if ($oldPath !== null && $oldPath !== $path && $disk->exists($oldPath)) {
                    $backupPath = $path.'.bak';

                    if (! $disk->copy($oldPath, $backupPath) || ! $disk->exists($backupPath)) {
                        throw new RuntimeException('Dokumen signed gagal dicadangkan.');
                    }
                }

                // Write the new authoritative file from the verified staged
                // copy. The local disk uses 'throw' => false, so a false
                // return must be treated as failure.
                $stored = $disk->put($path, (string) $disk->get($stagedPath));

                if ($stored !== true || ! $disk->exists($path)) {
                    throw new RuntimeException('Dokumen signed gagal disimpan.');
                }

                $authoritativePath = $path;

                $agreement->fill([
                    'signed_pdf_path' => $path,
                    'signed_at' => now(),
                ])->save();

                $agreement->refresh();

                return [
                    'ok' => true,
                    'agreement' => $agreement,
                ];
            });

            // Transaction committed: only now remove staging and the backup.
            $this->deleteIfExists($disk, $stagedPath);
            $this->deleteIfExists($disk, $backupPath);

            return $result;
        } catch (Throwable $e) {
            $this->cleanupAfterFailure($disk, $stagedPath, $backupPath, $authoritativePath);

            throw $e;
        }
    }

    /**
     * @param  object  $disk  Filesystem adapter (FilesystemAdapter or test double).
     */
    private function stage($disk, Event $event, UploadedFile $upload): string
    {
        $stagedPath = $this->directory($event).'/staged-'.Str::uuid().'.pdf';

        $stored = $disk->put($stagedPath, (string) $upload->get());

        if ($stored !== true || ! $disk->exists($stagedPath)) {
            $this->deleteIfExists($disk, $stagedPath);

            throw new RuntimeException('Dokumen signed gagal disimpan sementara.');
        }

        return $stagedPath;
    }

    /**
     * Best-effort restoration of the previously accepted signed PDF after a
     * failure. Only touches the authoritative path when a new file was
     * actually written; staged and backup files are always removed.
     *
     * @param  object  $disk  Filesystem adapter (FilesystemAdapter or test double).
     */
    private function cleanupAfterFailure($disk, ?string $stagedPath, ?string $backupPath, ?string $authoritativePath): void
    {
        try {
            if ($authoritativePath !== null) {
                if ($backupPath !== null && $disk->exists($backupPath)) {
                    $disk->put($authoritativePath, (string) $disk->get($backupPath));
                } else {
                    $this->deleteIfExists($disk, $authoritativePath);
                }
            }
        } catch (Throwable) {
            // Best effort only; the original exception takes priority.
        }

        $this->deleteIfExists($disk, $stagedPath);
        $this->deleteIfExists($disk, $backupPath);
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

    private function directory(Event $event): string
    {
        return 'private/agreements/'.$event->uid;
    }

    private function authoritativePath(Agreement $agreement): string
    {
        return 'private/agreements/'.$agreement->event_uid.'/'.self::SIGNED_FILENAME;
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
