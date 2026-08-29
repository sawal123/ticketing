<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminEventReviewFileController extends Controller
{
    public function bankBook(string $uid): StreamedResponse
    {
        $event = Event::query()
            ->with('bankAccount')
            ->where('uid', $uid)
            ->firstOrFail();

        $path = $event->bankAccount?->bank_book_path;
        $downloadName = $event->bankAccount?->bank_book_original_name ?: 'bank-book';

        return $this->streamPrivateFile($path, $downloadName, $event->bankAccount?->bank_book_mime);
    }

    public function organizerLetter(string $uid): StreamedResponse
    {
        $event = Event::query()
            ->with('organizerLetter')
            ->where('uid', $uid)
            ->firstOrFail();

        $path = $event->organizerLetter?->file_path;
        $downloadName = $event->organizerLetter?->original_name ?: 'organizer-letter';

        return $this->streamPrivateFile($path, $downloadName, $event->organizerLetter?->mime_type);
    }

    public function responsibleIdentity(string $uid): StreamedResponse
    {
        $event = Event::query()
            ->with('responsibleIdentityDocument')
            ->where('uid', $uid)
            ->firstOrFail();

        $path = $event->responsibleIdentityDocument?->file_path;
        $downloadName = $event->responsibleIdentityDocument?->original_name ?: 'responsible-identity';

        return $this->streamPrivateFile($path, $downloadName, $event->responsibleIdentityDocument?->mime_type);
    }

    private function streamPrivateFile(?string $path, string $downloadName, ?string $mimeType): StreamedResponse
    {
        $this->authorizeAdmin();

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
            'Content-Type' => $mimeType ?: ($disk->mimeType($path) ?: 'application/octet-stream'),
            'Content-Disposition' => 'inline; filename="'.$this->sanitizeDownloadName($downloadName).'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();

        abort_unless($user !== null, 403);
        abort_unless(strtolower((string) $user->role) === 'admin', 403);
    }

    private function sanitizeDownloadName(string $downloadName): string
    {
        return str_replace(['\\', '/', '"'], '-', $downloadName);
    }
}
