<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Mail\StaffInvitationMail;
// 👇 INI BARIS YANG HILANG (WAJIB ADA) 👇
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendStaffInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;
    public $name;
    public $url;

    public function __construct($email, $name, $url)
    {
        $this->email = $email;
        $this->name = $name;
        $this->url = $url;
    }

    public function handle(): void
    {
        // Karena kita sudah import di atas, pemanggilan Mail::to() di bawah ini tidak akan error lagi
        Mail::to($this->email)->send(new StaffInvitationMail($this->name, $this->url));
    }
}
