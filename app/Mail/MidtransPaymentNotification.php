<?php

namespace App\Mail;

use App\Models\Cart;
use App\Models\Event;
use App\Models\User;
use App\Services\Tickets\GateTokenService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Throwable;

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

    public function __construct(
        protected User $user,
        protected Cart $cart,
        public bool $isResend = false
    ) {
        $this->user = $user;
        $this->cart = $cart;
        $this->event = Event::where('uid', $this->cart->event_uid)->select('event', 'tanggal')->firstOrFail();
        $this->ticketUrl = URL::temporarySignedRoute(
            'online.ticket.show',
            $this->ticketUrlExpiresAt(),
            [
                'uid' => $this->cart->uid,
                'gate_access' => app(GateTokenService::class)->ticketAccessProof($this->cart),
            ],
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isResend
                ? 'PENTING: Barcode Tiket Terbaru GOTIK - '.$this->event->event
                : 'Barcode Verifikasi GOTIK - '.$this->event->event,
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
                'manualCode' => $this->cart->gate_manual_code_hash
                    ? app(GateTokenService::class)->manualCodeForDisplay($this->cart)
                    : null,
                'isResendTicket' => $this->isResend,
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

    private function ticketUrlExpiresAt(): Carbon
    {
        try {
            $expiresAt = Carbon::parse($this->event->tanggal)->addDay();

            return $expiresAt->isFuture() ? $expiresAt : now()->addDays(7);
        } catch (Throwable) {
            return now()->addDays(30);
        }
    }
}
