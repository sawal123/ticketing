<?php

namespace App\Jobs;

use App\Mail\CashNotifikasiMail;
use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class sendEmailTrnsaksi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public string $recipientEmail;

    public string $recipientName;

    public string $cartUid;

    public function __construct(
        string $recipientEmail,
        string $recipientName,
        string $cartUid,
        public bool $isResend = false
    ) {
        $this->recipientEmail = $recipientEmail;
        $this->recipientName = $recipientName;
        $this->cartUid = $cartUid;
        $this->afterCommit();
    }

    public function handle(): void
    {
        try {
            $cart = Cart::with('event')->where('uid', $this->cartUid)->firstOrFail();

            Mail::to($this->recipientEmail)->send(
                new CashNotifikasiMail($this->recipientName, $cart, $this->isResend)
            );
        } catch (Throwable $exception) {
            Log::error('Gagal mengirim email barcode cash.', [
                'cart_uid' => $this->cartUid,
                'recipient' => $this->recipientEmail,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
