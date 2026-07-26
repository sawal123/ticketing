<?php

namespace App\Mail;

use App\Models\Cart;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CashNotifikasiMail extends Mailable
{
    use Queueable, SerializesModels;

    public $event;

    public function __construct(protected string $recipientName, protected Cart $cart, protected string $barcode)
    {
        $this->cart = $cart;
        $this->barcode = $barcode;
        $this->event = $this->cart->relationLoaded('event') && $this->cart->event
            ? $this->cart->event
            : Event::where('uid', $this->cart->event_uid)->select('event')->firstOrFail();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Barcode Verifikasi GOTIK - '.$this->event->event,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'email.notif-email',
            with: [
                'name' => $this->recipientName,
                'cart' => $this->cart->invoice,
                'barcode' => $this->barcode,
                'event' => $this->event,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            // Attachment::fromPath(public_path('/pdf'))
        ];
    }
}
