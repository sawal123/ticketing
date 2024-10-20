<?php

namespace App\Mail;

use App\Models\Cart;
use App\Models\User;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Attachment;

class MidtransPaymentNotification extends Mailable
{
    use Queueable, SerializesModels;


    /**
     * Create a new message instance.
     * @param \App\Models\User $user
     * @param Cart $cart
     */
    public $event;
    public function __construct(protected User $user, protected Cart $cart, protected $barcode)
    {
        //
        $this->user = $user;
        $this->cart = $cart;
        $this->barcode = $barcode;
        $this->event = Event::where('uid', $this->cart->event_uid)->select('event')->firstOrFail();
    }

    /**
     * Get the message envelope.
     */

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Barcode Verifikasi GOTIK - '.$this->event ,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $user = Auth::user();

        return new Content(
            view: 'email.notif-email',
            with: [
                'name' => $this->user->name,
                'cart' => $this->cart->invoice,
                'barcode' => $this->barcode,
                'event'=> $this->event
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            // Attachment::fromPath(public_path('/pdf'))
        ];
    }
}
