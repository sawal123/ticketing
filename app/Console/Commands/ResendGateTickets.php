<?php

namespace App\Console\Commands;

use App\Jobs\sendEmailETransaksi;
use App\Jobs\sendEmailTrnsaksi;
use App\Models\Cart;
use App\Models\Event;
use App\Services\Tickets\GateTokenService;
use Illuminate\Console\Command;

class ResendGateTickets extends Command
{
    protected $signature = 'tickets:resend-gate-tickets
        {event_uid : Exact UID of the one event whose tickets will be resent}
        {--dry-run : Report eligible emails without queueing jobs}
        {--execute : Queue emails after an explicit confirmation}';

    protected $description = 'Safely resend tickets containing the current QR gate token and manual code.';

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

        $carts = $this->eligibleQuery($eventUid)->get();
        $recipients = $carts->filter(fn (Cart $cart) => $this->recipientIsValid($cart));
        $invalid = $carts->count() - $recipients->count();
        $execute = (bool) $this->option('execute');

        $this->newLine();
        $this->warn('Mode       : '.($execute ? 'EXECUTE' : 'DRY-RUN'));
        $this->line("Event      : {$event->event}");
        $this->line("Event UID  : {$event->uid}");
        $this->line('Isi tiket  : QR gate token + kode manual');
        $this->line("Akan kirim : {$recipients->count()}");
        $this->line("Dilewati   : {$invalid} (email/data penerima tiket invalid)");

        if (! $execute) {
            $this->info('Dry-run selesai. Tidak ada email yang dijadwalkan.');

            return self::SUCCESS;
        }

        if (! $tokens->eventIsEnabled($eventUid)) {
            $this->error('Execute ditolak: UID event tidak ada di GATE_TOKEN_EVENT_UIDS.');

            return self::FAILURE;
        }

        if (! $this->confirm("KIRIM ULANG {$recipients->count()} tiket untuk {$event->event} ({$eventUid})?", false)) {
            $this->warn('Pengiriman dibatalkan.');

            return self::FAILURE;
        }

        foreach ($recipients as $cart) {
            if ($cart->payment_type === 'cash') {
                dispatch(new sendEmailTrnsaksi(
                    $cart->cashBuyer->email,
                    $cart->cashBuyer->name,
                    $cart->uid,
                    true,
                ));
            } else {
                dispatch(new sendEmailETransaksi($cart->users, $cart, true));
            }
        }

        $this->info("Selesai: {$recipients->count()} email dijadwalkan.");

        return self::SUCCESS;
    }

    private function eligibleQuery(string $eventUid)
    {
        return Cart::query()
            ->with(['users', 'cashBuyer'])
            ->where('event_uid', $eventUid)
            ->where('status', Cart::STATUS_SUCCESS)
            ->whereNotNull('gate_token_hash')
            ->whereNotNull('gate_token_encrypted')
            ->whereNotNull('gate_manual_code_hash')
            ->whereNotNull('gate_manual_code_encrypted')
            ->whereNull('scanned_at')
            ->where(function ($query) {
                $query->whereNull('konfirmasi')->orWhere('konfirmasi', '0');
            })
            ->orderBy('id');
    }

    private function recipientIsValid(Cart $cart): bool
    {
        return $this->resolveRecipient($cart) !== null;
    }

    private function resolveRecipient(Cart $cart): ?array
    {
        if ($cart->payment_type === 'cash') {
            if (! $cart->cashBuyer
                || filter_var($cart->cashBuyer->email, FILTER_VALIDATE_EMAIL) === false) {
                return null;
            }

            return [
                'email' => $cart->cashBuyer->email,
                'name' => $cart->cashBuyer->name,
            ];
        }

        if (! $cart->users) {
            return null;
        }

        return sendEmailETransaksi::resolveRecipient($cart->users, $cart);
    }
}
