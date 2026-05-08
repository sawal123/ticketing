<?php

namespace App\Jobs;

use App\Mail\BlastEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProcessEmailBlast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $users;
    protected $subject;
    protected $content;

    /**
     * Create a new job instance.
     */
    public function __construct($users, $subject, $content)
    {
        $this->users = $users;
        $this->subject = $subject;
        $this->content = $content;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->users as $user) {
            if ($user->email) {
                Mail::to($user->email)->send(new BlastEmail($this->subject, $this->content));
            }
        }
    }
}
