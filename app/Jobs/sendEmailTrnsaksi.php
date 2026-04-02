<?php

namespace App\Jobs;

use App\Mail\CashNotifikasiMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class sendEmailTrnsaksi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;

    public $carts;

    public $order_id;

    public function __construct($user, $carts, $order_id)
    {
        $this->user = $user;
        $this->carts = $carts;
        $this->order_id = $order_id;
    }

    public function handle(): void
    {
        Mail::to($this->user)->send(new CashNotifikasiMail($this->user, $this->carts, $this->order_id));
    }
}
