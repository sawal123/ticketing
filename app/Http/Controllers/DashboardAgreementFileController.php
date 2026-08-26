<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Private MOU file access for the tenant who owns the Event.
 *
 * Authorization is always resolved server-side from the authenticated user
 * and the Event's owner; a client-provided path is never trusted.
 */
class DashboardAgreementFileController extends Controller
{
    public function unsigned(string $uid): StreamedResponse
    {
        $event = $this->authorizedEvent($uid);

        $path = $event->currentMouAgreement?->unsigned_pdf_path;

        return $this->stream($path, 'mou-unsigned.pdf');
    }

    public function signed(string $uid): StreamedResponse
    {
        $event = $this->authorizedEvent($uid);

        $path = $event->currentMouAgreement?->signed_pdf_path;

        return $this->stream($path, 'mou-signed.pdf');
    }

    /**
     * Only the penyewa that owns the Event may access its MOU files.
     */
    private function authorizedEvent(string $uid): Event
    {
        $user = Auth::user();

        abort_unless($user !== null, 403);
        abort_unless(strtolower((string) $user->role) === 'penyewa', 403);

        $event = Event::query()
            ->with('currentMouAgreement')
            ->where('uid', $uid)
            ->where('user_uid', $user->uid)
            ->first();

        abort_unless($event !== null, 404);

        return $event;
    }

    private function stream(?string $path, string $filename): StreamedResponse
    {
        $disk = Storage::disk('local');

        abort_unless(filled($path) && $disk->exists($path), 404);

        $stream = $disk->readStream($path);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
