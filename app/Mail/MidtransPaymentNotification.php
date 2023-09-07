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

class MidtransPaymentNotification extends Mailable
{
    use Queueable, SerializesModels;
    

    /**
     * Create a new message instance.
     * @param \App\Models\User $user
     * @param Cart $cart
     */
    public function __construct(protected User $user, protected Cart $cart,)
    {
        //
        $this->user = $user;
        $this->cart = $cart;
    }

    /**
     * Get the message envelope.
     */
    
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode Barcode Evanto',
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
        return [];
    }
}
