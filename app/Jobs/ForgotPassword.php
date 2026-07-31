<?php

namespace App\Jobs;

use App\Mail\ForgotPassword as FGPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ForgotPassword
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;

    public $email;

    public $resetUrl;

    /**
     * Create a new job instance.
     */
    public function __construct($user, $email, string $resetUrl)
    {
        $this->user = $user;
        $this->email = $email;
        $this->resetUrl = $resetUrl;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->email)->send(new FGPassword($this->user, $this->resetUrl));
    }
}
