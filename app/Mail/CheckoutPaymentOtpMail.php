<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CheckoutPaymentOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected User $user,
        protected Event $event,
        protected string $otpCode
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode Verifikasi Pembayaran - '.$this->event->event,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.checkout-payment-otp',
            with: [
                'name' => $this->user->name,
                'eventName' => $this->event->event,
                'otpCode' => $this->otpCode,
            ],
        );
    }
}
