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

    public string $barcode;

    public function __construct(string $recipientEmail, string $recipientName, string $cartUid, string $barcode)
    {
        $this->recipientEmail = $recipientEmail;
        $this->recipientName = $recipientName;
        $this->cartUid = $cartUid;
        $this->barcode = $barcode;
        $this->afterCommit();
    }

    public function handle(): void
    {
        try {
            $cart = Cart::with('event')->where('uid', $this->cartUid)->firstOrFail();

            Mail::to($this->recipientEmail)->send(
                new CashNotifikasiMail($this->recipientName, $cart, $this->barcode)
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
