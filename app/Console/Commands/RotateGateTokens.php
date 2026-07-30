<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\Event;
use App\Services\Tickets\GateTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RotateGateTokens extends Command
{
    protected $signature = 'tickets:rotate-gate-tokens
        {event_uid : Exact UID of the one event to rotate}
        {--dry-run : Report eligible tickets without changing data}
        {--execute : Rotate eligible tickets after an explicit confirmation}';

    protected $description = 'Rotate QR gate tokens and manual codes for unscanned SUCCESS tickets in one event.';

    public function handle(GateTokenService $tokens): int
    {
        if ($this->option('dry-run') && $this->option('execute')) {
            $this->error('Pilih salah satu: --dry-run atau --execute.');

            return self::INVALID;
        }

        $eventUid = (string) $this->argument('event_uid');
        $event = Event::where('uid', $eventUid)->first();
        if (! $event) {
            $this->error("Event UID {$eventUid} tidak ditemukan.");

            return self::FAILURE;
        }

        $counts = $this->counts($eventUid);
        $execute = (bool) $this->option('execute');
        $mode = $execute ? 'EXECUTE' : 'DRY-RUN';

        $this->newLine();
        $this->warn("Mode       : {$mode}");
        $this->line("Event      : {$event->event}");
        $this->line("Event UID  : {$event->uid}");
        $this->line("Semua cart : {$counts['total']}");
        $this->line("SUCCESS    : {$counts['success']}");
        $this->line("Belum verif: {$counts['eligible']}");
        $this->line("Akan rotasi: {$counts['eligible']} (gate token + kode manual)");
        $this->line("Dilewati   : {$counts['skipped']} (non-SUCCESS atau sudah scan)");

        if (! $execute) {
            $this->info('Dry-run selesai. Tidak ada data yang diubah.');

            return self::SUCCESS;
        }

        if (! $tokens->eventIsEnabled($eventUid)) {
            $this->error('Execute ditolak: UID event tidak ada di GATE_TOKEN_EVENT_UIDS.');

            return self::FAILURE;
        }

        if (! $this->confirm("ROTASI {$counts['eligible']} tiket untuk {$event->event} ({$eventUid})?", false)) {
            $this->warn('Rotasi dibatalkan. Tidak ada data yang diubah.');

            return self::FAILURE;
        }

        $rotated = 0;
        $ids = $this->eligibleQuery($eventUid)->orderBy('id')->pluck('id');
        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $eventUid, $tokens, &$rotated) {
                $cart = Cart::whereKey($id)
                    ->where('event_uid', $eventUid)
                    ->lockForUpdate()
                    ->first();

                if (! $cart || ! $this->isEligible($cart)) {
                    return;
                }

                $tokens->issue($cart, true);
                $rotated++;
            }, 3);
        }

        $this->info("Rotasi selesai: {$rotated} gate token dan kode manual diterbitkan ulang.");

        return self::SUCCESS;
    }

    private function counts(string $eventUid): array
    {
        $base = Cart::where('event_uid', $eventUid);
        $total = (clone $base)->count();
        $success = (clone $base)->where('status', Cart::STATUS_SUCCESS)->count();
        $eligible = $this->eligibleQuery($eventUid)->count();

        return [
            'total' => $total,
            'success' => $success,
            'eligible' => $eligible,
            'skipped' => $total - $eligible,
        ];
    }

    private function eligibleQuery(string $eventUid)
    {
        return Cart::query()
            ->where('event_uid', $eventUid)
            ->where('status', Cart::STATUS_SUCCESS)
            ->whereNull('scanned_at')
            ->where(function ($query) {
                $query->whereNull('konfirmasi')->orWhere('konfirmasi', '0');
            });
    }

    private function isEligible(Cart $cart): bool
    {
        return $cart->status === Cart::STATUS_SUCCESS
            && $cart->scanned_at === null
            && in_array((string) $cart->konfirmasi, ['', '0'], true);
    }
}
