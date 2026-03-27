<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Mail\CashNotifikasiMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class sendEmailTrnsaksi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    public $email;
    public $nama;
    public $order_id;
    public $events;
    public function __construct($email, $nama, $order_id, $events)
    {
        //
        $this->email = $email;
        $this->nama = $nama;
        $this->order_id = $order_id;
        $this->events = $events;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        Mail::to($this->email)->send(new CashNotifikasiMail($this->nama, $this->order_id, $this->events));
    }
}
