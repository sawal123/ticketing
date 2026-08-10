<?php

namespace App\Jobs;

use App\Mail\MidtransPaymentNotification;
use App\Models\Cart;
use App\Models\User;
use App\Services\Tickets\GateTokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class sendEmailETransaksi implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 60;

    public $uniqueFor = 3600;

    public string $userUid;

    public string $cartUid;

    public function __construct(
        User $user,
        Cart $cart,
        public bool $isResend = false
    ) {
        $this->userUid = $user->uid;
        $this->cartUid = $cart->uid;
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        $prefix = $this->isResend ? 'ticket-email-resend:' : 'ticket-email:';

        return $prefix.$this->cartUid;
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::where('uid', $this->userUid)->firstOrFail();
        $cart = Cart::where('uid', $this->cartUid)->firstOrFail();
        app(GateTokenService::class)->ensureTicketAccessReady($cart);
        $cart->refresh();

        Mail::to($user)->send(new MidtransPaymentNotification($user, $cart, $this->isResend));
    }
}
