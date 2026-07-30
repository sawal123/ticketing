<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\Event;
use App\Services\Tickets\GateTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IssueMissingManualCodes extends Command
{
    protected $signature = 'tickets:issue-missing-manual-codes
        {event_uid : Exact UID of the one event to backfill}
        {--dry-run : Report eligible tickets without changing data}
        {--execute : Issue missing manual codes after an explicit confirmation}';

    protected $description = 'Issue only missing manual codes without rotating existing QR gate tokens.';

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

        $eligible = $this->eligibleQuery($eventUid)->count();
        $execute = (bool) $this->option('execute');

        $this->newLine();
        $this->warn('Mode       : '.($execute ? 'EXECUTE' : 'DRY-RUN'));
        $this->line("Event      : {$event->event}");
        $this->line("Event UID  : {$event->uid}");
        $this->line("Akan terbit: {$eligible} kode manual");
        $this->line('Gate token : tidak diubah');

        if (! $execute) {
            $this->info('Dry-run selesai. Tidak ada data yang diubah.');

            return self::SUCCESS;
        }

        if (! $tokens->eventIsEnabled($eventUid)) {
            $this->error('Execute ditolak: UID event tidak ada di GATE_TOKEN_EVENT_UIDS.');

            return self::FAILURE;
        }

        if (! $this->confirm("TERBITKAN {$eligible} kode manual tanpa merotasi gate token untuk {$event->event} ({$eventUid})?", false)) {
            $this->warn('Penerbitan dibatalkan. Tidak ada data yang diubah.');

            return self::FAILURE;
        }

        $issued = 0;
        $ids = $this->eligibleQuery($eventUid)->orderBy('id')->pluck('id');
        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $eventUid, $tokens, &$issued) {
                $cart = Cart::whereKey($id)
                    ->where('event_uid', $eventUid)
                    ->lockForUpdate()
                    ->first();

                if (! $cart || ! $this->isEligible($cart)) {
                    return;
                }

                $tokens->issueMissingManualCode($cart);
                $issued++;
            }, 3);
        }

        $this->info("Selesai: {$issued} kode manual diterbitkan tanpa merotasi gate token.");

        return self::SUCCESS;
    }

    private function eligibleQuery(string $eventUid)
    {
        return Cart::query()
            ->where('event_uid', $eventUid)
            ->where('status', Cart::STATUS_SUCCESS)
            ->whereNotNull('gate_token_hash')
            ->whereNotNull('gate_token_encrypted')
            ->whereNull('gate_manual_code_hash')
            ->whereNull('gate_manual_code_encrypted')
            ->whereNull('scanned_at')
            ->where(function ($query) {
                $query->whereNull('konfirmasi')->orWhere('konfirmasi', '0');
            });
    }

    private function isEligible(Cart $cart): bool
    {
        return $cart->status === Cart::STATUS_SUCCESS
            && $cart->gate_token_hash
            && $cart->gate_token_encrypted
            && ! $cart->gate_manual_code_hash
            && ! $cart->gate_manual_code_encrypted
            && $cart->scanned_at === null
            && in_array((string) $cart->konfirmasi, ['', '0'], true);
    }
}
