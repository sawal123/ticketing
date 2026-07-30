<?php

namespace App\Mail;

use App\Models\Cart;
use App\Models\Event;
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

class CashNotifikasiMail extends Mailable
{
    use Queueable, SerializesModels;

    public $event;

    public string $ticketUrl;

    public function __construct(
        protected string $recipientName,
        protected Cart $cart,
        public bool $isResend = false
    ) {
        $this->cart = $cart;
        $this->event = $this->cart->relationLoaded('event') && $this->cart->event
            ? $this->cart->event
            : Event::where('uid', $this->cart->event_uid)->select('event', 'tanggal')->firstOrFail();
        $parameters = ['uid' => $this->cart->uid];
        if ($this->cart->gate_token_hash) {
            $parameters['gate_access'] = app(GateTokenService::class)->cashTicketProof($this->cart);
        }

        $this->ticketUrl = URL::temporarySignedRoute(
            'cash.ticket.show',
            $this->ticketUrlExpiresAt(),
            $parameters,
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isResend
                ? 'PENTING: Barcode Tiket Terbaru GOTIK - '.$this->event->event
                : 'Barcode Verifikasi GOTIK - '.$this->event->event,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'email.notif-email',
            with: [
                'name' => $this->recipientName,
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
