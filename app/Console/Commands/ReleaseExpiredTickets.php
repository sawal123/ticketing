<?php

namespace App\Console\Commands;

use App\Services\Tickets\TicketReservationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredTickets extends Command
{
    protected $signature = 'tickets:release-expired {--batch=100 : Maximum reservations to release in one run}';

    protected $description = 'Release expired RESERVED/PENDING ticket reservations in small batches.';

    public function handle(TicketReservationService $reservationService): int
    {
        $batch = max(1, min(100, (int) $this->option('batch')));
        $released = $reservationService->releaseExpiredBatch($batch);

        $message = "Released {$released} expired ticket reservations.";
        $this->info($message);
        Log::info($message);

        return self::SUCCESS;
    }
}
