<?php

namespace App\Mail;

use App\Models\Cart;
use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MidtransPaymentNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  User  $user
     * @param  Cart  $cart
     */
    public $event;

    public string $ticketUrl;

    public function __construct(protected User $user, protected Cart $cart)
    {
        $this->user = $user;
        $this->cart = $cart;
        $this->event = Event::where('uid', $this->cart->event_uid)->select('event')->firstOrFail();
        $this->ticketUrl = route('barcode.generate', [
            'data' => $this->cart->invoice,
        ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Barcode Verifikasi GOTIK - '.$this->event->event,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'email.notif-email',
            with: [
                'name' => $this->user->name,
                'cart' => $this->cart->invoice,
                'event' => $this->event,
                'ticketUrl' => $this->ticketUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            // Attachment::fromPath(public_path('/pdf'))
        ];
    }
}
