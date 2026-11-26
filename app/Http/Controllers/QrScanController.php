<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\QrScan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QrScanController extends Controller
{
    public function scan(string $uid)
    {
        $event = Event::where('uid', $uid)->where('konfirmasi', '1')->firstOrFail();

        try {
            QrScan::create([
                'event_uid' => $event->uid,
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 500),
            ]);
        } catch (\Throwable $e) {
            Log::warning('QR scan log failed', [
                'event_uid' => $event->uid,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect(url('/ticket/'.$event->slug));
    }

    public function downloadPdf(Request $request, string $uid)
    {
        $event = $this->authorizedEvent($uid);

        $validated = $request->validate([
            'image' => ['required', 'string'],
        ]);

        $image = $validated['image'];

        if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $image, $matches)) {
            abort(422);
        }

        $png = base64_decode($matches[1], true);

        if ($png === false || strlen($png) > 2_000_000 || substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            abort(422);
        }

        return Pdf::loadView('pdf.qr-download', [
            'event' => $event,
            'image' => $image,
            'scanUrl' => route('qr.scan', ['uid' => $event->uid]),
        ])->setPaper('a5', 'portrait')->download('qr-'.$event->slug.'.pdf');
    }

    private function authorizedEvent(string $uid): Event
    {
        $user = auth()->user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        return Event::where('uid', $uid)
            ->where('konfirmasi', '1')
            ->where('user_uid', $ownerId)
            ->firstOrFail();
    }
}
