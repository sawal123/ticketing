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
        protected ?User $user,
        protected Event $event,
        protected string $otpCode,
        protected ?string $recipientName = null
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
                'name' => filled($this->recipientName) ? $this->recipientName : ($this->user?->name ?? 'Pembeli'),
                'eventName' => $this->event->event,
                'otpCode' => $this->otpCode,
            ],
        );
    }
}
