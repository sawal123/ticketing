<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfileEmailChangeOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(protected User $user, protected string $otp)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode OTP Ganti Email GOTIK',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'email.profile-email-change-otp',
            with: [
                'name' => $this->user->name,
                'otp' => $this->otp,
            ],
        );
    }
}
