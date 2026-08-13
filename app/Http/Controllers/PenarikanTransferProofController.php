<?php

namespace App\Http\Controllers;

use App\Models\Penarikan;
use App\Services\PrivateTransferProofStorage;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PenarikanTransferProofController extends Controller
{
    public function __invoke(string $uid, PrivateTransferProofStorage $storage): StreamedResponse
    {
        $penarikan = Penarikan::query()
            ->where('uid', $uid)
            ->firstOrFail();

        $this->authorizeAccess($penarikan);

        abort_unless(filled($penarikan->transfer_proof) && $storage->exists($penarikan->transfer_proof), 404);

        $stream = $storage->readStream($penarikan->transfer_proof);
        abort_unless(is_resource($stream), 404);

        $mimeType = $storage->mimeType($penarikan->transfer_proof) ?? 'application/octet-stream';
        $downloadName = $storage->downloadName($penarikan->uid, $penarikan->transfer_proof);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeAccess(Penarikan $penarikan): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        if (strtolower((string) $user->role) === 'admin') {
            return;
        }

        abort_unless(in_array(strtolower((string) $user->role), ['penyewa', 'staff'], true), 403);

        $ownerUid = strtolower((string) $user->role) === 'staff'
            ? $user->parent_uid
            : $user->uid;

        abort_unless($ownerUid !== null && $ownerUid === $penarikan->uid_user, 403);
        abort_unless(strtoupper((string) $penarikan->status) === Penarikan::STATUS_SUCCESS, 403);
    }
}
