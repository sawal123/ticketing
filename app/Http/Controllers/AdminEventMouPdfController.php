<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminEventMouPdfController extends Controller
{
    /**
     * Stream the frozen unsigned MOU PDF from private storage.
     *
     * The path is always resolved from the current MOU Agreement's
     * unsigned_pdf_path — never from the request.
     */
    public function unsigned(string $uid): StreamedResponse
    {
        $this->authorizeAdmin();

        $event = Event::query()
            ->with('currentMouAgreement')
            ->where('uid', $uid)
            ->firstOrFail();

        return $this->stream($event->currentMouAgreement?->unsigned_pdf_path, 'mou-unsigned.pdf');
    }

    /**
     * Stream the tenant-uploaded signed MOU PDF from private storage.
     *
     * The signed path is resolved strictly from Event -> current MOU Agreement
     * -> signed_pdf_path. No client path or agreement UID is ever accepted.
     */
    public function signed(string $uid): StreamedResponse
    {
        $this->authorizeAdmin();

        $event = Event::query()
            ->with('currentMouAgreement')
            ->where('uid', $uid)
            ->firstOrFail();

        return $this->stream($event->currentMouAgreement?->signed_pdf_path, 'mou-signed.pdf');
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();

        abort_unless($user !== null, 403);
        abort_unless(strtolower((string) $user->role) === 'admin', 403);
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
