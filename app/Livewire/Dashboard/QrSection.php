<?php

namespace App\Livewire\Dashboard;

use App\Models\Event;
use App\Models\QrScan;
use Carbon\Carbon;
use Livewire\Component;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrSection extends Component
{
    public function render()
    {
        $user = auth()->user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        $events = Event::where('user_uid', $ownerId)
            ->where('konfirmasi', '1')
            ->latest()
            ->take(6)
            ->get();

        $eventUids = $events->pluck('uid')->all();

        $qrCodes = $events->mapWithKeys(function (Event $event) {
            return [
                $event->uid => QrCode::size(220)
                    ->margin(1)
                    ->generate(route('qr.scan', ['uid' => $event->uid]))
                    ->toHtml(),
            ];
        });

        $scansPerEvent = QrScan::selectRaw('event_uid, COUNT(*) as total')
            ->whereIn('event_uid', $eventUids)
            ->groupBy('event_uid')
            ->pluck('total', 'event_uid');

        $labels = [];
        $scans = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);

            $labels[] = $day->translatedFormat('d M');
            $scans[] = QrScan::whereIn('event_uid', $eventUids)
                ->whereDate('created_at', $day)
                ->count();
        }

        return view('livewire.dashboard.qr-section', [
            'events' => $events,
            'qrCodes' => $qrCodes,
            'scansPerEvent' => $scansPerEvent,
            'totalScans' => $scansPerEvent->sum(),
            'chart' => [
                'labels' => $labels,
                'scans' => $scans,
            ],
        ]);
    }
}
