<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Mail\MidtransPaymentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class sendEmailETransaksi implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $uniqueFor = 3600;

    /**
     * Create a new job instance.
     */
    public $user;
    public $carts;
    public $order_id;
    public function __construct($user, $carts, $order_id)
    {
        //
        $this->user = $user;
        $this->carts = $carts;
        $this->order_id = $order_id;
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return 'ticket-email:'.$this->order_id;
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
        //
        Mail::to($this->user)->send(new MidtransPaymentNotification($this->user, $this->carts, $this->order_id));
    }
}
